<?php
// Popup z lekkim widokiem miesiąca: ile zaplanowane / zrobione każdego dnia. id = YYYY-MM
session_start();
include __DIR__.'/../../../scripts/conn_db.php';
include __DIR__.'/../../../scripts/planner/planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$today = date('Y-m-d');
$ym = preg_match('/^\d{4}-\d{2}$/', $_GET['id'] ?? '') ? $_GET['id'] : date('Y-m');
$first = $ym.'-01';
$last = date('Y-m-t', strtotime($first));
$grid_from = hl_add_days($first, -((int)date('N', strtotime($first)) - 1));
$grid_to = hl_add_days($last, (7 - (int)date('N', strtotime($last))) % 7);
$counts = hl_day_counts($conn, $user_id, $grid_from, $grid_to, $today);
$prev = date('Y-m', strtotime($first.' -1 month'));
$next = date('Y-m', strtotime($first.' +1 month'));

$sum_total = 0; $sum_done = 0;
foreach ($counts as $d => $c) {
    if ($d < $first || $d > $last || $d > $today) continue;
    $sum_total += $c['total'];
    $sum_done += $c['done'];
}
?>
<div class="-mt-10 font-[poppins] p-1 text-gray-800 dark:text-gray-200">
    <div class="flex items-center gap-2 pr-10">
        <h1 class="text-lg font-semibold flex-1"><?=hl_month_name(date('n', strtotime($first)), false)?> <span class="text-gray-400 font-normal"><?=date('Y', strtotime($first))?></span></h1>
        <button type="button" onclick="monthLoad('<?=$prev?>')" class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10 active:scale-90 duration-150"><?=hl_icon('left', 'size-5', '2')?></button>
        <button type="button" onclick="monthLoad('<?=$next?>')" class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10 active:scale-90 duration-150"><?=hl_icon('right', 'size-5', '2')?></button>
    </div>

    <div class="mt-4 grid grid-cols-7 gap-1 text-center text-[10px] uppercase tracking-wide text-gray-400">
        <span>Pn</span><span>Wt</span><span>Śr</span><span>Cz</span><span>Pt</span><span>Sb</span><span>Nd</span>
    </div>
    <div class="mt-1 grid grid-cols-7 gap-1">
        <?php foreach ($counts as $d => $c):
            $in_month = $d >= $first && $d <= $last;
            $is_today = $d === $today;
            $full = $c['total'] > 0 && $c['done'] === $c['total'] && $d <= $today;
            $cls = $is_today ? 'ring-2 ring-inset ring-green-400 text-green-500 font-semibold' : 'text-gray-700 dark:text-gray-300';
            if ($full) $cls .= ' bg-green-400/10';
            if (!$in_month) $cls .= ' opacity-30';
            elseif ($d < $today) $cls .= ' opacity-80';
            $shown = min($c['total'], 4);
            $done_shown = $c['total'] ? (int)round($c['done'] / $c['total'] * $shown) : 0;
        ?>
        <button type="button" onclick="popupMonthOpenClose(); openDetailTab('today', 'list=today&date=<?=$d?>')" class="aspect-square flex flex-col items-center justify-center gap-1 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 active:scale-90 duration-150 <?=$cls?>">
            <span class="text-sm leading-none"><?=(int)date('j', strtotime($d))?></span>
            <span class="flex gap-0.5 h-1">
                <?php for ($i = 0; $i < $shown; $i++): ?>
                <span class="h-1 w-1 rounded-full <?=$i < $done_shown ? 'bg-green-400' : 'bg-gray-300 dark:bg-gray-500'?>"></span>
                <?php endfor; ?>
            </span>
        </button>
        <?php endforeach; ?>
    </div>

    <?php if ($sum_total): ?>
    <div class="mt-4 flex items-center gap-3 rounded-2xl bg-gray-50 dark:bg-white/5 px-4 py-3">
        <div class="flex-1">
            <p class="text-xs text-gray-400">Zrobione w tym miesiącu (do dziś)</p>
            <div class="mt-1.5 h-1.5 rounded-full bg-gray-200 dark:bg-white/10 overflow-hidden">
                <div class="h-full rounded-full bg-green-400" style="width:<?=round($sum_done / $sum_total * 100)?>%"></div>
            </div>
        </div>
        <span class="text-sm font-semibold"><?=$sum_done?>/<?=$sum_total?></span>
    </div>
    <?php endif; ?>
</div>
<script>
function monthLoad(ym) {
    fetch('components/panel/dashboard/month.php?id=' + ym)
        .then(r => r.text())
        .then(data => {
            const doc = new DOMParser().parseFromString(data, 'text/html');
            doc.querySelectorAll('script').forEach(s => s.remove());
            document.getElementById('pupupMonthOutput').innerHTML = doc.body.innerHTML;
        });
}
</script>
