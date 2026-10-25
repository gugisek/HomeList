<?php
// Zapis kolejności (i pory dnia) elementów w jednej sekcji feedu po przeciągnięciu.
// items: JSON ["t12", "r5", ...] w kolejności wyświetlania
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
include 'planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$date = $_POST['date'] ?? '';
$part = (int)($_POST['day_part'] ?? 0);
$items = json_decode($_POST['items'] ?? '[]', true);

if (!hl_valid_date($date) || !array_key_exists($part, hl_parts()) || !is_array($items)) {
    hl_json('warning', 'Nieprawidłowe dane');
    exit;
}

$d = mysqli_real_escape_string($conn, $date);
$list_ids = hl_user_list_ids($conn, $user_id);
$ids = hl_in($list_ids);

// typy list dla przesłanych elementów (tylko te, do których użytkownik ma dostęp)
$element_ids = array_map(fn($k) => (int)substr((string)$k, 1), $items);
$types = [];
$result = mysqli_query($conn, "SELECT list_elements.id, lists.list_type FROM list_elements JOIN lists ON lists.id=list_elements.list_id
                               WHERE list_elements.id IN (".hl_in($element_ids).") AND list_elements.list_id IN ($ids)");
while ($row = mysqli_fetch_assoc($result)) $types[(int)$row['id']] = $row['list_type'];

$position = 1;
foreach ($items as $key) {
    $key = (string)$key;
    $eid = (int)substr($key, 1);
    if (!isset($types[$eid])) continue;

    if ($key[0] === 't' && $types[$eid] === 'normal') {
        // zadanie: zachowaj godzinę jeśli było już na ten dzień, a resztę przepisz
        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT plan_time FROM day_plan WHERE user_id='$user_id' AND element_id='$eid' AND plan_date='$d' LIMIT 1"));
        $time_sql = !empty($old['plan_time']) ? "'".$old['plan_time']."'" : "NULL";
        mysqli_query($conn, "DELETE FROM day_plan WHERE user_id='$user_id' AND element_id='$eid'");
        mysqli_query($conn, "INSERT INTO day_plan (user_id, element_id, plan_date, day_part, plan_time, position)
                             VALUES ('$user_id', '$eid', '$d', '$part', $time_sql, '$position')");
    } elseif ($key[0] === 'r' && $types[$eid] === 'routine') {
        // rutyna: nadpisanie pory/kolejności tylko na ten dzień
        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM day_plan WHERE user_id='$user_id' AND element_id='$eid' AND plan_date='$d' LIMIT 1"));
        if ($old) {
            mysqli_query($conn, "UPDATE day_plan SET day_part='$part', position='$position' WHERE id='".(int)$old['id']."'");
        } else {
            mysqli_query($conn, "INSERT INTO day_plan (user_id, element_id, plan_date, day_part, plan_time, position)
                                 VALUES ('$user_id', '$eid', '$d', '$part', NULL, '$position')");
        }
    }
    $position++;
}

hl_json('success', 'Zapisano kolejność');
