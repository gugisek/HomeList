<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../scripts/security.php'; ?>
<?php
// Boczna pigułka nawigacji (tablet / komputer) – pionowy odpowiednik dolnego paska list (dashboard/lists.php).
// Includowana w panel_sidebar.php, odświeżana przez hlRailRefresh() (panel_body.php) po zmianach list.
// Zwykły użytkownik: Dziś, listy, archiwum, „więcej” i profil. Moduły administracyjne według uprawnień roli.
include __DIR__.'/../../scripts/conn_db.php';
$rail_uid = (int)$_SESSION['login_id'];
$stmt = $conn->prepare('SELECT DISTINCT lists.id, lists.full_name, lists.list_type FROM lists LEFT JOIN list_invities ON list_invities.list_id = lists.id WHERE lists.owner_id = ? OR list_invities.user_id = ? ORDER BY lists.id ASC');
$stmt->bind_param('ii', $rail_uid, $rail_uid);
$stmt->execute();
$rail_lists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$rail_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$rail_icon = fn($d, $w = '1.6') => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="'.$w.'" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="'.$d.'" /></svg>';

// monogram listy: inicjały dwóch pierwszych słów albo dwie pierwsze litery (bez wymagania mbstring)
$rail_pl = ['ą' => 'Ą', 'ć' => 'Ć', 'ę' => 'Ę', 'ł' => 'Ł', 'ń' => 'Ń', 'ó' => 'Ó', 'ś' => 'Ś', 'ź' => 'Ź', 'ż' => 'Ż'];
$rail_upper = fn($s) => function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper(strtr($s, $rail_pl));
$rail_lower = fn($s) => function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower(strtr($s, array_flip($rail_pl)));
$rail_mono = function ($name) use ($rail_upper, $rail_lower) {
    $words = preg_split('/\s+/u', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$words) return '?';
    $first = preg_split('//u', $words[0], -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) > 1) {
        $second = preg_split('//u', $words[1], -1, PREG_SPLIT_NO_EMPTY);
        return $rail_upper($first[0].$second[0]);
    }
    return $rail_upper($first[0]).$rail_lower($first[1] ?? '');
};
// odcienie pór dnia ze strony głównej – każda lista dostaje stały kolor
$rail_tones = ['#4ade80', '#fbbf24', '#38bdf8', '#fb7185', '#818cf8', '#fb923c', '#2dd4bf', '#c084fc'];

$rail_d = [
    'today' => 'M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z',
    'archive' => 'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
    'repeat' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
    'dots' => 'M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
    'x' => 'M6 18 18 6M6 6l12 12',
    'new' => 'M12 4.5v15m7.5-7.5h-15',
    'code' => 'M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5',
    'order' => 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
    'install' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
    'logout' => 'M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15',
];

// moduły administracyjne – ten sam check robi serwer w każdym widoku
$rail_admin = [
    'users' => ['Użytkownicy', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
    'logs' => ['Archiwum zmian', 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776'],
    'faq' => ['Pytania (FAQ)', 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z'],
    'reports' => ['Zgłoszenia błędów', 'M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0 1 12 12.75Zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 0 1-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44.125 2.104.52 4.136 1.153 6.06M12 12.75a2.25 2.25 0 0 0 2.248-2.354M12 12.75a2.25 2.25 0 0 1-2.248-2.354M12 8.25c.995 0 1.971-.08 2.922-.236.403-.066.74-.358.795-.762a3.778 3.778 0 0 0-.399-2.25M12 8.25c-.995 0-1.97-.08-2.922-.236-.402-.066-.74-.358-.795-.762a3.734 3.734 0 0 1 .4-2.253M12 8.25a2.25 2.25 0 0 0-2.248 2.146M12 8.25a2.25 2.25 0 0 1 2.248 2.146M8.683 5a6.032 6.032 0 0 1-1.155-1.002c.07-.63.27-1.222.574-1.747m.581 2.749A3.75 3.75 0 0 1 15.318 5m0 0c.427-.283.815-.62 1.155-.999a4.471 4.471 0 0 0-.575-1.752M4.921 6a24.048 24.048 0 0 0-.392 3.314c1.668.546 3.416.914 5.223 1.082M19.08 6c.205 1.08.337 2.187.392 3.314a23.882 23.882 0 0 1-5.223 1.082'],
    'settings' => ['Ustawienia aplikacji', 'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75'],
];
$rail_admin = array_filter($rail_admin, fn($k) => hl_can($k, 'read'), ARRAY_FILTER_USE_KEY);
$rail_reports_new = isset($rail_admin['reports']) ? (int)$conn->query('SELECT COUNT(*) AS c FROM bug_reports WHERE seen_at IS NULL')->fetch_assoc()['c'] : 0;

$rail_img = !empty($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : 'default.png';
?>
<nav id="hl_rail" class="hl-rail" aria-label="Nawigacja">
    <div class="hl-rail-pill liquid-glass">
        <a data-rail="list_today" onclick="hlNav('list=today')" class="hl-rail-item" title="Dziś">
            <span class="hl-rail-ico"><?=$rail_icon($rail_d['today'], '1.8')?></span>
            <span class="hl-rail-label">Dziś</span>
        </a>
        <span class="hl-rail-sep"></span>
        <div id="rail_lists" class="hl-rail-scroll">
            <?php foreach ($rail_lists as $l): $tone = $rail_tones[(int)$l['id'] % count($rail_tones)]; ?>
            <a data-rail="list_<?=(int)$l['id']?>" data-list-id="<?=(int)$l['id']?>" onclick="hlNav('list=<?=(int)$l['id']?>')" class="hl-rail-item" title="<?=$rail_e($l['full_name'])?>">
                <span class="hl-rail-ico">
                    <span class="hl-rail-mono" style="--mono: <?=$tone?>"><?=$rail_e($rail_mono($l['full_name']))?>
                        <?php if ($l['list_type'] === 'routine'): ?><span class="hl-rail-routine"><?=$rail_icon($rail_d['repeat'], '3')?></span><?php endif; ?>
                    </span>
                </span>
                <span class="hl-rail-label"><?=$rail_e($l['full_name'])?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <a data-rail="list_archive" onclick="hlNav('list=archive')" class="hl-rail-item" title="Archiwum">
            <span class="hl-rail-ico"><?=$rail_icon($rail_d['archive'])?></span>
            <span class="hl-rail-label">Archiwum</span>
        </a>
        <?php if ($rail_admin): ?>
        <span class="hl-rail-sep"></span>
        <?php foreach ($rail_admin as $site => [$label, $d]): ?>
        <a data-rail="<?=$site?>" onclick="openPanelSite('<?=$site?>')" class="hl-rail-item" title="<?=$label?>">
            <span class="hl-rail-ico"><?=$rail_icon($d)?></span>
            <?php if ($site === 'reports' && $rail_reports_new): ?><span id="reports_nav_dot" class="hl-rail-dot"></span><?php endif; ?>
            <span class="hl-rail-label"><?=$label?></span>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="hl-rail-more">
        <button type="button" id="rail_more_btn" onclick="hlRailMenu()" class="hl-rail-circle liquid-glass" title="Więcej" aria-haspopup="true" aria-expanded="false">
            <span class="hl-more-ico">
                <svg class="is-dots" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="<?=$rail_d['dots']?>" /></svg>
                <svg class="is-x" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="<?=$rail_d['x']?>" /></svg>
            </span>
        </button>
        <div id="rail_menu" class="hl-rail-menu hl-pop hl-pop-side">
            <div class="hl-menu flex flex-col gap-0.5">
                <span class="hl-menu-label">Listy</span>
                <a onclick="hlRailAction('openPopupAddLists')" class="hl-menu-item"><?=$rail_icon($rail_d['new'])?>Utwórz nową listę</a>
                <a onclick="hlRailAction('openPopupCodeLists')" class="hl-menu-item"><?=$rail_icon($rail_d['code'])?>Wprowadź kod listy</a>
                <a onclick="hlRailAction('openPopupEditLists')" class="hl-menu-item"><?=$rail_icon($rail_d['order'])?>Edytuj listy</a>
                <span class="hl-menu-sep"></span>
                <a onclick="hlRailMenu(false);hlInstallApp()" class="hl-install-btn hl-menu-item hl-menu-cta"><?=$rail_icon($rail_d['install'])?>Zainstaluj aplikację<span class="hl-install-shine pointer-events-none absolute inset-y-0 -left-1/2 w-1/3 skew-x-[-20deg] bg-white/25 blur-sm"></span></a>
                <a href="scripts/login/logout.php" class="hl-menu-item is-danger"><?=$rail_icon($rail_d['logout'])?>Wyloguj</a>
            </div>
        </div>
    </div>

    <a data-rail="profile" onclick="openPanelSite('profile')" class="hl-rail-circle hl-rail-avatar liquid-glass" title="Konto i ustawienia">
        <img src="img/users_images/<?=$rail_e($rail_img)?>" alt="" onerror="this.onerror=null;this.src='img/users_images/default.png'">
    </a>
</nav>
