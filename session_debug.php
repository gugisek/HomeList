<?php
// TYMCZASOWA diagnostyka sesji na produkcji – usunąć po sprawdzeniu.
// Otwórz 2-3 razy z rzędu: "licznik" powinien rosnąć, a "id sesji" się nie zmieniać.
require_once __DIR__ . '/scripts/session_bootstrap.php';

$_SESSION['hl_debug_counter'] = ($_SESSION['hl_debug_counter'] ?? 0) + 1;
$savePath = session_save_path() ?: sys_get_temp_dir();
$mask = fn($v) => $v === '' ? '(brak)' : substr($v, 0, 6) . '…';

header('Content-Type: text/plain; charset=utf-8');
print_r([
    'licznik (powinien rosnąć)' => $_SESSION['hl_debug_counter'],
    'id sesji' => $mask(session_id()),
    'PHPSESSID wysłany przez przeglądarkę' => $mask((string)($_COOKIE[session_name()] ?? '')),
    'liczba ciasteczek PHPSESSID w nagłówku' => substr_count((string)($_SERVER['HTTP_COOKIE'] ?? ''), session_name() . '='),
    'nazwy ciasteczek' => array_keys($_COOKIE),
    'zalogowany w sesji' => isset($_SESSION['logged']) ? 'tak' : 'nie',
    'csrf_token w sesji' => isset($_SESSION['csrf_token']) ? 'tak' : 'nie',
    'appIsHttps()' => appIsHttps(),
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
    'parametry ciasteczka' => session_get_cookie_params(),
    'session.save_handler' => ini_get('session.save_handler'),
    'session.save_path' => $savePath,
    'save_path zapisywalny' => is_writable($savePath),
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'session.use_strict_mode' => ini_get('session.use_strict_mode'),
    'Set-Cookie w tej odpowiedzi' => array_values(array_filter(headers_list(), fn($h) => stripos($h, 'Set-Cookie') === 0 && stripos($h, session_name()) !== false)) ? 'tak' : 'nie',
    'PHP' => PHP_VERSION,
]);
