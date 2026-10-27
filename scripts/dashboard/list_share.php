<?php
// Udostępnianie listy (tylko właściciel):
//   action=regen  – nowy kod zaproszenia (stary kod / QR przestaje działać)
//   action=remove – odebranie dostępu osobie (POST user_id)
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include "../conn_db.php";
include "../access.php";
require_once "../login/account_lib.php";

$user_id = (int)$_SESSION['login_id'];
$list_id = (int)($_POST['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');

$access = hl_list_access($conn, $user_id, $list_id);
if (!$access) {
    hl_json_exit('error', 'Nie znaleziono listy');
}
if ($access['role'] !== 'owner') {
    hl_json_exit('warning', 'Udostępnianiem listy zarządza tylko jej właściciel');
}
$list = $access['list'];

if ($action === 'regen') {
    $code = hl_new_list_code($conn);
    $stmt = $conn->prepare('UPDATE lists SET list_code = ? WHERE id = ?');
    $stmt->bind_param('si', $code, $list_id);
    if ($stmt->execute()) {
        //log
        $before = 'Kod: '.$list['list_code'];
        $after = 'Kod: '.$code;
        $object_id = $list_id;
        $object_type="List";
        $action_type = '1';
        $desc = 'Wygenerowano nowy kod listy';
        include "../log_stripped_without_conn.php";
        //log
        hl_json_exit('success', 'Nowy kod gotowy – poprzedni już nie działa', ['code' => $code]);
    }
    hl_json_exit('error', 'Błąd generowania kodu');
}

if ($action === 'remove') {
    $member_id = (int)($_POST['user_id'] ?? 0);
    if (!$member_id || $member_id === $user_id) {
        hl_json_exit('warning', 'Nie można usunąć tej osoby');
    }
    $stmt = $conn->prepare('DELETE FROM list_invities WHERE list_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $list_id, $member_id);
    $stmt->execute();
    if ($stmt->affected_rows < 1) {
        hl_json_exit('warning', 'Ta osoba nie ma już dostępu do listy');
    }
    // jej prywatny plan dnia i wyciszenie nie mają już sensu
    $cleanup = $conn->prepare('DELETE day_plan FROM day_plan JOIN list_elements ON list_elements.id = day_plan.element_id WHERE day_plan.user_id = ? AND list_elements.list_id = ?');
    $cleanup->bind_param('ii', $member_id, $list_id);
    $cleanup->execute();
    $cleanup = $conn->prepare('DELETE FROM push_list_mute WHERE user_id = ? AND list_id = ?');
    $cleanup->bind_param('ii', $member_id, $list_id);
    $cleanup->execute();

    //log
    $before = 'Lista: '.$list['name'].' <br/>Użytkownik: '.$member_id;
    $after = '';
    $object_id = $list_id;
    $object_type="List";
    $action_type = '3';
    $desc = 'Odebrano dostęp do listy';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Odebrano dostęp do listy');
}

hl_json_exit('warning', 'Nieznana akcja');
