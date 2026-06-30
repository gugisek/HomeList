<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Catch all fatal errors and return as JSON instead of 500
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode(['error' => "PHP error $errno: $errstr in $errfile:$errline"]);
    exit;
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo json_encode(['error' => "Fatal: {$e['message']} in {$e['file']}:{$e['line']}"]);
    }
});

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

if (!class_exists('DOMDocument')) {
    echo json_encode(['error' => 'DOMDocument not available']);
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
    echo json_encode(['error' => "HTTP $httpCode from remote"]);
    exit;
}

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$htmlChunk = substr($html, 0, 300000);
$doc->loadHTML('<?xml encoding="UTF-8"?>' . $htmlChunk);
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

if ($preview['image']) {
    if (str_starts_with($preview['image'], '//')) {
        $preview['image'] = $scheme . ':' . $preview['image'];
    } elseif (!preg_match('/^https?:\/\//', $preview['image'])) {
        $preview['image'] = $scheme . '://' . $host . '/' . ltrim($preview['image'], '/');
    }
}

if (!$preview['image']) {
    $preview['image']     = 'https://www.google.com/s2/favicons?domain=' . urlencode($host) . '&sz=64';
    $preview['is_favicon'] = true;
}

$preview['title']       = substr($preview['title'], 0, 200);
$preview['description'] = substr($preview['description'], 0, 400);

echo json_encode($preview, JSON_UNESCAPED_UNICODE);
