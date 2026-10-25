<?php
// Edycja własnego profilu (imię, nazwisko, opis, zdjęcie, email). Zmiana emaila = zmiana loginu,
// więc wymaga potwierdzenia aktualnym hasłem. Hasło zmienia się osobno (change_password.php).
include '../security.php';
include "../conn_db.php";
require_once 'user_admin_lib.php';
require_once 'avatar_lib.php';
require_once '../password_helper.php';
require_once '../log_lib.php';

$id = (int)$_SESSION['login_id'];
$name = trim((string)($_POST['name'] ?? ''));
$sur_name = trim((string)($_POST['sur_name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$description = (string)($_POST['description'] ?? '');

$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row_old = $stmt->get_result()->fetch_assoc();

if ($name === '' || $email === '') {
    hl_admin_back('Nie wprowadzono wszystkich danych', 'error');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    hl_admin_back('Nieprawidłowy adres email', 'error');
}

$messages = [];
$email_changed = strcasecmp($email, (string)$row_old['mail']) !== 0;
if ($email_changed) {
    if (!verifyPassword((string)($_POST['current_password'] ?? ''), (string)$row_old['pswd'])) {
        hl_admin_back('Aby zmienić adres email, podaj poprawne aktualne hasło', 'error');
    }
    $stmt = $conn->prepare('SELECT id FROM users WHERE (LOWER(login) = LOWER(?) OR LOWER(mail) = LOWER(?)) AND id <> ? LIMIT 1');
    $stmt->bind_param('ssi', $email, $email, $id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        hl_admin_back('Konto z tym adresem email już istnieje', 'error');
    }
}

if ($row_old['name'] !== $name || $row_old['sur_name'] !== $sur_name || $email_changed || (string)$row_old['description'] !== $description) {
    $stmt = $conn->prepare('UPDATE users SET name = ?, sur_name = ?, mail = ?, login = ?, description = ? WHERE id = ?');
    $stmt->bind_param('sssssi', $name, $sur_name, $email, $email, $description, $id);
    if (!$stmt->execute()) {
        hl_admin_back('Błąd aktualizacji danych', 'error');
    }
    hl_log($conn, $id, 'users',
        'Imię: ' . $row_old['name'] . ' ' . $row_old['sur_name'] . ' <br/> Email: ' . $row_old['mail'] . '<br/> Opis: ' . $row_old['description'],
        'Imię: ' . $name . ' ' . $sur_name . '<br/> Email: ' . $email . '<br/> Opis: ' . $description,
        1, 'Edytowano profil użytkownika');
    $_SESSION['user'] = trim($name . ' ' . $sur_name);
    $messages[] = 'Profil został zaktualizowany';
}

$avatar = hl_save_avatar($_FILES['photo_img'] ?? null, $id);
if ($avatar && !$avatar['ok']) {
    hl_admin_back($avatar['error'], 'error');
}
if ($avatar) {
    $stmt = $conn->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
    $stmt->bind_param('si', $avatar['file'], $id);
    $stmt->execute();
    $_SESSION['profile_picture'] = $avatar['file'];
    hl_log($conn, $id, 'users', '', $avatar['file'], 1, 'Zmieniono zdjęcie profilowe (profil)');
    $messages[] = 'Zdjęcie zostało zmienione';
}

hl_admin_back($messages ? implode('. ', $messages) : 'Nie wprowadzono nowych danych', $messages ? 'success' : 'warning');
