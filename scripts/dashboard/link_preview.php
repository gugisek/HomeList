<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

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

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
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
curl_close($ch);

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
