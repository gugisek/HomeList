<?php
// Wyszukiwarka produktów (GET q, meal).
// Bez zapytania: „Ostatnio i często” (najpierw to, co jadasz w tym posiłku), dania domowe, Twoje produkty.
// Z zapytaniem: baza lokalna; ranking = Twoja historia > historia domowników > początek nazwy > produkty podstawowe > popularność w OFF.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$meal = (int)($_GET['meal'] ?? 0);
$q = hl_food_norm(mb_substr((string)($_GET['q'] ?? ''), 0, 100));
$vis = hl_food_visible_sql($conn, $user_id);
$home = implode(',', hl_food_household($conn, $user_id));

$fetch = function (string $sql) use ($conn): array {
    $rows = [];
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) $rows[] = $row;
    return $rows;
};
$groups = [];

if ($q === '') {
    $recent = $fetch("SELECT p.*, u.in_meal, u.last FROM food_products p
                      JOIN (SELECT product_id, SUM(meal = $meal) AS in_meal, COUNT(*) AS cnt, MAX(created_at) AS last FROM food_log
                            WHERE user_id = $user_id AND product_id IS NOT NULL AND created_at > NOW() - INTERVAL 90 DAY
                            GROUP BY product_id) u ON u.product_id = p.id
                      WHERE $vis
                      ORDER BY (u.in_meal > 0) DESC, u.in_meal + u.cnt DESC, u.last DESC LIMIT 12");
    if ($recent) $groups[] = ['key' => 'recent', 'title' => 'Ostatnio i często', 'items' => hl_food_rows($conn, $user_id, $recent)];

    $dishes = $fetch("SELECT p.* FROM food_products p JOIN food_dishes d ON d.product_id = p.id
                      WHERE p.source = 'dish' AND d.archived = 0 AND d.owner_id IN ($home)
                      ORDER BY d.updated_at DESC LIMIT 8");
    if ($dishes) $groups[] = ['key' => 'dishes', 'title' => 'Dania domowe', 'items' => hl_food_rows($conn, $user_id, $dishes)];

    $own = $fetch("SELECT p.* FROM food_products p WHERE p.source = 'user' AND p.owner_id = $user_id AND p.status <> 'rejected'
                   ORDER BY p.id DESC LIMIT 6");
    if ($own) $groups[] = ['key' => 'own', 'title' => 'Twoje produkty', 'items' => hl_food_rows($conn, $user_id, $own)];

    hl_food_json('success', '', ['groups' => $groups, 'query' => '']);
}

$tokens = array_slice(array_filter(explode(' ', $q), fn($t) => $t !== ''), 0, 6);
$where = [];
foreach ($tokens as $t) {
    // odmiana: „jajko” ma znaleźć też „jajka”, „kurczak” – „kurczaka” (ucinamy końcówkę dłuższych słów)
    $len = strlen($t);
    $stem = $len >= 7 ? substr($t, 0, -2) : ($len >= 5 && !ctype_digit($t) ? substr($t, 0, -1) : $t);
    $where[] = "p.name_search LIKE '%" . $conn->real_escape_string(addcslashes($stem, '%_\\')) . "%'";
}
$first = $conn->real_escape_string(addcslashes($tokens[0], '%_\\'));
$items = $fetch("SELECT p.* FROM food_products p
                 LEFT JOIN (SELECT product_id, COUNT(*) AS cnt FROM food_log WHERE user_id = $user_id AND product_id IS NOT NULL GROUP BY product_id) mine ON mine.product_id = p.id
                 LEFT JOIN (SELECT product_id, COUNT(*) AS cnt FROM food_log WHERE user_id IN ($home) AND user_id <> $user_id AND product_id IS NOT NULL
                            AND created_at > NOW() - INTERVAL 180 DAY GROUP BY product_id) home ON home.product_id = p.id
                 WHERE $vis AND (p.status <> 'rejected' OR p.source = 'dish')
                   AND NOT EXISTS (SELECT 1 FROM food_dishes ad WHERE ad.product_id = p.id AND ad.archived = 1) AND " . implode(' AND ', $where) . "
                 ORDER BY LEAST(COALESCE(mine.cnt, 0), 20) * 1000 + LEAST(COALESCE(home.cnt, 0), 20) * 200
                        + (p.name_search LIKE '$first%') * 400 + (p.name_search LIKE '% $first%') * 150
                        + (p.source = 'base') * 300 + (p.source = 'dish') * 350 + LOG(p.popularity + 1) * 25 - CHAR_LENGTH(p.name) DESC
                 LIMIT 30");

hl_food_json('success', '', [
    'groups' => $items ? [['key' => 'local', 'title' => 'W HomeList', 'items' => hl_food_rows($conn, $user_id, $items)]] : [],
    'query' => $q,
]);
