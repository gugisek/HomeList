<?php
// Ochrona CSRF. Token żyje w sesji; panel.php wystawia go w <meta name="csrf-token">, a globalny
// wrapper fetch() i nasłuch submit (components/panel/csrf_js.php) dokładają go do każdego POST-a.
// Weryfikacja odbywa się automatycznie w scripts/security.php dla każdego żądania POST.

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function csrf_verify(): bool
    {
        $expected = $_SESSION['csrf_token'] ?? '';
        if ($expected === '') return false;

        $provided = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($provided) && $provided !== '' && hash_equals($expected, $provided);
    }

    function csrf_input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
