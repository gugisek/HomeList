<?php if (!isset($acc)) exit; // część account.php
require_once __DIR__ . '/../../../scripts/users/text_scale_lib.php';
$acc_scale = isset(HL_TEXT_SCALES[(int)$acc['text_scale']]) ? (int)$acc['text_scale'] : 100;
$acc_scale_on = 'bg-green-400 text-white ring-green-400';
$acc_scale_off = 'bg-gray-50 dark:bg-[#3d3d3d]/60 text-gray-700 dark:text-gray-300 ring-black/5 dark:ring-white/10 hover:bg-gray-100 dark:hover:bg-white/5';
?>
<p class="<?=$acc_title?>">Wygląd</p>
<p class="<?=$acc_subtitle?>">Rozmiar tekstu w całej aplikacji – na każdym urządzeniu, na którym się zalogujesz.</p>

<div id="acc_text_scale" data-on="<?=$acc_scale_on?>" data-off="<?=$acc_scale_off?>" class="<?=$acc_card?>">
    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Rozmiar tekstu</p>

    <!-- podgląd: zwykłe klasy text-*, więc zmienia się razem z resztą aplikacji -->
    <div class="mt-3 rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 p-4">
        <p class="text-11px font-semibold uppercase tracking-wider text-gray-400">Rano</p>
        <p class="mt-1 text-base font-medium text-gray-900 dark:text-gray-100">Kupić mleko i pieczywo</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Zakupy · do końca dnia</p>
    </div>

    <!-- litery A mają stały rozmiar (style), żeby skala przycisków nie skakała przy zmianie -->
    <div class="mt-4 grid grid-cols-5 gap-2" role="radiogroup" aria-label="Rozmiar tekstu">
        <?php $i = 0; foreach (HL_TEXT_SCALES as $v => $label): ?>
        <button type="button" role="radio" aria-checked="<?=$v === $acc_scale ? 'true' : 'false'?>" aria-label="<?=$label?>" title="<?=$label?>"
                data-scale="<?=$v?>" data-label="<?=$label?>" onclick="accTextScale(<?=$v?>)"
                class="flex items-end justify-center rounded-2xl ring-1 h-14 pb-2.5 font-semibold active:scale-95 duration-150 <?=$v === $acc_scale ? $acc_scale_on : $acc_scale_off?>">
            <span style="font-size: <?=14 + $i * 4?>px; line-height: 1">A</span>
        </button>
        <?php $i++; endforeach; ?>
    </div>
    <p class="mt-3 text-center text-sm text-gray-500 dark:text-gray-400"><span id="acc_text_scale_label"><?=HL_TEXT_SCALES[$acc_scale]?></span></p>
    <p class="hl-hint">Powiększa tekst – układ i ikony zostają bez zmian. Zmiana zapisuje się od razu.</p>
</div>

<script>
// funkcje (nie const/let) – skrypt może zostać wykonany ponownie przy przeładowaniu podstrony
function accTextScaleMark(scale) {
    var box = document.getElementById('acc_text_scale');
    if (!box) return;
    box.querySelectorAll('[data-scale]').forEach(function (b) {
        var on = +b.dataset.scale === scale;
        b.setAttribute('aria-checked', on ? 'true' : 'false');
        b.classList.remove.apply(b.classList, (on ? box.dataset.off : box.dataset.on).split(' '));
        b.classList.add.apply(b.classList, (on ? box.dataset.on : box.dataset.off).split(' '));
        if (on) document.getElementById('acc_text_scale_label').textContent = b.dataset.label;
    });
    document.documentElement.style.setProperty('--hl-fs', String(scale / 100));
}
function accTextScale(scale) {
    var box = document.getElementById('acc_text_scale');
    var prev = box.querySelector('[aria-checked="true"]');
    prev = prev ? +prev.dataset.scale : 100;
    if (scale === prev) return;
    accTextScaleMark(scale); // podgląd od razu, przy błędzie wracamy do poprzedniego
    var fd = new FormData();
    fd.append('scale', scale);
    hlJson('scripts/users/text_scale.php', fd).then(function (r) {
        showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
        if (r.status !== 'success') accTextScaleMark(prev);
    });
}
</script>
