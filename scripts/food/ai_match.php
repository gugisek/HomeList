<?php
// Dopasowanie składników z odpowiedzi czatu AI (POST items = JSON z „Policz ze zdjęcia”) do bazy HomeList.
// Kandydaci jak w wyszukiwarce (hl_food_find); wybieramy pierwszego, którego kcal/100 g zgadza się z oszacowaniem AI.
// Bez pewnego dopasowania zwracamy alternatywy, a użytkownik może zostać przy wartościach z AI.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$items = json_decode((string)($_POST['items'] ?? ''), true);
if (!is_array($items) || !$items) hl_food_json('warning', 'Brak składników w odpowiedzi');

$num = fn($v, float $min, float $max) => is_numeric($v) && (float)$v >= $min && (float)$v <= $max ? (float)$v : null;
$out = [];
foreach (array_slice($items, 0, 25) as $it) {
    if (!is_array($it)) continue;
    $name = mb_substr(trim((string)($it['name'] ?? '')), 0, 120);
    $grams = $num($it['grams'] ?? null, 0.5, 3000);
    if ($name === '' || $grams === null) continue;
    $p100 = is_array($it['per100'] ?? null) ? $it['per100'] : [];
    $ai = [
        'name' => mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1),
        'brand' => trim((string)($it['brand'] ?? '')) !== '' ? mb_substr(trim((string)$it['brand']), 0, 80) : null,
        'grams' => round($grams, 1),
        'count' => $num($it['count'] ?? null, 0.1, 100),
        'unit' => trim((string)($it['unit'] ?? '')) !== '' ? mb_substr(mb_strtolower(trim((string)$it['unit'])), 0, 30) : null,
        'per100' => [
            'kcal' => $num($p100['kcal'] ?? null, 0, 950),
            'protein' => $num($p100['protein'] ?? null, 0, 100) ?? 0.0,
            'fat' => $num($p100['fat'] ?? null, 0, 100) ?? 0.0,
            'carbs' => $num($p100['carbs'] ?? null, 0, 100) ?? 0.0,
        ],
        'confidence' => in_array($it['confidence'] ?? '', ['high', 'medium', 'low'], true) ? $it['confidence'] : 'medium',
    ];
    if ($ai['per100']['kcal'] === null) $ai['per100']['kcal'] = round(hl_food_kcal_from_macros($ai['per100']['protein'], $ai['per100']['fat'], $ai['per100']['carbs']));

    // najpierw z marką, potem sama nazwa, potem nazwa bez stanu („Pierś z kurczaka, smażona” → „Pierś z kurczaka”), na końcu dwa pierwsze słowa
    $queries = array_unique(array_filter([
        $ai['brand'] ? $name . ' ' . $ai['brand'] : null,
        $name,
        str_contains($name, ',') ? trim(explode(',', $name)[0]) : null,
        implode(' ', array_slice(explode(' ', hl_food_norm($name)), 0, 2)),
    ]));
    $cands = [];
    foreach ($queries as $q) {
        foreach (hl_food_find($conn, $user_id, $q, 8, false) as $row) $cands[(int)$row['id']] ??= $row;
        if (count($cands) >= 6) break;
    }
    $cands = array_values($cands);

    $norm = hl_food_norm($name);
    $aiK = (float)$ai['per100']['kcal'];
    $diff = fn(array $p) => abs((float)$p['kcal'] - $aiK) / max($aiK, 40);
    $pick = null;
    $sure = false;
    foreach ($cands as $i => $p) {
        if (hl_food_norm($p['name']) === $norm) { $pick = $i; $sure = $diff($p) <= 0.5; break; }
    }
    if ($pick === null) {
        foreach (array_slice($cands, 0, 6, true) as $i => $p) if ($diff($p) <= 0.25) { $pick = $i; $sure = true; break; }
    }
    if ($pick === null && $cands) {
        // nic nie zgadza się dokładnie – najbliższy kaloriami wśród pierwszych, o ile nie odjeżdża za daleko
        $best = null;
        foreach (array_slice($cands, 0, 5, true) as $i => $p) if ($best === null || $diff($p) < $diff($cands[$best])) $best = $i;
        if ($diff($cands[$best]) <= 0.45) $pick = $best;
    }

    $rows = hl_food_rows($conn, $user_id, $cands);
    $match = $pick !== null ? $rows[$pick] : null;
    $alts = array_values(array_filter($rows, fn($r) => !$match || $r['id'] !== $match['id']));
    $out[] = ['ai' => $ai, 'match' => $match, 'sure' => $sure, 'alts' => array_slice($alts, 0, 4)];
}
if (!$out) hl_food_json('warning', 'Nie rozpoznano żadnego składnika – sprawdź, czy wklejasz całą odpowiedź');
hl_food_json('success', '', ['items' => $out]);
