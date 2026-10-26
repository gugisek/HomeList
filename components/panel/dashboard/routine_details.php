<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Szczegóły rutyny – includowane z element_details.php ($row = wiersz elementu + list_type, list_name)
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
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
$muted = (bool)mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM push_mute WHERE user_id='$user_id' AND element_id='$rid'"));

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
    <h1 class="hl-popup-title"><?=hl_e($row['title'])?></h1>
    <div class="mt-2 flex flex-wrap gap-1.5">
        <span class="<?=$chip?>"><?=hl_icon('repeat', 'size-3.5')?><?=hl_e(hl_routine_summary($row))?></span>
        <span class="<?=$chip?>"><span class="<?=$parts[$part]['color']?>"><?=hl_icon($parts[$part]['icon'], 'size-3.5')?></span><?=$parts[$part]['label']?></span>
        <?php if (!empty($row['plan_time'])): ?><span class="<?=$chip?>"><?=hl_icon('clock', 'size-3.5')?><?=substr($row['plan_time'], 0, 5)?></span><?php endif; ?>
        <span class="<?=$chip?>"><?=hl_icon((int)$row['shared_done'] === 1 ? 'users' : 'user', 'size-3.5')?><?=(int)$row['shared_done'] === 1 ? 'Wspólne odhaczanie' : 'Każdy osobno'?></span>
        <button id="routine-mute" onclick="routineMuteToggle(<?=$rid?>)" data-muted="<?=$muted ? 1 : 0?>" class="<?=$chip?> hover:bg-gray-200 dark:hover:bg-white/10 active:scale-95 duration-150"><?=$muted ? '🔕 Bez przypomnień' : '🔔 Przypominaj'?></button>
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
            <span title="<?=hl_short_date($d['date'])?>" class="aspect-square rounded-[5px] <?=$c?>"></span>
            <?php endforeach; ?>
        </div>
    </div>

    <a class="mt-5 text-xs flex items-center justify-center mb-4 w-full text-gray-600 dark:text-gray-400">Lista <?=hl_e($row['list_name'])?> · dodano <?=date_format(date_create($row['create_date']), "d.m.Y")?> przez <?=hl_e($row['name'].' '.$row['sur_name'])?></a>

    <div class="flex gap-2">
        <button onclick="popupDetailsElementOpenClose(); openPopupEditElement(`<?=$rid?>`)" title="Edytuj" class="hl-btn hl-btn-soft hl-btn-square flex-none"><?=hl_icon('pencil')?></button>
        <?php if (!$occurs_today): ?>
        <span class="hl-btn flex-1 bg-gray-100 dark:bg-white/5 text-gray-400 pointer-events-none">Dziś wolne od tej rutyny</span>
        <?php elseif ($done_today): ?>
        <button onclick="routineDetailsToggle(<?=$rid?>, 0)" class="hl-btn hl-btn-accent flex-1">
            <?=hl_icon('check', 'size-5', '2')?>Zrobione dziś<?=$done_by ? ' ('.hl_e($done_by).')' : ''?> · cofnij
        </button>
        <?php else: ?>
        <button onclick="routineDetailsToggle(<?=$rid?>, 1)" class="hl-btn hl-btn-green flex-1"><?=hl_icon('check', '', '2')?>
            Zrobione dziś!
        </button>
        <?php endif; ?>
    </div>
</div>
<script>
function routineMuteToggle(id) {
    const btn = document.getElementById('routine-mute');
    const mute = btn.dataset.muted !== '1';
    const fd = new FormData();
    fd.append('element_id', id);
    fd.append('mute', mute ? '1' : '0');
    hlPost('scripts/push/mute.php', fd)
        .then(list => {
            const res = list[0] || {};
            showAlert(res.status || 'error', res.message || 'Nieznany status odpowiedzi');
            if (res.status !== 'success') return;
            btn.dataset.muted = mute ? '1' : '0';
            btn.textContent = mute ? '🔕 Bez przypomnień' : '🔔 Przypominaj';
        })
        .catch(() => showAlert('error', 'Wystąpił problem połączenia z serwerem'));
}

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
