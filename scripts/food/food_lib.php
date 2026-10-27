<?php
// Wspólne funkcje modułu Jedzenie: normalizacja do wyszukiwania, posiłki, widoczność produktów, porcje,
// mapowanie i zapis produktów z Open Food Facts (import CSV i API na żywo), dzień w dzienniku.
// Używane przez endpointy scripts/food/*, import_off.php i seed_base.php.

if (!function_exists('hl_food_norm')) {
    // tekst do kolumny name_search i do zapytań: małe litery, bez polskich znaków i interpunkcji
    function hl_food_norm(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = strtr($s, ['ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z']);
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) $s = $t;
        $s = preg_replace('/[^a-z0-9%]+/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    function hl_food_meals(): array
    {
        return [1 => 'Śniadanie', 2 => 'II śniadanie', 3 => 'Obiad', 4 => 'Podwieczorek', 5 => 'Kolacja'];
    }

    // kcal wyliczone z makro – kontrola spójności etykiety (import, formularz własnego produktu)
    function hl_food_kcal_from_macros(float $protein, float $fat, float $carbs): float
    {
        return 4 * $protein + 4 * $carbs + 9 * $fat;
    }

    // czy kcal z etykiety pasuje do makro; $tolerance = względna różnica, $abs = dopuszczalna różnica w kcal,
    // $extra = energia spoza B/T/W (alkohol 7 kcal/g, błonnik 2 kcal/g, poprawka na poliole liczone w węglowodanach)
    function hl_food_macros_match(float $kcal, float $protein, float $fat, float $carbs, float $tolerance = 0.15, float $abs = 25, float $extra = 0): bool
    {
        $est = hl_food_kcal_from_macros($protein, $fat, $carbs) + $extra;
        return abs($est - $kcal) <= max($abs, $kcal * $tolerance);
    }

    // poziomy tłuszczu / tłuszczów nasyconych / cukrów / soli (progi FSA, te same co w OFF; napoje mają niższe)
    // zwraca ['fat' => 'low|moderate|high', ...] tylko dla znanych wartości
    function hl_food_levels(array $p): array
    {
        $drink = !empty($p['per_ml']);
        $limits = $drink
            ? ['fat' => [1.5, 8.75], 'sat_fat' => [0.75, 2.5], 'sugars' => [2.5, 11.25], 'salt' => [0.3, 0.75]]
            : ['fat' => [3, 17.5], 'sat_fat' => [1.5, 5], 'sugars' => [5, 22.5], 'salt' => [0.3, 1.5]];
        $out = [];
        foreach ($limits as $key => [$low, $high]) {
            if (!isset($p[$key]) || $p[$key] === null || $p[$key] === '') continue;
            $v = (float)$p[$key];
            $out[$key] = $v <= $low ? 'low' : ($v > $high ? 'high' : 'moderate');
        }
        return $out;
    }

    // 14 alergenów z rozporządzenia UE – tagi OFF (bez „en:”) -> nazwa po polsku
    function hl_food_allergen_names(): array
    {
        return [
            'gluten' => 'gluten', 'crustaceans' => 'skorupiaki', 'eggs' => 'jaja', 'fish' => 'ryby', 'peanuts' => 'orzeszki ziemne',
            'soybeans' => 'soja', 'milk' => 'mleko', 'nuts' => 'orzechy', 'celery' => 'seler', 'mustard' => 'gorczyca',
            'sesame-seeds' => 'sezam', 'sulphur-dioxide-and-sulphites' => 'siarczyny', 'lupin' => 'łubin', 'molluscs' => 'mięczaki',
        ];
    }

    // domownicy = osoby, z którymi użytkownik ma choć jedną wspólną listę (razem z nim samym)
    function hl_food_household(mysqli $conn, int $userId): array
    {
        static $cache = [];
        if (isset($cache[$userId])) return $cache[$userId];
        $stmt = $conn->prepare(
            'SELECT DISTINCT member FROM (
                 SELECT lists.owner_id AS member, lists.id AS list_id FROM lists
                 UNION ALL
                 SELECT list_invities.user_id, list_invities.list_id FROM list_invities
             ) m
             WHERE m.list_id IN (
                 SELECT id FROM lists WHERE owner_id = ?
                 UNION SELECT list_id FROM list_invities WHERE user_id = ?
             )'
        );
        $stmt->bind_param('ii', $userId, $userId);
        $stmt->execute();
        $ids = [$userId];
        $res = $stmt->get_result();
        while ($row = $res->fetch_row()) $ids[] = (int)$row[0];
        $stmt->close();
        return $cache[$userId] = array_values(array_unique($ids));
    }

    // warunek SQL: produkty widoczne dla użytkownika (alias tabeli food_products = $a)
    // podstawowe i z OFF – wszyscy; własne – zaakceptowane wszyscy, oczekujące autor i domownicy, odrzucone tylko autor;
    // dania – autor i domownicy
    function hl_food_visible_sql(mysqli $conn, int $userId, string $a = 'p'): string
    {
        $home = implode(',', array_map('intval', hl_food_household($conn, $userId)));
        $me = (int)$userId;
        return "($a.source IN ('base','off')
                 OR ($a.source = 'user' AND ($a.status = 'approved' OR $a.owner_id = $me OR ($a.status = 'pending' AND $a.owner_id IN ($home))))
                 OR ($a.source = 'dish' AND $a.owner_id IN ($home)))";
    }

    function hl_food_product(mysqli $conn, int $userId, int $id): ?array
    {
        $vis = hl_food_visible_sql($conn, $userId);
        $stmt = $conn->prepare("SELECT p.* FROM food_products p WHERE p.id = ? AND $vis");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // jednostki porcji: własne (sztuka, kromka…), porcja i opakowanie z etykiety, zawsze też 100 g/ml
    // jednostki zgłoszone przez użytkownika widzi od razu on sam, pozostali po akceptacji (mine + id = może je usunąć)
    function hl_food_units(mysqli $conn, array $products, int $userId): array
    {
        $ids = array_map(fn($p) => (int)$p['id'], $products);
        $custom = [];
        if ($ids) {
            $res = $conn->query('SELECT id, product_id, label, grams, owner_id, status FROM food_product_units
                                 WHERE product_id IN (' . implode(',', $ids) . ") AND (status = 'approved' OR owner_id = " . (int)$userId . ')
                                 ORDER BY product_id, sort, id');
            while ($row = $res->fetch_assoc()) {
                $u = ['label' => $row['label'], 'grams' => (float)$row['grams']];
                if ((int)$row['owner_id'] === $userId && $row['status'] !== 'approved') $u += ['id' => (int)$row['id'], 'mine' => true, 'status' => $row['status']];
                $custom[(int)$row['product_id']][] = $u;
            }
        }
        $out = [];
        foreach ($products as $p) {
            $units = $custom[(int)$p['id']] ?? [];
            $sg = $p['serving_g'] !== null ? (float)$p['serving_g'] : null;
            $pg = $p['package_g'] !== null && (float)$p['package_g'] <= 1500 ? (float)$p['package_g'] : null;
            $sl = $p['serving_label'] ?? null;
            // porcja równa opakowaniu (baton, butelka) = jedna jednostka „opakowanie”, chyba że etykieta mówi konkretniej
            if ($sg !== null) $units[] = ['label' => $sl ?? ($sg === $pg ? 'opakowanie' : 'porcja'), 'grams' => $sg];
            if ($pg !== null && $pg !== $sg) $units[] = ['label' => $sl === 'opakowanie' ? 'całe opakowanie' : 'opakowanie', 'grams' => $pg];
            // ta sama jednostka z kilku źródeł (np. zgłoszone opakowanie, które potem trafiło do OFF) tylko raz
            $seen = [];
            $units = array_values(array_filter($units, function ($u) use (&$seen) {
                $k = mb_strtolower($u['label'], 'UTF-8') . '|' . $u['grams'];
                if (isset($seen[$k])) return false;
                return $seen[$k] = true;
            }));
            $units[] = ['label' => $p['per_ml'] ? '100 ml' : '100 g', 'grams' => 100.0, 'base' => true];
            $out[(int)$p['id']] = $units;
        }
        return $out;
    }

    // etykieta porcji z opisu OFF (serving_size: „1 bottle (555 ml)”, „2 wafle (20 g)”); null = zwykła „porcja”
    function hl_food_serving_label(string $s): ?string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $map = [
            'łyżeczka' => '/teaspoon|\btsp\b|łyżeczk/u',
            'łyżka' => '/tablespoon|\btbsp\b|łyżk/u',
            'butelka' => '/bottle|butel/u',
            'puszka' => '/\bcan\b|puszk/u',
            'saszetka' => '/sachet|saszet/u',
            'opakowanie' => '/\bpack|\bbag\b|pouch|\bopak|torebk/u',
            'baton' => '/\bbar\b|baton/u',
            'wafel' => '/wafer|wafel|wafl/u',
            'ciastko' => '/cookie|biscuit|\bciast|herbatnik/u',
            'kromka' => '/kromk/u',
            'plaster' => '/slice|plast/u',
            'szklanka' => '/\bcup\b|\bglass|szklank|kub(ek|k)/u',
            'sztuka' => '/piece|\bpcs?\b|\bszt|\begg|\bjaj/u',
        ];
        foreach ($map as $label => $re) if (preg_match($re, $s)) return $label;
        return null;
    }

    // ostatnio wpisana gramatura i jednostka danego produktu (domyślna porcja = „jak zwykle”)
    function hl_food_last_portions(mysqli $conn, int $userId, array $productIds): array
    {
        $ids = array_filter(array_map('intval', $productIds));
        if (!$ids) return [];
        $res = $conn->query('SELECT l.product_id, l.grams, l.unit_label, l.unit_g FROM food_log l
                             JOIN (SELECT product_id, MAX(id) AS id FROM food_log WHERE user_id = ' . (int)$userId . ' AND product_id IN (' . implode(',', $ids) . ')
                                   GROUP BY product_id) last ON last.id = l.id');
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[(int)$row['product_id']] = ['grams' => (float)$row['grams'], 'unit_label' => $row['unit_label'], 'unit_g' => $row['unit_g'] !== null ? (float)$row['unit_g'] : null];
        }
        return $out;
    }

    // produkt -> wiersz JSON dla wyszukiwarki i arkusza porcji
    function hl_food_rows(mysqli $conn, int $userId, array $products): array
    {
        if (!$products) return [];
        $units = hl_food_units($conn, $products, $userId);
        $last = hl_food_last_portions($conn, $userId, array_column($products, 'id'));
        $rows = [];
        foreach ($products as $p) {
            $id = (int)$p['id'];
            $u = $units[$id];
            $default = isset($last[$id])
                ? ['grams' => $last[$id]['grams'], 'label' => $last[$id]['unit_label'], 'unit_g' => $last[$id]['unit_g'], 'last' => true]
                : ['grams' => $u[0]['grams'], 'label' => empty($u[0]['base']) ? $u[0]['label'] : null, 'unit_g' => empty($u[0]['base']) ? $u[0]['grams'] : null, 'last' => false];
            $rows[] = [
                'id' => $id, 'name' => $p['name'], 'brand' => $p['brand'], 'source' => $p['source'], 'status' => $p['status'],
                'quantity' => $p['quantity'], 'per_ml' => (bool)$p['per_ml'], 'code' => $p['code'],
                'kcal' => (float)$p['kcal'], 'protein' => (float)$p['protein'], 'fat' => (float)$p['fat'], 'carbs' => (float)$p['carbs'],
                'units' => $u, 'default' => $default, 'mine' => (int)$p['owner_id'] === $userId,
            ];
        }
        return $rows;
    }

    // ---------- Open Food Facts ----------

    function hl_food_off_request(mysqli $conn, string $url): ?array
    {
        require_once __DIR__ . '/../app_settings.php';
        $contact = trim(hl_setting($conn, 'off_contact', ''));
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_USERAGENT => 'HomeList/2.0' . ($contact !== '' ? " ($contact)" : ''),
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 500) return null;
        $json = json_decode((string)$body, true);
        return is_array($json) ? $json : null;
    }

    // pola potrzebne do mapowania produktu (API produktu i wyszukiwarka OFF)
    function hl_food_off_fields(): string
    {
        return 'code,product_name,product_name_pl,generic_name,brands,quantity,product_quantity,serving_quantity,serving_size,nutriments,'
             . 'ingredients_text,ingredients_text_pl,lang,allergens_tags,traces_tags,additives_tags,nova_group,nutriscore_grade,'
             . 'ingredients_analysis_tags,image_front_small_url,last_modified_t,unique_scans_n';
    }

    // produkt z API (JSON) -> płaski wiersz w nazewnictwie eksportu CSV, żeby obsłużył go ten sam mapper
    function hl_food_off_flatten(array $p): array
    {
        $join = fn($v) => is_array($v) ? implode(',', $v) : (string)($v ?? '');
        $plIngredients = trim((string)($p['ingredients_text_pl'] ?? ''));
        $row = [
            'code' => (string)($p['code'] ?? ''),
            'product_name' => trim((string)($p['product_name_pl'] ?? '')) ?: (string)($p['product_name'] ?? ''),
            'generic_name' => (string)($p['generic_name'] ?? ''),
            'brands' => $join($p['brands'] ?? ''), 'quantity' => (string)($p['quantity'] ?? ''),
            'product_quantity' => (string)($p['product_quantity'] ?? ''), 'serving_quantity' => (string)($p['serving_quantity'] ?? ''),
            'serving_size' => (string)($p['serving_size'] ?? ''),
            'ingredients_text' => $plIngredients !== '' ? $plIngredients : (string)($p['ingredients_text'] ?? ''),
            'ingredients_lang' => $plIngredients !== '' ? 'pl' : (($p['ingredients_text'] ?? '') !== '' ? ($p['lang'] ?? null) : null),
            'allergens' => $join($p['allergens_tags'] ?? ''), 'traces_tags' => $join($p['traces_tags'] ?? ''),
            'additives_tags' => $join($p['additives_tags'] ?? ''), 'nova_group' => (string)($p['nova_group'] ?? ''),
            'nutriscore_grade' => (string)($p['nutriscore_grade'] ?? ''), 'ingredients_analysis_tags' => $join($p['ingredients_analysis_tags'] ?? ''),
            'image_small_url' => (string)($p['image_front_small_url'] ?? ''), 'last_modified_t' => (string)($p['last_modified_t'] ?? ''),
            'unique_scans_n' => (string)($p['unique_scans_n'] ?? ''),
        ];
        foreach (($p['nutriments'] ?? []) as $k => $v) {
            if (substr($k, -5) === '_100g' && is_scalar($v)) $row[$k] = (string)$v;
        }
        return $row;
    }

    // wiersz OFF (kolumny eksportu CSV) -> ['product' => …, 'details' => …] albo ['reject' => powód]
    function hl_food_off_map(array $r): array
    {
        $num = fn($v) => ($v === null || $v === '' || !is_numeric($v)) ? null : (float)$v;
        $cut = fn($v, int $len) => $v === null || trim((string)$v) === '' ? null : mb_substr(trim((string)$v), 0, $len, 'UTF-8');
        $tags = function ($v, int $max = 300) {
            if ($v === null || trim((string)$v) === '') return null;
            $list = array_filter(array_map(fn($t) => preg_replace('/^[a-z]{2}:/', '', trim($t)), explode(',', (string)$v)));
            $s = implode(',', array_unique($list));
            while (strlen($s) > $max && strpos($s, ',') !== false) $s = substr($s, 0, strrpos($s, ','));
            return $s === '' || strlen($s) > $max ? null : $s;
        };

        $code = preg_replace('/\D/', '', (string)($r['code'] ?? ''));
        if ($code === '' || strlen($code) > 32) return ['reject' => 'code'];
        $name = $cut($r['product_name'] ?? null, 200) ?? $cut($r['generic_name'] ?? null, 200);
        if ($name === null) return ['reject' => 'no_name'];

        $kcal = $num($r['energy-kcal_100g'] ?? null);
        if ($kcal === null) {
            $kj = $num($r['energy-kj_100g'] ?? null) ?? $num($r['energy_100g'] ?? null);
            if ($kj !== null) $kcal = $kj / 4.184;
        }
        $p = $num($r['proteins_100g'] ?? null);
        $f = $num($r['fat_100g'] ?? null);
        $c = $num($r['carbohydrates_100g'] ?? null);
        if ($kcal === null || $p === null || $f === null || $c === null) return ['reject' => 'no_nutrition'];
        // energia spoza B/T/W: alkohol, błonnik (UE liczy go osobno), poliole (2,4 zamiast 4 kcal/g w węglowodanach)
        $extra = 7 * ($num($r['alcohol_100g'] ?? null) ?? 0) + 2 * ($num($r['fiber_100g'] ?? null) ?? 0) - 1.6 * ($num($r['polyols_100g'] ?? null) ?? 0);
        // odrzucamy oczywiste błędy etykiet: wartości spoza zakresu albo kcal zupełnie niezgodne z makro
        if ($kcal < 0 || $kcal > 950 || min($p, $f, $c) < 0 || max($p, $f, $c) > 100 || $p + $f + $c > 105
            || !hl_food_macros_match($kcal, $p, $f, $c, 0.3, 40, $extra)) {
            return ['reject' => 'implausible'];
        }

        $brand = $cut(explode(',', (string)($r['brands'] ?? ''))[0] ?? null, 120);
        $quantity = $cut($r['quantity'] ?? null, 60);
        $pq = $num($r['product_quantity'] ?? null);
        $sq = $num($r['serving_quantity'] ?? null);
        $serving = $sq !== null && $sq >= 1 && $sq <= 2000 ? round($sq, 1) : null;
        $servingSize = (string)($r['serving_size'] ?? '');
        $servingLabel = $serving !== null ? hl_food_serving_label($servingSize) : null;
        // „3 ciastka (25 g)” – jednostką jest jedna sztuka
        if ($servingLabel !== null && preg_match('/^\s*(\d+)\s*(?:x\s*)?\p{L}/u', $servingSize, $m) && (int)$m[1] > 1) {
            $n = (int)$m[1];
            if ($n <= 20 && $serving / $n >= 1) $serving = round($serving / $n, 1);
            else $servingLabel = null;
        }
        // „1 serving (100 g)” to tylko przeliczenie etykiety na 100 g – dubluje jednostkę 100 g
        if ($serving === 100.0 && $servingLabel === null) $serving = null;
        $opt = fn($v) => $v === null || $v < 0 || $v > 100 ? null : round($v, 2);

        $product = [
            'source' => 'off', 'code' => $code, 'name' => $name, 'brand' => $brand, 'quantity' => $quantity,
            'package_g' => $pq !== null && $pq >= 1 && $pq <= 5000 ? round($pq, 1) : null,
            'serving_g' => $serving, 'serving_label' => $servingLabel,
            'per_ml' => $quantity !== null && preg_match('/\d\s*(ml|cl|l)\b/i', $quantity) ? 1 : 0,
            'kcal' => round($kcal, 1), 'protein' => round($p, 2), 'fat' => round($f, 2), 'carbs' => round($c, 2),
            'sugars' => $opt($num($r['sugars_100g'] ?? null)), 'fiber' => $opt($num($r['fiber_100g'] ?? null)),
            'sat_fat' => $opt($num($r['saturated-fat_100g'] ?? null)), 'salt' => $opt($num($r['salt_100g'] ?? null)),
            'name_search' => mb_substr(hl_food_norm($name . ' ' . ($brand ?? '')), 0, 400),
            'popularity' => (int)($r['unique_scans_n'] ?? 0), 'status' => 'approved', 'off_modified' => (int)($r['last_modified_t'] ?? 0),
        ];

        $analysis = ',' . ($r['ingredients_analysis_tags'] ?? '') . ',';
        $flag = function (array $map) use ($analysis) {
            foreach ($map as $tag => $val) if (strpos($analysis, ",en:$tag,") !== false) return $val;
            return null;
        };
        $lang = $r['ingredients_lang'] ?? null;
        // eksport CSV nie ma języka składu – znamy go tylko ze zdjęcia składu (ingredients_pl.…)
        if ($lang === null && preg_match('/ingredients_([a-z]{2})\./', (string)($r['image_ingredients_url'] ?? ''), $m)) $lang = $m[1];
        $nova = (int)($r['nova_group'] ?? 0);
        $ns = strtolower(trim((string)($r['nutriscore_grade'] ?? '')));

        $details = [
            'ingredients' => $cut($r['ingredients_text'] ?? null, 5000), 'ingredients_lang' => $lang ? substr($lang, 0, 2) : null,
            'allergens' => $tags($r['allergens'] ?? null), 'traces' => $tags($r['traces_tags'] ?? null),
            'additives' => $tags($r['additives_tags'] ?? null, 500),
            'nova' => $nova >= 1 && $nova <= 4 ? $nova : null,
            'nutriscore' => in_array($ns, ['a', 'b', 'c', 'd', 'e'], true) ? $ns : null,
            'palm_oil' => $flag(['palm-oil' => 1, 'may-contain-palm-oil' => 2, 'palm-oil-free' => 0]),
            'vegan' => $flag(['vegan' => 1, 'maybe-vegan' => 2, 'non-vegan' => 0]),
            'vegetarian' => $flag(['vegetarian' => 1, 'maybe-vegetarian' => 2, 'non-vegetarian' => 0]),
            'image_url' => $cut($r['image_small_url'] ?? null, 255),
        ];
        return ['product' => $product, 'details' => $details];
    }

    // zapis paczki zmapowanych produktów OFF (upsert po kodzie); zwraca kod => id
    // produkt z innego źródła z tym samym kodem (np. dodany przez użytkownika) zostaje nietknięty
    // $mode: import (eksport CSV – pełne dane), live (API produktu, znacznik fetched_at), search (wyszukiwarka OFF – dane niepełne)
    function hl_food_off_save(mysqli $conn, array $batch, string $mode = "import"): array
    {
        if (!$batch) return [];
        $q = fn($v) => $v === null ? 'NULL' : (is_float($v) || is_int($v) ? (string)$v : "'" . $conn->real_escape_string((string)$v) . "'");
        $pcols = array_keys($batch[0]['product']);
        $update = implode(', ', array_map(fn($c) => "`$c` = IF(`source` = 'off', VALUES(`$c`), `$c`)", array_diff($pcols, ['source', 'code', 'status'])));
        $values = array_map(fn($b) => '(' . implode(',', array_map(fn($c) => $q($b['product'][$c]), $pcols)) . ')', $batch);
        $conn->query('INSERT INTO food_products (`' . implode('`,`', $pcols) . '`) VALUES ' . implode(',', $values) . " ON DUPLICATE KEY UPDATE $update");

        $codes = implode(',', array_map(fn($b) => $q($b['product']['code']), $batch));
        $ids = [];
        $res = $conn->query("SELECT code, id FROM food_products WHERE source = 'off' AND code IN ($codes)");
        while ($row = $res->fetch_row()) $ids[$row[0]] = (int)$row[1];

        $dcols = array_merge(['product_id'], array_keys($batch[0]['details']), ['fetched_at']);
        $now = $mode === "live" ? date('Y-m-d H:i:s') : null;
        $values = [];
        foreach ($batch as $b) {
            if (!isset($ids[$b['product']['code']])) continue;
            $d = ['product_id' => $ids[$b['product']['code']]] + $b['details'] + ['fetched_at' => $now];
            $values[] = '(' . implode(',', array_map(fn($c) => $q($d[$c]), $dcols)) . ')';
        }
        if ($values) {
            // wyniki wyszukiwarki OFF nie zawsze mają skład – wtedy nie zerujemy tego, co już wiemy
            $keep = fn($c) => $c === 'fetched_at' || $mode === 'search';
            $dupdate = implode(', ', array_map(fn($c) => $keep($c) ? "`$c` = COALESCE(VALUES(`$c`), `$c`)" : "`$c` = VALUES(`$c`)", array_slice($dcols, 1)));
            $conn->query('INSERT INTO food_product_details (`' . implode('`,`', $dcols) . '`) VALUES ' . implode(',', $values) . " ON DUPLICATE KEY UPDATE $dupdate");
        }
        return $ids;
    }

    // ---------- dziennik ----------

    // cel obowiązujący w danym dniu (ostatni z valid_from <= dzień); null = nie ustawiony
    function hl_food_goal(mysqli $conn, int $userId, string $date): ?array
    {
        $stmt = $conn->prepare('SELECT kcal, protein, fat, carbs, valid_from, calc FROM food_goals WHERE user_id = ? AND valid_from <= ? ORDER BY valid_from DESC LIMIT 1');
        $stmt->bind_param('is', $userId, $date);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return null;
        foreach (['kcal', 'protein', 'fat', 'carbs'] as $k) $row[$k] = (int)$row[$k];
        $row['calc'] = $row['calc'] ? json_decode($row['calc'], true) : null;
        return $row;
    }

    function hl_food_settings(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare('SELECT enabled, meals, show_on_today FROM food_settings WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $meals = array_values(array_intersect(array_keys(hl_food_meals()), array_map('intval', explode(',', $row['meals'] ?? '1,2,3,4,5'))));
        return ['enabled' => (bool)($row['enabled'] ?? false), 'meals' => $meals ?: [1, 2, 3, 4, 5], 'show_on_today' => (bool)($row['show_on_today'] ?? true)];
    }

    function hl_food_entry(array $row): array
    {
        return [
            'id' => (int)$row['id'], 'date' => $row['log_date'], 'meal' => (int)$row['meal'],
            'product_id' => $row['product_id'] !== null ? (int)$row['product_id'] : null, 'name' => $row['name'],
            'grams' => $row['grams'] !== null ? (float)$row['grams'] : null,
            'unit_label' => $row['unit_label'], 'unit_g' => $row['unit_g'] !== null ? (float)$row['unit_g'] : null,
            'kcal' => (float)$row['kcal'], 'protein' => (float)$row['protein'], 'fat' => (float)$row['fat'], 'carbs' => (float)$row['carbs'],
            'source' => $row['source'], 'ml' => !empty($row['per_ml']),
        ];
    }

    // dzień w dzienniku: wpisy w posiłkach, sumy, cel
    function hl_food_day(mysqli $conn, int $userId, string $date): array
    {
        $stmt = $conn->prepare('SELECT l.*, p.per_ml FROM food_log l LEFT JOIN food_products p ON p.id = l.product_id
                                WHERE l.user_id = ? AND l.log_date = ? ORDER BY l.meal, l.sort, l.id');
        $stmt->bind_param('is', $userId, $date);
        $stmt->execute();
        $res = $stmt->get_result();
        $meals = [];
        foreach (hl_food_meals() as $m => $label) $meals[$m] = ['meal' => $m, 'label' => $label, 'entries' => [], 'kcal' => 0, 'protein' => 0, 'fat' => 0, 'carbs' => 0];
        $total = ['kcal' => 0, 'protein' => 0, 'fat' => 0, 'carbs' => 0];
        while ($row = $res->fetch_assoc()) {
            $e = hl_food_entry($row);
            if (!isset($meals[$e['meal']])) continue;
            $meals[$e['meal']]['entries'][] = $e;
            foreach ($total as $k => $_) {
                $meals[$e['meal']][$k] += $e[$k];
                $total[$k] += $e[$k];
            }
        }
        $stmt->close();
        $round = function (array $a): array {
            foreach (['kcal', 'protein', 'fat', 'carbs'] as $k) $a[$k] = round($a[$k], 1);
            return $a;
        };
        return ['date' => $date, 'meals' => array_values(array_map($round, $meals)), 'total' => $round($total), 'goal' => hl_food_goal($conn, $userId, $date)];
    }

    // suma kcal w kolejnych dniach (pasek dni) i cel każdego dnia: date => [kcal, goal|null]
    function hl_food_strip(mysqli $conn, int $userId, string $from, string $to): array
    {
        $stmt = $conn->prepare('SELECT log_date, SUM(kcal) FROM food_log WHERE user_id = ? AND log_date BETWEEN ? AND ? GROUP BY log_date');
        $stmt->bind_param('iss', $userId, $from, $to);
        $stmt->execute();
        $sums = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_NUM) as [$d, $k]) $sums[$d] = (float)$k;
        $stmt->close();
        $stmt = $conn->prepare('SELECT valid_from, kcal FROM food_goals WHERE user_id = ? AND valid_from <= ? ORDER BY valid_from');
        $stmt->bind_param('is', $userId, $to);
        $stmt->execute();
        $goals = $stmt->get_result()->fetch_all(MYSQLI_NUM);
        $stmt->close();
        $out = [];
        for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            $goal = null;
            foreach ($goals as [$validFrom, $kcal]) if ($validFrom <= $d) $goal = (int)$kcal;
            $out[] = ['date' => $d, 'kcal' => round($sums[$d] ?? 0), 'goal' => $goal];
        }
        return $out;
    }

    // posiłki z poprzedniego dnia (przycisk „Jak wczoraj”): meal => [count, kcal]
    function hl_food_prev(mysqli $conn, int $userId, string $date): array
    {
        $prev = date('Y-m-d', strtotime($date . ' -1 day'));
        $stmt = $conn->prepare('SELECT meal, COUNT(*), SUM(kcal) FROM food_log WHERE user_id = ? AND log_date = ? GROUP BY meal');
        $stmt->bind_param('is', $userId, $prev);
        $stmt->execute();
        $out = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_NUM) as [$meal, $count, $kcal]) $out[(int)$meal] = ['count' => (int)$count, 'kcal' => round((float)$kcal)];
        $stmt->close();
        return $out;
    }

    // komplet danych ekranu Jedzenie (pierwsze renderowanie i odświeżenie po zmianie dnia / synchronizacji)
    function hl_food_payload(mysqli $conn, int $userId, string $date, bool $withStrip = true): array
    {
        $data = ['day' => hl_food_day($conn, $userId, $date), 'prev' => hl_food_prev($conn, $userId, $date),
                 'settings' => hl_food_settings($conn, $userId), 'today' => date('Y-m-d'), 'now' => date('H:i')];
        if ($withStrip) {
            $today = date('Y-m-d');
            // w przód tyle, żeby wybrany dzień dało się wycentrować na szerokim ekranie (jak pasek planu dnia)
            $data['strip'] = hl_food_strip($conn, $userId, date('Y-m-d', strtotime(min($date, $today) . ' -27 day')), date('Y-m-d', strtotime(max($date, $today) . ' +14 day')));
        }
        return $data;
    }

    // makro porcji z wartości na 100 g
    function hl_food_portion(array $p, float $grams): array
    {
        $k = $grams / 100;
        return ['kcal' => round((float)$p['kcal'] * $k, 1), 'protein' => round((float)$p['protein'] * $k, 2),
                'fat' => round((float)$p['fat'] * $k, 2), 'carbs' => round((float)$p['carbs'] * $k, 2)];
    }

    function hl_food_valid_date($s): bool
    {
        return is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && strtotime($s) !== false;
    }

    function hl_food_float($v, float $min, float $max): ?float
    {
        $v = str_replace(',', '.', trim((string)$v));
        if ($v === '' || !is_numeric($v)) return null;
        $f = (float)$v;
        return $f < $min || $f > $max ? null : $f;
    }

    function hl_food_json(string $status, string $message = '', array $extra = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
