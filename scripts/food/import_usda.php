<?php
// Produkty podstawowe z USDA FoodData Central, SR Legacy (domena publiczna, https://fdc.nal.usda.gov) – tylko z wiersza poleceń.
// Importuje produkty ze słownika usda_map.php (polskie nazwy) jako source = 'base', seed_key = 'usda_<klucz>'.
//   php scripts/food/import_usda.php                  pobiera paczkę CSV z USDA (~6 MB .zip, wymaga rozszerzenia zip)
//   php scripts/food/import_usda.php --dir=folder     z rozpakowanej wcześniej paczki CSV (food.csv, food_nutrient.csv, food_portion.csv)
//   php scripts/food/import_usda.php --api-key=KLUCZ  przez API (darmowy klucz: https://api.data.gov/signup, DEMO_KEY ma ~10 zapytań/h)
//   --dry            tylko pokaż, co by zapisał
// Można uruchamiać wielokrotnie: produkty są aktualizowane po kluczu, jednostki zastępowane (poza zgłoszonymi przez użytkowników),
// produkty poprawione w HomeList (edited_at) zostają nietknięte. Najpierw seed_base.php, potem ten skrypt.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('forbidden');
}
set_time_limit(0);

include __DIR__ . '/../conn_db.php';
require_once __DIR__ . '/food_lib.php';
$conn->set_charset('utf8mb4');

const HL_USDA_ZIP = 'https://fdc.nal.usda.gov/fdc-datasets/FoodData_Central_sr_legacy_food_csv_2018-04.zip';
const HL_USDA_API = 'https://api.nal.usda.gov/fdc/v1/foods';
// trochę niżej niż seed_data.php (5000) – przy tej samej nazwie wygrywa ręcznie dobrany produkt podstawowy
const HL_USDA_POPULARITY = 3000;
// identyfikatory składników w FoodData Central
const HL_USDA_N = ['kcal' => 1008, 'kcal_g' => 2047, 'kcal_s' => 2048, 'kj' => 1062, 'protein' => 1003, 'fat' => 1004, 'carbs' => 1005,
                   'fiber' => 1079, 'sugars' => 2000, 'sugars_nlea' => 1063, 'sat_fat' => 1258, 'sodium' => 1093];
// produkty liczone na 100 ml (USDA podaje na 100 g – przy gęstości ~1 różnica jest pomijalna)
const HL_USDA_PER_ML = ['mleko_kozie', 'napoj_sojowy', 'napoj_migdalowy', 'woda_kokosowa', 'mleczko_kokosowe', 'ocet_balsamiczny', 'ocet_jablkowy'];

$opt = getopt('', ['dir:', 'zip:', 'api-key:', 'dry']);
$dry = isset($opt['dry']);
$out = fn(string $s) => fwrite(STDOUT, $s . "\n");
$fail = function (string $s) {
    fwrite(STDERR, $s . "\n");
    exit(1);
};

$map = require __DIR__ . '/usda_map.php';
$ids = array_keys($map);

// ---------- dane z USDA: fdc_id => ['desc' => …, 'n' => [nutrient_id => ilość], 'portions' => [[modifier, gramy na 1], …]] ----------
$foods = [];

if (isset($opt['api-key'])) {
    foreach (array_chunk($ids, 20) as $chunk) {
        $ch = curl_init(HL_USDA_API . '?api_key=' . rawurlencode($opt['api-key']));
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['fdcIds' => $chunk, 'format' => 'full']),
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 429) $fail('USDA: limit zapytań wyczerpany (DEMO_KEY ma ~10/h) – użyj własnego klucza albo trybu CSV');
        $json = $body ? json_decode($body, true) : null;
        if ($code !== 200 || !is_array($json)) $fail("USDA API odpowiedziało $code");
        foreach ($json as $f) {
            $id = (int)$f['fdcId'];
            $foods[$id] = ['desc' => $f['description'], 'n' => [], 'portions' => []];
            foreach ($f['foodNutrients'] ?? [] as $n) {
                if (isset($n['amount'], $n['nutrient']['id'])) $foods[$id]['n'][(int)$n['nutrient']['id']] = (float)$n['amount'];
            }
            foreach ($f['foodPortions'] ?? [] as $p) {
                if ((float)($p['amount'] ?? 0) > 0) $foods[$id]['portions'][] = [(string)($p['modifier'] ?? ''), (float)$p['gramWeight'] / (float)$p['amount']];
            }
        }
    }
} else {
    $dir = $opt['dir'] ?? null;
    if ($dir === null) {
        if (!class_exists('ZipArchive')) {
            $fail("Brak rozszerzenia zip w PHP. Pobierz i rozpakuj " . ($opt['zip'] ?? HL_USDA_ZIP) . "\n"
                . "i uruchom z --dir=ścieżka_do_folderu_z_csv (albo użyj --api-key=…)");
        }
        $zipPath = $opt['zip'] ?? null;
        if ($zipPath === null) {
            $zipPath = tempnam(sys_get_temp_dir(), 'usda');
            $out('Pobieram ' . HL_USDA_ZIP);
            if (!@copy(HL_USDA_ZIP, $zipPath)) $fail('Nie udało się pobrać paczki USDA');
        }
        $dir = sys_get_temp_dir() . '/hl_usda_' . getmypid();
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) $fail("Nie można otworzyć: $zipPath");
        $zip->extractTo($dir);
        $zip->close();
    }
    // paczka bywa z podfolderem – szukamy food.csv
    $found = is_file("$dir/food.csv") ? [$dir] : glob("$dir/*", GLOB_ONLYDIR);
    $found = array_values(array_filter($found ?: [], fn($d) => is_file("$d/food.csv")));
    if (!$found) $fail("Nie ma food.csv w $dir");
    $dir = $found[0];

    $want = array_fill_keys($ids, true);
    $read = function (string $file, callable $row) use ($dir, $fail) {
        $fh = @fopen("$dir/$file", 'r');
        if (!$fh) $fail("Nie można otworzyć $dir/$file");
        $head = fgetcsv($fh, 0, ',', '"', '');
        while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            if (count($r) === count($head)) $row(array_combine($head, $r));
        }
        fclose($fh);
    };
    $read('food.csv', function ($r) use (&$foods, $want) {
        $id = (int)$r['fdc_id'];
        if (isset($want[$id])) $foods[$id] = ['desc' => $r['description'], 'n' => [], 'portions' => []];
    });
    $read('food_nutrient.csv', function ($r) use (&$foods) {
        $id = (int)$r['fdc_id'];
        if (isset($foods[$id]) && $r['amount'] !== '') $foods[$id]['n'][(int)$r['nutrient_id']] = (float)$r['amount'];
    });
    $read('food_portion.csv', function ($r) use (&$foods) {
        $id = (int)$r['fdc_id'];
        if (isset($foods[$id]) && (float)$r['amount'] > 0) $foods[$id]['portions'][] = [$r['modifier'], (float)$r['gram_weight'] / (float)$r['amount']];
    });
}

// ---------- porcje USDA -> polskie jednostki (gdy słownik ich nie podaje) ----------
// pierwsze dopasowanie dla danej jednostki wygrywa, więc „medium” jest przed „large”
$portionUnits = function (array $portions): string {
    $rules = [
        ['/^medium\b/', 'sztuka'], ['/^(fruit|egg|whole|piece|nut)\b/', 'sztuka'], ['/^large\b/', 'sztuka'],
        ['/^fillet\b/', 'filet'], ['/^tbsp\b/', 'łyżka'], ['/^tsp\b/', 'łyżeczka'],
    ];
    $units = [];
    foreach ($rules as [$re, $label]) {
        if (isset($units[$label])) continue;
        foreach ($portions as [$mod, $grams]) {
            if ($grams > 0 && preg_match($re, strtolower(trim($mod)))) {
                $units[$label] = round($grams, $grams < 10 ? 1 : 0);
                break;
            }
        }
    }
    return implode('|', array_map(fn($l, $g) => "$l:$g", array_keys($units), $units));
};

// ---------- zapis ----------
$cols = array_column($conn->query('SHOW COLUMNS FROM food_products')->fetch_all(MYSQLI_NUM), 0);
$edited = in_array('edited_at', $cols, true)
    ? array_column($conn->query("SELECT seed_key FROM food_products WHERE source = 'base' AND edited_at IS NOT NULL AND seed_key LIKE 'usda\\_%'")->fetch_all(MYSQLI_NUM), 0)
    : [];
// ta sama nazwa co w seed_data.php = duplikat w wyszukiwarce
$seedNames = [];
foreach (require __DIR__ . '/seed_data.php' as $row) $seedNames[hl_food_norm($row[1])] = $row[0];

$upsert = $conn->prepare(
    "INSERT INTO food_products (source, seed_key, name, per_ml, kcal, protein, fat, carbs, fiber, sugars, sat_fat, salt, name_search, popularity, status)
     VALUES ('base', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
     ON DUPLICATE KEY UPDATE name = VALUES(name), per_ml = VALUES(per_ml), kcal = VALUES(kcal), protein = VALUES(protein),
         fat = VALUES(fat), carbs = VALUES(carbs), fiber = VALUES(fiber), sugars = VALUES(sugars), sat_fat = VALUES(sat_fat),
         salt = VALUES(salt), name_search = VALUES(name_search), popularity = VALUES(popularity)"
);
$find = $conn->prepare('SELECT id FROM food_products WHERE seed_key = ?');
$clear = $conn->prepare('DELETE FROM food_product_units WHERE product_id = ? AND owner_id IS NULL');
$unit = $conn->prepare('INSERT INTO food_product_units (product_id, label, grams, sort) VALUES (?, ?, ?, ?)');

$count = 0;
$warn = [];
foreach ($map as $fdcId => [$key, $name, $units]) {
    $f = $foods[$fdcId] ?? null;
    if (!$f) {
        $warn[] = "$name: brak fdc_id $fdcId w USDA";
        continue;
    }
    if (isset($seedNames[hl_food_norm($name)])) $warn[] = "$name: ta sama nazwa jest już w seed_data.php ({$seedNames[hl_food_norm($name)]})";
    $n = $f['n'];
    $v = fn(string $k) => $n[HL_USDA_N[$k]] ?? null;
    $kcal = $v('kcal') ?? $v('kcal_g') ?? $v('kcal_s') ?? ($v('kj') !== null ? $v('kj') / 4.184 : null);
    if ($kcal === null || $v('protein') === null || $v('fat') === null || $v('carbs') === null) {
        $warn[] = "$name ({$f['desc']}): brak kcal albo makro – pominięty";
        continue;
    }
    $kcal = round($kcal, 1);
    $p = round($v('protein'), 2);
    $fat = round($v('fat'), 2);
    $c = round($v('carbs'), 2);
    $fiber = $v('fiber') !== null ? round($v('fiber'), 2) : null;
    $sugars = ($s = $v('sugars') ?? $v('sugars_nlea')) !== null ? round($s, 2) : null;
    $satFat = $v('sat_fat') !== null ? round($v('sat_fat'), 2) : null;
    $salt = $v('sodium') !== null ? round($v('sodium') * 2.5 / 1000, 2) : null;
    $perMl = in_array($key, HL_USDA_PER_ML, true) ? 1 : 0;
    $units = $units ?? $portionUnits($f['portions']);
    $seedKey = 'usda_' . $key;

    // węglowodany w USDA są z błonnikiem (jak w seed_data.php), a błonnik to ~2 kcal/g, nie 4
    if (!hl_food_macros_match($kcal, $p, $fat, $c, 0.15, 25, -2 * ($fiber ?? 0))) {
        $warn[] = "$name: $kcal kcal, z makro " . round(hl_food_kcal_from_macros($p, $fat, $c) - 2 * ($fiber ?? 0));
    }
    if ($dry) {
        $out(sprintf('%-45s %6s kcal  B %5s  T %5s  W %5s  %s   ← %s', $name, $kcal, $p, $fat, $c, $units ?: '-', $f['desc']));
        $count++;
        continue;
    }
    if (in_array($seedKey, $edited, true)) { $count++; continue; }

    $search = hl_food_norm($name);
    $pop = HL_USDA_POPULARITY;
    $upsert->bind_param('ssiddddddddsi', $seedKey, $name, $perMl, $kcal, $p, $fat, $c, $fiber, $sugars, $satFat, $salt, $search, $pop);
    $upsert->execute();

    $find->bind_param('s', $seedKey);
    $find->execute();
    $id = (int)$find->get_result()->fetch_row()[0];
    $clear->bind_param('i', $id);
    $clear->execute();
    foreach (array_values(array_filter(explode('|', $units))) as $i => $u) {
        [$label, $grams] = explode(':', $u);
        $grams = (float)$grams;
        $unit->bind_param('isdi', $id, $label, $grams, $i);
        $unit->execute();
    }
    $count++;
}
$out("Produkty z USDA: $count" . ($dry ? ' (--dry, nic nie zapisano)' : ''));
if ($warn) $out("Do sprawdzenia:\n  " . implode("\n  ", $warn));
