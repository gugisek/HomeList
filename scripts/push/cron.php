<?php
// Cron powiadomień – uruchamiać co 5 minut:
//   */5 * * * * php /sciezka/HomeList/scripts/push/cron.php
// albo przez HTTP: scripts/push/cron.php?key=KLUCZ   (klucz: php scripts/push/cron.php --key)
//
// Zasady anty-spamowe:
//  - każde zdarzenie (początek pory, koniec dnia, konkretna godzina, termin) najwyżej raz dziennie (push_log)
//  - zdarzenie wysyłane tylko w oknie 30 min od swojej godziny – po przestoju crona nie ma zaległej lawiny
//  - wszystko, co wypadło w jednym przebiegu, scalane w JEDNO powiadomienie
//  - powiadomienie tylko, gdy faktycznie coś zostało do zrobienia
include __DIR__.'/../conn_db.php';
include __DIR__.'/../planner/planner_lib.php';
include __DIR__.'/push_lib.php';

$cron_key = substr(hash('sha256', 'hl-cron'.(hl_vapid()['pem'] ?? '')), 0, 32);
if (PHP_SAPI === 'cli') {
    if (in_array('--key', $argv ?? [])) exit($cron_key."\n");
} elseif (!hash_equals($cron_key, (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}

const HL_WINDOW = 30; // minut

function hl_min($hhmm) {
    [$h, $m] = array_map('intval', explode(':', $hhmm));
    return $h * 60 + $m;
}

function hl_in_window($now, $at, $before = 0) {
    return $now >= $at - $before && $now < $at - $before + HL_WINDOW;
}

// "Pranie, Zakupy i 2 więcej"
function hl_titles(array $items, $max = 3) {
    $titles = array_map(fn($it) => $it['title'], array_slice($items, 0, $max));
    $rest = count($items) - count($titles);
    return implode(', ', $titles).($rest > 0 ? " i $rest więcej" : '');
}

function hl_things($n) {
    return $n === 1 ? '1 rzecz' : "$n rzeczy";
}

// --dry: tylko wypisuje, nic nie zapisuje w push_log (cofnięte transakcją) i nie wysyła
$dry = PHP_SAPI === 'cli' && in_array('--dry', $argv ?? []);
if ($dry) mysqli_begin_transaction($conn);
$today = date('Y-m-d');
$now = hl_min(date('H:i'));
$part_from = [1 => 'z rana', 2 => 'z popołudnia', 3 => 'z wieczora'];
$part_name = [1 => 'Rano', 2 => 'Popołudnie', 3 => 'Wieczór'];
$sent_total = 0;

$users = mysqli_query($conn, "SELECT DISTINCT push_subscriptions.user_id FROM push_subscriptions
                              LEFT JOIN push_settings ON push_settings.user_id=push_subscriptions.user_id
                              WHERE push_settings.enabled IS NULL OR push_settings.enabled=1");
while ($u = mysqli_fetch_assoc($users)) {
    $uid = (int)$u['user_id'];
    $s = hl_push_settings($conn, $uid);
    $start = [1 => hl_min($s['morning_start']), 2 => hl_min($s['afternoon_start']), 3 => hl_min($s['evening_start'])];
    $end = hl_min($s['day_end']);

    // czy w ogóle coś wypada teraz – unikamy liczenia feedu co 5 minut bez potrzeby
    $feed = null;
    $muted = hl_push_muted($conn, $uid);
    $load = function () use (&$feed, $conn, $uid, $today, $s, $muted) {
        if ($feed !== null) return $feed;
        $feed = hl_feed($conn, $uid, $today, $today);
        $keep = fn($it) => !$it['done'] && ($it['type'] !== 'routine' || ($s['notify_routines'] && !isset($muted[$it['id']])));
        foreach ($feed['parts'] as $p => $items) $feed['parts'][$p] = array_values(array_filter($items, $keep));
        $feed['deadline'] = array_values(array_filter($feed['deadline'], $keep));
        return $feed;
    };

    $msgs = []; // [title, body, tag]

    // 1. początek pory dnia: co jest teraz + co zostało z poprzedniej pory
    foreach ($start as $p => $at) {
        if (!hl_in_window($now, $at) || !($s['notify_parts'] || $s['notify_missed'] || ($p === 1 && $s['notify_deadlines']))) continue;
        if (!hl_push_claim($conn, $uid, "part$p", $today)) continue;
        $f = $load();
        $lines = [];
        $title = null;
        if ($p === 1 && $s['notify_deadlines'] && $f['deadline']) {
            $lines[] = 'Termin dziś: '.hl_titles($f['deadline']);
        }
        if ($s['notify_parts'] && $f['parts'][$p]) {
            $title = $part_name[$p].' · '.hl_things(count($f['parts'][$p])).' do zrobienia';
            $lines[] = hl_titles($f['parts'][$p]);
        }
        if ($p === 1 && $s['notify_missed'] && ($late = count($f['overdue']) + count($f['late']))) {
            $lines[] = 'Zaległe z poprzednich dni: '.$late;
        }
        if ($p > 1 && $s['notify_missed'] && $f['parts'][$p - 1]) {
            $lines[] = 'Zostało '.$part_from[$p - 1].': '.hl_titles($f['parts'][$p - 1]);
            $title = $title ?? 'Nie zapomniałeś o czymś '.$part_from[$p - 1].'?';
        }
        if ($p === 1 && !$title && $lines) $title = 'Plan na dziś';
        if ($lines) $msgs[] = [$title, implode("\n", $lines), "part$p"];
    }

    // 2. koniec dnia: ostatnie przypomnienie o tym, co zostało na dziś
    if ($s['notify_missed'] && hl_in_window($now, $end) && hl_push_claim($conn, $uid, 'end', $today)) {
        $f = $load();
        $left = array_merge($f['deadline'], $f['parts'][3], $f['parts'][0]);
        if ($left) $msgs[] = ['Zostało na dziś: '.hl_things(count($left)), hl_titles($left), 'end'];
    }

    // 3. pozycje z konkretną godziną (zadania i rutyny) – 5 min przed
    if ($s['notify_timed']) {
        $f = $load();
        foreach (array_merge(...array_values($f['parts'])) as $it) {
            if (!$it['time'] || !hl_in_window($now, hl_min($it['time']), 5)) continue;
            if (!hl_push_claim($conn, $uid, 'at_'.$it['key'], $today)) continue;
            $msgs[] = [$it['time'].' · '.$it['title'], $it['type'] === 'routine' ? 'Rutyna · '.$it['list_name'] : 'Lista '.$it['list_name'], 'at_'.$it['key']];
        }
    }

    // 4. terminy z godziną – godzinę przed (terminy bez godziny są w porannym podsumowaniu)
    if ($s['notify_deadlines']) {
        $f = $load();
        foreach (array_merge($f['deadline'], ...array_values($f['parts'])) as $it) {
            if ($it['type'] !== 'task' || !$it['deadline'] || substr($it['deadline'], 0, 10) !== $today) continue;
            $dl = substr($it['deadline'], 11, 5);
            if ($dl === '' || $dl === '00:00' || !hl_in_window($now, hl_min($dl), 60)) continue;
            if (!hl_push_claim($conn, $uid, 'dl_'.$it['id'], $today)) continue;
            $msgs[] = ['Termin o '.$dl.' · '.$it['title'], 'Lista '.$it['list_name'], 'dl_'.$it['id']];
        }
    }

    if (!$msgs) continue;
    if (count($msgs) === 1) {
        [$title, $body, $tag] = $msgs[0];
    } else {
        $title = 'HomeList · '.count($msgs).' przypomnienia';
        $body = implode("\n", array_map(fn($m) => $m[0], $msgs));
        $tag = 'digest';
    }
    if ($dry) {
        echo "[user $uid] $title\n$body\n\n";
        continue;
    }
    $sent_total += hl_push_notify($conn, $uid, $title, $body, $tag, 'panel.php');
}

if ($dry) {
    mysqli_rollback($conn);
    exit();
}
echo date('Y-m-d H:i')." wysłano: $sent_total\n";
mysqli_query($conn, "DELETE FROM push_log WHERE ref_date < CURDATE() - INTERVAL 7 DAY");
