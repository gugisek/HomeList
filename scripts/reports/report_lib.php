<?php
// Zgłoszenia błędów: zapis zrzutów ekranu i odbiorcy powiadomień.
// Zrzuty leżą w uploads/reports/ (Require all denied) – wydaje je tylko screenshot.php po sprawdzeniu uprawnień.

if (!function_exists('hl_report_dir')) {
    define('HL_REPORT_MAX_SHOTS', 3);

    function hl_report_dir(): string
    {
        $dir = __DIR__ . '/../../uploads/reports/';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        return $dir;
    }

    // Typ ustalany z zawartości pliku (jak hl_save_avatar). Zwraca ['ok' => true, 'file' => nazwa] albo ['ok' => false, 'error' => ...]
    function hl_report_save_shot(array $upload, int $userId, int $n): array
    {
        if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            return ['ok' => false, 'error' => 'Nie udało się przesłać zrzutu ekranu'];
        }
        if ($upload['size'] > 8000000) {
            return ['ok' => false, 'error' => 'Zrzut ekranu jest za duży (maks. 8 MB)'];
        }
        $info = @getimagesize($upload['tmp_name']);
        $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($types[$info[2]])) {
            return ['ok' => false, 'error' => 'Zrzut ekranu musi być obrazkiem (JPG, PNG, GIF lub WEBP)'];
        }
        $file = 'br-' . $userId . '_' . time() . '_' . $n . '_' . bin2hex(random_bytes(4)) . '.' . $types[$info[2]];
        if (!move_uploaded_file($upload['tmp_name'], hl_report_dir() . $file)) {
            return ['ok' => false, 'error' => 'Nie udało się zapisać zrzutu ekranu'];
        }
        return ['ok' => true, 'file' => $file];
    }

    function hl_report_shots(?string $json): array
    {
        $list = json_decode((string)$json, true);
        return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }

    // "Android · Chrome" z user agenta (jak w Konto > Urządzenia)
    function hl_report_device(?string $ua): string
    {
        $ua = (string)$ua;
        $os = preg_match('/iPhone|iPad/i', $ua) ? 'iPhone / iPad' : (preg_match('/Android/i', $ua) ? 'Android' : (preg_match('/Windows/i', $ua) ? 'Windows' : (preg_match('/Mac OS/i', $ua) ? 'Mac' : (preg_match('/Linux/i', $ua) ? 'Linux' : 'Urządzenie'))));
        $br = preg_match('/Edg\//', $ua) ? 'Edge' : (preg_match('/Chrome\//', $ua) ? 'Chrome' : (preg_match('/Firefox\//', $ua) ? 'Firefox' : (preg_match('/Safari\//', $ua) ? 'Safari' : 'przeglądarka')));
        return $os . ' · ' . $br;
    }

    // aktywne konta z dostępem do modułu zgłoszeń i poprawnym adresem email
    function hl_report_recipients(mysqli $conn): array
    {
        $out = [];
        $result = $conn->query(
            'SELECT users.id, users.mail, users.name FROM users
             JOIN user_roles ON user_roles.id = users.role_id
             JOIN user_status ON user_status.id = users.status_id
             JOIN status_privileges ON status_privileges.id = user_status.privileges
             WHERE user_roles.reports IN (1, 2, 3, 4) AND status_privileges.login = 1'
        );
        while ($row = $result->fetch_assoc()) {
            if (filter_var($row['mail'], FILTER_VALIDATE_EMAIL)) $out[] = $row;
        }
        return $out;
    }
}
