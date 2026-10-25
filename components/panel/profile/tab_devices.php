<?php if (!isset($acc)) exit; // część account.php ?>
<p class="<?=$acc_title?>">Urządzenia</p>
<p class="<?=$acc_subtitle?>">Urządzenia z zaznaczonym „Zapamiętaj mnie” (logowanie ważne 30 dni od ostatniego użycia).</p>

<div class="<?=$acc_card?>">
    <div class="flex items-center justify-between gap-3">
        <p class="font-medium text-lg text-black dark:text-gray-200 font-[poppins]">Zapamiętane urządzenia</p>
        <?php if (count($acc_devices) > 1 || ($acc_devices && $acc_current_selector === null)): ?>
        <button type="button" onclick="accSessions('revoke_others')" class="flex-none text-xs font-medium px-3 py-1.5 rounded-full ring-1 ring-rose-300 text-rose-400 hover:bg-rose-400 hover:text-white active:scale-95 duration-150">Wyloguj pozostałe</button>
        <?php endif; ?>
    </div>
    <div class="mt-4 flex flex-col gap-2">
        <?php if (!$acc_devices): ?>
        <p class="text-sm text-gray-500 dark:text-gray-400">Brak zapamiętanych urządzeń.</p>
        <?php endif; ?>
        <?php foreach ($acc_devices as $d):
            $is_current = $d['selector'] === $acc_current_selector;
        ?>
        <div class="flex items-center gap-3 rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 p-3">
            <span class="flex-none rounded-full bg-white dark:bg-[#2c2c2c] p-2 text-gray-500 dark:text-gray-400"><?=$acc_icon($acc_tabs['devices'][1])?></span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate"><?=$acc_e($acc_device_name($d['user_agent']))?>
                    <?php if ($is_current): ?><span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">to urządzenie</span><?php endif; ?></p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Ostatnio: <?=$acc_e(date('d.m.Y H:i', strtotime($d['last_used_at'] ?? $d['created_at'])))?></p>
            </div>
            <?php if (!$is_current): ?>
            <button type="button" onclick="accSessions('revoke', <?=(int)$d['id']?>)" class="flex-none text-xs font-medium px-3 py-1.5 rounded-full ring-1 ring-gray-300 dark:ring-gray-600 text-gray-600 dark:text-gray-300 hover:bg-rose-400 hover:text-white hover:ring-rose-400 active:scale-95 duration-150">Wyloguj</button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function accSessions(action, id) {
    var data = new FormData();
    data.append('action', action);
    if (id) data.append('id', id);
    hlJson('scripts/users/sessions.php', data).then(function (r) {
        showAlert(r.status, r.message);
        if (r.status === 'success') accReload();
    });
}
</script>
