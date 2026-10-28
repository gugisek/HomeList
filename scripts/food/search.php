<?php
// Wyszukiwarka produktów (GET q, meal, tab).
// Bez zapytania – zakładki arkusza: tab=recent „Ostatnio i często” (najpierw to, co jadasz w tym posiłku) + Twoje produkty,
// tab=dishes Twoje dania, tab=community dania domowników i społeczności (dom najpierw, potem najczęściej jedzone).
// Z zapytaniem: baza lokalna, ranking w hl_food_find().
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$meal = (int)($_GET['meal'] ?? 0);
$q = hl_food_norm(mb_substr((string)($_GET['q'] ?? ''), 0, 100));
$tab = in_array($_GET['tab'] ?? '', ['dishes', 'community'], true) ? $_GET['tab'] : 'recent';
$vis = hl_food_visible_sql($conn, $user_id);
$home = implode(',', hl_food_household($conn, $user_id));

$fetch = function (string $sql) use ($conn): array {
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
};
$groups = [];

if ($q === '') {
    if ($tab === 'recent') {
        $recent = $fetch("SELECT p.*, u.in_meal, u.last FROM food_products p
                          JOIN (SELECT product_id, SUM(meal = $meal) AS in_meal, COUNT(*) AS cnt, MAX(created_at) AS last FROM food_log
                                WHERE user_id = $user_id AND product_id IS NOT NULL AND created_at > NOW() - INTERVAL 90 DAY
                                GROUP BY product_id) u ON u.product_id = p.id
                          WHERE $vis
                          ORDER BY (u.in_meal > 0) DESC, u.in_meal + u.cnt DESC, u.last DESC LIMIT 15");
        if ($recent) $groups[] = ['key' => 'recent', 'title' => 'Ostatnio i często', 'items' => hl_food_rows($conn, $user_id, $recent)];

        $own = $fetch("SELECT p.* FROM food_products p WHERE p.source = 'user' AND p.owner_id = $user_id AND p.status <> 'rejected'
                       ORDER BY p.id DESC LIMIT 8");
        if ($own) $groups[] = ['key' => 'own', 'title' => 'Twoje produkty', 'items' => hl_food_rows($conn, $user_id, $own)];
    } elseif ($tab === 'dishes') {
        $dishes = $fetch("SELECT p.* FROM food_products p JOIN food_dishes d ON d.product_id = p.id
                          WHERE p.source = 'dish' AND d.archived = 0 AND d.owner_id = $user_id
                          ORDER BY d.updated_at DESC LIMIT 60");
        if ($dishes) $groups[] = ['key' => 'dishes', 'title' => 'Twoje dania', 'items' => hl_food_rows($conn, $user_id, $dishes)];
    } else {
        $others = $fetch("SELECT p.*, d.owner_id IN ($home) AS in_home FROM food_products p JOIN food_dishes d ON d.product_id = p.id
                          LEFT JOIN (SELECT product_id, COUNT(*) AS cnt FROM food_log WHERE product_id IS NOT NULL AND created_at > NOW() - INTERVAL 180 DAY
                                     GROUP BY product_id) u ON u.product_id = p.id
                          WHERE p.source = 'dish' AND d.archived = 0 AND d.owner_id <> $user_id AND $vis
                          ORDER BY in_home DESC, COALESCE(u.cnt, 0) DESC, d.updated_at DESC LIMIT 60");
        $inHome = array_values(array_filter($others, fn($p) => (int)$p['in_home'] === 1));
        $rest = array_values(array_filter($others, fn($p) => (int)$p['in_home'] !== 1));
        if ($inHome) $groups[] = ['key' => 'home', 'title' => 'Od domowników', 'items' => hl_food_rows($conn, $user_id, $inHome)];
        if ($rest) $groups[] = ['key' => 'community', 'title' => 'Społeczność HomeList', 'items' => hl_food_rows($conn, $user_id, $rest)];
    }
    hl_food_json('success', '', ['groups' => $groups, 'query' => '', 'tab' => $tab]);
}

$items = hl_food_find($conn, $user_id, $q);
hl_food_json('success', '', [
    'groups' => $items ? [['key' => 'local', 'title' => 'W HomeList', 'items' => hl_food_rows($conn, $user_id, $items)]] : [],
    'query' => $q,
]);
