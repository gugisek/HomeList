<?php
// Popup szybkiego dodania zadania prosto do planu. id = YYYY-MM-DD, opcjonalnie &part=N
session_start();
include __DIR__.'/../../../scripts/conn_db.php';
include __DIR__.'/../../../scripts/planner/planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$today = date('Y-m-d');
$date = hl_valid_date($_GET['id'] ?? '') ? $_GET['id'] : $today;
$parts = hl_parts();
$sel_part = isset($_GET['part']) && array_key_exists((int)$_GET['part'], $parts) ? (int)$_GET['part'] : hl_default_part($date, $today);

$ids = hl_in(hl_user_list_ids($conn, $user_id));
$lists = [];
$result = mysqli_query($conn, "SELECT id, full_name FROM lists WHERE id IN ($ids) AND list_type='normal' ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($result)) $lists[] = $row;

$day_label = hl_relative_day($date, $today);
if (!in_array($day_label, ['Dziś', 'Jutro', 'Wczoraj'])) $day_label .= ' '.date('d.m', strtotime($date));
$label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 mt-5';
?>
<div id="today_add_loading"></div>
<div class="-mt-10 font-[poppins] p-1 text-gray-800 dark:text-gray-200">
    <input type="hidden" id="today_add_date" value="<?=$date?>">
    <input type="hidden" id="today_add_part" value="<?=$sel_part?>">
    <input type="hidden" id="today_add_list" value="">

    <h1 class="text-lg font-semibold pr-8">Nowe zadanie na: <span class="text-green-400"><?=$day_label?></span></h1>

    <?php if (!$lists): ?>
    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Najpierw utwórz zwykłą listę zadań – nowe zadanie musi do jakiejś należeć.</p>
    <?php else: ?>
    <label for="today_add_title" class="<?=$label?>">Nazwa zadania <span class="text-red-400">*</span></label>
    <input id="today_add_title" type="text" maxlength="500" placeholder="Co do zrobienia?" onkeydown="if(event.key==='Enter'){todayAddSave();}" class="w-full rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm text-gray-800 dark:text-gray-300 font-medium placeholder-gray-400">

    <label class="<?=$label?>">Lista</label>
    <div id="today_add_lists" class="no-scrollbar flex gap-1.5 overflow-x-auto -mx-1 px-1 py-1">
        <?php foreach ($lists as $l): ?>
        <button type="button" data-list="<?=(int)$l['id']?>" onclick="todayAddList(this)" class="<?=hl_chip_class(false)?> list_<?=(int)$l['id']?> flex-none whitespace-nowrap px-3.5 py-1.5 rounded-full text-sm font-medium active:scale-95 duration-150"><?=hl_e($l['full_name'])?></button>
        <?php endforeach; ?>
    </div>

    <label class="<?=$label?>">Pora dnia</label>
    <div class="grid grid-cols-4 gap-2">
        <?php foreach ($parts as $p => $info): ?>
        <button type="button" onclick="todayAddPart(this, <?=$p?>)" class="<?=hl_chip_class($p === $sel_part)?> flex flex-col items-center gap-1 py-2.5 rounded-2xl active:scale-95 duration-150">
            <span class="<?=$info['color']?>"><?=hl_icon($info['icon'], 'size-5')?></span>
            <span class="text-[11px] font-medium"><?=$info['label']?></span>
        </button>
        <?php endforeach; ?>
    </div>

    <label for="today_add_time" class="<?=$label?>">Godzina <span class="font-normal text-gray-400">(opcjonalnie)</span></label>
    <input id="today_add_time" type="time" class="w-full cursor-pointer rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-300">

    <div class="mt-6 flex flex-row-reverse gap-2">
        <button onclick="todayAddSave()" class="active:scale-95 duration-150 flex w-full justify-center shadow-xl cursor-pointer rounded-2xl bg-green-400 dark:bg-green-400/20 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-white ring-1 font-medium ring-green-400 px-4 py-2 text-sm text-white items-center gap-1 hover:bg-white hover:text-green-400">
            <?=hl_icon('check', 'size-5', '1.8')?>Dodaj do planu
        </button>
        <button onclick="popupTodayAddOpenClose()" class="active:scale-95 duration-150 flex w-full justify-center cursor-pointer rounded-2xl font-medium ring-rose-400 ring-1 px-4 py-2 text-sm text-rose-400 items-center gap-1 hover:bg-rose-400 hover:text-white">
            Anuluj
        </button>
    </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var box = document.getElementById('today_add_lists');
    if (!box) return;
    // kolejność jak w navbarze, domyślnie ostatnio używana lista
    var order = null;
    try { order = JSON.parse(localStorage.getItem(storageKey)); } catch (e) {}
    if (Array.isArray(order)) order.slice().reverse().forEach(function (id) {
        var b = box.querySelector('.list_' + id);
        if (b) box.prepend(b);
    });
    var last = null;
    try { last = localStorage.getItem(storageKey + '_quick_list'); } catch (e) {}
    var pick = (last && box.querySelector('[data-list="' + last + '"]')) || box.querySelector('[data-list]');
    if (pick) todayAddList(pick);
    setTimeout(function () { document.getElementById('today_add_title').focus(); }, 250);
})();

function todayAddList(el) {
    hlChipSelect(el);
    document.getElementById('today_add_list').value = el.dataset.list;
    el.scrollIntoView({ block: 'nearest', inline: 'nearest' });
}
function todayAddPart(el, part) {
    hlChipSelect(el);
    document.getElementById('today_add_part').value = part;
}
function todayAddSave() {
    var list = document.getElementById('today_add_list').value;
    var fd = new FormData();
    fd.append('title', document.getElementById('today_add_title').value);
    fd.append('list_id', list);
    fd.append('date', document.getElementById('today_add_date').value);
    fd.append('day_part', document.getElementById('today_add_part').value);
    fd.append('plan_time', document.getElementById('today_add_time').value);
    hlPost('scripts/planner/quick_add.php', fd)
        .then(function (res) {
            res = res[0] || {};
            if (res.status === 'success') {
                try { localStorage.setItem(storageKey + '_quick_list', list); } catch (e) {}
                showAlert('success', res.message);
                popupTodayAddOpenClose();
                reloadTabSilently();
            } else {
                showAlert(res.status === 'warning' ? 'warning' : 'error', res.message || 'Nieznany status odpowiedzi');
            }
        })
        .catch(function () { showAlert('error', 'Wystąpił problem połączenia z serwerem'); });
}
</script>
