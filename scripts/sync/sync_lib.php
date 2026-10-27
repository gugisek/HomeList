<?php
// Synchronizacja na żywo: „odcisk” stanu widoku liczony z kilku indeksowanych agregatów
// (COUNT / MAX(id) / MAX(updated_at)). Klient porównuje go co kilka sekund (scripts/sync/state.php)
// i dopiero przy różnicy pobiera widok od nowa – serwer nie renderuje niczego „na zapas”.
//
// Zakresy (scope): list:ID | today | archive | food. Token zmienia się przy dodaniu / edycji / usunięciu zadania,
// zmianie planu dnia, odhaczeniu rutyny, zmianie nazwy listy i składu jej członków, a także o północy.
// Kolumny updated_at dodaje database/migration_sync.sql – bez niej wykrywane są tylko dodania i usunięcia.

if (!function_exists('hl_sync_state')) {
    // listy użytkownika: id => [name, full_name, list_type, owner_id]
    function hl_sync_lists(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare(
            'SELECT id, name, full_name, list_type, owner_id FROM lists
             WHERE owner_id = ? OR id IN (SELECT list_id FROM list_invities WHERE user_id = ?) ORDER BY id'
        );
        $stmt->bind_param('ii', $userId, $userId);
        $stmt->execute();
        $lists = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $lists[(int)$row['id']] = $row;
        $stmt->close();
        return $lists;
    }

    // agregaty jako tekst; '?' gdy zapytanie się nie powiodło (np. brak kolumny updated_at przed migracją)
    function hl_sync_agg(mysqli $conn, string $sql, array $ids, string $types = '', array $params = []): string
    {
        if (!$ids) return '-';
        $sql = str_replace('{ids}', implode(',', array_map('intval', $ids)), $sql);
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) return '?';
            if ($types !== '') $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_NUM);
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            return '?';
        }
        return json_encode($rows);
    }

    // ['token' => ..., 'nav' => ..., 'shared' => bool]
    function hl_sync_state(mysqli $conn, int $userId, string $scope): array
    {
        $lists = hl_sync_lists($conn, $userId);
        $ids = array_keys($lists);

        // skład członków list – zmienia nawigację (nowa lista wspólna) i nagłówek listy (awatary)
        $members = [];
        if ($ids) {
            $res = $conn->query('SELECT list_id, COUNT(*), MAX(id), SUM(user_id) FROM list_invities WHERE list_id IN ('.implode(',', $ids).') GROUP BY list_id');
            while ($row = $res->fetch_row()) $members[(int)$row[0]] = $row;
        }
        $shared = [];
        foreach ($lists as $id => $l) {
            if (isset($members[$id]) && ((int)$members[$id][1] > 1 || (int)$members[$id][3] !== (int)$l['owner_id'])) $shared[] = $id;
        }
        $nav = substr(md5(json_encode([array_map(fn($l) => [$l['name'], $l['full_name'], $l['list_type']], $lists), $members])), 0, 16);

        // Jedzenie: prywatny dziennik (zmiany z innego urządzenia), cel i ustawienia modułu
        if ($scope === 'food') {
            $parts = [
                hl_sync_agg($conn, 'SELECT COUNT(*), MAX(id), MAX(updated_at) FROM food_log WHERE user_id = ?', [$userId], 'i', [$userId]),
                hl_sync_agg($conn, 'SELECT COUNT(*), MAX(id), MAX(kcal + protein + fat + carbs) FROM food_goals WHERE user_id = ?', [$userId], 'i', [$userId]),
                hl_sync_agg($conn, 'SELECT enabled, meals FROM food_settings WHERE user_id = ?', [$userId], 'i', [$userId]),
                date('Y-m-d'),
            ];
            return ['token' => substr(md5($scope.json_encode($parts)), 0, 16), 'nav' => $nav, 'shared' => false];
        }

        if (preg_match('/^list:(\d+)$/', $scope, $m)) {
            $listId = (int)$m[1];
            if (!isset($lists[$listId])) return ['token' => 'gone', 'nav' => $nav, 'shared' => false];
            $scopeIds = [$listId];
            $isShared = in_array($listId, $shared, true);
            $parts = [$lists[$listId], $members[$listId] ?? null];
        } elseif ($scope === 'archive') {
            $scopeIds = array_keys(array_filter($lists, fn($l) => (int)$l['owner_id'] === $userId));
            $isShared = false;
            $parts = [];
        } else {
            $scope = 'today';
            $scopeIds = $ids;
            $isShared = (bool)$shared;
            $parts = [array_map(fn($l) => $l['full_name'], $lists)];
        }

        $sql = 'SELECT COUNT(*), MAX(id), MAX(updated_at) FROM list_elements WHERE list_id IN ({ids})';
        $elements = hl_sync_agg($conn, $sql, $scopeIds);
        if ($elements === '?') $elements = hl_sync_agg($conn, 'SELECT COUNT(*), MAX(id) FROM list_elements WHERE list_id IN ({ids})', $scopeIds);
        $parts[] = $elements;

        if ($scope !== 'archive') {
            // prywatny plan dnia (etykiety „zaplanowane” na listach, cały feed na Dziś)
            $plan = hl_sync_agg($conn, 'SELECT COUNT(*), MAX(id), MAX(updated_at) FROM day_plan WHERE user_id = ?', [$userId], 'i', [$userId]);
            if ($plan === '?') $plan = hl_sync_agg($conn, 'SELECT COUNT(*), MAX(id) FROM day_plan WHERE user_id = ?', [$userId], 'i', [$userId]);
            $parts[] = $plan;
            // odhaczenia rutyn z ostatniego tygodnia (wspólne odhaczenia innych osób też)
            $since = date('Y-m-d', strtotime('-7 days'));
            $parts[] = hl_sync_agg($conn,
                'SELECT COUNT(*), MAX(routine_done.id) FROM routine_done JOIN list_elements ON list_elements.id = routine_done.element_id
                 WHERE list_elements.list_id IN ({ids}) AND routine_done.done_date >= ?', $scopeIds, 's', [$since]);
        }
        // kapsułka kcal w nagłówku Dziś (moduł Jedzenie)
        if ($scope === 'today') {
            $parts[] = hl_sync_agg($conn, 'SELECT COUNT(*), MAX(id), MAX(updated_at) FROM food_log WHERE user_id = ?', [$userId], 'i', [$userId]);
        }
        // nowy dzień = inne terminy, zaległe i archiwum
        $parts[] = date('Y-m-d');

        return ['token' => substr(md5($scope.json_encode($parts)), 0, 16), 'nav' => $nav, 'shared' => $isShared];
    }

    // znacznik w renderowanym widoku – token liczony PRZED pobraniem danych, więc zmiana w trakcie
    // renderowania da różnicę przy najbliższym sprawdzeniu, a nie zgubi się
    function hl_sync_tag(mysqli $conn, int $userId, string $scope): string
    {
        $s = hl_sync_state($conn, $userId, $scope);
        return '<meta id="hl_sync" data-scope="'.htmlspecialchars($scope, ENT_QUOTES).'" data-token="'.$s['token'].'" data-nav="'.$s['nav'].'" data-shared="'.($s['shared'] ? '1' : '0').'">';
    }
}
