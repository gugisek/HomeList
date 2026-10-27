<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Dołączanie do listy: skan kodu QR aparatem albo wpisanie 6-znakowego kodu, potem potwierdzenie.
// ?id=KOD – kod z linku panel.php?join=KOD (QR zeskanowany zwykłym aparatem telefonu)
include_once '../../../scripts/planner/planner_lib.php';
$prefill = strtoupper((string)($_GET['id'] ?? ''));
if (!preg_match('/^[A-Z0-9]{6}$/', $prefill)) $prefill = '';
?>
<div class="-mt-[43px] sm:w-[420px]" id="join_list" data-prefill="<?=hl_e($prefill)?>">

    <!-- 1. kod -->
    <div id="jl_enter" class="hl-step">
        <h1 class="hl-popup-title">Dołącz do listy</h1>
        <p class="mt-1 text-sm text-gray-400">Zeskanuj kod QR z telefonu domownika albo wpisz kod z 6 znaków.</p>

        <div id="jl_scan_off" class="mt-5">
            <button type="button" onclick="jlScanStart()" class="hl-scan-cta">
                <span class="hl-scan-cta-ico">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" /></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[15px] font-semibold">Zeskanuj kod QR</span>
                    <span class="block text-xs text-white/55">Uruchomi aparat w tym oknie</span>
                </span>
                <span class="text-white/40"><?=hl_icon('right', 'size-5', '2')?></span>
            </button>
        </div>
        <div id="jl_scan_on" class="hidden mt-5">
            <div class="hl-scan" id="jl_scan_box">
                <video id="jl_video" playsinline muted></video>
                <div class="hl-scan-frame"></div>
                <div class="hl-scan-line"></div>
                <div class="hl-scan-hint" id="jl_scan_hint">Skieruj aparat na kod QR</div>
            </div>
            <button type="button" onclick="jlScanStop()" class="mt-3 hl-btn hl-btn-ghost hl-btn-block">Wyłącz aparat</button>
        </div>

        <div class="hl-or my-5">albo wpisz kod</div>

        <div class="hl-code-boxes" id="jl_boxes">
            <input id="add_list_code" type="text" maxlength="6" autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false" aria-label="Kod listy"
                   oninput="jlInput(this)" onfocus="jlBoxes()" onblur="jlBoxes()" onkeydown="if (event.key === 'Enter') jlPreview()">
            <?php for ($i = 0; $i < 6; $i++): ?><span class="hl-code-box"></span><?php endfor; ?>
        </div>

        <div class="mt-6 flex items-center gap-2">
            <button type="button" onclick="popupCodeListsOpenClose()" class="hl-btn hl-btn-ghost flex-1">Anuluj</button>
            <button type="button" id="jl_next" onclick="jlPreview()" class="hl-btn hl-btn-primary flex-1" disabled>Dalej<?=hl_icon('right', '', '2')?></button>
        </div>
    </div>

    <!-- 2. potwierdzenie (wypełniane z podglądu) -->
    <div id="jl_confirm" class="hidden hl-step">
        <h1 class="hl-popup-title" id="jl_confirm_title">Dołączyć do listy?</h1>
        <div class="mt-5 hl-share !text-left !p-5">
            <div class="flex items-center gap-4">
                <span class="hl-mono-lg" id="jl_mono"></span>
                <div class="min-w-0">
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate font-[poppins]" id="jl_name"></p>
                    <p class="text-xs text-gray-400" id="jl_type"></p>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-3">
                <img id="jl_owner_img" src="img/users_images/default.png" alt="" class="size-9 flex-none rounded-full object-cover border border-black/10 dark:border-white/10">
                <div class="min-w-0">
                    <p class="text-xs text-gray-400">Właściciel</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" id="jl_owner"></p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <div class="hl-stat !bg-[var(--hl-card)]"><b id="jl_open"></b><span id="jl_open_label">zadań do zrobienia</span></div>
                <div class="hl-stat !bg-[var(--hl-card)]">
                    <div class="flex items-center justify-between gap-2"><b id="jl_members_n"></b><span class="hl-avatars" id="jl_avatars"></span></div>
                    <span id="jl_members_label">osób z dostępem</span>
                </div>
            </div>
        </div>
        <p class="mt-3 px-1 text-xs text-gray-400" id="jl_note"></p>
        <div class="mt-6 flex items-center gap-2">
            <button type="button" onclick="jlBack()" class="hl-btn hl-btn-ghost flex-1"><?=hl_icon('left', '', '2')?>Wróć</button>
            <button type="button" id="jl_join" onclick="jlJoin()" class="hl-btn hl-btn-primary flex-[2]"></button>
        </div>
    </div>

    <!-- 3. gotowe -->
    <div id="jl_done" class="hidden hl-step py-6 text-center">
        <span class="hl-done-ico"><?=hl_icon('check', '', '2.5')?></span>
        <h1 class="mt-5 hl-popup-title !pr-0">Jesteś na liście!</h1>
        <p class="mt-1 text-sm text-gray-400" id="jl_done_name"></p>
    </div>
</div>

<script>
    function jlEl(id) { return document.getElementById(id); }

    // tekst z QR: link panel.php?join=KOD albo sam kod
    function jlParse(text) {
        var code = String(text || '').trim();
        try {
            var url = new URL(code);
            code = url.searchParams.get('join') || '';
        } catch (e) {}
        code = code.toUpperCase();
        return /^[A-Z0-9]{6}$/.test(code) ? code : '';
    }

    function jlBoxes() {
        var input = jlEl('add_list_code');
        if (!input) return;
        var value = input.value;
        var focused = document.activeElement === input;
        jlEl('jl_boxes').querySelectorAll('.hl-code-box').forEach(function (box, i) {
            var filled = i < value.length;
            if (box.textContent !== (value[i] || '')) box.textContent = value[i] || '';
            box.classList.toggle('is-filled', filled);
            box.classList.toggle('is-active', focused && i === Math.min(value.length, 5));
        });
        jlEl('jl_next').disabled = value.length !== 6;
    }

    function jlInput(input) {
        input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
        jlEl('jl_boxes').classList.remove('is-error');
        jlBoxes();
        if (input.value.length === 6) jlPreview();
    }

    function jlError() {
        var boxes = jlEl('jl_boxes');
        boxes.classList.remove('is-error');
        void boxes.offsetWidth;
        boxes.classList.add('is-error');
    }

    function jlStep(step) {
        ['jl_enter', 'jl_confirm', 'jl_done'].forEach(function (id) { jlEl(id).classList.toggle('hidden', id !== step); });
    }

    function jlPreview() {
        var code = jlEl('add_list_code').value;
        if (code.length !== 6 || window.jlBusy) return;
        window.jlBusy = true;
        var fd = new FormData();
        fd.append('code', code);
        fd.append('preview', '1');
        jlEl('jl_next').disabled = true;
        hlJson('scripts/dashboard/list_code.php', fd).then(function (r) {
            window.jlBusy = false;
            jlBoxes();
            if (r.status !== 'success' || !r.preview) {
                showAlert(r.status, r.message);
                jlError();
                return;
            }
            jlScanStop();
            jlShow(r.preview);
        });
    }

    function jlPlural(n, one, few, many) {
        if (n === 1) return one;
        return (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 12 || n % 100 > 14)) ? few : many;
    }

    function jlShow(p) {
        window.jlList = p;
        var mono = jlEl('jl_mono');
        mono.textContent = p.mono;
        mono.style.setProperty('--mono', p.tone);
        jlEl('jl_name').textContent = p.name;
        jlEl('jl_type').textContent = p.type === 'routine' ? 'Lista rutyn' : 'Lista zadań';
        jlEl('jl_owner').textContent = p.owner;
        jlEl('jl_owner_img').src = 'img/users_images/' + encodeURIComponent(p.owner_avatar);
        jlEl('jl_open').textContent = p.open;
        jlEl('jl_open_label').textContent = p.type === 'routine'
            ? jlPlural(p.open, 'rutyna', 'rutyny', 'rutyn')
            : jlPlural(p.open, 'zadanie', 'zadania', 'zadań') + ' do zrobienia';
        jlEl('jl_members_n').textContent = p.members.length;
        jlEl('jl_members_label').textContent = jlPlural(p.members.length, 'osoba', 'osoby', 'osób') + ' z dostępem';
        var avatars = jlEl('jl_avatars');
        avatars.innerHTML = '';
        p.members.slice(0, 4).forEach(function (m) {
            var img = document.createElement('img');
            img.src = 'img/users_images/' + encodeURIComponent(m.avatar);
            img.alt = '';
            img.title = m.name;
            img.className = 'size-6 rounded-full object-cover';
            avatars.appendChild(img);
        });

        var join = jlEl('jl_join');
        if (p.state === 'join') {
            jlEl('jl_confirm_title').textContent = 'Dołączyć do listy?';
            jlEl('jl_note').textContent = 'Po dołączeniu zobaczysz zadania tej listy i będziesz dodawać je razem z innymi. Możesz się odłączyć w każdej chwili.';
            join.innerHTML = 'Dołącz do listy';
        } else {
            jlEl('jl_confirm_title').textContent = 'Ta lista już jest u Ciebie';
            jlEl('jl_note').textContent = p.state === 'owner' ? 'Jesteś jej właścicielem – ten kod przekaż osobom, z którymi chcesz ją dzielić.' : 'Dołączono do niej wcześniej.';
            join.innerHTML = 'Otwórz listy';
        }
        jlStep('jl_confirm');
    }

    function jlBack() {
        jlStep('jl_enter');
        jlEl('add_list_code').value = '';
        jlBoxes();
        jlEl('add_list_code').focus();
    }

    function jlOpen(id) {
        popupCodeListsOpenClose();
        openDetailTab('todo', 'list=' + id);
        refreshLists();
        if (typeof hlRailRefresh === 'function') hlRailRefresh();
    }

    function jlJoin() {
        var p = window.jlList;
        if (!p) return;
        if (p.state !== 'join') { jlOpen(p.id); return; }
        var btn = jlEl('jl_join');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('code', jlEl('add_list_code').value);
        hlJson('scripts/dashboard/list_code.php', fd).then(function (r) {
            btn.disabled = false;
            if (r.status !== 'success') { showAlert(r.status, r.message); return; }
            if (navigator.vibrate) navigator.vibrate(20);
            jlEl('jl_done_name').textContent = p.name;
            jlStep('jl_done');
            setTimeout(function () { jlOpen(r.id); }, 1100);
        });
    }

    // ===== skaner QR: BarcodeDetector (Chrome / Android), w pozostałych przeglądarkach jsQR =====
    function jlDetector() {
        if ('BarcodeDetector' in window) {
            return BarcodeDetector.getSupportedFormats().then(function (formats) {
                if (formats.indexOf('qr_code') === -1) throw new Error('no qr');
                var detector = new BarcodeDetector({ formats: ['qr_code'] });
                return function (video) {
                    return detector.detect(video).then(function (codes) { return codes.length ? codes[0].rawValue : ''; });
                };
            }).catch(jlDetectorJs);
        }
        return jlDetectorJs();
    }

    function jlDetectorJs() {
        return hlLoadScript('https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js').then(function () {
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d', { willReadFrequently: true });
            return function (video) {
                var w = video.videoWidth, h = video.videoHeight;
                if (!w || !h) return Promise.resolve('');
                var scale = Math.min(1, 640 / Math.max(w, h));
                canvas.width = Math.round(w * scale);
                canvas.height = Math.round(h * scale);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                var img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                var found = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
                return Promise.resolve(found ? found.data : '');
            };
        });
    }

    function jlScanStart() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showAlert('warning', 'Ta przeglądarka nie daje dostępu do aparatu (wymaga HTTPS) – wpisz kod ręcznie');
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
            .then(function (stream) {
                window.jlStream = stream;
                var video = jlEl('jl_video');
                video.srcObject = stream;
                video.play().catch(function () {});
                jlEl('jl_scan_off').classList.add('hidden');
                jlEl('jl_scan_on').classList.remove('hidden');
                jlEl('jl_scan_box').classList.remove('is-found');
                return jlDetector().then(function (detect) { jlScanLoop(detect, stream); });
            })
            .catch(function (err) {
                jlScanStop();
                showAlert('warning', err && err.name === 'NotAllowedError'
                    ? 'Brak zgody na aparat – zezwól na niego w ustawieniach przeglądarki'
                    : 'Nie udało się uruchomić skanera – wpisz kod ręcznie');
            });
    }

    function jlScanLoop(detect, stream) {
        var popup = document.getElementById('popupCodeLists');
        var video = jlEl('jl_video');
        // popup zamknięty albo skaner wyłączony – zwalniamy aparat
        if (window.jlStream !== stream || !video || (popup && popup.classList.contains('popup-sheet-hidden'))) {
            if (window.jlStream === stream) jlScanStop();
            return;
        }
        detect(video).then(function (text) {
            if (window.jlStream !== stream) return;
            if (!text) { setTimeout(function () { jlScanLoop(detect, stream); }, 180); return; }
            var code = jlParse(text);
            if (!code) {
                jlEl('jl_scan_hint').textContent = 'To nie jest kod listy HomeList';
                setTimeout(function () { jlScanLoop(detect, stream); }, 900);
                return;
            }
            jlEl('jl_scan_box').classList.add('is-found');
            jlEl('jl_scan_hint').textContent = 'Mam! Sprawdzam listę…';
            if (navigator.vibrate) navigator.vibrate(30);
            jlEl('add_list_code').value = code;
            jlBoxes();
            jlPreview();
        }).catch(function () { setTimeout(function () { jlScanLoop(detect, stream); }, 400); });
    }

    function jlScanStop() {
        if (window.jlStream) window.jlStream.getTracks().forEach(function (t) { t.stop(); });
        window.jlStream = null;
        var off = jlEl('jl_scan_off');
        if (!off) return;
        off.classList.remove('hidden');
        jlEl('jl_scan_on').classList.add('hidden');
        jlEl('jl_scan_hint').textContent = 'Skieruj aparat na kod QR';
    }

    window.jlBusy = false;
    window.jlList = null;
    jlScanStop();
    (function () {
        var prefill = jlEl('join_list').dataset.prefill;
        if (prefill) {
            jlEl('add_list_code').value = prefill;
            jlBoxes();
            jlPreview();
        } else {
            jlBoxes();
            // telefon: bez automatycznej klawiatury – częściej skanuje się QR
            if (window.matchMedia('(min-width: 640px)').matches) setTimeout(function () { jlEl('add_list_code').focus(); }, 350);
        }
    })();
</script>
