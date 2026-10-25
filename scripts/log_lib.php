<?php
// Zapis do tabeli logs (archiwum zmian). Wywoływane przez log.php / log_stripped*.php, które
// zachowują dotychczasowy interfejs zmiennych ($object_id, $object_type, $before, $after, $action_type, $desc).

if (!function_exists('hl_log')) {
    function hl_log(mysqli $conn, $object_id, $object_type, $before, $after, $action_type, $desc, ?int $user_id = null): void
    {
        $user_id = $user_id ?? (int)($_SESSION['login_id'] ?? 0);
        $object_id = (int)$object_id;
        $action_type = (int)$action_type;
        $object_type = (string)$object_type;
        $before = (string)$before;
        $after = (string)$after;
        $desc = (string)$desc;
        $stmt = $conn->prepare('INSERT INTO `logs` (`user_id`, `when`, `object_id`, `object_type`, `before`, `after`, `type`, `description`) VALUES (?, current_timestamp(), ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iisssis', $user_id, $object_id, $object_type, $before, $after, $action_type, $desc);
        $stmt->execute();
        $stmt->close();
    }
}
