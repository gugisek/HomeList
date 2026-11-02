<?php if (!isset($acc)) exit; // część account.php
$acc_row = 'flex items-center justify-between gap-3 rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 p-3 cursor-pointer';
?>
<p class="<?=$acc_title?>">Plan dnia</p>
<p class="<?=$acc_subtitle?>">Zakładka Dziś: zadania zaplanowane na dzień i rutyny podzielone na pory dnia.</p>

<form id="feed-settings" onsubmit="feedSettingsSave(event)" class="<?=$acc_card?>">
    <label class="<?=$acc_row?>">
        <span class="min-w-0">
            <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">Plan dnia</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">Po wyłączeniu zaplanowane zadania i rutyny zostają zapisane – wrócisz do nich po ponownym włączeniu</span>
        </span>
        <input type="checkbox" name="enabled" <?=$acc['feed_enabled'] ? 'checked' : ''?> class="size-5 flex-none">
    </label>
    <p class="hl-hint">Przypomnienia o planie dnia ustawisz w zakładce Powiadomienia.</p>

    <div class="mt-6 flex flex-col sm:flex-row-reverse gap-2">
        <button class="<?=$acc_btn?>">Zapisz</button>
    </div>
</form>

<script>
function feedSettingsSave(e) {
    e.preventDefault();
    var fd = new FormData();
    fd.append('enabled', document.getElementById('feed-settings').enabled.checked ? '1' : '0');
    hlJson('scripts/planner/feed_settings.php', fd).then(function (r) {
        showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
        if (r.status !== 'success') return;
        // ikona Dziś w pasku i pigułce zależy od włączenia planu dnia
        if (typeof refreshLists === 'function' && document.getElementById('lists')) refreshLists();
        if (typeof hlRailRefresh === 'function') hlRailRefresh();
        accReload();
    });
}
</script>
