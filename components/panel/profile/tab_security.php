<?php if (!isset($acc)) exit; // część account.php ?>
<p class="<?=$acc_title?>">Bezpieczeństwo</p>
<p class="<?=$acc_subtitle?>">Hasło i dodatkowa ochrona logowania.</p>

<div class="flex flex-col gap-6">
    <!-- Hasło -->
    <form id="acc_password_form" onsubmit="accChangePassword(event)" class="<?=$acc_card?>">
        <p class="font-medium text-lg text-black dark:text-gray-200 font-[poppins]">Hasło</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Po zmianie pozostałe urządzenia zostaną wylogowane.</p>
        <div class="mt-4 grid sm:grid-cols-3 gap-3">
            <div>
                <label for="acc_pw_current" class="<?=$acc_label?>">Aktualne</label>
                <input name="current_password" id="acc_pw_current" type="password" autocomplete="current-password" class="<?=$acc_input?>" required>
            </div>
            <div>
                <label for="acc_pw_new" class="<?=$acc_label?>">Nowe</label>
                <input name="new_password" id="acc_pw_new" type="password" autocomplete="new-password" class="<?=$acc_input?>" required>
            </div>
            <div>
                <label for="acc_pw_repeat" class="<?=$acc_label?>">Powtórz nowe</label>
                <input name="repeat_new_password" id="acc_pw_repeat" type="password" autocomplete="new-password" class="<?=$acc_input?>" required>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between gap-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">Min. 8 znaków, wielka i mała litera oraz cyfra.</p>
            <button class="<?=$acc_btn?> flex-none">Zmień hasło</button>
        </div>
    </form>

    <!-- 2FA -->
    <div class="<?=$acc_card?>">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="font-medium text-lg text-black dark:text-gray-200 font-[poppins]">Weryfikacja dwuetapowa</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Kod z aplikacji (Google Authenticator, Authy, 1Password...) przy logowaniu hasłem.</p>
            </div>
            <span class="flex-none inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold <?=$acc_2fa ? 'text-green-800 bg-green-100' : 'text-gray-600 bg-gray-100 dark:text-gray-300 dark:bg-white/10'?>"><?=$acc_2fa ? 'Włączona' : 'Wyłączona'?></span>
        </div>

        <?php if (!$acc_2fa): ?>
        <div id="acc_2fa_start" class="mt-4">
            <button type="button" onclick="acc2faSetup(this)" class="<?=$acc_btn?>">Włącz 2FA</button>
        </div>
        <div id="acc_2fa_setup" class="hidden mt-4 flex flex-col sm:flex-row gap-5">
            <div class="flex-none flex flex-col items-center gap-2">
                <div id="acc_2fa_qr" class="rounded-2xl bg-white p-3 ring-1 ring-black/5"></div>
                <a id="acc_2fa_link" href="#" class="sm:hidden text-xs font-medium text-green-500">Otwórz w aplikacji</a>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-700 dark:text-gray-300">1. Zeskanuj kod w aplikacji uwierzytelniającej albo wpisz klucz ręcznie:</p>
                <code id="acc_2fa_secret" class="mt-2 block break-all rounded-xl bg-gray-100 dark:bg-[#3d3d3d] px-3 py-2 text-xs tracking-wider text-gray-800 dark:text-gray-200"></code>
                <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">2. Wpisz 6-cyfrowy kod z aplikacji:</p>
                <div class="mt-2 flex gap-2">
                    <input id="acc_2fa_code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="123456" class="<?=$acc_input?> text-center tracking-[0.3em]">
                    <button type="button" onclick="acc2faEnable(this)" class="<?=$acc_btn?> flex-none">Potwierdź</button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Pozostałe kody zapasowe: <span class="font-semibold <?=$acc_backup_left <= 2 ? 'text-amber-500' : ''?>"><?=$acc_backup_left?></span> z 8.</p>
        <div class="mt-4 grid sm:grid-cols-2 gap-4">
            <div class="rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 p-3">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Nowe kody zapasowe</p>
                <div class="mt-2 flex gap-2">
                    <input id="acc_2fa_regen_code" type="text" inputmode="numeric" maxlength="6" placeholder="Kod z aplikacji" class="<?=$acc_input?>">
                    <button type="button" onclick="acc2faRegenerate(this)" class="<?=$acc_btn?> flex-none">Generuj</button>
                </div>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 p-3">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Wyłącz 2FA</p>
                <div class="mt-2 flex flex-col gap-2">
                    <input id="acc_2fa_off_password" type="password" autocomplete="current-password" placeholder="Hasło" class="<?=$acc_input?>">
                    <div class="flex gap-2">
                        <input id="acc_2fa_off_code" type="text" maxlength="14" placeholder="Kod 2FA lub zapasowy" class="<?=$acc_input?>">
                        <button type="button" onclick="acc2faDisable(this)" class="active:scale-95 duration-150 flex-none inline-flex justify-center rounded-full ring-rose-400 ring-1 px-4 py-2 text-sm text-rose-400 hover:bg-rose-400 hover:text-white">Wyłącz</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div id="acc_2fa_codes" class="hidden mt-5 rounded-2xl bg-amber-50 dark:bg-amber-400/10 ring-1 ring-amber-200 dark:ring-amber-400/20 p-4">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Zapisz kody zapasowe – każdy działa raz, gdy nie masz telefonu. Nie zobaczysz ich ponownie.</p>
            <div id="acc_2fa_codes_list" class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm text-gray-800 dark:text-gray-200"></div>
            <div class="mt-3 flex gap-2">
                <button type="button" onclick="acc2faCopyCodes()" class="<?=$acc_btn_muted?>">Kopiuj</button>
                <button type="button" onclick="accReload()" class="<?=$acc_btn?>">Zapisałem</button>
            </div>
        </div>
    </div>
</div>

<script>
function accChangePassword(e) {
    e.preventDefault();
    var form = document.getElementById('acc_password_form');
    if (form.new_password.value !== form.repeat_new_password.value) {
        showAlert('warning', 'Nowe hasła nie są takie same.');
        return;
    }
    hlJson('scripts/users/change_password.php', new FormData(form)).then(function (r) {
        showAlert(r.status, r.message);
        if (r.status === 'success') form.reset();
    });
}
function accLoadQr(cb) {
    if (window.QRCode) return cb();
    var s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
    s.onload = cb;
    s.onerror = cb;
    document.head.appendChild(s);
}
function acc2faSetup(btn) {
    btn.disabled = true;
    hlJson('scripts/users/two_factor_setup.php').then(function (r) {
        btn.disabled = false;
        if (r.status !== 'success') { showAlert(r.status, r.message); return; }
        document.getElementById('acc_2fa_start').classList.add('hidden');
        document.getElementById('acc_2fa_setup').classList.remove('hidden');
        document.getElementById('acc_2fa_secret').textContent = r.secret.replace(/(.{4})/g, '$1 ').trim();
        document.getElementById('acc_2fa_link').href = r.otp_auth_uri;
        accLoadQr(function () {
            var box = document.getElementById('acc_2fa_qr');
            box.innerHTML = '';
            if (window.QRCode) new QRCode(box, { text: r.otp_auth_uri, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
            else box.textContent = 'Wpisz klucz ręcznie';
        });
        document.getElementById('acc_2fa_code').focus();
    });
}
function acc2faShowCodes(codes) {
    var list = document.getElementById('acc_2fa_codes_list');
    list.innerHTML = '';
    codes.forEach(function (c) {
        var el = document.createElement('div');
        el.className = 'rounded-lg bg-white dark:bg-[#2c2c2c] px-2 py-1 text-center';
        el.textContent = c;
        list.appendChild(el);
    });
    list.dataset.codes = codes.join('\n');
    document.getElementById('acc_2fa_codes').classList.remove('hidden');
}
function acc2faCopyCodes() {
    navigator.clipboard.writeText(document.getElementById('acc_2fa_codes_list').dataset.codes || '');
    showAlert('success', 'Skopiowano kody do schowka');
}
function acc2faEnable(btn) {
    var data = new FormData();
    data.append('code', document.getElementById('acc_2fa_code').value);
    btn.disabled = true;
    hlJson('scripts/users/two_factor_enable.php', data).then(function (r) {
        btn.disabled = false;
        showAlert(r.status, r.message);
        if (r.status === 'success') {
            document.getElementById('acc_2fa_setup').classList.add('hidden');
            acc2faShowCodes(r.backup_codes || []);
        }
    });
}
function acc2faRegenerate(btn) {
    var data = new FormData();
    data.append('code', document.getElementById('acc_2fa_regen_code').value);
    btn.disabled = true;
    hlJson('scripts/users/two_factor_regenerate_backup_codes.php', data).then(function (r) {
        btn.disabled = false;
        showAlert(r.status, r.message);
        if (r.status === 'success') acc2faShowCodes(r.backup_codes || []);
    });
}
function acc2faDisable(btn) {
    var data = new FormData();
    data.append('password', document.getElementById('acc_2fa_off_password').value);
    data.append('code', document.getElementById('acc_2fa_off_code').value);
    btn.disabled = true;
    hlJson('scripts/users/two_factor_disable.php', data).then(function (r) {
        btn.disabled = false;
        showAlert(r.status, r.message);
        if (r.status === 'success') accReload();
    });
}
</script>
