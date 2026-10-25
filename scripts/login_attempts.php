<?php
// Zapis prób logowania (udanych i nieudanych) do login_attempts – oddzielnie od logs.
// Na tej tabeli opiera się też throttling brute-force (hl_failed_attempts).

if (!function_exists('record_login_attempt')) {
    function hl_client_ip(): ?string
    {
        return filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: null;
    }

    function record_login_attempt(mysqli $conn, ?int $userId, string $loginInput, bool $success, string $method, ?string $failureReason = null): void
    {
        $ip = hl_client_ip();
        $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $successInt = $success ? 1 : 0;
        $loginInput = substr($loginInput, 0, 190);

        $stmt = $conn->prepare(
            'INSERT INTO login_attempts (user_id, login_input, success, method, failure_reason, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) return;
        $stmt->bind_param('isissss', $userId, $loginInput, $successInt, $method, $failureReason, $ip, $userAgent);
        $stmt->execute();
        $stmt->close();
    }

    // Liczba nieudanych prób w ostatnich $minutes minutach, per login (bez rozróżniania wielkości liter)
    // albo per IP. $methods – metody, które się liczą (np. ['password'] albo ['2fa', 'backup_code']).
    function hl_failed_attempts(mysqli $conn, string $field, $value, array $methods, int $minutes = 15): int
    {
        if ($value === null || $value === '') return 0;
        $in = implode(',', array_fill(0, count($methods), '?'));
        $where = [
            'login' => 'LOWER(login_input) = LOWER(?)',
            'ip' => 'ip_address = ?',
            'user' => 'user_id = ?',
        ][$field];
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM login_attempts
                                WHERE $where AND success = 0 AND method IN ($in)
                                  AND created_at > NOW() - INTERVAL $minutes MINUTE");
        $types = ($field === 'user' ? 'i' : 's') . str_repeat('s', count($methods));
        $stmt->bind_param($types, $value, ...$methods);
        $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();
        return $count;
    }
}
