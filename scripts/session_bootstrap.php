<?php
// Start sesji z parametrami ciasteczek dopasowanymi do środowiska (wzorzec z panels-mOsiedle-v2):
// na produkcji (HTTPS) Secure + SameSite=None, lokalnie po zwykłym HTTP (XAMPP) Lax bez Secure –
// inaczej przeglądarka odrzuca ciasteczko SameSite=None bez Secure i sesja nigdy się nie zapisuje.
// Wcześniej domena i Secure były wpisane na sztywno, przez co logowanie działało tylko na produkcji.

if (!function_exists('appCookieParams')) {
    // Domena produkcyjna – stare ciasteczka remember_me były ustawiane z jawnym domain=homelist.rgbpc.pl,
    // więc dla tego hosta zostajemy przy tej samej wartości (inaczej nie dałoby się ich nadpisać ani skasować).
    function appCookieDomain(): ?string
    {
        $host = strtolower(explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''))[0]);
        return $host === 'homelist.rgbpc.pl' ? 'homelist.rgbpc.pl' : null;
    }

    function appIsHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
        if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;

        // za reverse-proxy terminującym TLS (nginx, Cloudflare...) PHP widzi zwykłe HTTP
        $forwardedProto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        if ($forwardedProto !== '' && trim(explode(',', $forwardedProto)[0]) === 'https') return true;
        if (strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''))) === 'on') return true;
        if ((int)($_SERVER['HTTP_X_FORWARDED_PORT'] ?? 0) === 443) return true;

        return strtolower((string)($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https';
    }

    function appCookieParams(): array
    {
        $isHttps = appIsHttps();
        $params = [
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => $isHttps ? 'None' : 'Lax',
        ];
        $domain = appCookieDomain();
        if ($domain !== null) $params['domain'] = $domain;
        return $params;
    }

    // Jedno miejsce kasowania remember_me – zawsze z tymi samymi atrybutami co zapis,
    // inaczej przeglądarka trzyma "ducha" ciasteczka, którego nie da się usunąć.
    function appClearRememberCookie(): void
    {
        unset($_COOKIE['remember_me']);
        if (headers_sent()) return;
        setcookie('remember_me', '', ['expires' => time() - 3600] + appCookieParams());
    }

    // Bazowy adres aplikacji (do linków w mailach), np. https://homelist.rgbpc.pl/
    function appBaseUrl(): string
    {
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '') return '';
        $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
        $docRoot = str_replace('\\', '/', realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: '');
        $path = ($docRoot !== '' && stripos($root, $docRoot) === 0) ? substr($root, strlen($docRoot)) : '';
        return (appIsHttps() ? 'https' : 'http') . '://' . $host . rtrim($path, '/') . '/';
    }
}

// Własna nazwa ciasteczka sesji zamiast domyślnego PHPSESSID: na produkcji przeglądarka wysyłała dwa
// PHPSESSID (stare host-only / z domeny nadrzędnej rgbpc.pl), PHP brał osieroconą sesję bez logowania
// i tokenu CSRF – każde żądanie logowało się od nowa z remember_me, a każdy POST kończył się 403.
const HL_SESSION_NAME = 'HLSESSID';

if (session_status() === PHP_SESSION_NONE) {
    session_name(HL_SESSION_NAME);
    session_set_cookie_params(['lifetime' => 0] + appCookieParams());
    session_start();
}
