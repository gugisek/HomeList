<?php
// Wspólne dla rejestracji, aktywacji i dodawania kont przez admina.

require_once __DIR__ . '/../mailer.php';
require_once __DIR__ . '/../log_lib.php';

if (!function_exists('hl_send_activation_code')) {
    // Nowy 6-cyfrowy kod (ważny 24h) + mail. Zwraca wynik send_app_mail().
    function hl_send_activation_code(mysqli $conn, int $userId, string $email): array
    {
        $code = sprintf('%06d', random_int(0, 999999));
        $expiresAt = date('Y-m-d H:i:s', time() + 24 * 60 * 60);

        // poprzednie kody przestają działać – ważny jest zawsze tylko najnowszy
        $stmt = $conn->prepare('UPDATE activation_tokens SET used = 1 WHERE user_id = ? AND used = 0');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO activation_tokens (user_id, token, email, expires_at) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $userId, $code, $email, $expiresAt);
        $stmt->execute();
        $stmt->close();

        $content = '<p>Dziękujemy za rejestrację w HomeList. Aby aktywować konto, wpisz poniższy kod:</p>'
            . hl_mail_code($code)
            . '<p>Kod jest ważny przez 24 godziny.</p>'
            . "<p style='color:#6b7280;'>Jeśli to nie Ty zakładałeś konto, zignoruj tę wiadomość.</p>";
        return send_app_mail(['to' => $email, 'subject' => 'Aktywacja konta HomeList', 'body' => $content]);
    }

    // Unikalny 6-znakowy kod zaproszenia do listy
    function hl_new_list_code(mysqli $conn): string
    {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stmt = $conn->prepare('SELECT id FROM lists WHERE list_code = ? LIMIT 1');
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) $code .= $alphabet[random_int(0, 35)];
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $exists = (bool)$stmt->get_result()->fetch_assoc();
        } while ($exists);
        $stmt->close();
        return $code;
    }

    // Startowa lista "ToDo" dla nowego konta
    function hl_create_default_list(mysqli $conn, int $ownerId): void
    {
        $code = hl_new_list_code($conn);
        $stmt = $conn->prepare("INSERT INTO lists (name, full_name, owner_id, list_code) VALUES ('ToDo', 'ToDo', ?, ?)");
        $stmt->bind_param('is', $ownerId, $code);
        if ($stmt->execute()) {
            hl_log($conn, $stmt->insert_id, 'List', '', 'Nazwa: Start ToDo <br/>Owner: ' . $ownerId . ' <br/>Kod: ' . $code, 2, 'Dodano listę', $ownerId);
        }
        $stmt->close();
    }
}
