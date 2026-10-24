<?php
// Popup "Zaplanuj": wybór dnia, pory dnia i opcjonalnej godziny dla zwykłego zadania
// id = id zadania, opcjonalnie &date=YYYY-MM-DD (domyślny dzień)
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
include __DIR__.'/../../../scripts/conn_db.php';
include __DIR__.'/../../../scripts/planner/planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$today = date('Y-m-d');
$el = hl_get_element($conn, $user_id, (int)($_GET['id'] ?? 0));
if (!$el || $el['list_type'] !== 'normal') {
    echo 'Nie znaleziono zadania.';
    exit;
}
$eid = (int)$el['id'];
$plan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT plan_date, day_part, plan_time FROM day_plan WHERE user_id='$user_id' AND element_id='$eid' LIMIT 1"));

$sel_date = $plan['plan_date'] ?? (hl_valid_date($_GET['date'] ?? '') ? $_GET['date'] : $today);
$sel_part = $plan ? (int)$plan['day_part'] : hl_default_part($sel_date, $today);
$sel_time = !empty($plan['plan_time']) ? substr($plan['plan_time'], 0, 5) : '';
$parts = hl_parts();
$quick_days = [];
for ($i = 0; $i < 7; $i++) $quick_days[] = hl_add_days($today, $i);
$custom = !in_array($sel_date, $quick_days, true);
$label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 mt-5';
?>
<div id="plan_sheet_loading"></div>
<div class="-mt-10 font-[poppins] p-1 text-gray-800 dark:text-gray-200">
    <input type="hidden" id="plan_sheet_id" value="<?=$eid?>">
    <input type="hidden" id="plan_sheet_date" value="<?=$sel_date?>">
    <input type="hidden" id="plan_sheet_part" value="<?=$sel_part?>">

    <h1 class="text-lg font-semibold pr-8">Zaplanuj</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 truncate pr-8"><?=hl_e($el['title'])?></p>
    <?php if ($plan): ?>
    <span class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-400/20">
        <?=hl_icon('calendar', 'size-3.5')?>Teraz: <?=hl_plan_label($plan['plan_date'], $plan['day_part'], $today)?>
    </span>
    <?php endif; ?>

    <label class="<?=$label?>">Dzień</label>
    <div class="no-scrollbar flex gap-1.5 overflow-x-auto -mx-1 px-1 py-1">
        <?php foreach ($quick_days as $d):
            $top = hl_relative_day($d, $today);
            if (!in_array($top, ['Dziś', 'Jutro'])) $top = hl_day_name($d, true);
        ?>
        <button type="button" onclick="planSheetDate(this, '<?=$d?>')" class="<?=hl_chip_class($d === $sel_date)?> flex-none flex flex-col items-center min-w-[3.5rem] px-2.5 py-2 rounded-2xl active:scale-95 duration-150">
            <span class="text-xs font-semibold"><?=$top?></span>
            <span class="text-[10px] opacity-60"><?=date('d.m', strtotime($d))?></span>
        </button>
        <?php endforeach; ?>
        <label class="<?=hl_chip_class($custom)?> relative flex-none flex flex-col items-center justify-center min-w-[3.5rem] px-2.5 py-2 rounded-2xl active:scale-95 duration-150 cursor-pointer">
            <?=hl_icon('calendar', 'size-4')?>
            <span id="plan_sheet_custom_label" class="text-[10px] opacity-60"><?=$custom ? date('d.m', strtotime($sel_date)) : 'Inny'?></span>
            <input type="date" value="<?=$sel_date?>" onchange="planSheetCustom(this)" class="absolute inset-0 opacity-0 cursor-pointer">
        </label>
    </div>

    <label class="<?=$label?>">Pora dnia</label>
    <div class="grid grid-cols-4 gap-2">
        <?php foreach ($parts as $p => $info): ?>
        <button type="button" onclick="planSheetPart(this, <?=$p?>)" class="<?=hl_chip_class($p === $sel_part)?> flex flex-col items-center gap-1 py-2.5 rounded-2xl active:scale-95 duration-150">
            <span class="<?=$info['color']?>"><?=hl_icon($info['icon'], 'size-5')?></span>
            <span class="text-[11px] font-medium"><?=$info['label']?></span>
        </button>
        <?php endforeach; ?>
    </div>

    <label for="plan_sheet_time" class="<?=$label?>">Godzina <span class="font-normal text-gray-400">(opcjonalnie)</span></label>
    <input id="plan_sheet_time" type="time" value="<?=$sel_time?>" class="w-full cursor-pointer rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-300">

    <div class="mt-6 flex flex-row-reverse gap-2">
        <button onclick="planSheetSave()" class="active:scale-95 duration-150 flex w-full justify-center shadow-xl cursor-pointer rounded-2xl bg-green-400 dark:bg-green-400/20 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-white ring-1 font-medium ring-green-400 px-4 py-2 text-sm text-white items-center gap-1 hover:bg-white hover:text-green-400">
            <?=hl_icon('check', 'size-5', '1.8')?>Zapisz
        </button>
        <?php if ($plan): ?>
        <button onclick="planSheetUnplan()" class="active:scale-95 duration-150 flex w-full justify-center cursor-pointer rounded-2xl font-medium ring-rose-400 ring-1 px-4 py-2 text-sm text-rose-400 items-center gap-1 hover:bg-rose-400 hover:text-white">
            Usuń z planu
        </button>
        <?php endif; ?>
    </div>
</div>
<script>
function planSheetDate(el, date) {
    hlChipSelect(el);
    document.getElementById('plan_sheet_date').value = date;
    document.getElementById('plan_sheet_custom_label').textContent = 'Inny';
}
function planSheetCustom(input) {
    if (!input.value) return;
    hlChipSelect(input.parentElement);
    document.getElementById('plan_sheet_date').value = input.value;
    document.getElementById('plan_sheet_custom_label').textContent = input.value.slice(8, 10) + '.' + input.value.slice(5, 7);
}
function planSheetPart(el, part) {
    hlChipSelect(el);
    document.getElementById('plan_sheet_part').value = part;
}
function planSheetSend(fd) {
    hlPost('scripts/planner/plan.php', fd)
        .then(list => {
            const res = list[0] || {};
            if (res.status === 'success') {
                showAlert('success', res.message);
                popupPlanSheetOpenClose();
                reloadTabSilently();
            } else {
                showAlert(res.status === 'warning' ? 'warning' : 'error', res.message || 'Nieznany status odpowiedzi');
            }
        })
        .catch(() => showAlert('error', 'Wystąpił problem połączenia z serwerem'));
}
function planSheetSave() {
    const fd = new FormData();
    fd.append('element_id', document.getElementById('plan_sheet_id').value);
    fd.append('action', 'plan');
    fd.append('date', document.getElementById('plan_sheet_date').value);
    fd.append('day_part', document.getElementById('plan_sheet_part').value);
    fd.append('plan_time', document.getElementById('plan_sheet_time').value);
    planSheetSend(fd);
}
function planSheetUnplan() {
    const fd = new FormData();
    fd.append('element_id', document.getElementById('plan_sheet_id').value);
    fd.append('action', 'unplan');
    planSheetSend(fd);
}
</script>
