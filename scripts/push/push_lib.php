<?php
// Powiadomienia Web Push bez zewnętrznych bibliotek.
// Wysyłamy "pusty" push podpisany VAPID (ES256), a service worker (sw.js) pobiera treść z push_queue przez pull.php –
// dzięki temu nie trzeba szyfrować payloadu (RFC 8291).

define('HL_VAPID_FILE', __DIR__.'/vapid_keys.php');
define('HL_VAPID_SUBJECT', 'https://rgbpc.pl'); // kontakt dla usług push (mailto: albo https:)

function hl_b64u($bin) {
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

// klucze VAPID – generowane przy pierwszym użyciu i zapisywane w vapid_keys.php (nie commitować)
function hl_vapid() {
    static $keys = null;
    if ($keys) return $keys;
    if (is_file(HL_VAPID_FILE)) return $keys = include HL_VAPID_FILE;

    $opts = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
    $key = @openssl_pkey_new($opts);
    if (!$key) {
        // XAMPP na Windows: openssl potrzebuje ścieżki do openssl.cnf
        foreach ([getenv('OPENSSL_CONF'), dirname(PHP_BINARY).'/extras/ssl/openssl.cnf', 'C:/xampp/php/extras/ssl/openssl.cnf'] as $cnf) {
            if ($cnf && is_file($cnf) && ($key = @openssl_pkey_new($opts + ['config' => $cnf]))) break;
        }
    }
    if (!$key) return null;
    openssl_pkey_export($key, $pem, null, isset($cnf) && $cnf ? ['config' => $cnf] : []);
    $d = openssl_pkey_get_details($key)['ec'];
    $public = "\x04".str_pad($d['x'], 32, "\0", STR_PAD_LEFT).str_pad($d['y'], 32, "\0", STR_PAD_LEFT);
    $new = ['public' => hl_b64u($public), 'pem' => $pem];
    // bez zapisu klucze byłyby inne przy każdym żądaniu (subskrypcje do kosza) – wtedy lepiej nie działać wcale
    if (!@file_put_contents(HL_VAPID_FILE, "<?php\n// Klucze VAPID – wygenerowane automatycznie. Zmiana kluczy unieważnia wszystkie subskrypcje.\nreturn ".var_export($new, true).";\n")) {
        error_log('HomeList push: brak uprawnień do zapisu '.HL_VAPID_FILE.' – uruchom raz: php scripts/push/cron.php --key');
        return null;
    }
    return $keys = $new;
}

// podpis ES256: openssl zwraca DER (SEQUENCE{INTEGER r, INTEGER s}), JWT wymaga surowego r||s po 32 bajty
function hl_der_to_raw($der) {
    $pos = 2;
    if (ord($der[1]) & 0x80) $pos += ord($der[1]) & 0x7f;
    $out = '';
    for ($i = 0; $i < 2; $i++) {
        $len = ord($der[$pos + 1]);
        $int = ltrim(substr($der, $pos + 2, $len), "\0");
        $out .= str_pad($int, 32, "\0", STR_PAD_LEFT);
        $pos += 2 + $len;
    }
    return $out;
}

function hl_vapid_header($endpoint) {
    $keys = hl_vapid();
    $u = parse_url($endpoint);
    $header = hl_b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $claims = hl_b64u(json_encode(['aud' => $u['scheme'].'://'.$u['host'], 'exp' => time() + 12 * 3600, 'sub' => HL_VAPID_SUBJECT], JSON_UNESCAPED_SLASHES));
    openssl_sign("$header.$claims", $sig, $keys['pem'], OPENSSL_ALGO_SHA256);
    return 'vapid t='."$header.$claims.".hl_b64u(hl_der_to_raw($sig)).', k='.$keys['public'];
}

// wysyła pusty push, zwraca kod HTTP (201 = OK, 404/410 = subskrypcja wygasła)
function hl_push_send($endpoint, $ttl = 4 * 3600) {
    $headers = [
        'Authorization: '.hl_vapid_header($endpoint),
        'TTL: '.$ttl,
        'Urgency: normal',
        'Content-Length: 0',
    ];
    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        curl_exec($ch);
        return (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }

    // serwer bez rozszerzenia curl – wbudowane strumienie HTTP (wymagają openssl i allow_url_fopen)
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headers),
        'content' => '',
        'timeout' => 10,
        'ignore_errors' => true, // kod 4xx też chcemy odczytać, a nie dostać false
    ]]);
    @file_get_contents($endpoint, false, $ctx);
    $response = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? []);
    if (!$response) {
        error_log('HomeList push: nie udało się połączyć z '.parse_url($endpoint, PHP_URL_HOST).' (sprawdź allow_url_fopen / openssl)');
        return 0;
    }
    return preg_match('~^HTTP/\S+\s+(\d{3})~', $response[0], $m) ? (int)$m[1] : 0;
}

// kolejkuje powiadomienie na każde urządzenie użytkownika i budzi je pushem; zwraca liczbę urządzeń, które przyjęły
function hl_push_notify($conn, $user_id, $title, $body, $tag, $url = 'panel.php') {
    if (!hl_vapid()) return 0;
    $user_id = (int)$user_id;
    $t = mysqli_real_escape_string($conn, hl_substr($title, 200));
    $b = mysqli_real_escape_string($conn, hl_substr($body, 600));
    $g = mysqli_real_escape_string($conn, hl_substr($tag, 60));
    $l = mysqli_real_escape_string($conn, $url);
    $ok = 0;
    $devices = 0;
    $result = mysqli_query($conn, "SELECT id, endpoint FROM push_subscriptions WHERE user_id='$user_id'");
    while ($sub = mysqli_fetch_assoc($result)) {
        $devices++;
        $sid = (int)$sub['id'];
        // to samo zdarzenie (tag) zastępuje nieodebrane wcześniej
        mysqli_query($conn, "DELETE FROM push_queue WHERE subscription_id='$sid' AND (tag='$g' OR created_at < NOW() - INTERVAL 1 DAY)");
        mysqli_query($conn, "INSERT INTO push_queue (subscription_id, title, body, tag, url) VALUES ('$sid', '$t', '$b', '$g', '$l')");
        $code = hl_push_send($sub['endpoint']);
        if ($code >= 200 && $code < 300) {
            $ok++;
            mysqli_query($conn, "UPDATE push_subscriptions SET last_ok=NOW() WHERE id='$sid'");
        } elseif (in_array($code, [403, 404, 410])) {
            mysqli_query($conn, "DELETE FROM push_queue WHERE subscription_id='$sid'");
            mysqli_query($conn, "DELETE FROM push_subscriptions WHERE id='$sid'");
        }
    }
    // historia dla administratora (bez treści); bez migracji migration_push_history.sql po prostu się nie zapisuje
    if ($devices) {
        try {
            mysqli_query($conn, "INSERT INTO push_history (user_id, tag, devices, delivered) VALUES ('$user_id', '$g', '".min($devices, 127)."', '".min($ok, 127)."')");
        } catch (mysqli_sql_exception $ex) {}
    }
    return $ok;
}

function hl_push_defaults() {
    return [
        'enabled' => 1, 'morning_start' => '07:00', 'afternoon_start' => '12:00', 'evening_start' => '17:00', 'day_end' => '21:30',
        'notify_parts' => 1, 'notify_missed' => 1, 'notify_deadlines' => 1, 'notify_timed' => 1, 'notify_routines' => 1, 'notify_lists' => 1, 'notify_reports' => 1,
    ];
}

function hl_push_settings($conn, $user_id) {
    $user_id = (int)$user_id;
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM push_settings WHERE user_id='$user_id'"));
    $s = hl_push_defaults();
    if ($row) foreach ($s as $k => $v) $s[$k] = is_int($v) ? (int)$row[$k] : substr($row[$k], 0, 5);
    return $s;
}

function hl_push_muted($conn, $user_id) {
    $user_id = (int)$user_id;
    $set = [];
    $result = mysqli_query($conn, "SELECT element_id FROM push_mute WHERE user_id='$user_id'");
    while ($row = mysqli_fetch_assoc($result)) $set[(int)$row['element_id']] = true;
    return $set;
}

// true, jeśli zdarzenie zostało właśnie zarezerwowane (nie było wysłane dziś)
function hl_push_claim($conn, $user_id, $event_key, $date) {
    $user_id = (int)$user_id;
    $k = mysqli_real_escape_string($conn, $event_key);
    mysqli_query($conn, "INSERT IGNORE INTO push_log (user_id, event_key, ref_date) VALUES ('$user_id', '$k', '$date')");
    return mysqli_affected_rows($conn) === 1;
}

// czy użytkownik wyłączył powiadomienia o nowych zadaniach na tej liście wspólnej
function hl_push_list_muted($conn, $user_id, $list_id) {
    $user_id = (int)$user_id;
    $list_id = (int)$list_id;
    return (bool)mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM push_list_mute WHERE user_id='$user_id' AND list_id='$list_id'"));
}

// polska odmiana: 1 zadanie, 2–4 zadania, 5+ zadań (12–14 też „zadań”)
function hl_plural($n, $one, $few, $many) {
    $n = abs((int)$n);
    if ($n === 1) return $one;
    return ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14)) ? $few : $many;
}
