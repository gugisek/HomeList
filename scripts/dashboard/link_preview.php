<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';

$url = $_GET['url'] ?? '';

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

$scheme = parse_url($url, PHP_URL_SCHEME);
if (!in_array($scheme, ['http', 'https'])) {
    echo json_encode(['error' => 'Invalid scheme']);
    exit;
}

$host = parse_url($url, PHP_URL_HOST);

if (!function_exists('curl_init')) {
    echo json_encode(['error' => 'cURL not available']);
    exit;
}

// Ochrona SSRF: serwer pobiera tylko publiczne adresy (nie localhost / sieć domowa / Home Assistant).
// Każdy hop przekierowania jest sprawdzany osobno, a cURL łączy się dokładnie z zweryfikowanym IP
// (CURLOPT_RESOLVE), żeby podmiana DNS między sprawdzeniem a połączeniem nic nie dała.
function lp_public_ip(string $host): ?string {
    $host = trim($host, '[]');
    $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
    if (!$ips) return null;
    foreach ($ips as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return null;
    }
    return $ips[0];
}

$html = false;
$curlError = '';
$httpCode = 0;
for ($hop = 0; $hop <= 3; $hop++) {
    $parts = parse_url($url);
    $scheme = strtolower($parts['scheme'] ?? '');
    $host = $parts['host'] ?? '';
    $port = (int)($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
    if (!in_array($scheme, ['http', 'https'], true) || $host === '' || !in_array($port, [80, 443], true)) {
        echo json_encode(['error' => 'Invalid URL']);
        exit;
    }
    $ip = lp_public_ip($host);
    if ($ip === null) {
        echo json_encode(['error' => 'Blocked host']);
        exit;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RESOLVE        => [$host . ':' . $port . ':' . (strpos($ip, ':') !== false ? '[' . $ip . ']' : $ip)],
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER         => false,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; LinkPreviewBot/1.0)',
        CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
    ]);
    $html = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    if ($html === false || $httpCode < 300 || $httpCode >= 400 || !$redirect) break;
    $url = $redirect;
}

if ($html === false) {
    echo json_encode(['error' => 'cURL failed: ' . $curlError]);
    exit;
}

if ($httpCode < 200 || $httpCode >= 400) {
    echo json_encode(['error' => "HTTP $httpCode"]);
    exit;
}

$html = substr($html, 0, 300000);

// Extract meta tag content by property or name attribute (regex, no DOM extension needed)
function getMeta(string $html, string $attr, string $value): string {
    // matches <meta property="og:title" content="..."> in any attribute order
    $pattern = '/<meta\s[^>]*' . $attr . '\s*=\s*["\']' . preg_quote($value, '/') . '["\'][^>]*content\s*=\s*["\']([^"\']*)["\'][^>]*>/is';
    if (preg_match($pattern, $html, $m)) return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // reverse attribute order
    $pattern2 = '/<meta\s[^>]*content\s*=\s*["\']([^"\']*)["\'][^>]*' . $attr . '\s*=\s*["\']' . preg_quote($value, '/') . '["\'][^>]*>/is';
    if (preg_match($pattern2, $html, $m)) return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return '';
}

$preview = [
    'title'      => '',
    'description'=> '',
    'image'      => '',
    'site_name'  => $host,
    'url'        => $url,
    'is_favicon' => false,
];

$preview['title']       = getMeta($html, 'property', 'og:title')
                       ?: getMeta($html, 'name', 'twitter:title');
$preview['description'] = getMeta($html, 'property', 'og:description')
                       ?: getMeta($html, 'name', 'twitter:description')
                       ?: getMeta($html, 'name', 'description');
$preview['image']       = getMeta($html, 'property', 'og:image')
                       ?: getMeta($html, 'name', 'twitter:image');
$siteName               = getMeta($html, 'property', 'og:site_name');
if ($siteName) $preview['site_name'] = $siteName;

// Fallback title from <title> tag
if (!$preview['title'] && preg_match('/<title[^>]*>([^<]*)<\/title>/is', $html, $m)) {
    $preview['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Fix protocol-relative and relative image URLs
if ($preview['image']) {
    if (str_starts_with($preview['image'], '//')) {
        $preview['image'] = $scheme . ':' . $preview['image'];
    } elseif (!preg_match('/^https?:\/\//', $preview['image'])) {
        $preview['image'] = $scheme . '://' . $host . '/' . ltrim($preview['image'], '/');
    }
}

// Fallback: Google favicon service
if (!$preview['image']) {
    $preview['image']     = 'https://www.google.com/s2/favicons?domain=' . urlencode($host) . '&sz=64';
    $preview['is_favicon'] = true;
}

$preview['title']       = substr($preview['title'], 0, 200);
$preview['description'] = substr($preview['description'], 0, 400);

echo json_encode($preview, JSON_UNESCAPED_UNICODE);
