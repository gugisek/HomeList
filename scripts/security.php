<?php
// Uniwersalny guard dołączany na starcie każdego chronionego pliku (wzorzec z panels-mOsiedle-v2).
// Sprawdza: zalogowanie (z cichym przywróceniem remember_me), czy konto nadal może się logować
// i czy sesja nie została unieważniona (reset hasła / "wyloguj wszędzie"), token CSRF dla POST
// oraz – opcjonalnie – poziom uprawnień roli do modułu.
//
// Endpoint JSON (fetch):
//   $SECURITY_REQUIRED_MODULE = ['users', 'edit'];   // opcjonalnie: moduł + minimalny poziom
//   include '../security_scripts.php';
//
// Widok panelu (fragment HTML ładowany przez openPanelSite / openDetailTab / popup):
//   $SECURITY_REQUIRED_MODULE = ['logs', 'read'];
//   include '../../../scripts/security.php';
//
// Poziomy: read < edit < add < full (user_role_privileges). Węższe sprawdzenie w trakcie skryptu:
//   hl_require('dashboard', 'full');
//
// Odpowiedź przy odmowie zależy od rodzaju żądania: JSON (fetch / $SECURITY_JSON), fragment HTML
// (components/), albo przekierowanie z komunikatem (zwykły formularz / strona).

require_once __DIR__ . '/session_restore.php';
require_once __DIR__ . '/csrf.php';

if (!function_exists('security_deny')) {
    function hl_request_wants_json(): bool
    {
        global $SECURITY_JSON;
        return !empty($SECURITY_JSON) || !empty($_SERVER['HTTP_X_HL_AJAX'])
            || stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
    }

    // $destroySession = false: odmowa przy w pełni ważnej sesji (brak uprawnień, zły CSRF) –
    // nie wylogowujemy za to użytkownika, tylko odmawiamy tej jednej akcji.
    function security_deny(string $reason, string $message, bool $destroySession = true): void
    {
        global $conn;
        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $isFragment = strpos($scriptPath, '/components/') !== false;
        $status = $reason === 'too_large' ? 413 : ($reason === 'forbidden' || $reason === 'csrf' ? 403 : 401);

        if ($destroySession) {
            if (!empty($_SESSION['logged'])) {
                if ($conn instanceof mysqli) hl_destroy_session($conn);
                hl_flash_session($message, 'warning');
            } else {
                // niezalogowany – nie ma czego niszczyć, a sesja może trzymać rozpoczęte logowanie 2FA
                $_SESSION['alert'] = $message;
                $_SESSION['alert_type'] = 'warning';
            }
            if (!headers_sent()) header('X-Session-Expired: 1');
        }

        global $SECURITY_JSON;
        if (!empty($SECURITY_JSON) || (!$isFragment && hl_request_wants_json())) {
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => $destroySession ? 'warning' : 'error',
                'code' => $reason,
                'session_expired' => $destroySession,
                'message' => $message,
            ]);
            exit();
        }

        if ($isFragment) {
            http_response_code($status);
            $msg = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            echo $destroySession
                ? '<div data-session-expired="1" class="p-6 text-sm text-gray-500">' . $msg . '</div>'
                : '<div data-panel-forbidden="1" class="mt-[30vh] flex flex-col items-center gap-2 p-6 text-center text-sm text-gray-500 dark:text-gray-400">'
                  . '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10 text-gray-300 dark:text-gray-600"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>'
                  . $msg . '</div>';
            exit();
        }

        // link z kodu QR (panel.php?join=KOD) – kod czeka w cookie, panel_body.php dokończy dołączanie po zalogowaniu
        if ($destroySession && preg_match('/^[A-Za-z0-9]{6}$/', (string)($_GET['join'] ?? '')) && !headers_sent()) {
            setcookie('hl_join', strtoupper($_GET['join']), ['expires' => time() + 1800, 'path' => '/', 'samesite' => 'Lax']);
        }
        // klasyczny formularz albo strona – wracamy z komunikatem
        if (!$destroySession) {
            $_SESSION['alert'] = $message;
            $_SESSION['alert_type'] = 'error';
        }
        header('Location: ' . hl_app_root_rel() . ($destroySession ? 'login.php' : 'panel.php'));
        exit();
    }

    // Względna ścieżka z katalogu wykonywanego skryptu do katalogu głównego aplikacji ("", "../../" ...)
    function hl_app_root_rel(): string
    {
        $appRoot = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
        $scriptDir = str_replace('\\', '/', dirname((string)realpath($_SERVER['SCRIPT_FILENAME'] ?? '')));
        $rel = trim(substr($scriptDir, strlen($appRoot)), '/');
        return $rel === '' ? '' : str_repeat('../', substr_count($rel, '/') + 1);
    }

    // Wartość z php.ini ("40M", "2G", "512K") w bajtach; 0/brak = bez limitu
    function hl_ini_bytes(string $key): int
    {
        $v = trim((string)ini_get($key));
        if ($v === '' || (int)$v <= 0) return PHP_INT_MAX;
        $n = (int)$v;
        switch (strtolower(substr($v, -1))) {
            case 'g': $n *= 1024;
            case 'm': $n *= 1024;
            case 'k': $n *= 1024;
        }
        return $n;
    }

    function hl_format_mb(int $bytes): string
    {
        return rtrim(rtrim(number_format($bytes / 1048576, 1, ',', ''), '0'), ',') . ' MB';
    }

    // Wymagany poziom uprawnień do modułu w trakcie skryptu (np. dopiero po odczytaniu akcji)
    function hl_require(string $module, string $level = 'read'): void
    {
        if (!hl_can($module, $level)) {
            security_deny('forbidden', 'Nie masz uprawnień do wykonania tej operacji.', false);
        }
    }
}

// conn_db.php zwykłym include (nie _once) – mógł być już wczytany wewnątrz funkcji (hl_session_start),
// a wtedy require_once nie utworzyłby $conn w tym zakresie; sam plik i tak nie otwiera drugiego połączenia
include __DIR__ . '/conn_db.php';

// security.php bywa includowany kilka razy w jednym żądaniu (widok + zagnieżdżone fragmenty) –
// pełna weryfikacja sesji raz, sprawdzenie modułu przy każdym include
if (!defined('HL_SECURITY_PASSED')) {
    if (!hl_session_start()) {
        security_deny('session_expired', 'Hola hola! Zaloguj się!');
    }

    // Odświeżenie danych konta przy każdym żądaniu: blokada konta, zmiana roli czy reset hasła
    // działają od razu, a nie dopiero po wygaśnięciu sesji.
    $securityUser = hl_auth_user($conn, 'id', (int)($_SESSION['login_id'] ?? 0));
    if (!$securityUser || (int)$securityUser['can_login'] !== 1) {
        security_deny('blocked', 'Konto jest nieaktywne lub zablokowane. Skontaktuj się z administratorem.');
    }
    if (!hash_equals((string)($_SESSION['auth_fp'] ?? ''), hl_auth_fingerprint($securityUser))) {
        security_deny('session_revoked', 'Sesja została zakończona (zmiana hasła lub wylogowanie ze wszystkich urządzeń). Zaloguj się ponownie.');
    }
    hl_apply_user_session($securityUser);
    unset($securityUser);

    // żądanie większe niż post_max_size – PHP odrzuca całe $_POST/$_FILES (razem z tokenem CSRF),
    // więc bez tego użytkownik dostałby mylący komunikat o tokenie zamiast o rozmiarze pliku
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && empty($_POST) && empty($_FILES)
        && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > hl_ini_bytes('post_max_size')) {
        security_deny('too_large', 'Przesłany plik jest za duży (maks. ' . hl_format_mb(hl_ini_bytes('post_max_size')) . ').', false);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && empty($SECURITY_SKIP_CSRF) && !csrf_verify()) {
        // aktualny token w odpowiedzi – wrapper fetch (csrf_js.php) podmienia nim stary i ponawia żądanie raz
        if (!headers_sent()) header('X-CSRF-Token: ' . csrf_token());
        security_deny('csrf', 'Nieprawidłowy token bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', false);
    }

    define('HL_SECURITY_PASSED', true);
}

if (!empty($SECURITY_REQUIRED_MODULE)) {
    hl_require((string)$SECURITY_REQUIRED_MODULE[0], (string)($SECURITY_REQUIRED_MODULE[1] ?? 'read'));
}
