<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Popup szybkiego dodania zadania prosto do planu. id = YYYY-MM-DD, opcjonalnie &part=N
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
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
if (!in_array($day_label, ['Dziś', 'Jutro', 'Wczoraj'])) $day_label .= ' '.hl_short_date($date);
$label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 mt-5';
?>
<div id="today_add_loading"></div>
<div class="-mt-10 font-[poppins] p-1 text-gray-800 dark:text-gray-200">
    <input type="hidden" id="today_add_date" value="<?=$date?>">
    <input type="hidden" id="today_add_part" value="<?=$sel_part?>">
    <input type="hidden" id="today_add_list" value="">

    <h1 class="hl-popup-title">Nowe zadanie na: <span class="text-green-400"><?=$day_label?></span></h1>

    <?php if (!$lists): ?>
    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Najpierw utwórz zwykłą listę zadań – nowe zadanie musi do jakiejś należeć.</p>
    <?php else: ?>
    <label for="today_add_title" class="<?=$label?>">Nazwa zadania <span class="text-red-400">*</span></label>
    <input id="today_add_title" type="text" maxlength="500" placeholder="Co do zrobienia?" onkeydown="if(event.key==='Enter'){todayAddSave();}" class="hl-input w-full">

    <!-- opis i link chowane domyślnie, żeby popup nie wymagał scrollowania -->
    <textarea id="today_add_desc" rows="2" maxlength="1000" placeholder="Opis zadania..." oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px'" class="hl-input hidden mt-2 w-full resize-none"></textarea>
    <div id="today_add_link_box" class="hidden relative mt-2">
        <input id="today_add_link" type="url" maxlength="2000" placeholder="https://..." onkeydown="if(event.key==='Enter'){todayAddSave();}" class="hl-input w-full pr-10">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-400"><?=hl_icon('link', 'size-4')?></div>
    </div>
    <div id="today_add_extras" class="mt-2 flex gap-1.5">
        <button type="button" onclick="todayAddExtra(this, 'today_add_desc', 'today_add_desc')" class="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100/80 dark:bg-[#3d3d3d] hover:text-green-400 active:scale-95 duration-150">
            <?=hl_icon('plus', 'size-3.5', '2')?>Opis
        </button>
        <button type="button" onclick="todayAddExtra(this, 'today_add_link_box', 'today_add_link')" class="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100/80 dark:bg-[#3d3d3d] hover:text-green-400 active:scale-95 duration-150">
            <?=hl_icon('plus', 'size-3.5', '2')?>Link
        </button>
    </div>

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
    <input id="today_add_time" type="time" class="hl-input w-full cursor-pointer">

    <div class="mt-6 flex flex-row-reverse gap-2">
        <button onclick="todayAddSave()" class="hl-btn hl-btn-primary flex-1">
            <?=hl_icon('check', 'size-5', '1.8')?>Dodaj do planu
        </button>
        <button onclick="popupTodayAddOpenClose()" class="hl-btn hl-btn-ghost flex-1">
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
function todayAddExtra(btn, boxId, inputId) {
    document.getElementById(boxId).classList.remove('hidden');
    btn.remove();
    var extras = document.getElementById('today_add_extras');
    if (!extras.children.length) extras.remove();
    document.getElementById(inputId).focus();
}
function todayAddPart(el, part) {
    hlChipSelect(el);
    document.getElementById('today_add_part').value = part;
}
function todayAddSave() {
    var list = document.getElementById('today_add_list').value;
    var fd = new FormData();
    fd.append('title', document.getElementById('today_add_title').value);
    fd.append('description', document.getElementById('today_add_desc').value);
    fd.append('link_url', document.getElementById('today_add_link').value);
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
