<?php
// „Konto i ustawienia” (na wzór konta Google): Start, Dane osobowe, Bezpieczeństwo, Urządzenia, Powiadomienia.
// Jedno źródło dla components/panel/pages/profile.php (avatar w sidebarze) i components/panel/dashboard/settings_app.php
// (Ustawienia w menu list). Podstrona: ?tab=..., inaczej ostatnio otwarta w tej sesji (sessionStorage), inaczej Start.
include_once __DIR__ . '/../../../scripts/security.php';
include __DIR__ . '/../../../scripts/conn_db.php';
require_once __DIR__ . '/../../../scripts/auth_lib.php';
include_once __DIR__ . '/../../../scripts/push/push_lib.php';

$acc_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$acc_id = (int)$_SESSION['login_id'];
$stmt = $conn->prepare('SELECT users.*, user_roles.role FROM users LEFT JOIN user_roles ON user_roles.id = users.role_id WHERE users.id = ?');
$stmt->bind_param('i', $acc_id);
$stmt->execute();
$acc = $stmt->get_result()->fetch_assoc();
$acc_img = $acc['profile_picture'] ?: 'default.png';
$acc_2fa = hl_user_has_2fa($conn, $acc_id);
$stmt = $conn->prepare('SELECT COUNT(*) AS c FROM user_two_factor_backup_codes WHERE user_id = ? AND used_at IS NULL');
$stmt->bind_param('i', $acc_id);
$stmt->execute();
$acc_backup_left = (int)$stmt->get_result()->fetch_assoc()['c'];

$acc_devices = [];
$stmt = $conn->prepare('SELECT id, selector, created_at, last_used_at, user_agent FROM remember_tokens WHERE user_id = ? AND expires > NOW() ORDER BY COALESCE(last_used_at, created_at) DESC');
$stmt->bind_param('i', $acc_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $acc_devices[] = $row;
$acc_current_selector = hl_current_remember_selector();

$acc_device_name = function ($ua) {
    $ua = (string)$ua;
    $os = preg_match('/iPhone|iPad/i', $ua) ? 'iPhone / iPad' : (preg_match('/Android/i', $ua) ? 'Android' : (preg_match('/Windows/i', $ua) ? 'Windows' : (preg_match('/Mac OS/i', $ua) ? 'Mac' : (preg_match('/Linux/i', $ua) ? 'Linux' : 'Urządzenie'))));
    $br = preg_match('/Edg\//', $ua) ? 'Edge' : (preg_match('/Chrome\//', $ua) ? 'Chrome' : (preg_match('/Firefox\//', $ua) ? 'Firefox' : (preg_match('/Safari\//', $ua) ? 'Safari' : 'przeglądarka')));
    return $os . ' · ' . $br;
};
$acc_plural = fn($n, $one, $few, $many) => $n == 1 ? $one : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? $few : $many);

$ps = hl_push_settings($conn, $acc_id);

$acc_card = 'rounded-3xl bg-white dark:bg-[#2c2c2c] shadow-sm ring-1 ring-black/5 dark:ring-white/5 p-5 sm:p-6';
$acc_input = 'w-full rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm text-gray-800 dark:text-gray-200 font-medium';
$acc_label = 'block pl-1 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
$acc_btn = 'active:scale-95 duration-150 inline-flex justify-center items-center gap-1 rounded-full ring-green-400 ring-1 px-4 py-2 text-sm text-green-500 hover:shadow-xl hover:bg-green-400 hover:text-white disabled:opacity-50';
$acc_btn_muted = 'active:scale-95 duration-150 inline-flex justify-center rounded-full ring-gray-300 dark:ring-gray-600 ring-1 px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5';
$acc_title = 'font-medium text-xl text-black dark:text-gray-200 font-[poppins]';
$acc_subtitle = 'mt-1 mb-5 text-sm text-gray-500 dark:text-gray-400';

$acc_icon = fn($d, $cls = 'size-5') => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="' . $cls . ' flex-none"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '" /></svg>';
$acc_tabs = [
    'home' => ['Start', 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
    'personal' => ['Dane osobowe', 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
    'security' => ['Bezpieczeństwo', 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z'],
    'devices' => ['Urządzenia', 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
    'notifications' => ['Powiadomienia', 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
];
$acc_icon_chevron = 'm8.25 4.5 7.5 7.5-7.5 7.5';
$acc_icon_logout = 'M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15';
$acc_initial = isset($acc_tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : '';

// klasy aktywnej / nieaktywnej pozycji nawigacji (przełączane w accTab)
$acc_side_on = 'bg-green-50 dark:bg-green-400/10 text-green-700 dark:text-green-400';
$acc_side_off = 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5';
$acc_pill_on = 'bg-green-400 text-white ring-green-400';
$acc_pill_off = 'bg-white dark:bg-[#2c2c2c] text-gray-700 dark:text-gray-300 ring-black/5 dark:ring-white/10';
?>
<div id="acc_root" data-initial="<?=$acc_e($acc_initial)?>" class="max-w-5xl mx-auto mb-28 lg:mb-10">

    <!-- nagłówek + zakładki (telefon: przyklejone u góry) -->
    <div data-aos="fade-up" data-aos-delay="100" class="sticky md:static top-0 z-[2] pt-6 pb-2 -mx-4 px-4 sm:-mx-6 sm:px-6 md:mx-0 md:px-0 bg-gradient-to-b from-[#fdfdfd] via-[#fdfdfd]/90 via-80% to-white/0 dark:from-[#3d3d3d] dark:via-[#3d3d3d]/90 dark:to-[#3d3d3d]/0 md:bg-none">
        <h1 class="font-medium text-2xl text-black font-[poppins] dark:text-gray-200">Konto i ustawienia</h1>
        <nav class="md:hidden mt-3 -mx-4 px-4 sm:-mx-6 sm:px-6 flex gap-2 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <?php foreach ($acc_tabs as $k => [$label, $d]): ?>
            <button type="button" data-acc-tab="<?=$k?>" data-pill="1" data-on="<?=$acc_pill_on?>" data-off="<?=$acc_pill_off?>" onclick="accTab('<?=$k?>', true)" class="flex-none inline-flex items-center gap-1.5 rounded-full ring-1 px-3.5 py-1.5 text-sm font-medium whitespace-nowrap active:scale-95 duration-150 <?=$acc_pill_off?>">
                <?=$acc_icon($d, 'size-4')?><?=$label?>
            </button>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="md:flex md:gap-8 mt-3 md:mt-6">
        <!-- boczna nawigacja (tablet / komputer) -->
        <nav data-aos="fade-up" data-aos-delay="150" class="hidden md:flex flex-col gap-1 w-56 flex-none self-start sticky top-6">
            <?php foreach ($acc_tabs as $k => [$label, $d]): ?>
            <button type="button" data-acc-tab="<?=$k?>" data-on="<?=$acc_side_on?>" data-off="<?=$acc_side_off?>" onclick="accTab('<?=$k?>', true)" class="flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-medium text-left active:scale-95 duration-150 <?=$acc_side_off?>">
                <?=$acc_icon($d)?><?=$label?>
            </button>
            <?php endforeach; ?>
            <a href="scripts/login/logout.php" class="mt-4 flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-medium text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-400/10 active:scale-95 duration-150">
                <?=$acc_icon($acc_icon_logout)?>Wyloguj
            </a>
        </nav>

        <div class="flex-1 min-w-0">
            <?php foreach (array_keys($acc_tabs) as $k): ?>
            <div data-acc-panel="<?=$k?>" class="hidden transition duration-200">
                <?php include __DIR__ . '/tab_' . $k . '.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// funkcje (nie const/let) – skrypt może zostać wykonany ponownie przy przeładowaniu podstrony
function accTab(tab, byUser) {
    var root = document.getElementById('acc_root');
    if (!root) return;
    if (!root.querySelector('[data-acc-panel="' + tab + '"]')) tab = 'home';
    try { sessionStorage.setItem('hl_account_tab', tab); } catch (e) {}
    root.querySelectorAll('[data-acc-panel]').forEach(function (p) {
        var on = p.dataset.accPanel === tab;
        p.classList.toggle('hidden', !on);
        if (on && byUser) {
            p.classList.add('opacity-0', 'translate-y-2');
            requestAnimationFrame(function () { requestAnimationFrame(function () { p.classList.remove('opacity-0', 'translate-y-2'); }); });
        }
    });
    root.querySelectorAll('[data-acc-tab]').forEach(function (b) {
        var on = b.dataset.accTab === tab;
        b.classList.remove.apply(b.classList, (on ? b.dataset.off : b.dataset.on).split(' '));
        b.classList.add.apply(b.classList, (on ? b.dataset.on : b.dataset.off).split(' '));
        if (on && b.dataset.pill && b.offsetParent) b.scrollIntoView({ inline: 'center', block: 'nearest', behavior: byUser ? 'smooth' : 'auto' });
    });
    if (byUser) {
        var top = root.getBoundingClientRect().top + window.scrollY;
        if (window.scrollY > top) window.scrollTo({ top: top, behavior: 'smooth' });
    }
}
function accReload() {
    // w Ustawieniach z menu list (zakładka dashboardu) odświeżamy tylko zakładkę; podstrona wraca z sessionStorage
    if (document.getElementById('dashboard_body') && typeof refreshCurrentTab === 'function') refreshCurrentTab();
    else openPanelSite(localStorage.getItem('PanelSite') || 'profile', localStorage.getItem('PanelSiteGet') || '');
}
(function () {
    var root = document.getElementById('acc_root');
    if (!root) return;
    var saved = null;
    try { saved = sessionStorage.getItem('hl_account_tab'); } catch (e) {}
    accTab(root.dataset.initial || saved || 'home', false);
})();
</script>
