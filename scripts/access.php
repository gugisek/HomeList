<?php
// Kontrola dostępu do list i zadań: właściciel listy albo zaproszony (list_invities).
// Używane przez scripts/dashboard/* i widoki components/panel/dashboard/*.

if (!function_exists('hl_list_access')) {
    // ['role' => 'owner'|'member', 'list' => wiersz lists] albo null
    function hl_list_access(mysqli $conn, int $userId, int $listId): ?array
    {
        if ($listId <= 0 || $userId <= 0) return null;
        $stmt = $conn->prepare(
            'SELECT lists.*, (SELECT COUNT(*) FROM list_invities WHERE list_invities.list_id = lists.id AND list_invities.user_id = ?) AS is_member
             FROM lists WHERE lists.id = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $userId, $listId);
        $stmt->execute();
        $list = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$list) return null;
        if ((int)$list['owner_id'] === $userId) return ['role' => 'owner', 'list' => $list];
        if ((int)$list['is_member'] > 0) return ['role' => 'member', 'list' => $list];
        return null;
    }

    // wiersz zadania (+ list_type, list_name, access) jeśli użytkownik ma dostęp do jego listy, inaczej null
    function hl_element_access(mysqli $conn, int $userId, int $elementId): ?array
    {
        if ($elementId <= 0) return null;
        $stmt = $conn->prepare('SELECT list_id FROM list_elements WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $elementId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return null;

        $access = hl_list_access($conn, $userId, (int)$row['list_id']);
        if (!$access) return null;

        $stmt = $conn->prepare(
            'SELECT list_elements.*, lists.list_type, lists.full_name AS list_name, users.name, users.sur_name
             FROM list_elements
             JOIN lists ON lists.id = list_elements.list_id
             LEFT JOIN users ON users.id = list_elements.creator_id
             WHERE list_elements.id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $elementId);
        $stmt->execute();
        $element = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$element) return null;
        $element['access'] = $access['role'];
        return $element;
    }

    function hl_json_exit(string $status, string $message, array $extra = []): void
    {
        echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
        exit();
    }

    // termin z formularza (datetime-local / date) -> 'Y-m-d H:i:s' albo null
    function hl_parse_deadline($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') return null;
        $ts = strtotime($value);
        return $ts === false ? null : date('Y-m-d H:i:s', $ts);
    }

    // opcjonalny link (tylko http/https)
    function hl_parse_link($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '' || strlen($value) > 2000 || !filter_var($value, FILTER_VALIDATE_URL)) return null;
        return in_array(strtolower((string)parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true) ? $value : null;
    }

    // Pola rutyny z hl_routine_fields_from_post() (fragmenty SQL z już zwalidowanych wartości)
    // -> kolumna => wartość PHP do bind_param
    function hl_routine_bind_values(array $routine): array
    {
        $out = [];
        foreach ($routine as $col => $sql) {
            if ($col === '_start') continue;
            $out[$col] = $sql === 'NULL' ? null : trim($sql, "'");
        }
        return $out;
    }
}
