<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Szczegóły i ustawienia listy: udostępnianie (QR + kod), osoby z dostępem, powiadomienia, nazwa, usuwanie.
// Otwierane z „Edytuj listy” oraz kliknięciem w nagłówek listy (todo.php).
include '../../../scripts/conn_db.php';
include_once '../../../scripts/planner/planner_lib.php';
include_once '../../../scripts/access.php';
include_once '../../../scripts/push/push_lib.php';
$user_id = (int)$_SESSION['login_id'];
$list_id = (int)($_GET['id'] ?? 0);
$access = hl_list_access($conn, $user_id, $list_id);
if (!$access) {
    echo '<div class="-mt-[43px] text-sm text-gray-500">Nie znaleziono listy.</div>';
    exit;
}
$row = $access['list'];
$is_owner = $access['role'] === 'owner';
$is_routine = $row['list_type'] === 'routine';
$members = hl_list_members($conn, $list_id);
$owner = $members[0] ?? null;
$is_shared = count($members) > 1;
$code = (string)$row['list_code'];

// bez literału '0000-00-00' w SQL – MySQL 8 (strict, NO_ZERO_DATE) rzuca na nim błąd; >= '1000-01-01' pomija NULL i daty zerowe
$stmt = $conn->prepare(
    "SELECT SUM(status_id NOT IN (2, 4)) AS open,
            SUM(status_id NOT IN (2, 4) AND deadline_date >= '1000-01-01' AND deadline_date < NOW()) AS overdue,
            SUM(status_id IN (2, 4)) AS done
     FROM list_elements WHERE list_id = ?"
);
$stmt->bind_param('i', $list_id);
$stmt->execute();
$counts = array_map('intval', $stmt->get_result()->fetch_assoc());
$stats = $is_routine
    ? [['Rutyny', $counts['open'], ''], ['Osoby', count($members), '']]
    : [['Do zrobienia', $counts['open'], ''], ['Po terminie', $counts['overdue'], $counts['overdue'] ? 'is-warn' : ''], ['Zrobione', $counts['done'], $counts['done'] ? 'is-ok' : '']];

$push = hl_push_settings($conn, $user_id);
$muted = hl_push_list_muted($conn, $user_id, $list_id);
?>
<div class="-mt-[43px] sm:w-[440px]" id="list_info" data-id="<?=$list_id?>" data-name="<?=hl_e($row['full_name'])?>">
    <div id="info_list_loading"></div>

    <!-- nagłówek -->
    <div class="flex items-center gap-4 pr-10">
        <span class="hl-mono-lg" style="--mono: <?=hl_list_tone($list_id)?>"><?=hl_e(hl_list_mono($row['full_name']))?></span>
        <div class="min-w-0">
            <h1 class="hl-popup-title !pr-0 truncate"><?=hl_e($row['full_name'])?></h1>
            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                <?php if ($is_routine): ?>
                <span class="hl-badge !bg-orange-400/15 !text-orange-500"><?=hl_icon('repeat', 'size-3.5', '2')?>Rutyny</span>
                <?php else: ?>
                <span class="hl-badge"><?=hl_icon('list', 'size-3.5', '2')?>Lista zadań</span>
                <?php endif; ?>
                <?php if ($is_shared): ?>
                <span class="hl-badge !bg-sky-400/15 !text-sky-500"><?=hl_icon('users', 'size-3.5', '2')?>Wspólna</span>
                <?php endif; ?>
                <?php if (!$is_owner && $owner): ?>
                <span class="text-xs text-gray-400">od <?=hl_e(trim($owner['name'].' '.$owner['sur_name']))?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- liczby -->
    <div class="mt-5 grid gap-2 <?=count($stats) === 3 ? 'grid-cols-3' : 'grid-cols-2'?>">
        <?php foreach ($stats as [$label, $value, $class]): ?>
        <div class="hl-stat <?=$class?>"><b><?=$value?></b><span><?=$label?></span></div>
        <?php endforeach; ?>
    </div>

    <!-- udostępnianie -->
    <span class="hl-eyebrow mt-6 mb-2 pl-1">Udostępnij</span>
    <div class="hl-share">
        <div id="list_share_ready" class="<?=$code === '' ? 'hidden' : ''?>">
            <div id="list_qr" class="hl-qr is-loading" title="Zeskanuj aparatem telefonu"></div>
            <div class="mt-4 hl-code" id="list_code_text"><?=hl_e($code)?></div>
            <p class="mt-2 text-xs text-gray-400 px-4">Zeskanuj aparatem albo wpisz kod w „Wprowadź kod listy”</p>
            <div class="mt-4 flex justify-center gap-2">
                <button type="button" onclick="listInfoCopy()" class="hl-btn hl-btn-primary"><?=hl_icon('copy')?>Kopiuj kod</button>
                <button type="button" onclick="listInfoShare()" class="hl-btn hl-btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" /></svg>
                    Udostępnij link
                </button>
            </div>
        </div>
        <?php if ($is_owner): ?>
        <div id="list_share_empty" class="<?=$code === '' ? '' : 'hidden'?> py-2">
            <span class="hl-tile !size-12 mx-auto"><?=hl_icon('users')?></span>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Ta lista nie ma jeszcze kodu zaproszenia.</p>
            <button type="button" onclick="listInfoRegen(true)" class="mt-3 hl-btn hl-btn-primary">Wygeneruj kod</button>
        </div>
        <div id="list_regen" class="<?=$code === '' ? 'hidden' : ''?> mt-3">
            <button type="button" onclick="listInfoRegenAsk(true)" class="text-xs font-medium text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline-offset-4 hover:underline">Wygeneruj nowy kod</button>
            <div id="list_regen_ask" class="hidden hl-step mt-2 text-left hl-confirm">
                <p class="text-sm text-gray-700 dark:text-gray-200">Obecny kod i QR przestaną działać. Osoby, które już dołączyły, zachowają dostęp.</p>
                <div class="mt-3 flex justify-end gap-2">
                    <button type="button" onclick="listInfoRegenAsk(false)" class="hl-btn hl-btn-ghost hl-btn-sm">Anuluj</button>
                    <button type="button" onclick="listInfoRegen()" class="hl-btn hl-btn-danger hl-btn-sm">Nowy kod</button>
                </div>
            </div>
        </div>
        <?php elseif ($code === ''): ?>
        <p class="py-4 text-sm text-gray-400">Właściciel nie udostępnił jeszcze kodu tej listy.</p>
        <?php endif; ?>
    </div>

    <!-- osoby -->
    <span class="hl-eyebrow mt-6 mb-1 pl-1">Osoby z dostępem · <span id="list_members_count"><?=count($members)?></span></span>
    <div class="hl-divide" id="list_members">
        <?php foreach ($members as $i => $m): $mid = (int)$m['id']; ?>
        <div class="flex items-center gap-3 py-2.5 px-1" data-member="<?=$mid?>">
            <?=hl_avatar_img($m['profile_picture'], 'size-9')?>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate"><?=hl_e(trim($m['name'].' '.$m['sur_name']))?><?=$mid === $user_id ? ' <span class="text-gray-400 font-normal">(Ty)</span>' : ''?></p>
                <p class="text-xs text-gray-400"><?=$i === 0 ? 'Właściciel' : 'Dołączono kodem'?></p>
            </div>
            <?php if ($is_owner && $i > 0): ?>
            <button type="button" onclick="listInfoRemoveAsk(<?=$mid?>, true)" title="Odbierz dostęp" class="hl-icon-btn hl-icon-btn-sm hover:!bg-rose-400"><?=hl_icon('x', '', '2')?></button>
            <div class="hidden items-center gap-1.5" data-remove-ask>
                <button type="button" onclick="listInfoRemoveAsk(<?=$mid?>, false)" class="hl-btn hl-btn-ghost hl-btn-sm">Nie</button>
                <button type="button" onclick="listInfoRemove(<?=$mid?>)" class="hl-btn hl-btn-danger hl-btn-sm">Odbierz</button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (!$is_shared): ?>
    <p class="hl-hint">Na razie tylko Ty. Pokaż komuś kod QR, żeby dodawać zadania razem.</p>
    <?php endif; ?>

    <!-- powiadomienia -->
    <?php if ($is_shared): ?>
    <span class="hl-eyebrow mt-6 mb-2 pl-1">Powiadomienia</span>
    <label class="hl-toggle-row" for="list_push">
        <span class="min-w-0">
            <span class="block text-sm font-medium text-gray-800 dark:text-gray-100">Nowe zadania od innych</span>
            <span class="block mt-0.5 text-xs text-gray-400">Seria dodanych zadań przychodzi jako jedno powiadomienie – po 5 minutach ciszy</span>
        </span>
        <input type="checkbox" id="list_push" onchange="listInfoMute(this)" <?=$muted ? '' : 'checked'?>>
    </label>
    <?php if (!$push['enabled'] || !$push['notify_lists']): ?>
    <p class="hl-hint">Wyłączone globalnie w Konto › Powiadomienia – ten przełącznik zacznie działać po ich włączeniu.</p>
    <?php endif; ?>
    <div id="list_push_device" class="hidden mt-2 flex items-center justify-between gap-3 rounded-2xl px-3.5 py-2.5 bg-amber-400/10 text-amber-600 dark:text-amber-300">
        <span class="text-xs">To urządzenie nie odbiera jeszcze powiadomień.</span>
        <button type="button" onclick="hlPushEnable().then(ok => { if (ok) document.getElementById('list_push_device').classList.add('hidden'); })" class="hl-btn hl-btn-sm hl-btn-soft flex-none">Włącz</button>
    </div>
    <?php endif; ?>

    <!-- ustawienia -->
    <?php if ($is_owner): ?>
    <span class="hl-eyebrow mt-6 mb-2 pl-1">Ustawienia</span>
    <input type="hidden" id="edit_list_id" value="<?=$list_id?>">
    <label for="edit_list_name" class="hl-label">Nazwa listy</label>
    <input id="edit_list_name" type="text" maxlength="100" value="<?=hl_e($row['full_name'])?>" placeholder="Wymagane" class="hl-input" onkeydown="if (event.key === 'Enter') listInfoSave()" required>
    <?php endif; ?>

    <!-- akcje -->
    <div id="list_actions" class="mt-6 flex items-center gap-2">
        <button onclick="listInfoDeleteAsk(true)" type="button" title="<?=$is_owner ? 'Usuń listę' : 'Odłącz listę od konta'?>" class="hl-btn hl-btn-danger <?=$is_owner ? 'hl-btn-square' : 'flex-1'?>">
            <?=hl_icon('trash')?><?=$is_owner ? '' : 'Odłącz od mojego konta'?>
        </button>
        <?php if ($is_owner): ?>
        <button onclick="popupInfoListsOpenClose()" type="button" class="hl-btn hl-btn-ghost flex-1">Anuluj</button>
        <button onclick="listInfoSave()" class="hl-btn hl-btn-primary flex-1"><?=hl_icon('check', '', '2')?>Zapisz</button>
        <?php else: ?>
        <button onclick="popupInfoListsOpenClose()" type="button" class="hl-btn hl-btn-ghost flex-1">Zamknij</button>
        <?php endif; ?>
    </div>
    <div id="list_delete_ask" class="hidden hl-step mt-6 hl-confirm">
        <div class="flex items-start gap-3">
            <span class="flex-none grid place-items-center size-9 rounded-full bg-rose-400/15 text-rose-500"><?=hl_icon('warning', 'size-5', '1.8')?></span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100"><?=$is_owner ? 'Usunąć listę „'.hl_e($row['full_name']).'”?' : 'Odłączyć listę od Twojego konta?'?></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?=$is_owner
                    ? ($is_shared ? 'Lista zniknie u wszystkich osób, które mają do niej dostęp.' : 'Lista i jej zadania znikną z aplikacji.')
                    : 'Zadania zostaną na liście u pozostałych. Wrócisz, wpisując kod jeszcze raz.'?></p>
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" onclick="listInfoDeleteAsk(false)" class="hl-btn hl-btn-ghost">Anuluj</button>
            <button type="button" onclick="listInfoDelete()" class="hl-btn bg-rose-500 text-white hover:bg-rose-600"><?=$is_owner ? 'Usuń listę' : 'Odłącz'?></button>
        </div>
    </div>
</div>

<script>
    function listInfoRoot() { return document.getElementById('list_info'); }

    function listInfoLink() {
        var code = document.getElementById('list_code_text').textContent.trim();
        return new URL('panel.php?join=' + encodeURIComponent(code), window.location.href).href;
    }

    function listInfoQr() {
        var box = document.getElementById('list_qr');
        var code = document.getElementById('list_code_text').textContent.trim();
        if (!box || !code) return;
        box.classList.add('is-loading');
        hlQrSvg(listInfoLink()).then(function (svg) {
            box.innerHTML = svg;
            box.classList.remove('is-loading');
        }).catch(function () {
            box.classList.remove('is-loading');
            box.innerHTML = '<span class="text-xs text-gray-400 px-4 text-center">Nie udało się wczytać kodu QR – użyj kodu poniżej</span>';
        });
    }

    function listInfoCopy() {
        var code = document.getElementById('list_code_text').textContent.trim();
        hlCopy(code).then(function () { showAlert('success', 'Skopiowano kod ' + code); });
    }

    function listInfoShare() {
        var name = listInfoRoot().dataset.name;
        var code = document.getElementById('list_code_text').textContent.trim();
        var link = listInfoLink();
        if (navigator.share) {
            navigator.share({ title: 'HomeList · ' + name, text: 'Dołącz do mojej listy „' + name + '” w HomeList (kod: ' + code + ')', url: link }).catch(function () {});
        } else {
            hlCopy(link).then(function () { showAlert('success', 'Skopiowano link zaproszenia'); });
        }
    }

    function listInfoRegenAsk(show) {
        document.getElementById('list_regen_ask').classList.toggle('hidden', !show);
    }

    function listInfoRegen() {
        var fd = new FormData();
        fd.append('id', listInfoRoot().dataset.id);
        fd.append('action', 'regen');
        hlJson('scripts/dashboard/list_share.php', fd).then(function (r) {
            showAlert(r.status, r.message);
            if (r.status !== 'success') return;
            document.getElementById('list_code_text').textContent = r.code;
            ['list_share_ready', 'list_regen'].forEach(function (id) { document.getElementById(id).classList.remove('hidden'); });
            document.getElementById('list_share_empty').classList.add('hidden');
            listInfoRegenAsk(false);
            listInfoQr();
        });
    }

    function listInfoRemoveAsk(uid, show) {
        var row = document.querySelector('#list_members [data-member="' + uid + '"]');
        row.querySelector('.hl-icon-btn').classList.toggle('hidden', show);
        var ask = row.querySelector('[data-remove-ask]');
        ask.classList.toggle('hidden', !show);
        ask.classList.toggle('flex', show);
    }

    function listInfoRemove(uid) {
        var fd = new FormData();
        fd.append('id', listInfoRoot().dataset.id);
        fd.append('action', 'remove');
        fd.append('user_id', uid);
        hlJson('scripts/dashboard/list_share.php', fd).then(function (r) {
            showAlert(r.status, r.message);
            if (r.status !== 'success') return;
            var row = document.querySelector('#list_members [data-member="' + uid + '"]');
            row.style.transition = 'opacity .25s ease, transform .25s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(12px)';
            setTimeout(function () { row.remove(); }, 250);
            var count = document.getElementById('list_members_count');
            count.textContent = Math.max(1, parseInt(count.textContent, 10) - 1);
            window.hlListInfoDirty = true;
        });
    }

    function listInfoMute(input) {
        var fd = new FormData();
        fd.append('list_id', listInfoRoot().dataset.id);
        fd.append('mute', input.checked ? '0' : '1');
        hlJson('scripts/push/list_mute.php', fd).then(function (r) {
            showAlert(r.status, r.message);
            if (r.status !== 'success') input.checked = !input.checked;
            listInfoDevice();
        });
    }

    // przełącznik włączony, a to urządzenie nie ma zgody na powiadomienia – podpowiedź z przyciskiem
    function listInfoDevice() {
        var box = document.getElementById('list_push_device');
        var toggle = document.getElementById('list_push');
        if (!box || typeof hlPushState !== 'function') return;
        var state = hlPushState();
        box.classList.toggle('hidden', !toggle.checked || state === 'granted' || state === 'unsupported');
    }

    function listInfoDeleteAsk(show) {
        document.getElementById('list_delete_ask').classList.toggle('hidden', !show);
        document.getElementById('list_actions').classList.toggle('hidden', show);
        if (show) document.getElementById('list_delete_ask').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // po zmianach: odświeżenie dashboardu (nazwy w nagłówku, pasek list, pigułka nawigacji)
    function listInfoAfter(deleted) {
        if (deleted && localStorage.getItem('current_todo') === 'list=' + listInfoRoot().dataset.id) {
            localStorage.setItem('current_todo', 'list=today');
        }
        popupInfoListsOpenClose();
        openPanelSite('dashboard');
        if (typeof hlRailRefresh === 'function') hlRailRefresh();
    }

    function listInfoSave() {
        var name = document.getElementById('edit_list_name').value.trim();
        // nazwa bez zmian – zamykamy (po odebraniu komuś dostępu dodatkowo odświeżamy widok)
        if (name === listInfoRoot().dataset.name) {
            if (window.hlListInfoDirty) listInfoAfter(false);
            else popupInfoListsOpenClose();
            return;
        }
        var fd = new FormData();
        fd.append('id', listInfoRoot().dataset.id);
        fd.append('name', name);
        hlJson('scripts/dashboard/list_edit.php', fd).then(function (r) {
            showAlert(r.status, r.message);
            if (r.status === 'success') listInfoAfter(false);
        });
    }

    function listInfoDelete() {
        var fd = new FormData();
        fd.append('id', listInfoRoot().dataset.id);
        fd.append('delete_confirm', 'true');
        hlJson('scripts/dashboard/list_delete.php', fd).then(function (r) {
            showAlert(r.status, r.message);
            if (r.status === 'success') listInfoAfter(true);
        });
    }

    window.hlListInfoDirty = false;
    listInfoQr();
    listInfoDevice();
</script>
