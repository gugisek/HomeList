<?php
// Import polskich produktów z Open Food Facts (dane: ODbL, https://world.openfoodfacts.org) – tylko z wiersza poleceń:
//   php scripts/food/import_off.php                      strumień eksportu CSV prosto z OFF (~820 MB .gz, nic nie zapisuje na dysk)
//   php scripts/food/import_off.php --file=sciezka.csv.gz   z pobranego wcześniej pliku (.csv albo .csv.gz)
//   --limit=N        zatrzymaj po N zaimportowanych produktach (test)
//   --dry            tylko policz, nic nie zapisuj
//   --additives-only tylko słownik dodatków (taksonomia OFF)
// Odświeżanie: raz w miesiącu z crona, np.  0 4 1 * * php /sciezka/HomeList/scripts/food/import_off.php
// Ponowny import pomija produkty bez zmian (last_modified_t) i nie nadpisuje produktów z innych źródeł o tym samym kodzie.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('forbidden');
}
set_time_limit(0);
ini_set('memory_limit', '1024M');

include __DIR__ . '/../conn_db.php';
require_once __DIR__ . '/food_lib.php';
$conn->set_charset('utf8mb4');

const HL_OFF_CSV = 'https://static.openfoodfacts.org/data/en.openfoodfacts.org.products.csv.gz';
const HL_OFF_ADDITIVES = 'https://static.openfoodfacts.org/data/taxonomies/additives.json';

$opt = getopt('', ['file:', 'limit:', 'dry', 'additives-only', 'additives-file:']);
$dry = isset($opt['dry']);
$limit = isset($opt['limit']) ? max(1, (int)$opt['limit']) : 0;
$out = fn(string $s) => fwrite(STDOUT, $s . "\n");

// ---------- słownik dodatków ----------
$json = @file_get_contents($opt['additives-file'] ?? HL_OFF_ADDITIVES);
$tax = $json ? json_decode($json, true) : null;
if (!is_array($tax)) {
    $out('Nie udało się pobrać taksonomii dodatków – pomijam słownik');
} else {
    $rows = 0;
    $stmt = $conn->prepare('INSERT INTO food_additives (code, name, risk, efsa_url) VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE name = VALUES(name), risk = VALUES(risk), efsa_url = VALUES(efsa_url)');
    foreach ($tax as $key => $a) {
        if (!preg_match('/^en:(e\d{3,4}[a-z]*)$/', $key, $m)) continue;
        $code = $m[1];
        $name = $a['name']['pl'] ?? $a['name']['en'] ?? strtoupper($code);
        $name = trim(preg_replace('/^E\d{3,4}[a-z()]*\s*[-–]\s*/i', '', $name)) ?: strtoupper($code);
        $risk = ['en:high' => 'high', 'en:moderate' => 'moderate', 'en:no' => 'none'][$a['efsa_evaluation_overexposure_risk']['en'] ?? ''] ?? null;
        $url = $a['efsa_evaluation_url']['en'] ?? null;
        if ($url !== null && strlen($url) > 255) $url = null;
        $rows++;
        if ($dry) continue;
        $stmt->bind_param('ssss', $code, $name, $risk, $url);
        $stmt->execute();
    }
    $stmt->close();
    $out("Dodatki: $rows");
}
if (isset($opt['additives-only'])) exit(0);

// ---------- produkty ----------
$src = $opt['file'] ?? HL_OFF_CSV;
$path = preg_match('/\.gz$/i', $src) ? 'compress.zlib://' . $src : $src;
$fh = @fopen($path, 'r');
if (!$fh) {
    fwrite(STDERR, "Nie można otworzyć: $src\n");
    exit(1);
}
$head = explode("\t", rtrim((string)fgets($fh), "\r\n"));
foreach (['code', 'product_name', 'countries_tags', 'energy-kcal_100g', 'proteins_100g', 'fat_100g', 'carbohydrates_100g'] as $need) {
    if (!in_array($need, $head, true)) {
        fwrite(STDERR, "Brak kolumny $need w eksporcie – zmienił się format?\n");
        exit(1);
    }
}
$width = count($head);

// znane produkty z OFF: kod -> last_modified_t (żeby pominąć niezmienione)
$known = [];
$res = $conn->query("SELECT code, off_modified FROM food_products WHERE source = 'off'");
while ($row = $res->fetch_row()) $known[$row[0]] = (int)$row[1];

$stats = ['read' => 0, 'pl' => 0, 'imported' => 0, 'unchanged' => 0, 'no_name' => 0, 'no_nutrition' => 0, 'implausible' => 0];
$batch = [];
$batchBytes = 0;
$flush = function () use (&$batch, &$batchBytes, $conn, $dry) {
    if ($batch && !$dry) hl_food_off_save($conn, $batch);
    $batch = [];
    $batchBytes = 0;
};

$t0 = microtime(true);
while (($line = fgets($fh)) !== false) {
    $stats['read']++;
    if ($stats['read'] % 250000 === 0) {
        $out(sprintf('… %s wierszy, %s PL, %s zapisanych (%ds)', number_format($stats['read'], 0, ',', ' '),
            number_format($stats['pl'], 0, ',', ' '), number_format($stats['imported'], 0, ',', ' '), microtime(true) - $t0));
    }
    // polskie = sprzedawane w Polsce (countries_tags) albo z polskim prefiksem GS1 590 – ponad połowa takich
    // produktów ma w OFF tylko kraj, w którym ktoś je zeskanował (np. Francję)
    $gs1pl = strncmp($line, '590', 3) === 0;
    if (!$gs1pl && strpos($line, 'en:poland') === false) continue;
    $r = explode("\t", rtrim($line, "\r\n"));
    if (count($r) !== $width) continue;
    $r = array_combine($head, $r);
    if (!$gs1pl && strpos($r['countries_tags'], 'en:poland') === false) continue;
    $stats['pl']++;

    $code = preg_replace('/\D/', '', $r['code']);
    if (isset($known[$code]) && $known[$code] === (int)$r['last_modified_t']) {
        $stats['unchanged']++;
        continue;
    }
    $mapped = hl_food_off_map($r);
    if (isset($mapped['reject'])) {
        if (isset($stats[$mapped['reject']])) $stats[$mapped['reject']]++;
        continue;
    }
    $batch[] = $mapped;
    $batchBytes += strlen($line);
    $stats['imported']++;
    // max_allowed_packet bywa 1 MB – paczki trzymamy wyraźnie poniżej
    if (count($batch) >= 200 || $batchBytes > 400000) $flush();
    if ($limit && $stats['imported'] >= $limit) break;
}
$flush();
fclose($fh);

$out(sprintf('Gotowe w %ds%s', microtime(true) - $t0, $dry ? ' (--dry, nic nie zapisano)' : ''));
foreach (['read' => 'wierszy w eksporcie', 'pl' => 'produktów z Polski', 'imported' => 'zapisanych', 'unchanged' => 'bez zmian',
          'no_name' => 'bez nazwy', 'no_nutrition' => 'bez wartości odżywczych', 'implausible' => 'odrzuconych (niespójne wartości)'] as $k => $label) {
    $out(str_pad(number_format($stats[$k], 0, ',', ' '), 12, ' ', STR_PAD_LEFT) . "  $label");
}
