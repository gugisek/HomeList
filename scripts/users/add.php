<?php
$SECURITY_REQUIRED_MODULE = ['users', 'add'];
include '../security.php';
include "../conn_db.php";
require_once 'user_admin_lib.php';
require_once '../password_helper.php';
require_once '../log_lib.php';
require_once '../login/account_lib.php';

$name = trim((string)($_POST['name'] ?? ''));
$sur_name = trim((string)($_POST['sur_name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$role = (int)($_POST['role'] ?? 0);
$description = (string)($_POST['description'] ?? '');
$status_id = (int)($_POST['status_id'] ?? 0);
$password = (string)($_POST['pswd'] ?? '');
$repeat_password = (string)($_POST['repeat_pswd'] ?? '');

if ($name === '' || $email === '' || !$role || $password === '' || !$status_id) {
    hl_admin_back('Nie wprowadzono wszystkich danych', 'error');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    hl_admin_back('Nieprawidłowy adres email', 'error');
}
if ($password !== $repeat_password) {
    hl_admin_back('Podane hasła różnią się od siebie', 'error');
}
if (($policy = passwordPolicyViolation($password)) !== null) {
    hl_admin_back($policy, 'error');
}
$role_row = hl_role_row($conn, $role);
if (!$role_row || !hl_role_within_mine($role_row)) {
    hl_admin_back('Nie możesz nadać tej roli – ma wyższe uprawnienia niż Twoje', 'error');
}
if (!hl_status_exists($conn, $status_id)) {
    hl_admin_back('Nieprawidłowy status konta', 'error');
}

$stmt = $conn->prepare('SELECT id FROM users WHERE LOWER(login) = LOWER(?) OR LOWER(mail) = LOWER(?) LIMIT 1');
$stmt->bind_param('ss', $email, $email);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    hl_admin_back('Konto z tym adresem email już istnieje', 'error');
}

$hash = hashPassword($password);
$stmt = $conn->prepare('INSERT INTO users (name, sur_name, mail, role_id, description, status_id, pswd, create_date, login) VALUES (?, ?, ?, ?, ?, ?, ?, current_timestamp(), ?)');
$stmt->bind_param('sssisiss', $name, $sur_name, $email, $role, $description, $status_id, $hash, $email);
if (!$stmt->execute()) {
    hl_admin_back('Błąd aktualizacji danych', 'error');
}
$id = $stmt->insert_id;
hl_create_default_list($conn, $id);

//log
$before = '';
$after = 'Imię: ' . $name . ' ' . $sur_name . '<br/> Email: ' . $email . '<br/> Rola: ' . $role . '<br/> Opis: ' . $description . '<br/> Status: ' . $status_id;
$object_id = $id;
$object_type="users";
$action_type = '2';
$desc = 'Dodano użytkownika';
include "../log_stripped_without_conn.php";
//log

hl_admin_back('Konto zostało dodane', 'success');
