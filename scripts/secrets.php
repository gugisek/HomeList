<?php
// Sekrety aplikacji (klucz szyfrowania 2FA) – generowane leniwie do scripts/app_secrets.php
// (w .gitignore, osobny plik na każdym środowisku), tak samo jak klucze VAPID w push/vapid_keys.php.
// Zmiana/utrata klucza 2FA unieważnia skonfigurowane 2FA (trzeba je włączyć ponownie).

if (!function_exists('hl_secret')) {
    define('HL_SECRETS_FILE', __DIR__ . '/app_secrets.php');

    function hl_secret(string $name): string
    {
        static $secrets = null;
        if ($secrets === null) {
            $secrets = is_file(HL_SECRETS_FILE) ? (include HL_SECRETS_FILE) : [];
            if (!is_array($secrets)) $secrets = [];
        }
        if (empty($secrets[$name])) {
            $secrets[$name] = base64_encode(random_bytes(32));
            $php = "<?php\n// Sekrety aplikacji – wygenerowane automatycznie, NIE commitować. Utrata klucza two_factor_key wyłącza działające 2FA.\nreturn " . var_export($secrets, true) . ";\n";
            file_put_contents(HL_SECRETS_FILE, $php, LOCK_EX);
        }
        return $secrets[$name];
    }
}
