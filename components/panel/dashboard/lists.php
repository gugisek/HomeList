<?php
// Dolny pływający navbar z listami. Includowany w pages/dashboard.php i odświeżany przez refreshLists().
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
include '../../../scripts/conn_db.php';
$user_id = $_SESSION['login_id'];
$sql = "SELECT lists.id, lists.name, lists.full_name, lists.list_type FROM `lists` left join list_invities on list_invities.list_id=lists.id where lists.owner_id='$user_id' or list_invities.user_id='$user_id' ORDER BY lists.id asc;";
$nav_lists = [];
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) $nav_lists[] = $row;
$routine_icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5 inline -mt-0.5 mr-1 opacity-60"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>';
?>
<section class="font-[poppins] z-[49] xs:scale-100 scale-90 text-white fixed sm:bottom-8 bottom-4 flex items-center justify-center gap-2">
    <section id="more_menu" class="min-w-[200px] absolute bottom-[4.25rem] z-[49] scale-y-0 origin-bottom w-full flex flex-col gap-2 duration-200">
        <?php
        if (count($nav_lists) > 0) {
            echo '<div id="more_nav_body" class="max-h-[60vh] overflow-y-auto overflow-x-hidden ';
            if (count($nav_lists) <= 3) {
                echo 'hidden';
            }
            echo ' bg-white/75 dark:bg-[#1e1e1e]/75 liquid-glass-menu rounded-3xl py-2 ring-1 ring-black/[0.06] dark:ring-white/[0.06]">';
            foreach ($nav_lists as $i => $row) {
                echo '<a onclick="moreMenuClose();openDetailTab(`todo`,`list='.$row['id'].'`)" id="nav_button_details" class="lists_main_nav list_'.$row['id'].' flex items-center gap-2.5 py-2.5 px-4 mx-1.5 hover:scale-[1.02] active:scale-95 cursor-pointer text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-400/10 hover:text-green-700 dark:hover:text-green-400 font-medium text-sm duration-150 rounded-xl ';
                if ($i <= 2) {
                    echo 'hidden';
                }
                echo '">
                        <span>'.($row['list_type'] === 'routine' ? $routine_icon : '').$row['full_name'].'</span>
                     </a>';
            }
            echo '</div>';
        }
        ?>
        <div class="bg-white/75 dark:bg-[#1e1e1e]/75 liquid-glass-menu rounded-3xl py-2 ring-1 ring-black/[0.06] dark:ring-white/[0.06]">
            <a onclick="moreMenuClose();openPopupAddLists();" class="flex gap-2 py-2.5 px-5 mx-1.5 hover:scale-[1.02] active:scale-95 cursor-pointer text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-400/10 hover:text-green-700 dark:hover:text-green-400 font-medium text-sm duration-150 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <span>Utwórz nową listę</span>
            </a>
            <a onclick="moreMenuClose();openPopupCodeLists();" class="flex gap-2 py-2.5 px-5 mx-1.5 hover:scale-[1.02] active:scale-95 cursor-pointer text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-400/10 hover:text-green-700 dark:hover:text-green-400 font-medium text-sm duration-150 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5" />
                </svg>
                <span>Wprowadź kod listy</span>
            </a>
            <a onclick="openPopupEditLists()" class="flex gap-2 py-2.5 px-5 mx-1.5 hover:scale-[1.02] active:scale-95 cursor-pointer text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-400/10 hover:text-green-700 dark:hover:text-green-400 font-medium text-sm duration-150 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <span>Edytuj listy</span>
            </a>
            <a onclick="moreMenuClose();openDetailTab(`todo`,`list=archive`)" id="nav_button_details" class="archive flex gap-2 py-2.5 px-5 mx-1.5 hover:scale-[1.02] active:scale-95 cursor-pointer text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-400/10 hover:text-green-700 dark:hover:text-green-400 font-medium text-sm duration-150 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <span>Archiwum</span>
            </a>
            <a onclick="moreMenuClose();openDetailTab(`settings_app`,`list=settings`)" id="nav_button_details" class="flex gap-2 py-2.5 px-5 mx-1.5 hover:scale-[1.02] active:scale-95 cursor-pointer text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-400/10 hover:text-green-700 dark:hover:text-green-400 font-medium text-sm duration-150 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>

                <span>Ustawienia</span>
            </a>
            <a onclick="moreMenu()" class="flex gap-2 py-2.5 px-5 mx-1.5 hover:scale-[1.02] active:scale-95 cursor-pointer text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-400/10 hover:text-green-700 dark:hover:text-green-400 font-medium text-sm duration-150 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                <span>Zamknij</span>
            </a>
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
        echo '">'.($row['list_type'] === 'routine' ? $routine_icon : '').$row['full_name'].'</a>';
    }
    ?>
    </div>
    </div>
    <a onclick="moreMenu()" id="nav_button_details" title="Więcej" class="liquid-glass liquid-glass-more rounded-full size-[52px] shrink-0 flex items-center justify-center hover:scale-105 cursor-pointer active:scale-95 focus:scale-95 text-white/60 hover:text-white duration-150">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
        </svg>
    </a>
</section>
