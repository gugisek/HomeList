<?php
// Wspólne funkcje planera dnia i list rutyn.
// Includowane zarówno przez komponenty widoków (components/panel/dashboard/*) jak i skrypty JSON (scripts/planner/*).

if (defined('HL_PLANNER_LIB')) return;
define('HL_PLANNER_LIB', true);

// Pory dnia: kolejność wyświetlania w feedzie (0 = kiedykolwiek na końcu)
function hl_parts() {
    return [
        1 => ['label' => 'Rano',         'icon' => 'sunrise', 'color' => 'text-amber-400',  'bg' => 'bg-amber-400'],
        2 => ['label' => 'Popołudnie',   'icon' => 'sun',     'color' => 'text-orange-400', 'bg' => 'bg-orange-400'],
        3 => ['label' => 'Wieczór',      'icon' => 'moon',    'color' => 'text-indigo-400', 'bg' => 'bg-indigo-400'],
        0 => ['label' => 'Kiedykolwiek', 'icon' => 'inbox',   'color' => 'text-gray-400',   'bg' => 'bg-gray-400'],
    ];
}

function hl_icon($name, $class = 'size-5', $stroke = '1.5') {
    $d = hl_icon_paths()[$name] ?? '';
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="'.$stroke.'" stroke="currentColor" class="'.$class.'"><path stroke-linecap="round" stroke-linejoin="round" d="'.$d.'" /></svg>';
}

// sprite z ikonami do wielokrotnego użycia przez hl_icon_use() (np. setki przycisków w backlogu)
function hl_icon_sprite(array $names) {
    $out = '<svg xmlns="http://www.w3.org/2000/svg" class="hidden">';
    foreach ($names as $name) {
        $out .= '<symbol id="hl-i-'.$name.'" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="'.(hl_icon_paths()[$name] ?? '').'" /></symbol>';
    }
    return $out.'</svg>';
}

function hl_icon_use($name, $class = 'size-5', $stroke = '1.5') {
    return '<svg fill="none" stroke="currentColor" stroke-width="'.$stroke.'" class="'.$class.'"><use href="#hl-i-'.$name.'" /></svg>';
}

function hl_icon_paths() {
    static $paths = [
        'sunrise'  => 'M3 18h18M6.75 18a5.25 5.25 0 0 1 10.5 0M12 4.5v3m-6.01.51 2.12 2.12m9.9-2.12-2.12 2.12M3.75 14.25H5.5m13 0h1.75',
        'sun'      => 'M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z',
        'moon'     => 'M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z',
        'inbox'    => 'M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z',
        'repeat'   => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
        'fire'     => 'M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z',
        'users'    => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'user'     => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
        'calendar' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'clock'    => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'queue'    => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z',
        'warning'  => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        'flag'     => 'M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5',
        'plus'     => 'M12 4.5v15m7.5-7.5h-15',
        'check'    => 'm4.5 12.75 6 6 9-13.5',
        'x'        => 'M6 18 18 6M6 6l12 12',
        'left'     => 'M15.75 19.5 8.25 12l7.5-7.5',
        'right'    => 'm8.25 4.5 7.5 7.5-7.5 7.5',
        'link'     => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244',
        'list'     => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z',
    ];
    return $paths;
}

function hl_e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// małe litery w UTF-8 bez wymagania rozszerzenia mbstring (nie każdy serwer je ma)
function hl_lower($s) {
    if (function_exists('mb_strtolower')) return mb_strtolower($s, 'UTF-8');
    return strtolower(strtr($s, ['Ą' => 'ą', 'Ć' => 'ć', 'Ę' => 'ę', 'Ł' => 'ł', 'Ń' => 'ń', 'Ó' => 'ó', 'Ś' => 'ś', 'Ź' => 'ź', 'Ż' => 'ż']));
}

// obcięcie do $len znaków UTF-8 bez wymagania mbstring
function hl_substr($s, $len) {
    if (function_exists('mb_substr')) return mb_substr($s, 0, $len, 'UTF-8');
    return preg_match('/^.{0,'.(int)$len.'}/us', $s, $m) ? $m[0] : substr($s, 0, $len);
}

function hl_valid_date($s) {
    return is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && strtotime($s) !== false;
}

function hl_add_days($date, $n) {
    return date('Y-m-d', strtotime($date.' '.($n >= 0 ? '+' : '').$n.' day'));
}

function hl_in(array $ids) {
    $ids = array_map('intval', $ids);
    return $ids ? implode(',', $ids) : '0';
}

function hl_csv_ints($csv) {
    if ($csv === null || $csv === '') return [];
    return array_values(array_filter(array_map('intval', explode(',', $csv))));
}

// ---------- nazwy dni / dat po polsku ----------

function hl_day_name($date, $short = false) {
    $long  = [1 => 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota', 'Niedziela'];
    $shortN = [1 => 'Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'Sb', 'Nd'];
    $n = (int)date('N', strtotime($date));
    return $short ? $shortN[$n] : $long[$n];
}

function hl_month_name($m, $genitive = true) {
    $gen = [1 => 'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia'];
    $nom = [1 => 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
    return $genitive ? $gen[(int)$m] : $nom[(int)$m];
}

// "Dziś" / "Jutro" / "Wczoraj" / "Czwartek"
function hl_relative_day($date, $today) {
    if ($date === $today) return 'Dziś';
    if ($date === hl_add_days($today, 1)) return 'Jutro';
    if ($date === hl_add_days($today, -1)) return 'Wczoraj';
    return hl_day_name($date);
}

// "poniedziałek, 19 października"
function hl_long_date($date) {
    $ts = strtotime($date);
    $s = hl_lower(hl_day_name($date)).', '.(int)date('j', $ts).' '.hl_month_name(date('n', $ts));
    if (date('Y', $ts) !== date('Y')) $s .= ' '.date('Y', $ts);
    return $s;
}

// krótka data "24.10" / "24.10.2025" (rok tylko gdy inny niż bieżący), z $time: "14:30 24.10"
function hl_short_date($date, $time = false) {
    $ts = strtotime($date);
    $s = date('d.m', $ts);
    if (date('Y', $ts) !== date('Y')) $s .= '.'.date('Y', $ts);
    return $time ? date('H:i', $ts).' '.$s : $s;
}

// krótka etykieta planu: "dziś · Rano", "pt 24.10 · Wieczór"
function hl_plan_label($date, $part, $today) {
    $parts = hl_parts();
    $rel = hl_relative_day($date, $today);
    if (!in_array($rel, ['Dziś', 'Jutro', 'Wczoraj'])) {
        $rel = hl_lower(hl_day_name($date, true)).' '.hl_short_date($date);
    }
    return $rel.' · '.$parts[(int)$part]['label'];
}

// ---------- dostęp ----------

// id list, do których użytkownik ma dostęp (właściciel lub zaproszony) – ta sama logika co w navbarze
function hl_user_list_ids($conn, $user_id) {
    $user_id = (int)$user_id;
    $sql = "SELECT DISTINCT lists.id FROM lists LEFT JOIN list_invities ON list_invities.list_id=lists.id
            WHERE lists.owner_id='$user_id' OR list_invities.user_id='$user_id'";
    $ids = [];
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) $ids[] = (int)$row['id'];
    return $ids;
}

// zwraca wiersz elementu (+ list_type) jeśli użytkownik ma do niego dostęp, inaczej null
function hl_get_element($conn, $user_id, $element_id) {
    $element_id = (int)$element_id;
    $ids = hl_in(hl_user_list_ids($conn, $user_id));
    $sql = "SELECT list_elements.*, lists.list_type, lists.full_name AS list_name FROM list_elements
            JOIN lists ON lists.id=list_elements.list_id
            WHERE list_elements.id='$element_id' AND list_elements.list_id IN ($ids)";
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
}

// ---------- rutyny ----------

function hl_routine_start($r) {
    if (!empty($r['recurrence_start']) && $r['recurrence_start'] !== '0000-00-00') return $r['recurrence_start'];
    return substr($r['create_date'], 0, 10);
}

function hl_routine_occurs(array $r, $date) {
    $start = hl_routine_start($r);
    if ($date < $start) return false;
    $ts = strtotime($date);
    switch ($r['recurrence_type']) {
        case 'weekly':
            return in_array((int)date('N', $ts), hl_csv_ints($r['recurrence_days']), true);
        case 'interval':
            $n = max(1, (int)$r['recurrence_days']);
            $diff = (int)round(($ts - strtotime($start)) / 86400);
            return $diff % $n === 0;
        case 'monthly':
            $days = hl_csv_ints($r['recurrence_days']);
            $d = (int)date('j', $ts);
            if (in_array($d, $days, true)) return true;
            // np. "31." w miesiącu który ma 30 dni -> ostatni dzień miesiąca
            $last = (int)date('t', $ts);
            if ($d === $last) foreach ($days as $x) if ($x > $last) return true;
            return false;
        default: // daily
            return true;
    }
}

function hl_routine_summary(array $r) {
    switch ($r['recurrence_type']) {
        case 'weekly':
            $days = hl_csv_ints($r['recurrence_days']);
            sort($days);
            if ($days === [1, 2, 3, 4, 5, 6, 7]) return 'Codziennie';
            if ($days === [1, 2, 3, 4, 5]) return 'Dni robocze';
            if ($days === [6, 7]) return 'Weekendy';
            $names = [1 => 'Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'Sb', 'Nd'];
            return implode(', ', array_map(fn($d) => $names[$d] ?? '?', $days));
        case 'interval':
            $n = max(1, (int)$r['recurrence_days']);
            return $n === 1 ? 'Codziennie' : 'Co '.$n.' dni';
        case 'monthly':
            $days = hl_csv_ints($r['recurrence_days']);
            sort($days);
            return 'Co miesiąc: '.implode(', ', array_map(fn($d) => $d.'.', $days));
        default:
            return 'Codziennie';
    }
}

// mapa element_id => [data => true] dni odhaczonych (z uwzględnieniem trybu wspólnego/osobnego)
function hl_routine_done_map($conn, array $routines, $user_id, $from, $to) {
    $map = [];
    if (!$routines) return $map;
    $shared = [];
    foreach ($routines as $r) {
        $map[(int)$r['id']] = [];
        $shared[(int)$r['id']] = (int)$r['shared_done'] === 1;
    }
    $ids = hl_in(array_keys($map));
    $from = mysqli_real_escape_string($conn, $from);
    $to = mysqli_real_escape_string($conn, $to);
    $sql = "SELECT element_id, user_id, done_date FROM routine_done
            WHERE element_id IN ($ids) AND done_date BETWEEN '$from' AND '$to'";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $eid = (int)$row['element_id'];
        if ($shared[$eid] || (int)$row['user_id'] === (int)$user_id) $map[$eid][$row['done_date']] = true;
    }
    return $map;
}

// seria: ile kolejnych zaplanowanych wystąpień zostało zrobionych (dzisiejsze niezrobione nie przerywa serii)
function hl_streak(array $r, array $doneSet, $today) {
    $streak = 0;
    $start = hl_routine_start($r);
    $d = $today;
    for ($i = 0; $i < 400 && $d >= $start; $i++) {
        if (hl_routine_occurs($r, $d)) {
            if (isset($doneSet[$d])) $streak++;
            elseif ($d !== $today) break;
        }
        $d = hl_add_days($d, -1);
    }
    return $streak;
}

function hl_user_routines($conn, array $list_ids) {
    $ids = hl_in($list_ids);
    $sql = "SELECT list_elements.*, lists.full_name AS list_name FROM list_elements
            JOIN lists ON lists.id=list_elements.list_id
            WHERE lists.list_type='routine' AND list_elements.list_id IN ($ids)";
    $rows = [];
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

// Pola rutyny z $_POST (formularz routine_fields.php) -> wartości SQL gotowe do wstawienia.
// Zwraca string z komunikatem błędu albo tablicę kolumna => wartość SQL ('_start' = data startu albo null = bez zmian/dziś).
function hl_routine_fields_from_post() {
    $type = $_POST['recurrence_type'] ?? 'daily';
    if (!in_array($type, ['daily', 'weekly', 'interval', 'monthly'], true)) $type = 'daily';
    $days = '';
    $start = null;
    if ($type === 'weekly' || $type === 'monthly') {
        $max = $type === 'weekly' ? 7 : 31;
        $list = array_unique(array_filter(hl_csv_ints($_POST['recurrence_days'] ?? ''), fn($d) => $d >= 1 && $d <= $max));
        sort($list);
        if (!$list) return $type === 'weekly' ? 'Wybierz przynajmniej jeden dzień tygodnia' : 'Wybierz przynajmniej jeden dzień miesiąca';
        $days = implode(',', $list);
    } elseif ($type === 'interval') {
        $days = (string)max(1, min(365, (int)($_POST['recurrence_days'] ?? 1)));
        $start = hl_valid_date($_POST['recurrence_start'] ?? '') ? $_POST['recurrence_start'] : date('Y-m-d');
    }
    $part = (int)($_POST['day_part'] ?? 0);
    if (!array_key_exists($part, hl_parts())) $part = 0;
    $time = $_POST['plan_time'] ?? '';
    return [
        'recurrence_type' => "'$type'",
        'recurrence_days' => $days === '' ? 'NULL' : "'$days'",
        'day_part' => "'$part'",
        'plan_time' => preg_match('/^\d{2}:\d{2}$/', $time) ? "'$time:00'" : 'NULL',
        'shared_done' => ($_POST['shared_done'] ?? '0') === '1' ? "'1'" : "'0'",
        '_start' => $start,
    ];
}

// ---------- feed dnia ----------

function hl_item_from_task(array $row, $date) {
    $done = (int)$row['status_id'] === 2;
    return [
        'key' => 't'.$row['id'],
        'type' => 'task',
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'list_name' => $row['list_name'],
        'list_id' => (int)$row['list_id'],
        'done' => $done,
        'status_id' => (int)$row['status_id'],
        'part' => isset($row['day_part']) ? (int)$row['day_part'] : 0,
        'time' => !empty($row['plan_time']) ? substr($row['plan_time'], 0, 5) : null,
        'position' => isset($row['position']) ? (int)$row['position'] : null,
        'plan_date' => $row['plan_date'] ?? null,
        'deadline' => (!empty($row['deadline_date']) && $row['deadline_date'] > '1000-01-01') ? $row['deadline_date'] : null,
        'link' => !empty($row['link_url']),
        'streak' => null,
        'shared' => false,
        'checkable' => true,
    ];
}

function hl_sort_items(array &$items) {
    usort($items, function ($a, $b) {
        $pa = $a['position'] === null ? PHP_INT_MAX : $a['position'];
        $pb = $b['position'] === null ? PHP_INT_MAX : $b['position'];
        if ($pa !== $pb) return $pa <=> $pb;
        $ta = $a['time'] ?? '99:99';
        $tb = $b['time'] ?? '99:99';
        if ($ta !== $tb) return strcmp($ta, $tb);
        return strcmp($a['key'], $b['key']);
    });
}

function hl_feed($conn, $user_id, $date, $today) {
    $user_id = (int)$user_id;
    $list_ids = hl_user_list_ids($conn, $user_id);
    $ids = hl_in($list_ids);
    $d = mysqli_real_escape_string($conn, $date);

    $feed = ['overdue' => [], 'deadline' => [], 'late' => [], 'parts' => [1 => [], 2 => [], 3 => [], 0 => []]];
    $task_cols = "list_elements.id, list_elements.title, list_elements.status_id, list_elements.deadline_date, list_elements.done_date,
                  list_elements.link_url, list_elements.list_id, lists.full_name AS list_name";

    // 1. zaplanowane zadania
    $sql = "SELECT $task_cols, day_plan.day_part, day_plan.plan_time, day_plan.position, day_plan.plan_date
            FROM day_plan JOIN list_elements ON list_elements.id=day_plan.element_id JOIN lists ON lists.id=list_elements.list_id
            WHERE day_plan.user_id='$user_id' AND day_plan.plan_date='$d' AND lists.list_type='normal' AND list_elements.list_id IN ($ids)";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $it = hl_item_from_task($row, $date);
        $feed['parts'][isset($feed['parts'][$it['part']]) ? $it['part'] : 0][] = $it;
    }

    // 2. zaległe (tylko w widoku dzisiejszym): zaplanowane wcześniej i niezrobione
    if ($date === $today) {
        $sql = "SELECT $task_cols, day_plan.day_part, day_plan.plan_time, day_plan.position, day_plan.plan_date
                FROM day_plan JOIN list_elements ON list_elements.id=day_plan.element_id JOIN lists ON lists.id=list_elements.list_id
                WHERE day_plan.user_id='$user_id' AND day_plan.plan_date<'$d' AND list_elements.status_id NOT IN (2, 4)
                  AND lists.list_type='normal' AND list_elements.list_id IN ($ids)
                ORDER BY day_plan.plan_date ASC, day_plan.position ASC";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) $feed['overdue'][] = hl_item_from_task($row, $date);
    }

    // 3. zadania z deadlinem na ten dzień (dziś: także po terminie), których użytkownik nie zaplanował
    $deadline_cond = $date === $today ? "DATE(list_elements.deadline_date)<='$d'" : "DATE(list_elements.deadline_date)='$d'";
    $sql = "SELECT $task_cols FROM list_elements JOIN lists ON lists.id=list_elements.list_id
            WHERE list_elements.list_id IN ($ids) AND lists.list_type='normal'
              AND list_elements.deadline_date IS NOT NULL AND list_elements.deadline_date>'1000-01-01' AND $deadline_cond
              AND (list_elements.status_id NOT IN (2, 4) OR (list_elements.status_id=2 AND DATE(list_elements.done_date)='$d'))
              AND NOT EXISTS (SELECT 1 FROM day_plan WHERE day_plan.user_id='$user_id' AND day_plan.element_id=list_elements.id)
            ORDER BY list_elements.deadline_date ASC";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        // termin z wcześniejszych dni (już po terminie) – osobna, zwinięta grupa, nie liczy się do postępu dnia
        $bucket = substr($row['deadline_date'], 0, 10) < $date ? 'late' : 'deadline';
        $feed[$bucket][] = hl_item_from_task($row, $date);
    }

    // 4. rutyny przypadające na ten dzień
    $routines = array_values(array_filter(hl_user_routines($conn, $list_ids), fn($r) => hl_routine_occurs($r, $date)));
    if ($routines) {
        $rids = hl_in(array_column($routines, 'id'));
        $overrides = [];
        $result = mysqli_query($conn, "SELECT element_id, day_part, plan_time, position FROM day_plan
                                       WHERE user_id='$user_id' AND plan_date='$d' AND element_id IN ($rids)");
        while ($row = mysqli_fetch_assoc($result)) $overrides[(int)$row['element_id']] = $row;

        $range_end = max($date, $today);
        $done_map = hl_routine_done_map($conn, $routines, $user_id, hl_add_days($range_end, -400), $range_end);

        foreach ($routines as $r) {
            $rid = (int)$r['id'];
            $ov = $overrides[$rid] ?? null;
            $part = $ov ? (int)$ov['day_part'] : (int)$r['day_part'];
            $time = $ov && !empty($ov['plan_time']) ? $ov['plan_time'] : $r['plan_time'];
            $it = [
                'key' => 'r'.$rid,
                'type' => 'routine',
                'id' => $rid,
                'title' => $r['title'],
                'list_name' => $r['list_name'],
                'list_id' => (int)$r['list_id'],
                'done' => isset($done_map[$rid][$date]),
                'status_id' => 1,
                'part' => $part,
                'time' => !empty($time) ? substr($time, 0, 5) : null,
                'position' => $ov ? (int)$ov['position'] : null,
                'plan_date' => $date,
                'deadline' => null,
                'link' => !empty($r['link_url']),
                'streak' => hl_streak($r, $done_map[$rid], $today),
                'shared' => (int)$r['shared_done'] === 1,
                'checkable' => $date <= $today,
            ];
            $feed['parts'][isset($feed['parts'][$part]) ? $part : 0][] = $it;
        }
    }

    foreach ($feed['parts'] as &$items) hl_sort_items($items);
    unset($items);

    $total = 0; $done = 0;
    foreach (array_merge($feed['deadline'], ...array_values($feed['parts'])) as $it) {
        $total++;
        if ($it['done']) $done++;
    }
    $feed['total'] = $total;
    $feed['done'] = $done;
    return $feed;
}

// liczniki dla paska dni / miesiąca: data => ['total' => x, 'done' => y]
function hl_day_counts($conn, $user_id, $from, $to, $today) {
    $user_id = (int)$user_id;
    $list_ids = hl_user_list_ids($conn, $user_id);
    $ids = hl_in($list_ids);
    $f = mysqli_real_escape_string($conn, $from);
    $t = mysqli_real_escape_string($conn, $to);
    $counts = [];
    for ($d = $from; $d <= $to; $d = hl_add_days($d, 1)) $counts[$d] = ['total' => 0, 'done' => 0];

    $sql = "SELECT day_plan.plan_date, list_elements.status_id FROM day_plan
            JOIN list_elements ON list_elements.id=day_plan.element_id JOIN lists ON lists.id=list_elements.list_id
            WHERE day_plan.user_id='$user_id' AND day_plan.plan_date BETWEEN '$f' AND '$t'
              AND lists.list_type='normal' AND list_elements.list_id IN ($ids)";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        if (!isset($counts[$row['plan_date']])) continue;
        $counts[$row['plan_date']]['total']++;
        if ((int)$row['status_id'] === 2) $counts[$row['plan_date']]['done']++;
    }

    $routines = hl_user_routines($conn, $list_ids);
    if ($routines) {
        $done_map = hl_routine_done_map($conn, $routines, $user_id, $from, $to);
        foreach ($routines as $r) {
            foreach ($counts as $d => $_) {
                if (!hl_routine_occurs($r, $d)) continue;
                $counts[$d]['total']++;
                if (isset($done_map[(int)$r['id']][$d])) $counts[$d]['done']++;
            }
        }
    }
    return $counts;
}

// pozycja na końcu danej pory dnia
function hl_next_position($conn, $user_id, $date, $part) {
    $user_id = (int)$user_id;
    $part = (int)$part;
    $d = mysqli_real_escape_string($conn, $date);
    $result = mysqli_query($conn, "SELECT COALESCE(MAX(position), 0) + 1 AS p FROM day_plan WHERE user_id='$user_id' AND plan_date='$d' AND day_part='$part'");
    return (int)mysqli_fetch_assoc($result)['p'];
}

// klasy przełącznika (chip) – te same listy klas są w JS (hlChip w pages/dashboard.php)
function hl_chip_class($on) {
    return 'hl-chip '.($on
        ? 'bg-[#1c1c1e] text-white dark:bg-white dark:text-black shadow-lg'
        : 'bg-gray-100/80 dark:bg-[#3d3d3d] text-gray-700 dark:text-gray-300');
}

// domyślna pora dnia dla planowania "na dziś" na podstawie godziny
function hl_default_part($date, $today) {
    if ($date !== $today) return 0;
    $h = (int)date('G');
    return $h < 12 ? 1 : ($h < 17 ? 2 : 3);
}

function hl_json($status, $message, array $extra = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
}
