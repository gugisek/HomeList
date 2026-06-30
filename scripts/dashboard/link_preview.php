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
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
]);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($html === false || $httpCode < 200 || $httpCode >= 400) {
    echo json_encode(['error' => 'Could not fetch URL']);
    exit;
}

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML(mb_convert_encoding(substr($html, 0, 300000), 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();

$preview = [
    'title'      => '',
    'description'=> '',
    'image'      => '',
    'site_name'  => $host,
    'url'        => $url,
    'is_favicon' => false,
];

$metas = $doc->getElementsByTagName('meta');
foreach ($metas as $meta) {
    $property = strtolower($meta->getAttribute('property'));
    $name     = strtolower($meta->getAttribute('name'));
    $content  = $meta->getAttribute('content');

    if ($property === 'og:title'       && !$preview['title'])       $preview['title']       = $content;
    if ($property === 'og:description' && !$preview['description']) $preview['description'] = $content;
    if ($property === 'og:image'       && !$preview['image'])       $preview['image']       = $content;
    if ($property === 'og:site_name')                               $preview['site_name']   = $content;
    if ($name === 'twitter:title'       && !$preview['title'])       $preview['title']       = $content;
    if ($name === 'twitter:description' && !$preview['description']) $preview['description'] = $content;
    if ($name === 'twitter:image'       && !$preview['image'])       $preview['image']       = $content;
    if (($name === 'description')       && !$preview['description']) $preview['description'] = $content;
}

if (!$preview['title']) {
    $titles = $doc->getElementsByTagName('title');
    if ($titles->length > 0) {
        $preview['title'] = trim($titles->item(0)->textContent);
    }
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
