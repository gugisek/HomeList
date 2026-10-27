<?php if (!isset($acc)) exit; // część account.php
require_once __DIR__ . '/../../../scripts/food/food_lib.php';
$acc_food = hl_food_settings($conn, $acc_id);
$acc_food_goal = hl_food_goal($conn, $acc_id, date('Y-m-d'));
$acc_row = 'flex items-center justify-between gap-3 rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 p-3 cursor-pointer';
?>
<p class="<?=$acc_title?>">Jedzenie</p>
<p class="<?=$acc_subtitle?>">Licznik kalorii: które posiłki pokazywać i czy widać go na planie dnia.</p>

<form id="food-settings" onsubmit="foodSettingsSave(event)" class="<?=$acc_card?>">
    <label class="<?=$acc_row?>">
        <span class="min-w-0">
            <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">Licznik kalorii</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">Po wyłączeniu dziennik zostaje zapisany – wrócisz do niego po ponownym włączeniu</span>
        </span>
        <input type="checkbox" name="enabled" <?=$acc_food['enabled'] ? 'checked' : ''?> class="size-5 flex-none">
    </label>
    <label class="<?=$acc_row?> mt-2">
        <span class="min-w-0">
            <span class="block text-sm text-gray-800 dark:text-gray-200">Kalorie na planie dnia</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">Mały pierścień „zostało kcal” w nagłówku zakładki Dziś</span>
        </span>
        <input type="checkbox" name="show_on_today" <?=$acc_food['show_on_today'] ? 'checked' : ''?> class="size-5 flex-none">
    </label>

    <p class="mt-6 ml-1 text-sm font-medium text-gray-900 dark:text-gray-200">Posiłki</p>
    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
        <?php foreach (hl_food_meals() as $m => $label): ?>
        <label class="<?=$acc_row?>">
            <span class="text-sm text-gray-800 dark:text-gray-200"><?=$label?></span>
            <input type="checkbox" name="meal" value="<?=$m?>" <?=in_array($m, $acc_food['meals'], true) ? 'checked' : ''?> class="size-5 flex-none">
        </label>
        <?php endforeach; ?>
    </div>
    <p class="hl-hint">Wyłączony posiłek znika z dnia, chyba że coś w nim zapisano.</p>

    <div class="mt-6 flex flex-col sm:flex-row-reverse gap-2">
        <button class="<?=$acc_btn?>">Zapisz</button>
        <?php if ($acc_food['enabled']): ?>
        <button type="button" onclick="foodOpenSheet('dishes')" class="<?=$acc_btn_muted?>">Twoje dania</button>
        <button type="button" onclick="foodOpenSheet('goal')" class="<?=$acc_btn_muted?>"><?=$acc_food_goal ? 'Cel: ' . number_format($acc_food_goal['kcal'], 0, ',', ' ') . ' kcal – zmień' : 'Ustaw dzienny cel'?></button>
        <?php endif; ?>
    </div>
</form>

<script>
function foodSettingsSave(e) {
    e.preventDefault();
    var form = document.getElementById('food-settings'), fd = new FormData();
    fd.append('enabled', form.enabled.checked ? '1' : '0');
    fd.append('show_on_today', form.show_on_today.checked ? '1' : '0');
    fd.append('meals', [].slice.call(form.querySelectorAll('[name=meal]:checked')).map(function (i) { return i.value; }).join(','));
    hlJson('scripts/food/settings_save.php', fd).then(function (r) {
        showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
        if (r.status !== 'success') return;
        // ikona Jedzenia w pasku i pigułce zależy od włączenia modułu
        if (typeof refreshLists === 'function' && document.getElementById('lists')) refreshLists();
        if (typeof hlRailRefresh === 'function') hlRailRefresh();
        accReload();
    });
}
function foodOpenSheet(step) {
    // kalkulator celu i lista dań żyją w arkuszu zakładki Jedzenie
    sessionStorage.setItem('hl_food_open', step);
    if (typeof hlNav === 'function') hlNav('list=food');
}
</script>
