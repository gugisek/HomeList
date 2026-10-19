<?php
// Popup "Zaplanuj z list": niezaplanowane, niezrobione zadania ze wszystkich list użytkownika.
// Stuknięcie w porę dnia planuje zadanie na wybrany dzień. id = YYYY-MM-DD
session_start();
include __DIR__.'/../../../scripts/conn_db.php';
include __DIR__.'/../../../scripts/planner/planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$today = date('Y-m-d');
$date = hl_valid_date($_GET['id'] ?? '') ? $_GET['id'] : $today;
$parts = hl_parts();
$ids = hl_in(hl_user_list_ids($conn, $user_id));
// "Wkrótce termin": deadline od tygodnia wstecz do tygodnia naprzód (starsze przeterminowane zostają w swoich listach)
$soon_from = hl_add_days($today, -7).' 00:00:00';
$soon_limit = hl_add_days(max($date, $today), 7).' 23:59:59';

$sql = "SELECT list_elements.id, list_elements.title, list_elements.deadline_date, list_elements.status_id, list_elements.list_id, lists.full_name AS list_name
        FROM list_elements JOIN lists ON lists.id=list_elements.list_id
        WHERE list_elements.list_id IN ($ids) AND lists.list_type='normal' AND list_elements.status_id<>2
          AND NOT EXISTS (SELECT 1 FROM day_plan WHERE day_plan.user_id='$user_id' AND day_plan.element_id=list_elements.id)
        ORDER BY lists.id ASC,
                 (list_elements.deadline_date IS NULL OR list_elements.deadline_date<'1000-01-01'),
                 list_elements.deadline_date ASC, list_elements.create_date ASC";

// grupa "Wkrótce termin" (deadline w ciągu 7 dni lub po terminie) + grupy per lista
$soon = [];
$groups = [];
$count = 0;
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $count++;
    $row['deadline'] = !empty($row['deadline_date']) && $row['deadline_date'] > '1000-01-01' ? $row['deadline_date'] : null;
    if ($row['deadline'] && $row['deadline'] >= $soon_from && $row['deadline'] <= $soon_limit) {
        $soon[] = $row;
        continue;
    }
    $groups[(int)$row['list_id']]['name'] = $row['list_name'];
    $groups[(int)$row['list_id']]['items'][] = $row;
}
usort($soon, fn($a, $b) => strcmp($a['deadline'], $b['deadline']));
$open_all = $count <= 25;

$day_label = hl_relative_day($date, $today);
if (!in_array($day_label, ['Dziś', 'Jutro', 'Wczoraj'])) $day_label .= ' '.date('d.m', strtotime($date));

function backlog_row(array $row, array $parts, $show_list) {
    echo '<div class="backlog-row flex items-center gap-2 py-2 pl-3 pr-1.5 rounded-2xl hover:bg-gray-50 dark:hover:bg-white/5 duration-150" data-title="'.hl_e(hl_lower($row['title'].' '.$row['list_name'])).'">';
    echo '<div class="min-w-0 flex-1"><p class="text-sm font-medium truncate">'.hl_e($row['title']).'</p>';
    $meta = [];
    if ($show_list) $meta[] = '<span class="truncate text-gray-400">'.hl_e($row['list_name']).'</span>';
    if ((int)$row['status_id'] === 3) $meta[] = '<span class="whitespace-nowrap text-sky-400">W trakcie</span>';
    if ($row['deadline']) $meta[] = '<span class="whitespace-nowrap '.($row['deadline'] < date('Y-m-d H:i:s') ? 'text-red-400' : 'text-rose-400').'">Deadline '.date('H:i d.m', strtotime($row['deadline'])).'</span>';
    if ($meta) echo '<p class="text-xs flex items-center gap-1.5 min-w-0">'.implode('<span class="text-gray-300">·</span>', $meta).'</p>';
    // przyciski pór dnia wstawia JS z szablonu #backlog_actions_tpl (mniejszy payload przy setkach zadań)
    echo '</div><div class="backlog-actions flex items-center gap-1 flex-none" data-id="'.(int)$row['id'].'"></div></div>';
}

function backlog_group($key, $title, array $items, array $parts, $open, $show_list, $accent = '') {
    echo '<div class="backlog-group list_'.$key.'" data-open="'.($open ? '1' : '0').'">';
    echo '<button type="button" onclick="backlogToggleGroup(this.parentElement)" class="w-full flex items-center gap-2 px-1 py-1.5 text-left">
            <h3 class="text-xs font-semibold uppercase tracking-wide '.($accent ?: 'text-gray-400').' truncate">'.$title.'</h3>
            <span class="backlog-count text-xs text-gray-400">'.count($items).'</span>
            <span class="backlog-chevron ml-auto text-gray-400 duration-200'.($open ? ' rotate-90' : '').'">'.hl_icon('right', 'size-4', '2').'</span>
          </button>';
    echo '<div class="backlog-items'.($open ? '' : ' hidden').'">';
    foreach ($items as $row) backlog_row($row, $parts, $show_list);
    echo '</div></div>';
}
?>
<style>
.bl-btn { height: 2rem; width: 2rem; display: grid; place-items: center; border-radius: .75rem; background: rgb(243 244 246 / .8); transition: background-color .15s, color .15s, transform .15s; }
.bl-btn:hover { background: #4ade80; color: #fff; }
.bl-btn:active { transform: scale(.9); }
@media (prefers-color-scheme: dark) { .bl-btn { background: #3d3d3d; } .bl-btn:hover { background: #4ade80; } }
</style>
<div class="-mt-10 font-[poppins] p-1 text-gray-800 dark:text-gray-200">
    <?=hl_icon_sprite(['sunrise', 'sun', 'moon', 'inbox'])?>
    <template id="backlog_actions_tpl">
        <?php foreach ($parts as $p => $info): ?>
        <button type="button" title="<?=$info['label']?>" data-part="<?=$p?>" class="bl-btn <?=$info['color']?>"><?=hl_icon_use($info['icon'], 'size-4')?></button>
        <?php endforeach; ?>
    </template>
    <h1 class="text-lg font-semibold pr-8">Zaplanuj na: <span class="text-green-400"><?=$day_label?></span></h1>
    <p class="text-xs text-gray-400 dark:text-gray-500 pr-8">Stuknij porę dnia przy zadaniu, aby dodać je do planu</p>

    <?php if ($count): ?>
    <input id="backlog_search" type="text" oninput="backlogFilter(this.value)" placeholder="Szukaj w <?=$count?> zadaniach..." class="mt-4 w-full rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm text-gray-800 dark:text-gray-300 font-medium placeholder-gray-400">
    <div class="mt-3 flex items-center justify-end gap-3 px-1 text-[10px] text-gray-400">
        <?php foreach ($parts as $p => $info): ?>
        <span class="flex items-center gap-0.5"><span class="<?=$info['color']?>"><?=hl_icon_use($info['icon'], 'size-3.5')?></span><?=$info['label']?></span>
        <?php endforeach; ?>
    </div>

    <div id="backlog_groups" class="mt-1 space-y-1">
        <?php
        if ($soon) backlog_group('soon', hl_icon('flag', 'size-3.5 inline -mt-0.5 mr-0.5').'Wkrótce termin', $soon, $parts, true, true, 'text-rose-400');
        foreach ($groups as $list_id => $g) backlog_group($list_id, hl_e($g['name']), $g['items'], $parts, $open_all, false);
        ?>
    </div>
    <p id="backlog_none" class="hidden py-6 text-center text-sm text-gray-400">Brak pasujących zadań</p>
    <?php else: ?>
    <div class="py-10 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-green-400/10 text-green-400"><?=hl_icon('check', 'size-7', '2')?></div>
        <h3 class="mt-3 text-sm font-semibold">Wszystko już zaplanowane</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nie masz zadań bez planu na swoich listach.</p>
    </div>
    <?php endif; ?>

    <button onclick="popupBacklogOpenClose()" class="mt-6 active:scale-95 duration-150 flex w-full justify-center cursor-pointer rounded-2xl bg-[#3d3d3d] dark:bg-white/10 font-medium px-4 py-2 text-sm text-white hover:bg-green-400">Gotowe</button>
</div>
<script>
(function () {
    var tpl = document.getElementById('backlog_actions_tpl');
    document.querySelectorAll('#backlog_groups .backlog-actions').forEach(function (box) {
        box.appendChild(tpl.content.cloneNode(true));
    });
    var groups = document.getElementById('backlog_groups');
    if (groups) groups.addEventListener('click', function (e) {
        var btn = e.target.closest('.bl-btn');
        if (btn) backlogPlan(btn, btn.parentElement.dataset.id, btn.dataset.part);
    });
})();

(function () {
    // kolejność grup taka jak kolejność list w navbarze ("Wkrótce termin" zawsze pierwsze)
    var container = document.getElementById('backlog_groups');
    var order = null;
    try { order = JSON.parse(localStorage.getItem(storageKey)); } catch (e) {}
    if (!container || !Array.isArray(order)) return;
    order.forEach(function (id) {
        var g = container.querySelector('.list_' + id);
        if (g) container.appendChild(g);
    });
})();

function backlogToggleGroup(group, open) {
    if (open === undefined) open = group.dataset.open !== '1';
    group.dataset.open = open ? '1' : '0';
    group.querySelector('.backlog-items').classList.toggle('hidden', !open);
    group.querySelector('.backlog-chevron').classList.toggle('rotate-90', open);
}

function backlogFilter(q) {
    q = q.trim().toLowerCase();
    var any = false;
    document.querySelectorAll('#backlog_groups .backlog-group').forEach(function (g) {
        var visible = 0;
        g.querySelectorAll('.backlog-row').forEach(function (row) {
            var show = !q || row.dataset.title.indexOf(q) !== -1;
            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });
        g.classList.toggle('hidden', visible === 0);
        // przy wyszukiwaniu rozwijamy grupy z trafieniami
        if (q && visible) backlogToggleGroup(g, true);
        if (visible) any = true;
    });
    document.getElementById('backlog_none').classList.toggle('hidden', any);
}

function backlogPlan(btn, id, part) {
    var row = btn.closest('.backlog-row');
    var fd = new FormData();
    fd.append('element_id', id);
    fd.append('action', 'plan');
    fd.append('date', '<?=$date?>');
    fd.append('day_part', part);
    row.style.opacity = '0.5';
    hlPost('scripts/planner/plan.php', fd)
        .then(function (list) {
            var res = list[0] || {};
            row.style.opacity = '';
            if (res.status !== 'success') {
                showAlert(res.status === 'warning' ? 'warning' : 'error', res.message || 'Nieznany status odpowiedzi');
                return;
            }
            row.querySelector('.backlog-actions').innerHTML = '<span class="flex items-center gap-1 pr-2 text-xs font-medium text-green-500"><?=hl_icon('check', 'size-4', '2.5')?>' + res.message.replace('Zaplanowano: ', '') + '</span>';
            setTimeout(function () {
                row.style.maxHeight = row.offsetHeight + 'px';
                requestAnimationFrame(function () { row.classList.add('feed-removing'); });
                setTimeout(function () {
                    var g = row.closest('.backlog-group');
                    row.remove();
                    if (g) {
                        var left = g.querySelectorAll('.backlog-row').length;
                        if (!left) g.remove();
                        else g.querySelector('.backlog-count').textContent = left;
                    }
                }, 380);
            }, 700);
            hlDebouncedReload();
        })
        .catch(function () {
            row.style.opacity = '';
            showAlert('error', 'Wystąpił problem połączenia z serwerem');
        });
}
</script>
