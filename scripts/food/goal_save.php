<?php
// Zapis celu (POST kcal, protein, fat, carbs, calc?) – obowiązuje od dziś; wcześniejsze dni zachowują stary cel.
// calc = JSON danych z kalkulatora (sex m|k, age, height, weight, activity, goal cut|keep|bulk), żeby kalkulator pamiętał wpisy.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$kcal = hl_food_float($_POST['kcal'] ?? '', 800, 8000);
$protein = hl_food_float($_POST['protein'] ?? '', 0, 500);
$fat = hl_food_float($_POST['fat'] ?? '', 0, 500);
$carbs = hl_food_float($_POST['carbs'] ?? '', 0, 1500);
if ($kcal === null || $protein === null || $fat === null || $carbs === null) hl_food_json('warning', 'Uzupełnij kalorie i makro');

$calc = null;
$raw = json_decode((string)($_POST['calc'] ?? ''), true);
if (is_array($raw)) {
    $calc = json_encode([
        'sex' => ($raw['sex'] ?? '') === 'k' ? 'k' : 'm',
        'age' => max(14, min(99, (int)($raw['age'] ?? 0))),
        'height' => max(120, min(230, (int)($raw['height'] ?? 0))),
        'weight' => max(35, min(250, round((float)($raw['weight'] ?? 0), 1))),
        'activity' => in_array((float)($raw['activity'] ?? 0), [1.2, 1.375, 1.55, 1.725], true) ? (float)$raw['activity'] : 1.375,
        'goal' => in_array($raw['goal'] ?? '', ['cut', 'keep', 'bulk'], true) ? $raw['goal'] : 'keep',
    ]);
}

$today = date('Y-m-d');
$k = (int)round($kcal);
$p = (int)round($protein);
$f = (int)round($fat);
$c = (int)round($carbs);
$stmt = $conn->prepare('INSERT INTO food_goals (user_id, valid_from, kcal, protein, fat, carbs, calc) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE kcal = VALUES(kcal), protein = VALUES(protein), fat = VALUES(fat), carbs = VALUES(carbs), calc = VALUES(calc)');
$stmt->bind_param('isiiiis', $user_id, $today, $k, $p, $f, $c, $calc);
if (!$stmt->execute()) hl_food_json('error', 'Nie udało się zapisać celu');
$stmt->close();
hl_food_json('success', 'Zapisano cel', ['goal' => hl_food_goal($conn, $user_id, $today)]);
