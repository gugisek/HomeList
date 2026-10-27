<?php
// „Szukaj dalej w Open Food Facts” (GET q): wyszukiwarka OFF z filtrem na produkty z Polski.
// Znalezione produkty zapisujemy w bazie (następnym razem znajdzie je już wyszukiwarka lokalna),
// a zapytanie zapamiętujemy na tydzień. OFF ma limit ~10 zapytań/min – pilnujemy go globalnie.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$q = hl_food_norm(mb_substr((string)($_GET['q'] ?? ''), 0, 100));
if (mb_strlen($q) < 2) hl_food_json('warning', 'Wpisz co najmniej 2 znaki');

$codes = null;
$stmt = $conn->prepare('SELECT codes FROM food_off_queries WHERE query = ? AND fetched_at > NOW() - INTERVAL 7 DAY');
$stmt->bind_param('s', $q);
$stmt->execute();
$cached = $stmt->get_result()->fetch_row();
$stmt->close();

if ($cached) {
    $codes = array_filter(explode(',', (string)$cached[0]));
} else {
    $recent = (int)$conn->query('SELECT COUNT(*) FROM food_off_queries WHERE fetched_at > NOW() - INTERVAL 1 MINUTE')->fetch_row()[0];
    if ($recent >= 8) hl_food_json('warning', 'Open Food Facts potrzebuje chwili przerwy – spróbuj za minutę');

    $url = 'https://search.openfoodfacts.org/search?' . http_build_query([
        'q' => $q . ' countries_tags:"en:poland"', 'langs' => 'pl,en', 'page_size' => 30, 'fields' => hl_food_off_fields(),
    ]);
    $json = hl_food_off_request($conn, $url);
    if ($json === null || !isset($json['hits'])) hl_food_json('error', 'Open Food Facts nie odpowiada – spróbuj za chwilę');

    $batch = [];
    foreach ($json['hits'] as $hit) {
        $mapped = hl_food_off_map(hl_food_off_flatten($hit));
        if (!isset($mapped['reject'])) $batch[$mapped['product']['code']] = $mapped;
    }
    hl_food_off_save($conn, array_values($batch), 'search');
    $codes = array_keys($batch);

    $list = implode(',', $codes);
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT INTO food_off_queries (query, codes, fetched_at) VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE codes = VALUES(codes), fetched_at = VALUES(fetched_at)');
    $stmt->bind_param('sss', $q, $list, $now);
    $stmt->execute();
    $stmt->close();
}

$items = [];
if ($codes) {
    $in = implode(',', array_map(fn($c) => "'" . $conn->real_escape_string($c) . "'", $codes));
    $res = $conn->query("SELECT p.* FROM food_products p WHERE p.code IN ($in) AND " . hl_food_visible_sql($conn, $user_id) . "
                         ORDER BY FIELD(p.code, $in)");
    while ($row = $res->fetch_assoc()) $items[] = $row;
}
hl_food_json('success', '', [
    'groups' => $items ? [['key' => 'off', 'title' => 'Z Open Food Facts', 'items' => hl_food_rows($conn, $user_id, $items)]] : [],
    'query' => $q,
]);
