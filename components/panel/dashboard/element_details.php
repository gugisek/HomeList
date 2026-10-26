<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include '../../../scripts/conn_db.php';
include_once '../../../scripts/planner/planner_lib.php';
include_once '../../../scripts/access.php';
$id = (int)($_GET['id'] ?? 0);
$row = hl_element_access($conn, (int)$_SESSION['login_id'], $id);
if ($row) {
    // rutyny mają własny widok szczegółów
    if ($row['list_type'] === 'routine') {
        include 'routine_details.php';
        exit;
    }
    // plan dnia zalogowanego użytkownika dla tego zadania
    $plan_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT plan_date, day_part FROM day_plan WHERE user_id = '".(int)$_SESSION['login_id']."' AND element_id = '".(int)$row['id']."' LIMIT 1"));

    // argumenty "Kopiuj" jako bezpieczne literały JS (wcześniej surowy tekst w szablonie `...`)
    $copy_args = hl_e(implode(', ', array_map('json_encode', [$row['title'], (string)$row['description'], (string)$row['deadline_date'], (string)$row['list_id']])));

    // escapowanie + entery z bazy danych jako <br>
    $row['description'] = nl2br(hl_e((string)$row['description']), false);
} else {
    echo "Nie znaleziono elementu.";
    exit;
}
?>
<div class="-mt-10 font-[poppins] p-1 text-gray-800 dark:text-gray-200">
<div id="element_loading"></div>
    <h1 class="hl-popup-title"><?=hl_e($row['title'])?></h1>
    <?php
    if($row['deadline_date'] != null && $row['deadline_date'] != "0000-00-00 00:00:00") {
    $date = hl_short_date($row['deadline_date'], true);
    $late = $row['deadline_date'] < date('Y-m-d H:i:s') && !in_array((int)$row['status_id'], [2, 4], true);
    echo '<span class="hl-badge mt-2 '.($late ? 'text-red-500 dark:text-red-400 !bg-red-400/10' : 'text-rose-500 dark:text-rose-400 !bg-rose-400/10').'">'.hl_icon('flag', 'size-3.5').'Deadline '.$date.'</span>';
    }
    ?>
    <p class="pt-4 text-sm leading-relaxed break-words text-gray-600 dark:text-gray-300"><?=$row['description']?></p>

    <?php if (!empty($row['link_url'])): ?>
    <div id="link_preview_badge" class="mt-4">
        <div class="flex items-center gap-3 rounded-2xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-[#2c2c2c] p-3 animate-pulse">
            <div class="w-14 h-14 rounded-xl bg-gray-200 dark:bg-[#3a3a3a] flex-shrink-0"></div>
            <div class="flex-1 space-y-2 min-w-0">
                <div class="h-2.5 bg-gray-200 dark:bg-[#3a3a3a] rounded w-1/4"></div>
                <div class="h-3.5 bg-gray-200 dark:bg-[#3a3a3a] rounded w-3/4"></div>
                <div class="h-2.5 bg-gray-200 dark:bg-[#3a3a3a] rounded"></div>
            </div>
            <div class="w-4 h-4 bg-gray-200 dark:bg-[#3a3a3a] rounded flex-shrink-0"></div>
        </div>
    </div>
    <script>
    (function() {
        const linkUrl = <?=json_encode($row['link_url'])?>;
        const badge = document.getElementById('link_preview_badge');

        function esc(s) {
            return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function renderFallback() {
            let hostname = linkUrl;
            try { hostname = new URL(linkUrl).hostname; } catch(e) {}
            badge.innerHTML = `
                <a href="${esc(linkUrl)}" target="_blank" rel="noopener noreferrer"
                   class="flex items-center gap-3 rounded-2xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-[#2c2c2c] p-3 hover:bg-gray-100 dark:hover:bg-[#333] transition-all duration-150 group overflow-hidden">
                    <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-gray-100 dark:bg-[#3d3d3d] flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate">${esc(hostname)}</p>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate group-hover:text-green-400 transition-colors">${esc(linkUrl)}</p>
                    </div>
                    <svg class="flex-shrink-0 h-4 w-4 text-gray-400 group-hover:text-green-400 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>`;
        }

        fetch('scripts/dashboard/link_preview.php?url=' + encodeURIComponent(linkUrl))
            .then(r => r.json())
            .then(data => {
                if (data.error) { renderFallback(); return; }

                const isFavicon = !!data.is_favicon;
                const imgWrap = isFavicon
                    ? 'flex items-center justify-center w-14 h-14 rounded-xl bg-gray-100 dark:bg-[#3d3d3d] flex-shrink-0'
                    : 'w-14 h-14 rounded-xl overflow-hidden bg-gray-200 dark:bg-[#3d3d3d] flex-shrink-0';
                const imgClass = isFavicon ? 'w-8 h-8 object-contain' : 'w-full h-full object-cover';

                badge.innerHTML = `
                    <a href="${esc(linkUrl)}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-3 rounded-2xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-[#2c2c2c] p-3 hover:bg-gray-100 dark:hover:bg-[#333] transition-all duration-150 group overflow-hidden">
                        <div class="${imgWrap}">
                            <img src="${esc(data.image)}" class="${imgClass}"
                                 onerror="this.parentElement.innerHTML='<svg xmlns=\\'http://www.w3.org/2000/svg\\' fill=\\'none\\' viewBox=\\'0 0 24 24\\' stroke-width=\\'1.5\\' stroke=\\'currentColor\\' class=\\'size-6 text-gray-400\\'><path stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\' d=\\'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244\\' /></svg>'" alt="">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate">${esc(data.site_name)}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate group-hover:text-green-400 transition-colors">${esc(data.title || linkUrl)}</p>
                            ${data.description ? `<p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">${esc(data.description)}</p>` : ''}
                        </div>
                        <svg class="flex-shrink-0 h-4 w-4 text-gray-400 group-hover:text-green-400 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                    </a>`;
            })
            .catch(() => renderFallback());
    })();
    </script>
    <?php endif; ?>

    <?php if (!in_array((int)$row['status_id'], [2, 4], true) || $plan_row): ?>
    <button type="button" onclick="popupDetailsElementOpenClose(); openPopupPlanSheet('<?=$row['id']?>')" class="mt-5 w-full flex items-center gap-3 rounded-2xl px-3 py-2.5 text-left duration-150 active:scale-[0.98] <?=$plan_row ? 'bg-green-400/10 ring-1 ring-green-400/30 hover:bg-green-400/20' : 'bg-gray-100/80 dark:bg-[#2c2c2c] hover:bg-gray-200/80 dark:hover:bg-[#333]'?>">
        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl <?=$plan_row ? 'bg-green-400 text-white' : 'bg-white dark:bg-[#3d3d3d] text-gray-500 dark:text-gray-300'?>"><?=hl_icon('calendar', 'size-5')?></span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-gray-800 dark:text-gray-200"><?=$plan_row ? 'Zaplanowane' : 'Zaplanuj'?></span>
            <span class="block text-xs text-gray-500 dark:text-gray-400 truncate"><?=$plan_row ? hl_plan_label($plan_row['plan_date'], $plan_row['day_part'], date('Y-m-d')) : 'Dodaj do planu dnia na wybraną porę'?></span>
        </span>
        <span class="text-gray-400"><?=hl_icon('right', 'size-4', '2')?></span>
    </button>
    <?php endif; ?>

    <a class="mt-6 sm:mt-6 text-xs flex items-center justify-center gap-2 mb-4 w-full text-gray-600 dark:text-gray-400"><?php if (hl_list_is_shared($conn, (int)$row['list_id'])) echo hl_avatar_img($row['profile_picture'], 'size-6'); ?><span>Dodano <?=hl_short_date($row['create_date'], true)?> przez <?=hl_e($row['name'].' '.$row['sur_name'])?></span></a>

    <?php
    $st = (int)$row['status_id'];
    $menu_btn = function ($onclick, $icon, $label, $tone = '') {
        return '<button type="button" onclick="'.$onclick.'" class="hl-menu-item'.($tone ? ' '.$tone : '').'">'.hl_icon($icon, '').$label.'</button>';
    };
    ?>
    <div class="flex items-center gap-2">
        <?php if ($st === 4): ?>
        <span class="hl-badge !px-4 !py-2.5 !text-sm text-amber-600 dark:text-amber-400 !bg-amber-100 dark:!bg-amber-400/15"><?=hl_icon('archive', 'size-5')?>Zarchiwizowane</span>
        <button onclick="updateStatus(<?=$row['id']?>, `1`)" class="hl-btn hl-btn-primary flex-1"><?=hl_icon('undo')?>Przywróć</button>
        <?php else: ?>
        <div class="relative flex-none">
            <button type="button" onclick="toggleMenu(this)" title="Więcej" class="hl-btn hl-btn-soft hl-btn-square"><?=hl_icon('dots', '', '2')?></button>
            <div class="menu-dropdown hl-pop hidden min-w-[200px]">
                <div class="hl-menu flex flex-col gap-0.5">
                    <?=$menu_btn("popupDetailsElementOpenClose(); openPopupEditElement(`".$row['id']."`)", 'pencil', 'Edytuj')?>
                    <?=$menu_btn("popupDetailsElementOpenClose(); openPopupMoveElement(`".$row['id']."`)", 'move', 'Przenieś', 'is-sky')?>
                    <?=$menu_btn("popupDetailsElementOpenClose(); openPopupCopyElement(".$copy_args.")", 'copy', 'Kopiuj', 'is-sky')?>
                    <span class="hl-menu-sep"></span>
                    <?=$menu_btn("updateStatus(".$row['id'].", `4`)", 'archive', 'Archiwizuj', 'is-amber')?>
                </div>
            </div>
        </div>
        <?php if ($st === 3): ?>
        <button onclick="updateStatus(<?=$row['id']?>, `1`)" title="Cofnij „W trakcie”" class="hl-btn flex-1 bg-sky-400/15 text-sky-500 dark:text-sky-400 hover:bg-sky-400 hover:text-white"><?=hl_icon('progress')?><span class="hidden sm:inline">W trakcie</span></button>
        <?php else: ?>
        <button onclick="updateStatus(<?=$row['id']?>, `3`)" class="hl-btn hl-btn-ghost flex-1"><?=hl_icon('progress')?><span class="hidden sm:inline">W trakcie</span></button>
        <?php endif; ?>
        <?php if ($st === 2): ?>
        <button onclick="updateStatus(<?=$row['id']?>, `1`)" title="Cofnij wykonanie" class="hl-btn hl-btn-accent flex-1"><?=hl_icon('check', '', '2')?>Zrobione</button>
        <?php else: ?>
        <button onclick="updateStatus(<?=$row['id']?>, `2`)" class="hl-btn hl-btn-green flex-1"><?=hl_icon('check', '', '2')?>Zrobione!</button>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<script>
    function updateStatus(element_id, status_id){
        const postData = new FormData();
        postData.append('element_id', element_id);
        postData.append('status_id', status_id);

    // Pokazanie kółka ładowania
    var delivery_loading = document.getElementById('element_loading');
    delivery_loading.innerHTML = "<div class='w-full duartion-150 flex items-center justify-center z-[999]'><div class='z-[30] fixed bg-black/90 p-4 mt-40 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";


    // Wysyłanie żądania POST do skryptu PHP
    fetch('scripts/dashboard/update_status.php', {
        method: 'POST',
        body: postData
    })

    .then(response => response.text())
    .then(text => {
        // Rozbijanie tekstu na fragmenty JSON-owe
        const rawJsonParts = text.trim().split(/(?<=\})\s*(?=\{)/); // Dzielenie na podstawie zakończenia jednego JSON-a i początku następnego

        const jsonObjects = rawJsonParts.map(jsonString => {
            try {
                return JSON.parse(jsonString);  // Parsowanie każdego fragmentu jako JSON
            } catch (e) {
                console.error('Błąd parsowania JSON:', e, 'Fragment:', jsonString);
                return null;
            }
        }).filter(obj => obj !== null);

        var i = 1;

        jsonObjects.forEach(data => {
            if (data.status) {  // Jeśli dane posiadają status
                switch (data.status) {
                    case 'success':
                        showAlert('success', data.message);
                        if(i==1){
                          popupDetailsElementOpenClose();
                          refreshCurrentTab('restore_scroll');
                        }
                        break;
                    case 'error':
                        showAlert('error', data.message);
                        break;
                    case 'warning':
                        showAlert('warning', data.message);
                        break;
                    default:
                        showAlert('error', 'Nieznany status odpowiedzi');
                }
            }
            i = i+1;
        });
    })
    .catch(error => {
        showAlert('error', 'Wystąpił problem połączenia z serwerem');
        console.error('Błąd:', error);
    })

    .finally(() => {
        delivery_loading.innerHTML = ""; // Ukrycie kółka ładowania po zakończeniu żądania
    });

    }

    
</script>
<script>
// menu „więcej” w szczegółach: pozycjonowane fixed (omija overflow karty popupu), wjeżdża jak menu nawigacji
function toggleMenu(btn) {
    const menu = btn.parentElement.querySelector(".menu-dropdown");
    const isHidden = !menu.classList.contains("is-open");

    document.querySelectorAll(".menu-dropdown").forEach(m => {
        m.classList.remove("is-open");
        m.classList.add("hidden");
        m.style.cssText = "";
    });

    if (isHidden) {
        const rect = btn.getBoundingClientRect();
        menu.classList.remove("hidden");
        menu.style.position = "fixed";
        menu.style.left = rect.left + "px";
        menu.style.bottom = (window.innerHeight - rect.top + 8) + "px";
        menu.style.top = "auto";
        menu.style.zIndex = "9999";
        menu.style.transformOrigin = "left bottom";
        requestAnimationFrame(() => menu.classList.add("is-open"));

        document.addEventListener("click", function handler(e) {
            if (!btn.parentElement.contains(e.target) || e.target.closest(".hl-menu-item")) {
                menu.classList.remove("is-open");
                setTimeout(() => { if (!menu.classList.contains("is-open")) { menu.classList.add("hidden"); menu.style.cssText = ""; } }, 200);
                document.removeEventListener("click", handler);
            }
        });
    }
}
</script>
