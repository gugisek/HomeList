<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Pola formularza rutyny – includowane w element_add.php i element_edit.php dla list typu 'routine'.
// Wymaga: $rf_prefix ('add' | 'edit'), $rf (wiersz elementu albo []), planner_lib.php
$P = $rf_prefix;
$rf_type = $rf['recurrence_type'] ?? 'daily';
$rf_days = hl_csv_ints($rf['recurrence_days'] ?? '');
$rf_week = $rf_type === 'weekly' ? $rf_days : [(int)date('N')];
$rf_month = $rf_type === 'monthly' ? $rf_days : [(int)date('j')];
$rf_interval = $rf_type === 'interval' ? max(1, (int)($rf['recurrence_days'] ?? 2)) : 2;
$rf_start = !empty($rf['recurrence_start']) && $rf['recurrence_start'] !== '0000-00-00' ? $rf['recurrence_start'] : date('Y-m-d');
$rf_part = isset($rf['day_part']) ? (int)$rf['day_part'] : 1;
$rf_time = !empty($rf['plan_time']) ? substr($rf['plan_time'], 0, 5) : '';
$rf_shared = (int)($rf['shared_done'] ?? 0);
$rf_label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 mt-4';
$rf_input = 'rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-3 py-2 text-sm font-medium text-gray-800 dark:text-gray-300';
$types = ['daily' => 'Codziennie', 'weekly' => 'Dni tyg.', 'interval' => 'Co X dni', 'monthly' => 'Dni mies.'];
$week_names = [1 => 'Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'Sb', 'Nd'];
?>
<input type="hidden" id="<?=$P?>_element_recurrence_type" value="<?=$rf_type?>">
<input type="hidden" id="<?=$P?>_element_day_part" value="<?=$rf_part?>">
<input type="hidden" id="<?=$P?>_element_shared_done" value="<?=$rf_shared?>">

<label class="<?=$rf_label?>">Powtarzanie</label>
<div class="grid grid-cols-4 gap-1.5">
    <?php foreach ($types as $t => $name): ?>
    <button type="button" onclick="rfType(this, '<?=$P?>', '<?=$t?>')" class="<?=hl_chip_class($t === $rf_type)?> py-2 rounded-xl text-xs font-medium active:scale-95 duration-150"><?=$name?></button>
    <?php endforeach; ?>
</div>

<div id="<?=$P?>_rf_weekly" class="mt-2 flex flex-wrap gap-1.5 <?=$rf_type === 'weekly' ? '' : 'hidden'?>">
    <?php foreach ($week_names as $n => $name): $on = in_array($n, $rf_week, true); ?>
    <button type="button" data-day="<?=$n?>" data-on="<?=$on ? '1' : '0'?>" onclick="rfToggle(this)" class="<?=hl_chip_class($on)?> h-9 w-9 rounded-full text-xs font-medium active:scale-90 duration-150"><?=$name?></button>
    <?php endforeach; ?>
</div>

<div id="<?=$P?>_rf_interval" class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-700 dark:text-gray-300 <?=$rf_type === 'interval' ? '' : 'hidden'?>">
    Co <input id="<?=$P?>_element_interval" type="number" min="1" max="365" value="<?=$rf_interval?>" class="w-20 <?=$rf_input?>"> dni, licząc od
    <input id="<?=$P?>_element_recurrence_start" type="date" value="<?=$rf_start?>" class="cursor-pointer <?=$rf_input?>">
</div>

<div id="<?=$P?>_rf_monthly" class="mt-2 grid grid-cols-7 gap-1 <?=$rf_type === 'monthly' ? '' : 'hidden'?>">
    <?php for ($n = 1; $n <= 31; $n++): $on = in_array($n, $rf_month, true); ?>
    <button type="button" data-day="<?=$n?>" data-on="<?=$on ? '1' : '0'?>" onclick="rfToggle(this)" class="<?=hl_chip_class($on)?> h-8 rounded-lg text-xs font-medium active:scale-90 duration-150"><?=$n?></button>
    <?php endfor; ?>
    <p class="col-span-7 mt-1 text-[11px] text-gray-400">Dni, których brakuje w krótszym miesiącu (np. 31.), przypadają na jego ostatni dzień.</p>
</div>

<label class="<?=$rf_label?>">Pora dnia</label>
<div class="grid grid-cols-4 gap-2">
    <?php foreach (hl_parts() as $p => $info): ?>
    <button type="button" onclick="rfPart(this, '<?=$P?>', <?=$p?>)" class="<?=hl_chip_class($p === $rf_part)?> flex flex-col items-center gap-1 py-2.5 rounded-2xl active:scale-95 duration-150">
        <span class="<?=$info['color']?>"><?=hl_icon($info['icon'], 'size-5')?></span>
        <span class="text-[11px] font-medium"><?=$info['label']?></span>
    </button>
    <?php endforeach; ?>
</div>

<label for="<?=$P?>_element_plan_time" class="<?=$rf_label?>">Godzina <span class="font-normal text-gray-400">(opcjonalnie)</span></label>
<input id="<?=$P?>_element_plan_time" type="time" value="<?=$rf_time?>" class="w-full cursor-pointer <?=$rf_input?>">

<label class="<?=$rf_label?>">Odhaczanie</label>
<div class="grid grid-cols-2 gap-2">
    <button type="button" onclick="rfShared(this, '<?=$P?>', 0)" class="<?=hl_chip_class($rf_shared === 0)?> text-left rounded-2xl p-3 active:scale-95 duration-150">
        <?=hl_icon('user', 'size-5')?>
        <span class="mt-1.5 block text-sm font-semibold">Każdy osobno</span>
        <span class="block text-xs opacity-60">Każdy ma własne odhaczenie i serię</span>
    </button>
    <button type="button" onclick="rfShared(this, '<?=$P?>', 1)" class="<?=hl_chip_class($rf_shared === 1)?> text-left rounded-2xl p-3 active:scale-95 duration-150">
        <?=hl_icon('users', 'size-5')?>
        <span class="mt-1.5 block text-sm font-semibold">Wspólnie</span>
        <span class="block text-xs opacity-60">Ktokolwiek odhaczy – zrobione u wszystkich</span>
    </button>
</div>

<script>
function rfType(el, p, type) {
    hlChipSelect(el);
    document.getElementById(p + '_element_recurrence_type').value = type;
    ['weekly', 'interval', 'monthly'].forEach(function (t) {
        document.getElementById(p + '_rf_' + t).classList.toggle('hidden', t !== type);
    });
}
function rfToggle(el) {
    hlChip(el, el.dataset.on !== '1');
}
function rfPart(el, p, part) {
    hlChipSelect(el);
    document.getElementById(p + '_element_day_part').value = part;
}
function rfShared(el, p, value) {
    hlChipSelect(el);
    document.getElementById(p + '_element_shared_done').value = value;
}
// dopisuje pola rutyny do FormData, zwraca komunikat błędu albo null
function rfCollect(p, fd) {
    var val = function (id) { return document.getElementById(p + '_element_' + id).value; };
    var type = val('recurrence_type');
    var days = '';
    if (type === 'weekly' || type === 'monthly') {
        days = Array.from(document.querySelectorAll('#' + p + '_rf_' + type + ' [data-on="1"]')).map(function (e) { return e.dataset.day; }).join(',');
        if (!days) return type === 'weekly' ? 'Wybierz przynajmniej jeden dzień tygodnia' : 'Wybierz przynajmniej jeden dzień miesiąca';
    }
    if (type === 'interval') {
        var n = parseInt(val('interval'), 10);
        if (!(n >= 1)) return 'Podaj co ile dni ma się powtarzać';
        days = String(n);
        fd.append('recurrence_start', val('recurrence_start'));
    }
    fd.append('recurrence_type', type);
    fd.append('recurrence_days', days);
    fd.append('day_part', val('day_part'));
    fd.append('plan_time', val('plan_time'));
    fd.append('shared_done', val('shared_done'));
    return null;
}
</script>
