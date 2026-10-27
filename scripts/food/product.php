<?php
// Szczegóły produktu do arkusza porcji (GET id): wartości, porcje, ostatnio wpisana porcja i sekcja „Skład i jakość”.
// Produkt z wyszukiwarki OFF nie ma składu – dociągamy go raz z API produktu.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$id = (int)($_GET['id'] ?? 0);
$p = hl_food_product($conn, $user_id, $id);
if (!$p) hl_food_json('error', 'Nie znaleziono produktu');

$detailsOf = function () use ($conn, $id): ?array {
    $res = $conn->query('SELECT * FROM food_product_details WHERE product_id = ' . $id);
    return $res->fetch_assoc() ?: null;
};
$d = $detailsOf();
if ($p['source'] === 'off' && $p['code'] && (!$d || ($d['fetched_at'] === null && $d['ingredients'] === null))) {
    $json = hl_food_off_request($conn, 'https://world.openfoodfacts.org/api/v2/product/' . rawurlencode($p['code']) . '.json?fields=' . hl_food_off_fields());
    if ($json !== null) {
        $mapped = !empty($json['product']) ? hl_food_off_map(hl_food_off_flatten($json['product'])) : ['reject' => 'missing'];
        if (!isset($mapped['reject'])) {
            hl_food_off_save($conn, [$mapped], 'live');
            $p = hl_food_product($conn, $user_id, $id);
        } else {
            // nie pytamy ponownie przy każdym otwarciu
            $conn->query("INSERT INTO food_product_details (product_id, fetched_at) VALUES ($id, NOW()) ON DUPLICATE KEY UPDATE fetched_at = NOW()");
        }
        $d = $detailsOf();
    }
}

$row = hl_food_rows($conn, $user_id, [$p])[0];
foreach (['sugars', 'fiber', 'sat_fat', 'salt'] as $k) $row[$k] = $p[$k] !== null ? (float)$p[$k] : null;

$quality = null;
if ($d) {
    $names = hl_food_allergen_names();
    $tagNames = fn($s) => array_values(array_map(fn($t) => $names[$t] ?? str_replace('-', ' ', $t), array_filter(explode(',', (string)$s))));
    $additives = [];
    if ($d['additives']) {
        // tylko kody główne (e322, bez wariantów e322i), z nazwą i oceną EFSA
        $codes = array_values(array_unique(array_filter(explode(',', $d['additives']), fn($c) => preg_match('/^e\d{3,4}[a-z]?$/', $c))));
        $known = [];
        if ($codes) {
            $in = implode(',', array_map(fn($c) => "'" . $conn->real_escape_string($c) . "'", $codes));
            $res = $conn->query("SELECT code, name, risk, efsa_url FROM food_additives WHERE code IN ($in)");
            while ($a = $res->fetch_assoc()) $known[$a['code']] = $a;
        }
        foreach ($codes as $c) {
            if (preg_match('/^(e\d+)[a-z]$/', $c, $m) && in_array($m[1], $codes, true)) continue;
            $additives[] = ['code' => strtoupper($c), 'name' => $known[$c]['name'] ?? null, 'risk' => $known[$c]['risk'] ?? null, 'url' => $known[$c]['efsa_url'] ?? null];
        }
    }
    $quality = [
        'nutriscore' => $d['nutriscore'], 'nova' => $d['nova'] !== null ? (int)$d['nova'] : null,
        'levels' => hl_food_levels($p),
        'palm_oil' => $d['palm_oil'] !== null ? (int)$d['palm_oil'] : null,
        'vegan' => $d['vegan'] !== null ? (int)$d['vegan'] : null, 'vegetarian' => $d['vegetarian'] !== null ? (int)$d['vegetarian'] : null,
        'allergens' => $tagNames($d['allergens']), 'traces' => $tagNames($d['traces']), 'additives' => $additives,
        // alergeny w tekście są oznaczone _podkreślnikami_ (format OFF) – interfejs je pogrubia
        'ingredients' => $d['ingredients'], 'ingredients_lang' => $d['ingredients_lang'], 'image_url' => $d['image_url'],
    ];
} elseif (in_array($p['source'], ['base', 'user', 'dish'], true)) {
    $levels = hl_food_levels($p);
    if ($levels) $quality = ['levels' => $levels];
}
$row['quality'] = $quality;

if ($p['source'] === 'dish') {
    $stmt = $conn->prepare('SELECT d.id, d.cooked_g, d.servings, d.owner_id, u.name AS owner_name FROM food_dishes d LEFT JOIN users u ON u.id = d.owner_id WHERE d.product_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $dish = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($dish) {
        $items = $prods = [];
        $res = $conn->query('SELECT i.grams, i.unit_label, i.unit_g, p.* FROM food_dish_items i JOIN food_products p ON p.id = i.product_id
                             WHERE i.dish_id = ' . (int)$dish['id'] . ' ORDER BY i.sort, i.id');
        while ($it = $res->fetch_assoc()) $prods[] = $it;
        // jednostki składników – edytor dania pozwala przełączać „3 × sztuka” / gramy
        $units = hl_food_units($conn, $prods, $user_id);
        foreach ($prods as $it) {
            $items[] = ['product_id' => (int)$it['id'], 'name' => $it['name'], 'grams' => (float)$it['grams'], 'kcal' => round((float)$it['kcal'] * $it['grams'] / 100),
                        'unit_label' => $it['unit_label'], 'unit_g' => $it['unit_g'] !== null ? (float)$it['unit_g'] : null, 'per_ml' => (bool)$it['per_ml'],
                        'p100' => ['kcal' => (float)$it['kcal'], 'protein' => (float)$it['protein'], 'fat' => (float)$it['fat'], 'carbs' => (float)$it['carbs']],
                        'units' => $units[(int)$it['id']]];
        }
        $row['dish'] = ['id' => (int)$dish['id'], 'cooked_g' => $dish['cooked_g'] !== null ? (float)$dish['cooked_g'] : null,
                        'servings' => $dish['servings'] !== null ? (int)$dish['servings'] : null,
                        'public' => (bool)$p['public'], 'status' => $p['status'],
                        'owner' => $dish['owner_name'], 'mine' => (int)$dish['owner_id'] === $user_id, 'items' => $items];
    }
} elseif ($p['source'] === 'user' && $p['owner_id']) {
    $stmt = $conn->prepare('SELECT name FROM users WHERE id = ?');
    $owner = (int)$p['owner_id'];
    $stmt->bind_param('i', $owner);
    $stmt->execute();
    $row['owner'] = $stmt->get_result()->fetch_row()[0] ?? null;
    $stmt->close();
}

hl_food_json('success', '', ['product' => $row]);
