<?php if (!isset($acc)) exit; // część account.php
ob_start();
include __DIR__ . '/../../../version.php';
$acc_version = trim((string)ob_get_clean());
?>
<p class="<?=$acc_title?>">Zgłoś błąd</p>
<p class="<?=$acc_subtitle?>">Coś nie działa jak powinno? Opisz to – wiadomość trafi prosto do twórców HomeList.</p>

<form id="acc_report_form" onsubmit="accReportSend(event)" class="<?=$acc_card?>">
    <label for="acc_report_desc" class="<?=$acc_label?>">Co się stało?</label>
    <textarea name="description" id="acc_report_desc" rows="5" maxlength="2000" required oninput="document.getElementById('acc_report_count').textContent = this.value.length" placeholder="Np. po odhaczeniu rutyny wieczorem znika z listy Dziś…" class="<?=str_replace('rounded-xl', 'rounded-2xl', $acc_input)?> resize-none"></textarea>
    <p class="mt-1 pr-1 text-right text-[11px] text-gray-400"><span id="acc_report_count">0</span>/2000</p>

    <p class="<?=$acc_label?> mt-2">Zrzuty ekranu <span class="font-normal text-gray-400">(opcjonalnie, maks. 3)</span></p>
    <div id="acc_report_shots" class="grid grid-cols-3 sm:grid-cols-4 gap-2"></div>
    <input type="file" id="acc_report_file" accept="image/*" multiple class="hidden" onchange="accReportPick(this)">
    <p class="hidden md:block mt-2 pl-1 text-xs text-gray-400">Możesz też wkleić zrzut ze schowka (Ctrl+V).</p>

    <div class="mt-5 flex items-start gap-2 rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 p-3 text-xs text-gray-500 dark:text-gray-400">
        <?=$acc_icon('m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z', 'size-4 mt-px')?>
        <span>Razem z opisem wyślemy wersję aplikacji (<?=$acc_e($acc_version)?>), przeglądarkę i rozmiar ekranu – to pomaga szybciej znaleźć przyczynę.</span>
    </div>

    <div class="mt-5 flex justify-end">
        <button id="acc_report_btn" class="<?=$acc_btn?>">
            <?=$acc_icon('M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5')?>
            Wyślij zgłoszenie
        </button>
    </div>
</form>

<div id="acc_report_done" class="hidden <?=$acc_card?> flex flex-col items-center text-center py-10">
    <span class="rounded-full bg-green-50 dark:bg-green-400/10 p-4 text-green-500"><?=$acc_icon('m4.5 12.75 6 6 9-13.5', 'size-8')?></span>
    <p class="mt-4 font-medium text-xl text-black dark:text-gray-200 font-[poppins]">Dziękujemy!</p>
    <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">Zgłoszenie dotarło do twórców aplikacji. Nie odpisujemy na każde, ale każde czytamy.</p>
    <button type="button" onclick="accReportReset()" class="mt-6 <?=$acc_btn_muted?>">Zgłoś kolejny</button>
</div>

<script>
// stan w window (nie const/let) – skrypt może zostać wykonany ponownie przy przeładowaniu podstrony
window.accReportFiles = [];

function accReportRender() {
    var box = document.getElementById('acc_report_shots');
    if (!box) return;
    box.innerHTML = '';
    accReportFiles.forEach(function (f, i) {
        var tile = document.createElement('div');
        tile.className = 'relative aspect-square rounded-2xl overflow-hidden ring-1 ring-black/5 dark:ring-white/10 bg-gray-100 dark:bg-[#3d3d3d]';
        tile.innerHTML = '<img class="size-full object-cover" alt="">'
            + '<button type="button" title="Usuń" class="absolute top-1.5 right-1.5 rounded-full bg-black/60 p-1 text-white hover:bg-rose-500 active:scale-90 duration-150">'
            + '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>';
        tile.querySelector('img').src = f.url;
        tile.querySelector('button').onclick = function () { accReportRemove(i); };
        box.appendChild(tile);
    });
    if (accReportFiles.length < 3) {
        var add = document.createElement('button');
        add.type = 'button';
        add.onclick = function () { document.getElementById('acc_report_file').click(); };
        add.className = 'aspect-square flex flex-col items-center justify-center gap-1 rounded-2xl border-2 border-dashed border-gray-200 dark:border-white/10 text-gray-400 hover:border-green-400 hover:text-green-500 active:scale-95 duration-150';
        add.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>'
            + '<span class="text-[11px] font-medium">Dodaj</span>';
        box.appendChild(add);
    }
}

// zmniejszenie do 2000 px i JPEG – zrzuty z telefonu bywają po kilka MB, a przy okazji znikają metadane
function accReportShrink(file) {
    return new Promise(function (resolve) {
        var url = URL.createObjectURL(file);
        var img = new Image();
        img.onload = function () {
            var scale = Math.min(1, 2000 / Math.max(img.naturalWidth, img.naturalHeight));
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(img.naturalWidth * scale);
            canvas.height = Math.round(img.naturalHeight * scale);
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);
            canvas.toBlob(function (blob) { resolve(blob || file); }, 'image/jpeg', 0.85);
        };
        img.onerror = function () { URL.revokeObjectURL(url); resolve(null); };
        img.src = url;
    });
}

function accReportAdd(files) {
    files = Array.prototype.filter.call(files, function (f) { return f.type.indexOf('image/') === 0; });
    if (!files.length) return;
    if (accReportFiles.length + files.length > 3) showAlert('warning', 'Możesz dołączyć maksymalnie 3 zrzuty ekranu');
    files.slice(0, 3 - accReportFiles.length).forEach(function (f) {
        accReportShrink(f).then(function (blob) {
            if (!blob) { showAlert('error', 'Nie udało się odczytać obrazka'); return; }
            if (accReportFiles.length >= 3) return;
            accReportFiles.push({ blob: blob, url: URL.createObjectURL(blob) });
            accReportRender();
        });
    });
}

function accReportPick(input) {
    accReportAdd(input.files);
    input.value = '';
}

function accReportRemove(i) {
    URL.revokeObjectURL(accReportFiles[i].url);
    accReportFiles.splice(i, 1);
    accReportRender();
}

function accReportSend(e) {
    e.preventDefault();
    var form = document.getElementById('acc_report_form');
    var btn = document.getElementById('acc_report_btn');
    var data = new FormData();
    data.append('description', document.getElementById('acc_report_desc').value);
    var standalone = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
    data.append('viewport', window.innerWidth + '×' + window.innerHeight + ' @' + (window.devicePixelRatio || 1) + 'x · ' + (standalone ? 'aplikacja' : 'przeglądarka'));
    accReportFiles.forEach(function (f, i) { data.append('screenshots[]', f.blob, 'zrzut-' + (i + 1) + '.jpg'); });
    btn.disabled = true;
    hlJson('scripts/reports/add.php', data).then(function (r) {
        btn.disabled = false;
        if (r.status !== 'success') { showAlert(r.status, r.message); return; }
        form.classList.add('hidden');
        document.getElementById('acc_report_done').classList.remove('hidden');
    });
}

function accReportReset() {
    accReportFiles.forEach(function (f) { URL.revokeObjectURL(f.url); });
    accReportFiles = [];
    accReportRender();
    var form = document.getElementById('acc_report_form');
    form.reset();
    document.getElementById('acc_report_count').textContent = '0';
    form.classList.remove('hidden');
    document.getElementById('acc_report_done').classList.add('hidden');
}

accReportRender();

// wklejanie zrzutu ze schowka, gdy otwarta jest ta podstrona (nasłuch rejestrowany raz)
if (!window.__hlReportPaste) {
    window.__hlReportPaste = true;
    document.addEventListener('paste', function (e) {
        var panel = document.querySelector('[data-acc-panel="report"]');
        if (!panel || panel.classList.contains('hidden') || !document.getElementById('acc_report_shots')) return;
        var files = Array.prototype.map.call((e.clipboardData && e.clipboardData.items) || [], function (it) { return it.kind === 'file' ? it.getAsFile() : null; })
            .filter(function (f) { return f && f.type.indexOf('image/') === 0; });
        if (!files.length) return;
        e.preventDefault();
        accReportAdd(files);
    });
}
</script>
