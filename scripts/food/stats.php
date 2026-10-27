<?php
// Podsumowanie dziennika (GET range = 7|30|90 dni wstecz od dziś): dni z sumami i celem (wykres),
// uzupełnione dni, średnie, trafienie w cel, serie, bilans, posiłki, dni tygodnia, najczęstsze produkty.
// Procent uzupełnionych dni liczony od pierwszego wpisu (jeśli późniejszy niż początek zakresu); dziś bez wpisów się nie liczy.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$range = in_array((int)($_GET['range'] ?? 30), [7, 30, 90], true) ? (int)$_GET['range'] : 30;
$today = date('Y-m-d');
$from = date('Y-m-d', strtotime($today . ' -' . ($range - 1) . ' day'));

$stmt = $conn->prepare('SELECT MIN(log_date) FROM food_log WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$first = $stmt->get_result()->fetch_row()[0];
$stmt->close();

$stmt = $conn->prepare('SELECT log_date, SUM(kcal), SUM(protein), SUM(fat), SUM(carbs), COUNT(*) FROM food_log
                        WHERE user_id = ? AND log_date BETWEEN ? AND ? GROUP BY log_date');
$stmt->bind_param('iss', $user_id, $from, $today);
$stmt->execute();
$sums = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_NUM) as [$d, $k, $p, $f, $c, $n]) $sums[$d] = [(float)$k, (float)$p, (float)$f, (float)$c, (int)$n];
$stmt->close();

$stmt = $conn->prepare('SELECT valid_from, kcal, protein, fat, carbs FROM food_goals WHERE user_id = ? AND valid_from <= ? ORDER BY valid_from');
$stmt->bind_param('is', $user_id, $today);
$stmt->execute();
$goals = $stmt->get_result()->fetch_all(MYSQLI_NUM);
$stmt->close();

$start = $first && $first > $from ? $first : $from;
$days = [];
$tracked = $logged = $entries = 0;
$avg = ['kcal' => 0, 'protein' => 0, 'fat' => 0, 'carbs' => 0];
$avgGoal = ['kcal' => 0, 'protein' => 0, 'fat' => 0, 'carbs' => 0];
$withGoal = 0;
$hit = ['under' => 0, 'ok' => 0, 'over' => 0, 'pending' => 0];
$balance = 0;
$balanceDays = 0;
$week = array_fill(1, 7, [0, 0]);
for ($d = $from; $d <= $today; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
    $goal = null;
    foreach ($goals as [$validFrom, $k, $p, $f, $c]) if ($validFrom <= $d) $goal = ['kcal' => (int)$k, 'protein' => (int)$p, 'fat' => (int)$f, 'carbs' => (int)$c];
    $s = $sums[$d] ?? null;
    $days[] = ['date' => $d, 'kcal' => $s ? round($s[0]) : 0, 'n' => $s ? $s[4] : 0, 'goal' => $goal ? $goal['kcal'] : null];
    if ($d < $start) continue;
    if ($s || $d < $today) $tracked++;
    if (!$s) continue;
    $logged++;
    $entries += $s[4];
    foreach (['kcal', 'protein', 'fat', 'carbs'] as $i => $k) $avg[$k] += $s[$i];
    $wd = (int)date('N', strtotime($d));
    $week[$wd][0] += $s[0];
    $week[$wd][1]++;
    if (!$goal) continue;
    $withGoal++;
    foreach ($avgGoal as $k => $v) $avgGoal[$k] += $goal[$k];
    // te same progi co pasek dni: ponad cel = powyżej 110%; dziś poniżej celu = dzień jeszcze trwa
    $r = $s[0] / $goal['kcal'];
    $hit[$r < 0.9 ? ($d === $today ? 'pending' : 'under') : ($r > 1.1 ? 'over' : 'ok')]++;
    if ($d < $today) { $balance += $s[0] - $goal['kcal']; $balanceDays++; }
}
foreach ($avg as $k => $v) $avg[$k] = $logged ? round($v / $logged, 1) : 0;
foreach ($avgGoal as $k => $v) $avgGoal[$k] = $withGoal ? round($v / $withGoal) : 0;

// serie liczone z całej historii: bieżąca może zaczynać się wczoraj, bo dzisiejszy dzień jeszcze trwa
$stmt = $conn->prepare('SELECT DISTINCT log_date FROM food_log WHERE user_id = ? AND log_date <= ? ORDER BY log_date');
$stmt->bind_param('is', $user_id, $today);
$stmt->execute();
$dates = array_column($stmt->get_result()->fetch_all(MYSQLI_NUM), 0);
$stmt->close();
$best = $run = 0;
$prev = null;
foreach ($dates as $d) {
    $run = $prev && date('Y-m-d', strtotime($prev . ' +1 day')) === $d ? $run + 1 : 1;
    $best = max($best, $run);
    $prev = $d;
}
$set = array_flip($dates);
$cur = 0;
$d = isset($set[$today]) ? $today : date('Y-m-d', strtotime($today . ' -1 day'));
while (isset($set[$d])) { $cur++; $d = date('Y-m-d', strtotime($d . ' -1 day')); }

$stmt = $conn->prepare('SELECT meal, SUM(kcal) FROM food_log WHERE user_id = ? AND log_date BETWEEN ? AND ? GROUP BY meal ORDER BY meal');
$stmt->bind_param('iss', $user_id, $start, $today);
$stmt->execute();
$meals = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_NUM) as [$m, $k]) $meals[] = ['meal' => (int)$m, 'kcal' => $logged ? round($k / $logged) : 0];
$stmt->close();

$stmt = $conn->prepare('SELECT name, COUNT(*) AS n, SUM(kcal) AS k FROM food_log WHERE user_id = ? AND log_date BETWEEN ? AND ? AND source <> \'quick\'
                        GROUP BY name ORDER BY n DESC, k DESC LIMIT 5');
$stmt->bind_param('iss', $user_id, $start, $today);
$stmt->execute();
$top = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_NUM) as [$name, $n, $k]) $top[] = ['name' => $name, 'n' => (int)$n, 'kcal' => round($k)];
$stmt->close();

hl_food_json('success', '', [
    'range' => $range, 'from' => $from, 'start' => $start, 'today' => $today, 'first' => $first, 'days' => $days,
    'tracked' => $tracked, 'logged' => $logged, 'entries' => $entries, 'avg' => $avg,
    'avg_goal' => $withGoal ? $avgGoal : null, 'with_goal' => $withGoal, 'hit' => $hit,
    'balance' => round($balance), 'balance_days' => $balanceDays, 'streak' => $cur, 'best' => $best,
    'week' => array_map(fn($w) => $w[1] ? round($w[0] / $w[1]) : null, array_values($week)),
    'meals' => $meals, 'top' => $top,
]);
