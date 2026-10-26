<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Dolny pływający navbar z listami. Includowany w pages/dashboard.php i odświeżany przez refreshLists().
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
include '../../../scripts/conn_db.php';
$user_id = $_SESSION['login_id'];
$sql = "SELECT DISTINCT lists.id, lists.name, lists.full_name, lists.list_type FROM `lists` left join list_invities on list_invities.list_id=lists.id where lists.owner_id='$user_id' or list_invities.user_id='$user_id' ORDER BY lists.id asc;";
$nav_lists = [];
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) $nav_lists[] = $row;
$routine_icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5 inline -mt-0.5 mr-1 opacity-60"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>';
?>
<?php
$more_icon = fn($d) => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="'.$d.'" /></svg>';
// moduły administracyjne – na telefonie dostępne z menu „więcej” (na tablecie / komputerze w bocznej pigułce)
$more_admin = array_filter([
    'users' => ['Użytkownicy', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
    'logs' => ['Archiwum zmian', 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776'],
    'faq' => ['Pytania (FAQ)', 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z'],
    'reports' => ['Zgłoszenia błędów', 'M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0 1 12 12.75Zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 0 1-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44.125 2.104.52 4.136 1.153 6.06M12 12.75a2.25 2.25 0 0 0 2.248-2.354M12 12.75a2.25 2.25 0 0 1-2.248-2.354M12 8.25c.995 0 1.971-.08 2.922-.236.403-.066.74-.358.795-.762a3.778 3.778 0 0 0-.399-2.25M12 8.25c-.995 0-1.97-.08-2.922-.236-.402-.066-.74-.358-.795-.762a3.734 3.734 0 0 1 .4-2.253'],
    'settings' => ['Ustawienia aplikacji', 'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75'],
], fn($k) => hl_can($k, 'read'), ARRAY_FILTER_USE_KEY);
?>
<section data-toast-dock class="md:hidden font-[poppins] z-[49] xs:scale-100 scale-90 text-white fixed sm:bottom-8 bottom-4 flex items-center justify-center gap-2">
    <section id="more_menu" class="hl-pop absolute bottom-[4.25rem] z-[49] max-h-[calc(100dvh-7.5rem)] rounded-[26px] origin-bottom flex flex-col gap-2">
        <div id="more_nav_body" class="<?=count($nav_lists) > 3 ? "" : "hidden "?>hl-menu flex-initial min-h-[5.5rem] max-h-[32vh] overflow-y-auto overflow-x-hidden overscroll-contain [scrollbar-width:none] [&::-webkit-scrollbar]:hidden flex flex-col gap-0.5">
            <span class="hl-menu-label">Pozostałe listy</span>
            <?php foreach ($nav_lists as $i => $row): ?>
            <a onclick="moreMenuClose();openDetailTab(`todo`,`list=<?=(int)$row['id']?>`)" id="nav_button_details" class="lists_main_nav list_<?=(int)$row['id']?> hl-menu-item <?=$i <= 2 ? 'hidden' : ''?>">
                <span class="truncate"><?=($row['list_type'] === 'routine' ? $routine_icon : '').htmlspecialchars($row['full_name'])?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="hl-menu flex-none flex flex-col gap-0.5">
            <a onclick="moreMenuClose();openPopupAddLists();" class="hl-menu-item"><?=$more_icon('M12 4.5v15m7.5-7.5h-15')?>Utwórz nową listę</a>
            <a onclick="moreMenuClose();openPopupCodeLists();" class="hl-menu-item"><?=$more_icon('M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5')?>Wprowadź kod listy</a>
            <a onclick="moreMenuClose();openPopupEditLists()" class="hl-menu-item"><?=$more_icon('M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5')?>Edytuj listy</a>
            <a onclick="moreMenuClose();openDetailTab(`todo`,`list=archive`)" id="nav_button_details" class="lists_main_nav list_archive hl-menu-item"><?=$more_icon('m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z')?>Archiwum</a>
            <a onclick="moreMenuClose();openDetailTab(`settings_app`,`list=settings`)" id="nav_button_details" class="lists_main_nav list_settings hl-menu-item"><?=$more_icon('M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z')?>Konto i ustawienia</a>
            <?php if ($more_admin): ?>
            <span class="hl-menu-sep"></span>
            <span class="hl-menu-label">Administracja</span>
            <?php foreach ($more_admin as $site => [$label, $d]): ?>
            <a onclick="moreMenuClose();openPanelSite('<?=$site?>')" class="hl-menu-item"><?=$more_icon($d)?><?=$label?></a>
            <?php endforeach; ?>
            <?php endif; ?>
            <span class="hl-menu-sep hl-install-btn"></span>
            <a onclick="moreMenuClose();hlInstallApp()" class="hl-install-btn hl-menu-item hl-menu-cta"><?=$more_icon('M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3')?>Zainstaluj aplikację<span class="hl-install-shine pointer-events-none absolute inset-y-0 -left-1/2 w-1/3 skew-x-[-20deg] bg-white/25 blur-sm"></span></a>
        </div>
    </section>
    <div class="liquid-glass liquid-glass-pill rounded-full p-1.5 flex items-center gap-0.5">
    <a onclick="moreMenuClose();openDetailTab(`today`,`list=today`)" title="Plan dnia" class="lists_main_nav list_today flex items-center gap-1.5 hover:scale-105 active:scale-95 cursor-pointer focus:scale-95 px-3.5 sm:pl-4 sm:pr-5 py-2.5 hover:bg-white/10 text-white/75 hover:text-white rounded-full text-sm font-medium">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
        <span class="hidden sm:inline">Dziś</span>
    </a>
    <span class="w-px h-5 mx-0.5 bg-white/15"></span>
    <div id="todo-nav" class="contents">
    <?php
    foreach ($nav_lists as $i => $row) {
        echo '
            <a onclick="moreMenuClose();openDetailTab(`todo`,`list='.$row['id'].'`)" id="nav_button_details" class="lists_main_nav list_'.$row['id'].' inline-block hover:scale-105 active:scale-95 cursor-pointer focus:scale-95 px-3.5 sm:px-5 py-2.5 hover:bg-white/10 text-white/75 hover:text-white rounded-full text-sm font-medium whitespace-nowrap max-w-[6.5rem] sm:max-w-[12rem] truncate ';
        if ($i > 2) {
            echo 'hidden';
        }
        echo '">'.($row['list_type'] === 'routine' ? $routine_icon : '').htmlspecialchars($row['full_name']).'</a>';
    }
    ?>
    </div>
    </div>
    </a>
    <a onclick="moreMenu()" id="more_btn" title="Więcej" class="liquid-glass liquid-glass-more rounded-full size-[52px] shrink-0 flex items-center justify-center hover:scale-105 cursor-pointer active:scale-95 text-white/60 hover:text-white duration-150">
        <span class="hl-more-ico">
            <svg class="is-dots" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
            <svg class="is-x" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </span>
    </a>
</section>
