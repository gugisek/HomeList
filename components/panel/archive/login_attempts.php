<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['logs', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Dziennik prób logowania (login_attempts) – zakładka w Archiwum
include '../../../scripts/conn_db.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$only_failed = !empty($_GET['failed']);
$where = $only_failed ? 'WHERE la.success = 0' : '';
$total = (int)$conn->query("SELECT COUNT(*) AS c FROM login_attempts la $where")->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total / 25));
$page = min($total_pages, max(1, (int)($_GET['page_id'] ?? 1)));
$offset = ($page - 1) * 25;
$result = $conn->query("SELECT la.*, users.name, users.sur_name FROM login_attempts la LEFT JOIN users ON users.id = la.user_id $where ORDER BY la.id DESC LIMIT 25 OFFSET $offset");

$methods = ['password' => 'Hasło', '2fa' => '2FA', 'backup_code' => 'Kod zapasowy', 'remember_me' => 'Zapamiętane', 'activation' => 'Aktywacja'];
$reasons = [
    'invalid_credentials' => 'Złe hasło lub login', 'account_locked' => 'Blokada konta (limit prób)', 'ip_locked' => 'Blokada IP (limit prób)',
    'account_inactive' => 'Konto nieaktywowane', 'account_blocked' => 'Konto zablokowane', '2fa_invalid' => 'Zły kod 2FA', '2fa_locked' => 'Blokada 2FA',
    'remember_token_reuse' => 'Podejrzenie kradzieży ciasteczka', 'invalid_code' => 'Zły kod aktywacyjny', 'registered' => 'Rejestracja konta',
];
// prosty opis przeglądarki z user-agenta
$device = function ($ua) {
    $ua = (string)$ua;
    $os = preg_match('/iPhone|iPad/i', $ua) ? 'iOS' : (preg_match('/Android/i', $ua) ? 'Android' : (preg_match('/Windows/i', $ua) ? 'Windows' : (preg_match('/Mac OS/i', $ua) ? 'macOS' : (preg_match('/Linux/i', $ua) ? 'Linux' : ''))));
    $br = preg_match('/Edg\//', $ua) ? 'Edge' : (preg_match('/Chrome\//', $ua) ? 'Chrome' : (preg_match('/Firefox\//', $ua) ? 'Firefox' : (preg_match('/Safari\//', $ua) ? 'Safari' : ($ua === '' ? '—' : 'Inna'))));
    return trim($br . ($os ? ' · ' . $os : ''));
};
?>
<div>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 cursor-pointer">
            <input type="checkbox" class="size-4 accent-green-400" <?=$only_failed ? 'checked' : ''?> onchange="openLoginAttempts(1, this.checked)"> Tylko nieudane
        </label>
        <div class="flex items-center gap-2 text-sm text-gray-400">
            <button type="button" onclick="openLoginAttempts(<?=max(1, $page - 1)?>, <?=$only_failed ? 'true' : 'false'?>)" class="hl-icon-btn hl-icon-btn-sm text-lg">‹</button>
            Strona <?=$page?> z <?=$total_pages?>
            <button type="button" onclick="openLoginAttempts(<?=min($total_pages, $page + 1)?>, <?=$only_failed ? 'true' : 'false'?>)" class="hl-icon-btn hl-icon-btn-sm text-lg">›</button>
        </div>
    </div>
    <div class="hl-card overflow-x-auto p-2">
    <table class="w-full text-sm">
        <tr class="text-left">
            <th class="py-3 px-4 text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Kiedy</th>
            <th class="py-3 px-4 text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Konto / wpisany login</th>
            <th class="py-3 px-4 text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Metoda</th>
            <th class="py-3 px-4 text-[11px] uppercase tracking-wider text-gray-400 font-semibold">Wynik</th>
            <th class="py-3 px-4 text-[11px] uppercase tracking-wider text-gray-400 font-semibold hidden md:table-cell">IP</th>
            <th class="py-3 px-4 text-[11px] uppercase tracking-wider text-gray-400 font-semibold hidden lg:table-cell">Urządzenie</th>
        </tr>
        <?php if ($result->num_rows === 0): ?>
        <tr><td colspan="6" class="py-6 px-4 text-center text-gray-500 dark:text-gray-400">Brak wpisów</td></tr>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()):
            $ok = (int)$row['success'] === 1;
        ?>
        <tr class="border-t border-black/5 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:bg-black/[.03] dark:hover:bg-white/5 duration-150">
            <td class="py-2.5 px-4 whitespace-nowrap text-gray-500 dark:text-gray-400"><?=$e(date('d.m H:i', strtotime($row['created_at'])))?></td>
            <td class="py-2.5 px-4">
                <?=$row['name'] !== null ? '<span class="font-medium">' . $e(trim($row['name'] . ' ' . $row['sur_name'])) . '</span><br>' : ''?>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?=$e($row['login_input'] ?: '—')?></span>
            </td>
            <td class="py-2.5 px-4 whitespace-nowrap"><?=$e($methods[$row['method']] ?? $row['method'])?></td>
            <td class="py-2.5 px-4">
                <span class="hl-badge <?=$ok ? 'text-green-600 !bg-green-100 dark:text-green-400 dark:!bg-green-400/15' : 'text-rose-600 !bg-rose-100 dark:text-rose-400 dark:!bg-rose-400/15'?>"><?=$ok ? 'OK' : 'Odmowa'?></span>
                <?php if ($row['failure_reason']): ?><span class="block text-xs text-gray-500 dark:text-gray-400"><?=$e($reasons[$row['failure_reason']] ?? $row['failure_reason'])?></span><?php endif; ?>
            </td>
            <td class="py-2.5 px-4 hidden md:table-cell text-gray-500 dark:text-gray-400"><?=$e($row['ip_address'] ?? '—')?></td>
            <td class="py-2.5 px-4 hidden lg:table-cell text-gray-500 dark:text-gray-400" title="<?=$e($row['user_agent'])?>"><?=$e($device($row['user_agent']))?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    </div>
</div>
