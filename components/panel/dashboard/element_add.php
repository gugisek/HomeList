<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Formularz dodawania zadania/rutyny. Ładowany w dwóch popupach: ElementAdd (?id=lista) i CopyElement (?id=lista&name=&description=&deadline=).
// Oba mogą naraz siedzieć w DOM, dlatego JS szuka pól wewnątrz [data-hl-add] (data-f=...), a nie po id.
$list_id = (int)($_GET['id'] ?? 0);
$is_copy = isset($_GET['name']);
$element_name = (string)($_GET['name'] ?? '');
$element_description = (string)($_GET['description'] ?? '');
$element_deadline = (string)($_GET['deadline'] ?? '');

include '../../../scripts/conn_db.php';
include '../../../scripts/planner/planner_lib.php';
$stmt = $conn->prepare('SELECT full_name, list_type FROM lists WHERE id = ?');
$stmt->bind_param('i', $list_id);
$stmt->execute();
$list_row = $stmt->get_result()->fetch_assoc();
$is_routine = $list_row && $list_row['list_type'] === 'routine';
$popup = $is_copy ? 'CopyElement' : 'ElementAdd';
$uid = 'hla_'.bin2hex(random_bytes(4));

// deadline do wstępnego wypełnienia: data + godzina; 23:59:59 = „do końca dnia” (bez godziny)
$dl_date = $dl_time = '';
if ($element_deadline !== '' && strpos($element_deadline, '0000-00-00') !== 0 && ($ts = strtotime($element_deadline))) {
    $dl_date = date('Y-m-d', $ts);
    $dl_time = date('H:i:s', $ts) === '23:59:59' ? '' : date('H:i', $ts);
}

$title = $is_copy ? 'Kopia zadania' : ($is_routine ? 'Nowa rutyna' : 'Nowe zadanie');
$chip = 'flex-none flex items-center gap-1.5 h-9 px-3.5 rounded-full text-sm font-medium whitespace-nowrap active:scale-95 duration-150 cursor-pointer';
$extra = 'flex items-center gap-1 h-8 px-3 rounded-full text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100/80 dark:bg-[#3d3d3d] hover:text-green-500 active:scale-95 duration-150';
?>
<div id="<?=$uid?>" data-hl-add data-popup="<?=$popup?>" data-routine="<?=$is_routine ? 1 : 0?>" onkeydown="hlAddRootKey(event)" class="font-[poppins] p-1">
    <input type="hidden" data-f="list" value="<?=$list_id?>">
    <input type="hidden" data-f="deadline" value="">

    <div class="-mt-[45px] flex items-start gap-3">
        <div class="min-w-0 flex-1">
            <h1 class="hl-popup-title !pr-0 truncate"><?=$title?></h1>
            <?php if ($list_row): ?>
            <p class="hl-sub truncate">na liście <span class="font-medium text-gray-700 dark:text-gray-200"><?=hl_e($list_row['full_name'])?></span></p>
            <?php endif; ?>
        </div>
        <!-- zapis obok „X”: osiągalny także przy otwartej klawiaturze, gdy dolne przyciski są pod nią -->
        <button type="button" data-save onmousedown="event.preventDefault()" onclick="hlAddSave(this)" class="hl-btn hl-btn-primary hl-btn-sm relative z-10 mr-8 mt-0.5 flex-none">
            <?=hl_icon('check', 'size-4', '2')?>Dodaj
        </button>
    </div>

    <!-- nazwa: textarea, żeby długi tytuł był widoczny w całości; Enter zapisuje, nowe linie są zamieniane na spacje -->
    <div data-wrap class="hl-add-field mt-5">
        <label for="<?=$uid?>_name" class="sr-only">Nazwa</label>
        <textarea id="<?=$uid?>_name" data-f="name" rows="1" maxlength="500" placeholder="<?=$is_routine ? 'Co powtarzać?' : 'Co trzeba zrobić?'?>" autocapitalize="sentences" enterkeyhint="done" oninput="hlAddName(this)" onkeydown="hlAddEnter(event)" class="hl-input hl-add-name w-full"><?=htmlspecialchars($element_name)?></textarea>
    </div>

    <!-- opis i link chowane, dopóki nie są potrzebne – krótszy formularz nad klawiaturą -->
    <textarea data-f="description" rows="2" maxlength="1000" placeholder="Opis, notatki…" oninput="hlAddGrow(this, 160)" onkeydown="if((event.metaKey||event.ctrlKey)&&event.key==='Enter'){event.preventDefault();hlAddSave(this);}" class="hl-input mt-2 w-full <?=$element_description === '' ? 'hidden' : ''?>"><?=htmlspecialchars($element_description)?></textarea>
    <div data-wrap data-box="link" class="hl-add-field relative mt-2 hidden">
        <input data-f="link" type="url" inputmode="url" maxlength="2000" placeholder="https://…" autocapitalize="off" autocorrect="off" spellcheck="false" enterkeyhint="done" oninput="this.closest('[data-wrap]').classList.remove('is-invalid')" onkeydown="hlAddEnter(event)" class="hl-input w-full pr-10">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-400"><?=hl_icon('link', 'size-4')?></div>
    </div>
    <div data-extras class="mt-2 flex gap-1.5">
        <?php if ($element_description === ''): ?>
        <button type="button" onclick="hlAddExtra(this, 'description')" class="<?=$extra?>"><?=hl_icon('plus', 'size-3.5', '2')?>Opis</button>
        <?php endif; ?>
        <button type="button" onclick="hlAddExtra(this, 'link')" class="<?=$extra?>"><?=hl_icon('plus', 'size-3.5', '2')?>Link</button>
    </div>

    <?php if ($is_routine): ?>
    <?php $rf_prefix = 'add'; $rf = []; include 'routine_fields.php'; ?>
    <?php else: ?>
    <p class="hl-label mt-5">Termin</p>
    <div data-dl-chips class="flex flex-wrap gap-1.5">
        <button type="button" data-dl="" onclick="hlAddDl(this)" class="<?=hl_chip_class(true)?> <?=$chip?>">Bez terminu</button>
        <button type="button" data-dl="0" onclick="hlAddDl(this)" class="<?=hl_chip_class(false)?> <?=$chip?>">Dziś</button>
        <button type="button" data-dl="1" onclick="hlAddDl(this)" class="<?=hl_chip_class(false)?> <?=$chip?>">Jutro</button>
        <button type="button" data-dl="7" onclick="hlAddDl(this)" class="<?=hl_chip_class(false)?> <?=$chip?>">Za tydzień</button>
        <!-- przezroczysty input na chipie: na iOS dotknięcie od razu otwiera natywny kalendarz -->
        <label data-dl="custom" class="<?=hl_chip_class(false)?> <?=$chip?> relative overflow-hidden">
            <?=hl_icon('calendar', 'size-4')?><span data-dl-label>Inna data</span>
            <input type="date" data-f="date" onchange="hlAddSetDate(hlAddRoot(this), this.value)" onclick="try{this.showPicker()}catch(e){}" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" aria-label="Wybierz datę">
        </label>
    </div>
    <div data-dl-more class="mt-2 hidden items-center gap-2 pl-1">
        <p data-dl-hint class="flex-1 min-w-0 text-xs text-gray-500 dark:text-gray-400 truncate"></p>
        <label class="<?=$extra?> relative overflow-hidden cursor-pointer">
            <?=hl_icon('clock', 'size-3.5', '2')?><span data-time-label>Godzina</span>
            <input type="time" data-f="time" onchange="hlAddSetTime(hlAddRoot(this), this.value)" onclick="try{this.showPicker()}catch(e){}" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" aria-label="Wybierz godzinę">
        </label>
        <button type="button" data-time-clear onclick="hlAddSetTime(hlAddRoot(this), '')" title="Usuń godzinę" class="hidden h-8 w-8 flex-none place-items-center rounded-full text-gray-400 hover:text-gray-700 dark:hover:text-white bg-gray-100/80 dark:bg-[#3d3d3d] active:scale-90 duration-150"><?=hl_icon('x', 'size-3.5', '2')?></button>
    </div>
    <?php endif; ?>

    <div class="hl-add-foot sticky bottom-0 z-10 -mx-1 mt-6 px-1 pt-2 bg-white dark:bg-[#1e1e1e]">
        <?php if (!$is_copy): ?>
        <label class="flex items-center justify-between gap-3 mb-3 pl-1 cursor-pointer select-none">
            <span class="text-sm text-gray-600 dark:text-gray-300">Dodaj kolejne po zapisaniu</span>
            <input type="checkbox" data-f="more" onchange="hlAddMore(this)">
        </label>
        <?php endif; ?>
        <div class="flex flex-row-reverse gap-2">
            <!-- mousedown bez domyślnej akcji: fokus zostaje w polu, więc klawiatura na telefonie się nie chowa -->
            <button type="button" data-save onmousedown="event.preventDefault()" onclick="hlAddSave(this)" class="hl-btn hl-btn-primary flex-[1.4]">
                <?=hl_icon('check', 'size-5', '1.8')?><span data-save-label><?=$is_routine ? 'Dodaj rutynę' : 'Dodaj zadanie'?></span>
            </button>
            <button type="button" onclick="hlAddClose(this)" class="hl-btn hl-btn-ghost flex-1">Anuluj</button>
        </div>
    </div>
</div>
<script>
(function () {
    var root = document.getElementById('<?=$uid?>');
    if (!root) return;
    if (root.querySelector('[data-dl-chips]')) {
        root.dataset.dlTime = '<?=$dl_time?>';
        hlAddSetDate(root, '<?=$dl_date?>');
    }
    var more = hlAddF(root, 'more');
    if (more) {
        try { more.checked = localStorage.getItem(storageKey + '_add_more') === '1'; } catch (e) {}
        hlAddMore(more);
    }
    root.querySelectorAll('textarea').forEach(function (t) { if (t.value) hlAddGrow(t, t.dataset.f === 'name' ? 140 : 160); });
    // nowe zadanie: od razu pisanie (na telefonie klawiatura jest już otwarta przez hlPrimeKeyboard)
    if (!<?=$is_copy ? 'true' : 'false'?>) {
        var name = hlAddF(root, 'name');
        name.focus({ preventScroll: true });
    }
    var primer = document.getElementById('hl_kb_primer');
    if (primer) primer.remove();
})();

function hlAddRoot(el) { return el.closest('[data-hl-add]'); }
function hlAddF(root, f) { return root.querySelector('[data-f="' + f + '"]'); }
function hlAddGrow(el, max) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight + 2, max) + 'px';
}
function hlAddName(el) {
    if (/[\r\n]/.test(el.value)) el.value = el.value.replace(/\s*[\r\n]+\s*/g, ' ');
    el.closest('[data-wrap]').classList.remove('is-invalid');
    hlAddGrow(el, 140);
}
function hlAddEnter(e) {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
        e.preventDefault();
        hlAddSave(e.target);
    }
}
function hlAddRootKey(e) {
    if (e.key === 'Escape') {
        e.preventDefault();
        hlAddClose(e.target);
    }
}
function hlAddExtra(btn, f) {
    var root = hlAddRoot(btn);
    var field = hlAddF(root, f);
    (field.closest('[data-box]') || field).classList.remove('hidden');
    var extras = btn.parentElement;
    btn.remove();
    if (!extras.children.length) extras.remove();
    field.focus();
}
function hlAddMore(cb) {
    var root = hlAddRoot(cb);
    try { localStorage.setItem(storageKey + '_add_more', cb.checked ? '1' : '0'); } catch (e) {}
    // klawisz Enter na klawiaturze telefonu: „dalej” przy seryjnym dodawaniu
    hlAddF(root, 'name').setAttribute('enterkeyhint', cb.checked ? 'next' : 'done');
}

// ---- termin ----
function hlAddYmd(d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}
function hlAddDays(n) {
    var d = new Date();
    d.setHours(12, 0, 0, 0);
    d.setDate(d.getDate() + n);
    return hlAddYmd(d);
}
function hlAddDl(btn) {
    hlAddSetDate(hlAddRoot(btn), btn.dataset.dl === '' ? '' : hlAddDays(+btn.dataset.dl));
}
function hlAddSetDate(root, ymd) {
    root.dataset.dlDate = ymd || '';
    var chips = root.querySelector('[data-dl-chips]');
    var custom = chips.querySelector('[data-dl="custom"]');
    var match = null;
    chips.querySelectorAll('[data-dl]').forEach(function (c) {
        var v = c.dataset.dl;
        if (match || v === 'custom') return;
        if (v === '' ? !ymd : ymd === hlAddDays(+v)) match = c;
    });
    var label = custom.querySelector('[data-dl-label]');
    if (match) {
        label.textContent = 'Inna data';
    } else {
        var d = new Date(ymd + 'T12:00:00');
        label.textContent = ['nd', 'pn', 'wt', 'śr', 'cz', 'pt', 'sb'][d.getDay()] + ' ' + ymd.slice(8, 10) + '.' + ymd.slice(5, 7) + (ymd.slice(0, 4) !== String(new Date().getFullYear()) ? '.' + ymd.slice(0, 4) : '');
        match = custom;
    }
    hlChipSelect(match);
    if (match !== custom || !hlAddF(root, 'date').value) hlAddF(root, 'date').value = ymd || '';

    var moreRow = root.querySelector('[data-dl-more]');
    moreRow.classList.toggle('hidden', !ymd);
    moreRow.classList.toggle('flex', !!ymd);
    if (!ymd) root.dataset.dlTime = '';
    hlAddSetTime(root, root.dataset.dlTime || '');
}
function hlAddSetTime(root, hm) {
    root.dataset.dlTime = hm || '';
    hlAddF(root, 'time').value = hm || '';
    root.querySelector('[data-time-label]').textContent = hm || 'Godzina';
    var clear = root.querySelector('[data-time-clear]');
    clear.classList.toggle('hidden', !hm);
    clear.classList.toggle('grid', !!hm);
    var ymd = root.dataset.dlDate;
    // bez godziny = do końca dnia (23:59:59 – wyświetlane bez godziny, a zadanie nie jest „po terminie” od rana)
    hlAddF(root, 'deadline').value = ymd ? ymd + ' ' + (hm ? hm + ':00' : '23:59:59') : '';
    if (ymd) {
        var txt = new Date(ymd + 'T12:00:00').toLocaleDateString('pl-PL', { weekday: 'long', day: 'numeric', month: 'long' });
        root.querySelector('[data-dl-hint]').textContent = txt.charAt(0).toUpperCase() + txt.slice(1) + (hm ? '' : ' · do końca dnia');
    }
}

// ---- zamykanie / zapis ----
function hlAddDirty(root) {
    if (!root) return false;
    return ['name', 'description', 'link', 'deadline'].some(function (f) {
        var el = hlAddF(root, f);
        return el && el.value.trim() !== '';
    });
}
window.hlPopupDirty = window.hlPopupDirty || {};
window.hlPopupDirty.ElementAdd = function () {
    return hlAddDirty(document.querySelector('#pupupElementAddOutput [data-hl-add]'));
};
function hlAddClose(el) {
    var p = hlAddRoot(el).dataset.popup;
    if (typeof window['popup' + p + 'RequestClose'] === 'function') window['popup' + p + 'RequestClose']();
    else window['popup' + p + 'CloseConfirm']();
}
function hlAddInvalid(root, f) {
    var wrap = hlAddF(root, f).closest('[data-wrap]');
    wrap.classList.remove('is-invalid');
    void wrap.offsetWidth; // restart animacji
    wrap.classList.add('is-invalid');
    hlAddF(root, f).focus();
}
function hlAddSave(el) {
    var root = hlAddRoot(el);
    if (!root || root.dataset.busy) return;
    var name = hlAddF(root, 'name');
    if (!name.value.trim()) return hlAddInvalid(root, 'name');

    // link bez protokołu („allegro.pl/…”) – dopisujemy https://, inaczej serwer po cichu by go odrzucił
    var link = hlAddF(root, 'link');
    var url = link.value.trim();
    if (url && !/^[a-z][a-z0-9+.-]*:/i.test(url)) url = 'https://' + url;
    if (url) {
        var ok = false;
        try { var u = new URL(url); ok = /^https?:$/.test(u.protocol) && u.hostname.indexOf('.') > 0; } catch (e) {}
        if (!ok) {
            showAlert('warning', 'Ten link wygląda na niepoprawny');
            return hlAddInvalid(root, 'link');
        }
        link.value = url;
    }

    var fd = new FormData();
    fd.append('name', name.value.trim());
    fd.append('description', hlAddF(root, 'description').value);
    fd.append('deadline', hlAddF(root, 'deadline').value);
    fd.append('list', hlAddF(root, 'list').value);
    fd.append('link_url', url);
    if (root.dataset.routine === '1') {
        var routine_error = rfCollect('add', fd);
        if (routine_error) return showAlert('warning', routine_error);
    }

    var btns = root.querySelectorAll('[data-save]');
    var label = root.querySelector('[data-save-label]');
    var text = label.textContent;
    root.dataset.busy = '1';
    btns.forEach(function (b) { b.disabled = true; });
    label.textContent = 'Zapisywanie…';
    hlPost('scripts/dashboard/add.php', fd)
        .then(function (res) {
            res = res[0] || {};
            if (res.status !== 'success') {
                showAlert(res.status === 'warning' ? 'warning' : 'error', res.message || 'Nieznany status odpowiedzi');
                return;
            }
            showAlert('success', res.message);
            refreshCurrentTab();
            var more = hlAddF(root, 'more');
            if (more && more.checked) {
                // seryjne dodawanie: czyścimy treść, termin zostaje
                ['name', 'description', 'link'].forEach(function (f) { hlAddF(root, f).value = ''; });
                name.style.height = '';
                name.focus();
            } else {
                if (document.activeElement && root.contains(document.activeElement)) document.activeElement.blur();
                window['popup' + root.dataset.popup + 'OpenClose']();
            }
        })
        .catch(function () { showAlert('error', 'Wystąpił problem połączenia z serwerem'); })
        .finally(function () {
            delete root.dataset.busy;
            btns.forEach(function (b) { b.disabled = false; });
            label.textContent = text;
        });
}
</script>
