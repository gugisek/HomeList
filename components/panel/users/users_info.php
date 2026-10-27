<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['users', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Karty w popupie użytkownika (users_edit.php): aktywność, bezpieczeństwo, powiadomienia – ładowane leniwie
include "../../../scripts/conn_db.php";
require_once __DIR__.'/../../../scripts/push/push_lib.php';
$id = (int)($_GET['id'] ?? 0);
$tab = $_GET['tab'] ?? '';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$dt = fn($s) => $s ? date('d.m.Y H:i', strtotime($s)) : '—';
$can_logs = hl_can('logs', 'read');

// SELECT z parametrami → wszystkie wiersze
$rows = function ($sql, $types = '', ...$params) use ($conn) {
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $out = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $out;
};
$one = fn($sql, $types = '', ...$params) => $rows($sql, $types, ...$params)[0] ?? null;
$num = fn($sql, $types = '', ...$params) => (int)(array_values($one($sql, $types, ...$params) ?? [0])[0] ?? 0);

$device = function ($ua) {
    $ua = (string)$ua;
    if ($ua === '') return 'Nieznane urządzenie';
    $os = preg_match('/iPhone|iPad/i', $ua) ? 'iPhone / iPad' : (preg_match('/Android/i', $ua) ? 'Android' : (preg_match('/Windows/i', $ua) ? 'Windows' : (preg_match('/Mac OS/i', $ua) ? 'Mac' : (preg_match('/Linux/i', $ua) ? 'Linux' : 'Urządzenie'))));
    $br = preg_match('/Edg\//', $ua) ? 'Edge' : (preg_match('/Chrome\//', $ua) ? 'Chrome' : (preg_match('/Firefox\//', $ua) ? 'Firefox' : (preg_match('/Safari\//', $ua) ? 'Safari' : 'przeglądarka')));
    return $os . ' · ' . $br;
};

$u = $one('SELECT id, login, mail, create_date, update_date, last_log FROM users WHERE id = ?', 'i', $id);
if (!$u) {
    echo '<p class="text-sm text-gray-500">Nie znaleziono użytkownika.</p>';
    exit;
}

// klocki widoku
$h = fn($t) => '<p class="hl-eyebrow mt-6 mb-2 px-1">' . $e($t) . '</p>';
$box = 'rounded-2xl bg-gray-50 dark:bg-white/5';
$kv = fn($label, $value) => '<div class="flex items-center justify-between gap-3 px-4 py-2.5"><span class="text-sm text-gray-500 dark:text-gray-400">' . $e($label) . '</span><span class="text-sm text-right text-gray-900 dark:text-gray-100">' . $value . '</span></div>';
$stat = fn($label, $value) => '<div class="' . $box . ' px-3 py-3"><p class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100 font-[poppins]">' . $e($value) . '</p><p class="text-xs text-gray-500 dark:text-gray-400">' . $e($label) . '</p></div>';
$badge_ok = 'text-green-600 !bg-green-100 dark:text-green-400 dark:!bg-green-400/15';
$badge_bad = 'text-rose-600 !bg-rose-100 dark:text-rose-400 dark:!bg-rose-400/15';
$badge_warn = 'text-amber-600 !bg-amber-100 dark:text-amber-400 dark:!bg-amber-400/15';
$badge = fn($text, $cls = '') => '<span class="hl-badge ' . $cls . '">' . $e($text) . '</span>';
$empty = fn($t) => '<p class="px-4 py-4 text-sm text-gray-400">' . $e($t) . '</p>';
?>

<?php if ($tab === 'activity'):
    $lists_own = $rows("SELECT lists.id, lists.name, lists.list_type,
            (SELECT COUNT(*) FROM list_invities li WHERE li.list_id = lists.id AND li.user_id <> lists.owner_id) AS members,
            (SELECT COUNT(*) FROM list_elements le WHERE le.list_id = lists.id AND le.status_id NOT IN (2, 4)) AS active
        FROM lists WHERE owner_id = ? ORDER BY lists.name", 'i', $id);
    $lists_joined = $rows("SELECT lists.id, lists.name, lists.list_type, TRIM(CONCAT(users.name, ' ', users.sur_name)) AS owner
        FROM list_invities li JOIN lists ON lists.id = li.list_id LEFT JOIN users ON users.id = lists.owner_id
        WHERE li.user_id = ? AND lists.owner_id <> ? GROUP BY lists.id ORDER BY lists.name", 'ii', $id, $id);
    $tasks = $one("SELECT COUNT(*) AS total, SUM(status_id = 2) AS done, SUM(status_id NOT IN (2, 4)) AS active FROM list_elements WHERE creator_id = ?", 'i', $id);
    $routines_30 = $num('SELECT COUNT(*) FROM routine_done WHERE user_id = ? AND done_date >= CURDATE() - INTERVAL 30 DAY', 'i', $id);
    $planned = $num('SELECT COUNT(*) FROM day_plan WHERE user_id = ? AND plan_date >= CURDATE()', 'i', $id);
    $reports = $num('SELECT COUNT(*) FROM bug_reports WHERE user_id = ?', 'i', $id);
    $log_types = [1 => ['Zmiana', ''], 2 => ['Dodanie', $badge_ok], 3 => ['Usunięcie', $badge_bad]];
?>
    <div class="grid grid-cols-3 gap-2">
        <?=$stat('Zadania dodane', (int)$tasks['total'])?>
        <?=$stat('Zrobione', (int)$tasks['done'])?>
        <?=$stat('Aktywne', (int)$tasks['active'])?>
        <?=$stat('Rutyny (30 dni)', $routines_30)?>
        <?=$stat('W planie dnia', $planned)?>
        <?=$stat('Zgłoszenia błędów', $reports)?>
    </div>

    <?=$h('Konto')?>
    <div class="<?=$box?> hl-divide">
        <?=$kv('Login', $e($u['login']))?>
        <?=$kv('Utworzone', $e($dt($u['create_date'])))?>
        <?=$kv('Ostatnia zmiana danych', $e($dt($u['update_date'])))?>
        <?=$kv('Ostatnie logowanie', $e($u['last_log'] ? $dt($u['last_log']) : 'nigdy'))?>
    </div>

    <?=$h('Własne listy · ' . count($lists_own))?>
    <div class="<?=$box?> hl-divide">
        <?php if (!$lists_own) echo $empty('Brak własnych list.'); ?>
        <?php foreach ($lists_own as $l): ?>
        <div class="flex items-center gap-3 px-4 py-2.5">
            <p class="min-w-0 flex-1 text-sm text-gray-900 dark:text-gray-100 truncate"><?=$e($l['name'])?></p>
            <?php if ($l['list_type'] === 'routine') echo $badge('rutyny'); ?>
            <?php if ((int)$l['members']) echo $badge('wspólna · ' . (int)$l['members'] . ' ' . hl_plural($l['members'], 'osoba', 'osoby', 'osób'), 'text-sky-600 !bg-sky-100 dark:text-sky-400 dark:!bg-sky-400/15'); ?>
            <span class="flex-none text-xs text-gray-400"><?=(int)$l['active']?> do zrobienia</span>
        </div>
        <?php endforeach; ?>
    </div>

    <?=$h('Dołączył do list · ' . count($lists_joined))?>
    <div class="<?=$box?> hl-divide">
        <?php if (!$lists_joined) echo $empty('Nie należy do cudzych list.'); ?>
        <?php foreach ($lists_joined as $l): ?>
        <div class="flex items-center gap-3 px-4 py-2.5">
            <p class="min-w-0 flex-1 text-sm text-gray-900 dark:text-gray-100 truncate"><?=$e($l['name'])?></p>
            <?php if ($l['list_type'] === 'routine') echo $badge('rutyny'); ?>
            <span class="flex-none text-xs text-gray-400 truncate max-w-[45%]">właściciel: <?=$e($l['owner'] ?: '—')?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($can_logs):
        $logs = $rows('SELECT `when`, object_type, `type`, description FROM logs WHERE user_id = ? ORDER BY id DESC LIMIT 20', 'i', $id);
        $logs_total = $num('SELECT COUNT(*) FROM logs WHERE user_id = ?', 'i', $id);
    ?>
    <?=$h('Ostatnie zmiany · ' . $logs_total . ' w archiwum')?>
    <div class="<?=$box?> hl-divide">
        <?php if (!$logs) echo $empty('Brak wpisów w archiwum.'); ?>
        <?php foreach ($logs as $l): [$t_label, $t_cls] = $log_types[(int)$l['type']] ?? ['Inne', '']; ?>
        <div class="flex items-center gap-3 px-4 py-2.5">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-900 dark:text-gray-100 truncate"><?=$e($l['description'] ?: '—')?></p>
                <p class="text-xs text-gray-400"><?=$e($l['object_type'])?> · <?=$e($dt($l['when']))?></p>
            </div>
            <?=$badge($t_label, $t_cls)?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php elseif ($tab === 'security'):
    $tfa = $one('SELECT is_enabled, enabled_at, last_used_at FROM user_two_factor_settings WHERE user_id = ?', 'i', $id);
    $codes = $one('SELECT COUNT(*) AS total, SUM(used_at IS NULL) AS left_ FROM user_two_factor_backup_codes WHERE user_id = ?', 'i', $id);
    $tfa_on = $tfa && (int)$tfa['is_enabled'] === 1;
    $devices = $rows('SELECT created_at, last_used_at, expires, user_agent FROM remember_tokens WHERE user_id = ? AND expires > NOW() ORDER BY COALESCE(last_used_at, created_at) DESC', 'i', $id);
    $reset = $one('SELECT MAX(created_at) AS last_req, MAX(used_at) AS last_used, SUM(created_at > NOW() - INTERVAL 30 DAY) AS req_30 FROM password_reset_tokens WHERE user_id = ?', 'i', $id);
    // ta sama reguła blokady co w scripts/login/login_script.php (5 nieudanych w 15 min, licząc od ostatniego resetu hasła)
    $lock_fails = $num("SELECT COUNT(*) FROM login_attempts
        WHERE LOWER(login_input) = LOWER(?) AND success = 0 AND method = 'password' AND created_at > NOW() - INTERVAL 15 MINUTE
          AND created_at > COALESCE((SELECT MAX(used_at) FROM password_reset_tokens WHERE user_id = ? AND used_at IS NOT NULL), '1970-01-01')", 'si', $u['login'], $id);
    $att_where = '(user_id = ? OR LOWER(login_input) = LOWER(?))';
    $last_ok = $one("SELECT created_at, ip_address, user_agent, method FROM login_attempts WHERE $att_where AND success = 1 AND method <> 'activation' ORDER BY id DESC LIMIT 1", 'is', $id, $u['login']);
    $fails = $one("SELECT SUM(created_at > NOW() - INTERVAL 1 DAY) AS d1, COUNT(*) AS d30 FROM login_attempts WHERE $att_where AND success = 0 AND created_at > NOW() - INTERVAL 30 DAY", 'is', $id, $u['login']);
    $ips = $num("SELECT COUNT(DISTINCT ip_address) FROM login_attempts WHERE $att_where AND success = 1 AND created_at > NOW() - INTERVAL 30 DAY", 'is', $id, $u['login']);

    $methods = ['password' => 'Hasło', '2fa' => '2FA', 'backup_code' => 'Kod zapasowy', 'remember_me' => 'Zapamiętane', 'activation' => 'Aktywacja'];
    $reasons = [
        'invalid_credentials' => 'Złe hasło lub login', 'account_locked' => 'Blokada konta (limit prób)', 'ip_locked' => 'Blokada IP (limit prób)',
        'account_inactive' => 'Konto nieaktywowane', 'account_blocked' => 'Konto zablokowane', '2fa_invalid' => 'Zły kod 2FA', '2fa_locked' => 'Blokada 2FA',
        'remember_token_reuse' => 'Podejrzenie kradzieży ciasteczka', 'invalid_code' => 'Zły kod aktywacyjny', 'registered' => 'Rejestracja konta',
    ];
?>
    <div class="grid grid-cols-3 gap-2">
        <?=$stat('Nieudane (24 h)', (int)$fails['d1'])?>
        <?=$stat('Nieudane (30 dni)', (int)$fails['d30'])?>
        <?=$stat('Adresy IP (30 dni)', $ips)?>
    </div>

    <?=$h('Logowanie')?>
    <div class="<?=$box?> hl-divide">
        <?=$kv('Blokada logowania', $lock_fails >= 5 ? $badge('aktywna · ' . $lock_fails . ' prób w 15 min', $badge_bad) : ($lock_fails ? $badge($lock_fails . ' z 5 prób w 15 min', $badge_warn) : $badge('brak', $badge_ok)))?>
        <?=$kv('Ostatnie udane', $last_ok ? $e($dt($last_ok['created_at'])) . '<span class="block text-xs text-gray-400">' . $e($device($last_ok['user_agent'])) . ' · ' . $e($last_ok['ip_address'] ?? '—') . ' · ' . $e($methods[$last_ok['method']] ?? $last_ok['method']) . '</span>' : 'brak')?>
    </div>

    <?=$h('Weryfikacja dwuetapowa')?>
    <div class="<?=$box?> hl-divide">
        <?=$kv('Status', $tfa_on ? $badge('włączona', $badge_ok) : $badge('wyłączona', $badge_warn))?>
        <?php if ($tfa_on): ?>
        <?=$kv('Włączona', $e($dt($tfa['enabled_at'])))?>
        <?=$kv('Ostatnie użycie kodu', $e($dt($tfa['last_used_at'])))?>
        <?=$kv('Kody zapasowe', ((int)$codes['left_'] <= 2 ? $badge((int)$codes['left_'] . ' z ' . (int)$codes['total'], $badge_warn) : $e((int)$codes['left_'] . ' z ' . (int)$codes['total'])) )?>
        <?php endif; ?>
    </div>

    <?=$h('Hasło')?>
    <div class="<?=$box?> hl-divide">
        <?=$kv('Ostatnia prośba o reset', $e($dt($reset['last_req'] ?? null)))?>
        <?=$kv('Ostatni reset przez maila', $e($dt($reset['last_used'] ?? null)))?>
        <?=$kv('Prośby o reset (30 dni)', (int)($reset['req_30'] ?? 0))?>
    </div>

    <?=$h('Zapamiętane urządzenia · ' . count($devices))?>
    <div class="<?=$box?> hl-divide">
        <?php if (!$devices) echo $empty('Brak zapamiętanych logowań.'); ?>
        <?php foreach ($devices as $d): ?>
        <div class="px-4 py-2.5">
            <p class="text-sm text-gray-900 dark:text-gray-100"><?=$e($device($d['user_agent']))?></p>
            <p class="text-xs text-gray-400">Ostatnio <?=$e($dt($d['last_used_at'] ?? $d['created_at']))?> · od <?=$e(date('d.m.Y', strtotime($d['created_at'])))?> · wygasa <?=$e(date('d.m.Y', strtotime($d['expires'])))?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($can_logs):
        $attempts = $rows("SELECT created_at, success, method, failure_reason, ip_address, user_agent FROM login_attempts WHERE $att_where ORDER BY id DESC LIMIT 25", 'is', $id, $u['login']);
    ?>
    <?=$h('Historia logowań')?>
    <div class="<?=$box?> hl-divide">
        <?php if (!$attempts) echo $empty('Brak prób logowania.'); ?>
        <?php foreach ($attempts as $a): $ok = (int)$a['success'] === 1; ?>
        <div class="flex items-center gap-3 px-4 py-2.5">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-900 dark:text-gray-100"><?=$e($dt($a['created_at']))?> · <?=$e($methods[$a['method']] ?? $a['method'])?></p>
                <p class="text-xs text-gray-400 truncate" title="<?=$e($a['user_agent'])?>"><?=$e($device($a['user_agent']))?> · <?=$e($a['ip_address'] ?? '—')?><?=$a['failure_reason'] ? ' · ' . $e($reasons[$a['failure_reason']] ?? $a['failure_reason']) : ''?></p>
            </div>
            <?=$ok ? $badge('OK', $badge_ok) : $badge('Odmowa', $badge_bad)?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php elseif ($tab === 'notifications'):
    $ps = hl_push_settings($conn, $id);
    $subs = $rows('SELECT created_at, last_ok, user_agent FROM push_subscriptions WHERE user_id = ? ORDER BY created_at DESC', 'i', $id);
    $muted_routines = $num('SELECT COUNT(*) FROM push_mute WHERE user_id = ?', 'i', $id);
    $muted_lists = $rows('SELECT lists.name FROM push_list_mute pm JOIN lists ON lists.id = pm.list_id WHERE pm.user_id = ? ORDER BY lists.name', 'i', $id);
    $kinds = [
        'notify_parts' => 'Początek pory dnia', 'notify_timed' => 'Zadania o godzinie', 'notify_deadlines' => 'Terminy',
        'notify_missed' => 'Podsumowanie dnia', 'notify_routines' => 'Rutyny', 'notify_lists' => 'Nowe zadania na wspólnych',
    ];
    if ($num('SELECT COUNT(*) FROM users JOIN user_roles ON user_roles.id = users.role_id WHERE users.id = ? AND user_roles.reports IN (1, 2, 3, 4)', 'i', $id)) $kinds['notify_reports'] = 'Zgłoszenia błędów';
    // historia bez treści – tylko rodzaj z tagu (scripts/push/cron.php, reports/cron.php, push/test.php)
    $history = null;
    try {
        $history = $rows('SELECT tag, devices, delivered, sent_at FROM push_history WHERE user_id = ? ORDER BY sent_at DESC, id DESC LIMIT 40', 'i', $id);
        $hist_sum = $one('SELECT SUM(sent_at > NOW() - INTERVAL 7 DAY) AS d7, COUNT(*) AS d30, SUM(delivered = 0) AS lost FROM push_history WHERE user_id = ? AND sent_at > NOW() - INTERVAL 30 DAY', 'i', $id);
    } catch (mysqli_sql_exception $ex) {}
    $list_names = [];
    foreach ($history ?? [] as $p) if (preg_match('/^list_(\d+)$/', $p['tag'], $m)) $list_names[(int)$m[1]] = null;
    if ($list_names) {
        $ids = array_keys($list_names);
        foreach ($rows('SELECT id, name FROM lists WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', str_repeat('i', count($ids)), ...$ids) as $l) $list_names[(int)$l['id']] = $l['name'];
    }
    $tag_label = function ($tag) use ($list_names) {
        $parts = ['part1' => 'Początek pory · rano', 'part2' => 'Początek pory · popołudnie', 'part3' => 'Początek pory · wieczór'];
        if (isset($parts[$tag])) return $parts[$tag];
        if ($tag === 'end') return 'Podsumowanie dnia';
        if ($tag === 'digest') return 'Kilka przypomnień naraz';
        if ($tag === 'test') return 'Powiadomienie testowe';
        if ($tag === 'bug_report') return 'Nowe zgłoszenie błędu';
        if (str_starts_with($tag, 'at_')) return 'Zadanie o godzinie';
        if (str_starts_with($tag, 'dl_')) return 'Termin zadania';
        if (preg_match('/^list_(\d+)$/', $tag, $m)) return 'Nowe zadania · ' . ($list_names[(int)$m[1]] ?? 'usunięta lista');
        return $tag;
    };
?>
    <div class="grid grid-cols-3 gap-2">
        <?=$stat('Urządzenia', count($subs))?>
        <?=$stat('Wysłane (7 dni)', $history !== null ? (int)$hist_sum['d7'] : '—')?>
        <?=$stat('Wysłane (30 dni)', $history !== null ? (int)$hist_sum['d30'] : '—')?>
    </div>

    <?=$h('Ustawienia')?>
    <div class="<?=$box?> hl-divide">
        <?=$kv('Powiadomienia', !$subs ? $badge('niewłączone na żadnym urządzeniu', $badge_warn) : ($ps['enabled'] ? $badge('włączone', $badge_ok) : $badge('wstrzymane', $badge_warn)))?>
        <?=$kv('Pory dnia', $e($ps['morning_start'] . ' · ' . $ps['afternoon_start'] . ' · ' . $ps['evening_start'] . ' · koniec ' . $ps['day_end']))?>
        <div class="px-4 py-3 flex flex-wrap gap-1.5">
            <?php foreach ($kinds as $k => $label) echo $badge(($ps[$k] ? '✓ ' : '✕ ') . $label, $ps[$k] ? $badge_ok : 'line-through opacity-70'); ?>
        </div>
        <?=$kv('Wyciszone rutyny', $muted_routines)?>
        <?=$kv('Wyciszone listy', $muted_lists ? $e(implode(', ', array_column($muted_lists, 'name'))) : '0')?>
    </div>

    <?=$h('Urządzenia z powiadomieniami')?>
    <div class="<?=$box?> hl-divide">
        <?php if (!$subs) echo $empty('Użytkownik nie włączył powiadomień na żadnym urządzeniu.'); ?>
        <?php foreach ($subs as $s): ?>
        <div class="px-4 py-2.5">
            <p class="text-sm text-gray-900 dark:text-gray-100"><?=$e($device($s['user_agent']))?></p>
            <p class="text-xs text-gray-400">Od <?=$e($dt($s['created_at']))?> · ostatnio doręczone <?=$e($s['last_ok'] ? $dt($s['last_ok']) : 'nigdy')?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <?=$h('Historia wysyłek')?>
    <div class="<?=$box?> hl-divide">
        <?php if ($history === null): ?>
        <?=$empty('Historia nie jest jeszcze zapisywana – uruchom database/migration_push_history.sql.')?>
        <?php else: ?>
        <?php if (!$history) echo $empty('Brak wysłanych powiadomień (historia od wdrożenia, 90 dni).'); ?>
        <?php if ((int)$hist_sum['lost']): ?>
        <p class="px-4 py-2.5 text-xs text-amber-600 dark:text-amber-400"><?=(int)$hist_sum['lost']?> z ostatnich 30 dni nie dotarło na żadne urządzenie.</p>
        <?php endif; ?>
        <?php foreach ($history as $p): $all = (int)$p['delivered'] >= (int)$p['devices']; ?>
        <div class="flex items-center gap-3 px-4 py-2.5">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-900 dark:text-gray-100 truncate"><?=$e($tag_label($p['tag']))?></p>
                <p class="text-xs text-gray-400"><?=$e($dt($p['sent_at']))?></p>
            </div>
            <?=$badge((int)$p['delivered'] . '/' . (int)$p['devices'] . ' ' . hl_plural($p['devices'], 'urządzenie', 'urządzenia', 'urządzeń'), (int)$p['delivered'] === 0 ? $badge_bad : ($all ? $badge_ok : $badge_warn))?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
