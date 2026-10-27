<?php
// Produkty podstawowe (seed_data.php) -> food_products (source = 'base') + jednostki porcji. Tylko z wiersza poleceń:
//   php scripts/food/seed_base.php
// Można uruchamiać wielokrotnie: produkty są aktualizowane po kluczu (seed_key), jednostki zastępowane.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('forbidden');
}
include __DIR__ . '/../conn_db.php';
require_once __DIR__ . '/food_lib.php';
$conn->set_charset('utf8mb4');

// produkty podstawowe wyżej w wynikach niż większość markowych z OFF
const HL_FOOD_BASE_POPULARITY = 5000;

$data = require __DIR__ . '/seed_data.php';
$upsert = $conn->prepare(
    "INSERT INTO food_products (source, seed_key, name, per_ml, kcal, protein, fat, carbs, fiber, sugars, name_search, popularity, status)
     VALUES ('base', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
     ON DUPLICATE KEY UPDATE name = VALUES(name), per_ml = VALUES(per_ml), kcal = VALUES(kcal), protein = VALUES(protein),
         fat = VALUES(fat), carbs = VALUES(carbs), fiber = VALUES(fiber), sugars = VALUES(sugars),
         name_search = VALUES(name_search), popularity = VALUES(popularity)"
);
$find = $conn->prepare('SELECT id FROM food_products WHERE seed_key = ?');
$clear = $conn->prepare('DELETE FROM food_product_units WHERE product_id = ? AND owner_id IS NULL');
$unit = $conn->prepare('INSERT INTO food_product_units (product_id, label, grams, sort) VALUES (?, ?, ?, ?)');

$count = 0;
$warn = [];
foreach ($data as $row) {
    [$key, $name, $kcal, $p, $f, $c, $fiber, $sugars, $units] = $row;
    $perMl = (int)($row[9] ?? 0);
    $search = hl_food_norm($name);
    $pop = HL_FOOD_BASE_POPULARITY;
    $upsert->bind_param('ssiddddddsi', $key, $name, $perMl, $kcal, $p, $f, $c, $fiber, $sugars, $search, $pop);
    $upsert->execute();

    $find->bind_param('s', $key);
    $find->execute();
    $id = (int)$find->get_result()->fetch_row()[0];
    $clear->bind_param('i', $id);
    $clear->execute();
    foreach (array_filter(explode('|', $units)) as $i => $u) {
        [$label, $grams] = explode(':', $u);
        $grams = (float)$grams;
        $unit->bind_param('isdi', $id, $label, $grams, $i);
        $unit->execute();
    }
    // napoje alkoholowe mają energię z alkoholu, poza B/T/W
    if (!in_array($key, ['piwo', 'wino_czerwone', 'wodka'], true) && !hl_food_macros_match($kcal, $p, $f, $c, 0.15, 25)) {
        $warn[] = "$name: $kcal kcal, z makro " . round(hl_food_kcal_from_macros($p, $f, $c));
    }
    $count++;
}
echo "Produkty podstawowe: $count\n";
if ($warn) echo "Do sprawdzenia (kcal niezgodne z makro):\n  " . implode("\n  ", $warn) . "\n";
