<?php
// Odczyt/zapis ustawień aplikacji z tabeli app_settings (SMTP, rejestracja).

if (!function_exists('hl_settings')) {
    function hl_settings(mysqli $conn, bool $refresh = false): array
    {
        static $cache = null;
        if ($cache !== null && !$refresh) return $cache;
        $cache = [];
        $result = $conn->query('SELECT name_type, value FROM app_settings');
        if ($result) {
            while ($row = $result->fetch_assoc()) $cache[$row['name_type']] = (string)$row['value'];
        }
        return $cache;
    }

    function hl_setting(mysqli $conn, string $name, string $default = ''): string
    {
        $settings = hl_settings($conn);
        return array_key_exists($name, $settings) ? $settings[$name] : $default;
    }

    function hl_setting_save(mysqli $conn, string $name, string $value): bool
    {
        $stmt = $conn->prepare('INSERT INTO app_settings (name_type, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $stmt->bind_param('ss', $name, $value);
        $ok = $stmt->execute();
        $stmt->close();
        hl_settings($conn, true);
        return $ok;
    }

    // czy wysyłka maili jest skonfigurowana (bez SMTP aktywacja kodem jest pomijana)
    function hl_mail_configured(mysqli $conn): bool
    {
        return trim(hl_setting($conn, 'smtp_server')) !== '' && trim(hl_setting($conn, 'smtp_email')) !== '';
    }
}
