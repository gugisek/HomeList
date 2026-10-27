<?php
// Dołączanie do listy kodem (wpisanym albo z kodu QR).
// preview=1 – tylko podgląd listy do potwierdzenia (nic nie zapisuje), bez – dołączenie.
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include "../conn_db.php";
include "../access.php";

$code = strtoupper(trim((string)($_POST['code'] ?? '')));
if (!preg_match('/^[A-Z0-9]{6}$/', $code)) {
    hl_json_exit('warning', 'Kod listy ma 6 znaków – litery i cyfry');
}

$user_id = (int)$_SESSION['login_id'];
// lista usunięta przez właściciela (owner_id = 0) nie przyjmuje nowych osób
$stmt = $conn->prepare("SELECT id, full_name, owner_id, list_type FROM lists WHERE list_code = ? AND owner_id <> 0 LIMIT 1");
$stmt->bind_param('s', $code);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    hl_json_exit('warning', 'Nie znaleziono listy o podanym kodzie');
}
$list_id = (int)$row['id'];
$access = hl_list_access($conn, $user_id, $list_id);
$state = $access ? $access['role'] : 'join';

if (!empty($_POST['preview'])) {
    $members = hl_list_members($conn, $list_id);
    $owner = $members[0] ?? null;
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM list_elements WHERE list_id = ? AND status_id NOT IN (2, 4)');
    $stmt->bind_param('i', $list_id);
    $stmt->execute();
    $open = (int)$stmt->get_result()->fetch_assoc()['c'];
    hl_json_exit('success', '', [
        'preview' => [
            'id' => $list_id,
            'name' => $row['full_name'],
            'mono' => hl_list_mono($row['full_name']),
            'tone' => hl_list_tone($list_id),
            'type' => $row['list_type'],
            'state' => $state, // join | member | owner
            'owner' => $owner ? trim($owner['name'].' '.$owner['sur_name']) : '',
            'owner_avatar' => basename((string)($owner['profile_picture'] ?? '')) ?: 'default.png',
            'members' => array_map(fn($m) => ['name' => trim($m['name'].' '.$m['sur_name']), 'avatar' => basename((string)$m['profile_picture']) ?: 'default.png'], $members),
            'open' => $open,
        ],
    ]);
}

if ($state === 'owner') {
    hl_json_exit('warning', 'To Twoja lista – masz ją już u siebie', ['id' => $list_id]);
}
if ($state === 'member') {
    hl_json_exit('warning', 'Masz już dodaną tę listę', ['id' => $list_id]);
}

$stmt = $conn->prepare('INSERT INTO list_invities (list_id, user_id) VALUES (?, ?)');
$stmt->bind_param('ii', $list_id, $user_id);
if ($stmt->execute()) {
    //log
    $before = '';
    $after = 'Kod: '.$code.' <br/>Lista: '.$list_id.' <br/>Użytkownik: '.$user_id;
    $object_id = $list_id;
    $object_type="List";
    $action_type = '2';
    $desc = 'Dodano użytkownika do listy';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Dołączono do listy „'.$row['full_name'].'”', ['id' => $list_id]);
}
hl_json_exit('error', 'Błąd dodawania użytkownika do listy');
