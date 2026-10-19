<?php
// Szczegóły rutyny – includowane z element_details.php ($row = wiersz elementu + list_type, list_name)
if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = (int)$_SESSION['login_id'];
$today = date('Y-m-d');
$parts = hl_parts();
$rid = (int)$row['id'];
$done_map = hl_routine_done_map($conn, [$row], $user_id, hl_add_days($today, -400), $today);
$done_set = $done_map[$rid];
$streak = hl_streak($row, $done_set, $today);
$occurs_today = hl_routine_occurs($row, $today);
$done_today = isset($done_set[$today]);
$part = (int)$row['day_part'];

// kto odhaczył dziś (przy rutynie wspólnej)
$done_by = '';
if ((int)$row['shared_done'] === 1 && $done_today) {
    $who = mysqli_fetch_assoc(mysqli_query($conn, "SELECT users.name FROM routine_done JOIN users ON users.id=routine_done.user_id WHERE routine_done.element_id='$rid' AND routine_done.done_date='$today' ORDER BY routine_done.done_at ASC LIMIT 1"));
    if ($who) $done_by = $who['name'];
}

// ostatnie 28 dni
$days = [];
$scheduled = 0; $completed = 0;
for ($i = 27; $i >= 0; $i--) {
    $d = hl_add_days($today, -$i);
    $occ = hl_routine_occurs($row, $d);
    $state = !$occ ? 'off' : (isset($done_set[$d]) ? 'done' : ($d === $today ? 'today' : 'missed'));
    if ($occ && $d !== $today) { $scheduled++; if (isset($done_set[$d])) $completed++; }
    $days[] = ['date' => $d, 'state' => $state];
}
$chip = 'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-300';
$description = str_replace("\r", '', nl2br(hl_e($row['description'])));
?>
<div class="-mt-10 font-[poppins] p-1 text-gray-800 dark:text-gray-200">
    <div id="element_loading"></div>
    <h1 class="font-[poppins] font-medium pr-8 text-gray-800 dark:text-gray-200"><?=hl_e($row['title'])?></h1>
    <div class="mt-2 flex flex-wrap gap-1.5">
        <span class="<?=$chip?>"><?=hl_icon('repeat', 'size-3.5')?><?=hl_e(hl_routine_summary($row))?></span>
        <span class="<?=$chip?>"><span class="<?=$parts[$part]['color']?>"><?=hl_icon($parts[$part]['icon'], 'size-3.5')?></span><?=$parts[$part]['label']?></span>
        <?php if (!empty($row['plan_time'])): ?><span class="<?=$chip?>"><?=hl_icon('clock', 'size-3.5')?><?=substr($row['plan_time'], 0, 5)?></span><?php endif; ?>
        <span class="<?=$chip?>"><?=hl_icon((int)$row['shared_done'] === 1 ? 'users' : 'user', 'size-3.5')?><?=(int)$row['shared_done'] === 1 ? 'Wspólne odhaczanie' : 'Każdy osobno'?></span>
    </div>

    <?php if (trim($row['description']) !== ''): ?>
    <p class="pt-4 text-wrap text-gray-800 dark:text-gray-300"><?=$description?></p>
    <?php endif; ?>

    <div class="mt-5 rounded-2xl bg-gray-50 dark:bg-[#2c2c2c] p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl <?=$streak ? 'bg-orange-400/15 text-orange-400' : 'bg-gray-200/70 dark:bg-white/5 text-gray-400'?>"><?=hl_icon('fire', 'size-6')?></div>
            <div class="flex-1">
                <p class="text-lg font-semibold leading-tight"><?=$streak?> <span class="text-sm font-normal text-gray-500 dark:text-gray-400">z rzędu</span></p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Ostatnie 4 tygodnie: <?=$completed?>/<?=$scheduled?> zrobione</p>
            </div>
        </div>
        <div class="mt-3 grid gap-1" style="grid-template-columns: repeat(14, minmax(0, 1fr));">
            <?php foreach ($days as $d):
                $c = ['done' => 'bg-green-400', 'missed' => 'bg-gray-300 dark:bg-gray-600', 'today' => 'ring-2 ring-inset ring-green-400/70', 'off' => 'bg-gray-200/40 dark:bg-white/5'][$d['state']];
            ?>
            <span title="<?=date('d.m', strtotime($d['date']))?>" class="aspect-square rounded-[5px] <?=$c?>"></span>
            <?php endforeach; ?>
        </div>
    </div>

    <a class="mt-5 text-xs flex items-center justify-center mb-4 w-full text-gray-600 dark:text-gray-400">Lista <?=hl_e($row['list_name'])?> · dodano <?=date_format(date_create($row['create_date']), "d.m.Y")?> przez <?=$row['name']?> <?=$row['sur_name']?></a>

    <div class="flex gap-2">
        <button onclick="popupDetailsElementOpenClose(); openPopupEditElement(`<?=$rid?>`)" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" title="Edytuj" class="active:scale-95 items-center duration-150 inline-flex justify-center rounded-2xl px-3 py-2 hover:scale-110 font-medium text-[#3d3d3d] dark:text-gray-200 shadow-sm hover:bg-[#3d3d3d] hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" />
            </svg>
        </button>
        <?php if (!$occurs_today): ?>
        <span class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-2 text-sm font-medium bg-gray-100 dark:bg-[#3d3d3d] text-gray-400">Dziś wolne od tej rutyny</span>
        <?php elseif ($done_today): ?>
        <button onclick="routineDetailsToggle(<?=$rid?>, 0)" class="active:scale-95 items-center gap-2 duration-150 inline-flex w-full justify-center rounded-2xl hover:bg-gray-300 dark:hover:bg-[#4a4a4a] px-4 py-2 hover:scale-105 font-medium text-sm shadow-sm bg-gray-200 dark:bg-[#3d3d3d] dark:text-gray-300 text-green-500">
            <?=hl_icon('check', 'size-5', '2')?>Zrobione dziś<?=$done_by ? ' ('.hl_e($done_by).')' : ''?> · cofnij
        </button>
        <?php else: ?>
        <button onclick="routineDetailsToggle(<?=$rid?>, 1)" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" class="active:scale-95 items-center gap-2 duration-150 inline-flex w-full justify-center rounded-2xl bg-green-400 dark:bg-green-400/20 dark:text-green-400 px-4 py-2 hover:scale-105 font-medium text-white shadow-sm text-sm hover:shadow-xl hover:bg-gray-200 hover:text-green-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            Zrobione dziś!
        </button>
        <?php endif; ?>
    </div>
</div>
<script>
function routineDetailsToggle(id, done) {
    const fd = new FormData();
    fd.append('type', 'routine');
    fd.append('id', id);
    fd.append('date', '<?=$today?>');
    fd.append('done', done ? '1' : '0');
    hlPost('scripts/planner/toggle.php', fd)
        .then(list => {
            const res = list[0] || {};
            if (res.status === 'success') {
                showAlert('success', res.message);
                popupDetailsElementOpenClose();
                refreshCurrentTab('restore_scroll');
            } else {
                showAlert(res.status === 'warning' ? 'warning' : 'error', res.message || 'Nieznany status odpowiedzi');
            }
        })
        .catch(() => showAlert('error', 'Wystąpił problem połączenia z serwerem'));
}
</script>
