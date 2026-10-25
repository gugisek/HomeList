<?php
// Wspólny szkielet stron dla niezalogowanych (login, rejestracja, aktywacja, reset hasła).
// Przed include ustaw: $title (tytuł karty), $auth_form (plik formularza w components/login/).
// Zalogowany użytkownik jest od razu przenoszony do panelu (także po cichym remember-me).
require_once __DIR__ . '/../../scripts/session_restore.php';
require_once __DIR__ . '/../../scripts/csrf.php';
if (empty($auth_allow_logged) && hl_session_start()) {
    header('Location: panel.php');
    exit();
}
csrf_token();
if ($auth_form === "login_form.php" && !empty($_SESSION["pending_2fa"])) $auth_heading = "Weryfikacja dwuetapowa";

$hl_input_cls = 'duration-150 px-4 block w-full rounded-xl border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 outline-green-400 focus:ring-inset focus:ring-green-300 sm:text-sm sm:leading-6';
$hl_btn_cls = 'flex w-full items-center justify-center gap-2 text-sm font-semibold leading-6 text-black hover:bg-green-100 px-8 py-2 bg-gray-100 rounded-xl hover:text-green-500 duration-150 active:scale-95 transition-all disabled:opacity-50 disabled:pointer-events-none';
$hl_label_cls = 'block text-sm font-medium leading-6 text-gray-900';
$hl_link_cls = 'font-semibold text-green-400 hover:text-green-300 duration-150';

// pole hasła z przyciskiem podglądu
function hl_password_input(string $id, string $label, string $autocomplete, string $cls): string
{
    return '<div>
          <label for="' . $id . '" class="block text-sm font-medium leading-6 text-gray-900">' . $label . '</label>
          <div class="mt-2 relative">
            <input name="' . $id . '" id="' . $id . '" type="password" required autocomplete="' . $autocomplete . '" class="' . $cls . ' pr-10">
            <button type="button" tabindex="-1" onclick="toggleAuthToken(\'' . $id . '\', this)" class="absolute inset-y-0 right-2 flex items-center text-gray-500 hover:text-gray-800">👁️</button>
          </div>
        </div>';
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<?php include __DIR__ . '/../head.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../alert.php'; ?>
    <?php include __DIR__ . '/../alert_js_front.php'; ?>
    <?php include __DIR__ . '/../landing_page/nav.php'; ?>
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8 mt-[8vh]">
      <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900"><?=htmlspecialchars($auth_heading ?? $title)?></h2>
        <div class="flex sm:flex-row flex-col items-center justify-center mt-2 gap-2 px-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-green-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
          </svg>
          <span class="font-[poppins] text-sm text-center"><?=htmlspecialchars($auth_subheading ?? 'HomeList - Twoja lista ToDo w Twoim domu.')?></span>
        </div>
      </div>

      <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-[480px]">
        <div class="bg-white px-6 py-12 shadow sm:rounded-2xl sm:px-12">
          <?php include __DIR__ . '/' . $auth_form; ?>
        </div>
        <?php if (!empty($auth_footer)) echo '<p class="mt-10 text-center text-sm text-gray-500">' . $auth_footer . '</p>'; ?>
      </div>
    </div>
    <?php include __DIR__ . '/../footer.php'; ?>
    <script>
        AOS.init();

        function toggleAuthToken(id, btn) {
            const input = document.getElementById(id);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? '🙈' : '👁️';
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
    </script>
</body>
</html>
