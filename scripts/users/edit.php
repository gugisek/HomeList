<?php
$SECURITY_REQUIRED_MODULE = ['users', 'edit'];
include '../security.php';
include "../conn_db.php";
require_once 'user_admin_lib.php';
require_once 'avatar_lib.php';
require_once '../password_helper.php';
require_once '../log_lib.php';

$id = (int)($_POST['id'] ?? 0);
$name = trim((string)($_POST['name'] ?? ''));
$sur_name = trim((string)($_POST['sur_name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$role = (int)($_POST['role'] ?? 0);
$description = (string)($_POST['description'] ?? '');
$status_id = (int)($_POST['status_id'] ?? 0);
$password = (string)($_POST['pswd'] ?? '');
$repeat_password = (string)($_POST['repeat_pswd'] ?? '');
$is_self = $id === (int)$_SESSION['login_id'];

if (!$id || $name === '' || $email === '' || !$role || !$status_id) {
    hl_admin_back('Nie wprowadzono wszystkich danych', 'error');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    hl_admin_back('Nieprawidłowy adres email', 'error');
}

$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row_old = $stmt->get_result()->fetch_assoc();
if (!$row_old) {
    hl_admin_back('Nie znaleziono użytkownika', 'error');
}

$old_role = hl_role_row($conn, (int)$row_old['role_id']);
if ($old_role && !$is_self && !hl_role_within_mine($old_role)) {
    hl_admin_back('Nie możesz edytować konta z wyższymi uprawnieniami niż Twoje', 'error');
}
// własnej roli i statusu nie zmieniamy z tego formularza – łatwo byłoby się odciąć od panelu
if ($is_self) {
    $role = (int)$row_old['role_id'];
    $status_id = (int)$row_old['status_id'];
}
$new_role = hl_role_row($conn, $role);
if (!$new_role || ($role !== (int)$row_old['role_id'] && !hl_role_within_mine($new_role))) {
    hl_admin_back('Nie możesz nadać tej roli – ma wyższe uprawnienia niż Twoje', 'error');
}
if (!hl_status_exists($conn, $status_id)) {
    hl_admin_back('Nieprawidłowy status konta', 'error');
}
$loses_admin = hl_is_full_admin_role($old_role) && (!hl_is_full_admin_role($new_role) || !hl_status_can_login($conn, $status_id));
if ($loses_admin && hl_full_admin_count($conn, $id) === 0) {
    hl_admin_back('To ostatnie aktywne konto z pełnym dostępem – nie można odebrać mu uprawnień ani go zablokować', 'error');
}

$stmt = $conn->prepare('SELECT id FROM users WHERE (LOWER(login) = LOWER(?) OR LOWER(mail) = LOWER(?)) AND id <> ? LIMIT 1');
$stmt->bind_param('ssi', $email, $email, $id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    hl_admin_back('Konto z tym adresem email już istnieje', 'error');
}

$messages = [];
$changed = $row_old['name'] !== $name || $row_old['sur_name'] !== $sur_name || $row_old['mail'] !== $email || (int)$row_old['role_id'] !== $role
    || (string)$row_old['description'] !== $description || (int)$row_old['status_id'] !== $status_id;

if ($changed) {
    $stmt = $conn->prepare('UPDATE users SET name = ?, sur_name = ?, mail = ?, login = ?, role_id = ?, description = ?, status_id = ? WHERE id = ?');
    $stmt->bind_param('ssssisii', $name, $sur_name, $email, $email, $role, $description, $status_id, $id);
    if (!$stmt->execute()) {
        hl_admin_back('Błąd aktualizacji danych', 'error');
    }
    // blokada konta: od razu wylogowujemy je ze wszystkich urządzeń
    if (!hl_status_can_login($conn, $status_id)) hl_revoke_user_sessions($conn, $id);

    hl_log($conn, $id, 'users',
        'Imię: ' . $row_old['name'] . ' ' . $row_old['sur_name'] . ' <br/> Email: ' . $row_old['mail'] . '<br/> Rola: ' . $row_old['role_id'] . '<br/> Opis: ' . $row_old['description'] . '<br/> Status: ' . $row_old['status_id'],
        'Imię: ' . $name . ' ' . $sur_name . '<br/> Email: ' . $email . '<br/> Rola: ' . $role . '<br/> Opis: ' . $description . '<br/> Status: ' . $status_id,
        1, 'Edytowano użytkownika');
    $messages[] = 'Dane zostały zaktualizowane';
}

if ($password !== '' || $repeat_password !== '') {
    if ($password !== $repeat_password) {
        hl_admin_back('Podane hasła różnią się od siebie', 'error');
    }
    if (($policy = passwordPolicyViolation($password)) !== null) {
        hl_admin_back($policy, 'error');
    }
    $hash = hashPassword($password);
    $stmt = $conn->prepare('UPDATE users SET pswd = ? WHERE id = ?');
    $stmt->bind_param('si', $hash, $id);
    $stmt->execute();
    // nowe hasło nadane przez admina = wylogowanie konta wszędzie (poza bieżącą sesją, jeśli to własne konto)
    hl_revoke_user_sessions($conn, $id, $is_self);
    hl_log($conn, $id, 'users', '', 'Hasło zmienione przez administratora', 1, 'Zmieniono hasło użytkownika');
    $messages[] = 'Hasło zostało zmienione';
}

if (!empty($_POST['disable_2fa'])) {
    $stmt = $conn->prepare('UPDATE user_two_factor_settings SET is_enabled = 0, secret = NULL, enabled_at = NULL WHERE user_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt = $conn->prepare('DELETE FROM user_two_factor_backup_codes WHERE user_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    hl_log($conn, $id, 'users', '2FA aktywne', '2FA wyłączone przez administratora', 1, 'Wyłączono 2FA użytkownika');
    $messages[] = '2FA zostało wyłączone';
}

if (!empty($_POST['revoke_sessions'])) {
    hl_revoke_user_sessions($conn, $id, $is_self);
    hl_log($conn, $id, 'users', '', 'Wylogowano ze wszystkich urządzeń', 1, 'Wylogowano użytkownika ze wszystkich urządzeń');
    $messages[] = 'Konto zostało wylogowane ze wszystkich urządzeń';
}

$avatar = hl_save_avatar($_FILES['photo_img'] ?? null, $id);
if ($avatar && !$avatar['ok']) {
    hl_admin_back($avatar['error'], 'error');
}
if ($avatar) {
    $stmt = $conn->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
    $stmt->bind_param('si', $avatar['file'], $id);
    $stmt->execute();
    if ($is_self) $_SESSION['profile_picture'] = $avatar['file'];
    hl_log($conn, $id, 'users', '', $avatar['file'], 1, 'Zmieniono zdjęcie profilowe');
    $messages[] = 'Zdjęcie zostało zmienione';
}

hl_admin_back($messages ? implode('. ', $messages) : 'Nie wprowadzono nowych danych', $messages ? 'success' : 'warning');
