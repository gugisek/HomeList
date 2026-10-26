<?php
// Wspólny szkielet stron dla niezalogowanych (login, rejestracja, aktywacja, reset hasła) – w stylu strony głównej.
// Przed include ustaw: $title (tytuł karty), $auth_form (plik formularza w components/login/),
// opcjonalnie $auth_heading, $auth_subheading, $auth_footer, $auth_visual (feed | empty | lock), $auth_note (treść powiadomienia przy 'lock').
// Zalogowany użytkownik jest od razu przenoszony do panelu (także po cichym remember-me).
require_once __DIR__ . '/../../scripts/session_restore.php';
require_once __DIR__ . '/../../scripts/csrf.php';
if (empty($auth_allow_logged) && hl_session_start()) {
    header('Location: panel.php');
    exit();
}
csrf_token();
include_once __DIR__ . '/../landing/ui.php';

if ($auth_form === 'login_form.php' && !empty($_SESSION['pending_2fa'])) {
    $auth_heading = 'Jeszcze jeden krok';
    $auth_subheading = 'Konto jest chronione weryfikacją dwuetapową.';
    $auth_visual = 'lock';
    $auth_note = ['Weryfikacja dwuetapowa', 'Wpisz kod z aplikacji uwierzytelniającej'];
}
$auth_visual = $auth_visual ?? 'feed';

// powitanie zależne od pory dnia – te same pory co w planie dnia
$auth_hour = (int)date('G');
$auth_part = $auth_hour >= 5 && $auth_hour < 12 ? 1 : ($auth_hour >= 12 && $auth_hour < 18 ? 2 : 3);
$auth_greet = [1 => ['Dzień dobry', 'sunrise', 'amber'], 2 => ['Dzień dobry', 'sun', 'orange'], 3 => ['Dobry wieczór', 'moon', 'indigo']][$auth_part];

$hl_input_cls = 'lp-input';
$hl_btn_cls = 'lp-btn lp-btn-main lp-btn-block';
$hl_label_cls = 'lp-label';
$hl_link_cls = 'lp-link';

// pole hasła z przyciskiem podglądu
function hl_password_input(string $id, string $label, string $autocomplete, string $cls): string
{
    return '<div class="lp-field">
          <label for="' . $id . '" class="lp-label">' . $label . '</label>
          <div class="lp-pass">
            <input name="' . $id . '" id="' . $id . '" type="password" required autocomplete="' . $autocomplete . '" class="' . $cls . '">
            <button type="button" tabindex="-1" aria-label="Pokaż hasło" onclick="toggleAuthToken(\'' . $id . '\', this)">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="lp-i lp-eye-on"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="lp-i lp-eye-off"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
            </button>
          </div>
        </div>';
}

// komunikat z sesji (np. błąd logowania po przekierowaniu) – pokazywany jako toast
$auth_flash = null;
if (isset($_SESSION['alert'])) {
    $auth_flash = ['type' => $_SESSION['alert_type'] ?? 'info', 'message' => (string)$_SESSION['alert']];
    unset($_SESSION['alert'], $_SESSION['alert_type']);
}
$auth_is_login = $auth_form === 'login_form.php';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>HomeList – <?=htmlspecialchars($title)?></title>
    <meta name="theme-color" content="#f5f5f3" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#262626" media="(prefers-color-scheme: dark)">
    <meta name="color-scheme" content="light dark">
    <link rel="icon" href="img/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="img/apple-touch-icon.png">
    <link rel="manifest" href="manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/landing.css?v=<?=filemtime(__DIR__ . '/../../css/landing.css')?>" rel="stylesheet">
    <link href="css/auth.css?v=<?=filemtime(__DIR__ . '/../../css/auth.css')?>" rel="stylesheet">
    <link href="css/toast.css?v=<?=filemtime(__DIR__ . '/../../css/toast.css')?>" rel="stylesheet">
</head>
<body class="lp lp-auth">
    <div class="lp-sky" aria-hidden="true"><span class="is-on" data-sky-layer="<?=$auth_greet[2]?>"></span></div>
    <div class="hl-toasts" id="alertsContainer" aria-live="polite"></div>

    <header class="lp-top lp-auth-top">
        <a href="./" class="lp-brand"><img src="img/icon.svg" alt="" width="28" height="28"><span>HomeList</span></a>
        <?php if ($auth_is_login && empty($_SESSION['pending_2fa'])): ?>
        <a href="register.php" class="lp-top-login">Załóż konto</a>
        <?php elseif (!$auth_is_login): ?>
        <a href="login.php" class="lp-top-login">Zaloguj się</a>
        <?php endif; ?>
    </header>

    <main class="lp-auth-grid">
        <section class="lp-auth-pane">
            <div class="lp-auth-box">
                <p class="lp-eyebrow lp-tone-<?=$auth_greet[2]?>"><?=lp_icon($auth_greet[1])?><span><?=$auth_greet[0]?></span><em><?=hl_long_date(date('Y-m-d'))?></em></p>
                <h1 class="lp-auth-h"><?=htmlspecialchars($auth_heading ?? $title)?></h1>
                <?php if (!empty($auth_subheading)): ?><p class="lp-auth-sub"><?=htmlspecialchars($auth_subheading)?></p><?php endif; ?>
                <div class="lp-auth-body">
                    <?php include __DIR__ . '/' . $auth_form; ?>
                </div>
                <?php if (!empty($auth_footer)) echo '<p class="lp-auth-foot">' . $auth_footer . '</p>'; ?>
            </div>
        </section>

        <aside class="lp-auth-visual" aria-hidden="true">
            <div class="lp-auth-stage" data-auth-stage>
                <?php if ($auth_visual === 'lock'): ?>
                <div class="lp-lock is-in lp-auth-lock">
                    <div class="lp-lock-time"><small><?=hl_long_date(date('Y-m-d'))?></small><b><?=date('H:i')?></b></div>
                    <div class="lp-notes">
                        <div class="lp-note lp-glass-card" style="--i:0">
                            <span class="lp-push-ico"><img src="img/icon.svg" alt=""></span>
                            <div><p><b><?=htmlspecialchars($auth_note[0] ?? 'HomeList')?></b><small>teraz</small></p><span><?=htmlspecialchars($auth_note[1] ?? '')?></span></div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="lp-phone">
                    <div class="lp-screen lp-screen-phone" data-feed>
                        <div class="lp-feed-head">
                            <div><b>Dziś</b><small><?=hl_long_date(date('Y-m-d'))?></small></div>
                            <div class="lp-feed-head-end"><?=$auth_visual === 'empty' ? '<span class="lp-plus">' . lp_icon('plus', 'lp-i', '2') . '</span>' : lp_ring(1, 4, ' data-ring')?></div>
                        </div>
                        <?=lp_day_strip(-2, 6)?>
                        <?php if ($auth_visual === 'empty'): ?>
                        <div class="lp-auth-empty">
                            <span class="lp-auth-empty-ico"><?=lp_icon('sun', 'lp-i')?></span>
                            <b>Nic tu jeszcze nie ma</b>
                            <small>Wybierz zadania z list albo dodaj nowe prosto do planu. Rutyny pojawią się tu same.</small>
                            <div><span class="lp-auth-fake-btn is-dark"><?=lp_icon('queue', 'lp-i-sm')?>Zaplanuj z list</span><span class="lp-auth-fake-btn"><?=lp_icon('plus', 'lp-i-sm')?>Nowe</span></div>
                        </div>
                        <?php else: ?>
                        <?=lp_part_head(1)?>
                        <ul class="lp-list">
                            <?=lp_item('Leki', ['routine' => true, 'done' => true, 'streak' => 41, 'meta' => [lp_icon('repeat', 'lp-i-xs') . 'Rutyny', lp_icon('clock', 'lp-i-xs') . '08:00']])?>
                        </ul>
                        <?=lp_part_head(2)?>
                        <ul class="lp-list">
                            <?=lp_item('Odebrać paczkę', ['meta' => ['Dom', '<span class="lp-tone-sky">' . lp_icon('link', 'lp-i-xs', '1.8') . '</span>']])?>
                            <?=lp_item('Zakupy na kolację', ['meta' => ['Zakupy', lp_icon('users', 'lp-i-xs')]])?>
                        </ul>
                        <?=lp_part_head(3)?>
                        <ul class="lp-list">
                            <?=lp_item('Podlać kwiaty', ['routine' => true, 'streak' => 23, 'meta' => [lp_icon('repeat', 'lp-i-xs') . 'Rutyny']])?>
                        </ul>
                        <?php endif; ?>
                        <?=lp_app_dock('Dziś')?>
                    </div>
                </div>
                <?php if ($auth_visual === 'empty'): ?>
                <div class="lp-float lp-auth-float-a lp-glass-card">
                    <span class="lp-auth-float-ico"><?=lp_icon('hash', 'lp-i')?></span>
                    <div><p><b>Masz kod listy?</b></p><span>Od domownika – wpiszesz go po zalogowaniu.</span></div>
                </div>
                <?php else: ?>
                <div class="lp-float lp-auth-float-a lp-glass-card">
                    <span class="lp-push-ico"><img src="img/icon.svg" alt=""></span>
                    <div><p><b><?=hl_parts()[$auth_part]['label']?></b><small>teraz</small></p><span><?=[1 => 'Leki i Odebrać paczkę – plan na rano', 2 => '2 zadania na start: Odebrać paczkę, Zakupy na kolację', 3 => 'Wieczorna rutyna: Podlać kwiaty'][$auth_part]?></span></div>
                </div>
                <div class="lp-float lp-float-streak lp-auth-float-b lp-glass-card">
                    <span class="lp-tone-orange"><?=lp_icon('fire', 'lp-i')?></span><b>41</b><small>dni z rzędu</small>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </aside>
    </main>

    <script src="js/toast.js?v=<?=filemtime(__DIR__ . '/../../js/toast.js')?>"></script>
    <script>
        function toggleAuthToken(id, btn) {
            const input = document.getElementById(id);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.classList.toggle('is-shown', show);
            btn.setAttribute('aria-label', show ? 'Ukryj hasło' : 'Pokaż hasło');
        }

        // wysyłka formularza AJAX-em: JSON {status, message, redirect?}
        function authSubmit(form, url) {
            const btn = form.querySelector('[type=submit]');
            if (btn) btn.disabled = true;
            return fetch(url, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (data.redirect && (data.status === 'success' || data.status === 'info')) {
                        if (data.status !== 'success') showAlert(data.status, data.message);
                        setTimeout(() => { window.location.href = data.redirect; }, data.status === 'success' ? 0 : 1200);
                        return data;
                    }
                    showAlert(data.status || 'error', data.message || 'Nieznana odpowiedź serwera');
                    if (data.redirect) setTimeout(() => { window.location.href = data.redirect; }, 1800);
                    return data;
                })
                .catch(() => showAlert('error', 'Wystąpił problem połączenia z serwerem'))
                .finally(() => { if (btn) btn.disabled = false; });
        }

        // formularze bez AJAX-a (logowanie, 2FA) – stan ładowania na przycisku
        document.querySelectorAll('form[action]').forEach(f => f.addEventListener('submit', () => {
            const btn = f.querySelector('[type=submit]');
            if (btn) setTimeout(() => { btn.disabled = true; }, 0);
        }));

        // makieta delikatnie podąża za kursorem
        (function () {
            const stage = document.querySelector('[data-auth-stage]');
            if (!stage || !matchMedia('(hover: hover) and (pointer: fine)').matches || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            window.addEventListener('pointermove', (e) => {
                const x = e.clientX / innerWidth * 2 - 1, y = e.clientY / innerHeight * 2 - 1;
                stage.style.setProperty('--mx', (x * 5).toFixed(2) + 'deg');
                stage.style.setProperty('--my', (y * -4).toFixed(2) + 'deg');
            }, { passive: true });
        })();

        <?php if ($auth_flash): ?>
        showAlert(<?=json_encode($auth_flash['type'], JSON_HEX_TAG)?>, <?=json_encode($auth_flash['message'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)?>);
        <?php endif; ?>
    </script>
    <script src="js/landing.js?v=<?=filemtime(__DIR__ . '/../../js/landing.js')?>"></script>
</body>
</html>
