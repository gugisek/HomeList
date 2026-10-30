<?php
// Propozycja zmiany produktu – dowolnego, który widzi użytkownik (OFF, podstawowy, od innych osób), poza daniami.
//   GET  id – dane do formularza: aktualne wartości, a jeśli czeka już moja propozycja – jej wartości, komentarz i zdjęcie etykiety
//   POST id + pola jak product_save.php, note? (co poprawiam), label_photo? (plik), label_photo_remove?
// Zwykły użytkownik: zmiana czeka na akceptację (jedna oczekująca propozycja na osobę i produkt – kolejny zapis ją aktualizuje,
// zapis bez różnic ją wycofuje). Moderator bazy produktów (food, edit): zmiana od razu, z wpisem w historii i archiwum zmian.
// Zapisujemy tylko zmienione pola – reszta produktu (np. świeże dane z OFF) zostaje. Wpisy w dziennikach mają własne kopie wartości.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
require_once '../log_lib.php';

$user_id = (int)$_SESSION['login_id'];
$moderator = hl_can('food', 'edit');
$id = (int)($_REQUEST['id'] ?? 0);
$p = hl_food_product($conn, $user_id, $id);
if (!$p) hl_food_json('error', 'Nie znaleziono produktu');
if ($p['source'] === 'dish') hl_food_json('warning', 'Danie zmienisz w edytorze przepisu');

$stmt = $conn->prepare("SELECT * FROM food_product_edits WHERE product_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$last = $stmt->get_result()->fetch_assoc();
$stmt->close();
$mine = $last && $last['status'] === 'pending' ? $last : null;
$cur = hl_food_edit_values(hl_food_edit_data($conn, $p));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    session_write_close();
    $data = hl_food_edit_data($conn, $p);
    // zdjęcie etykiety produktu należy do jego autora – w propozycji tylko własne
    $data['label_photo'] = null;
    unset($data['status'], $data['review_note']);
    if ($mine) {
        foreach (json_decode($mine['data'], true) ?: [] as $k => $v) {
            $data[$k] = in_array($k, ['allergens', 'traces'], true) ? array_values(array_filter(explode(',', (string)$v))) : $v;
        }
        $data['note'] = $mine['note'];
        $data['label_photo'] = hl_food_edit_photo_url($mine);
    }
    hl_food_json('success', '', ['product' => $data + [
        'suggest' => true, 'direct' => $moderator, 'pending' => (bool)$mine, 'source' => $p['source'],
        // ostatnia propozycja odrzucona – pokazujemy powód, żeby nie wysłać tego samego
        'rejected_note' => $last && $last['status'] === 'rejected' ? ($last['review_note'] ?: '') : null,
    ]]);
}

$new = hl_food_edit_values($_POST);
$err = hl_food_product_check($_POST, $new);
if ($err !== null) hl_food_json('warning', $err);
// porcja bez wagi albo waga bez nazwy – liczy się para
if (($new['unit_label'] === null) !== ($new['unit_g'] === null)) {
    if ($new['unit_g'] !== null) $new['unit_label'] = 'porcja';
    else $new['unit_label'] = null;
}
$ch = hl_food_edit_diff($cur, $new);

if (array_key_exists('code', $ch) && $ch['code'] !== null) {
    $stmt = $conn->prepare('SELECT id FROM food_products WHERE code = ? AND id <> ?');
    $stmt->bind_param('si', $ch['code'], $id);
    $stmt->execute();
    $taken = $stmt->get_result()->fetch_row();
    $stmt->close();
    if ($taken) hl_food_json('warning', 'Ten kod kreskowy ma już inny produkt w bazie');
}

$note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 300, 'UTF-8') ?: null;
$photo = $mine['label_photo'] ?? null;
$photo_new = null;
if (!empty($_FILES['label_photo']) && ($_FILES['label_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $saved = hl_food_save_photo($_FILES['label_photo'], $user_id, 'label');
    if (!$saved['ok']) hl_food_json('warning', $saved['error']);
    $photo_new = $saved['file'];
}
$photo_drop = $photo !== null && ($photo_new !== null || !empty($_POST['label_photo_remove']));

if (!$ch) {
    hl_food_delete_photo($photo_new);
    // ta sama treść co teraz w bazie = wycofanie mojej oczekującej propozycji
    if ($mine && !$moderator) {
        $conn->query('DELETE FROM food_product_edits WHERE id = ' . (int)$mine['id']);
        hl_food_delete_photo($mine['label_photo']);
        hl_food_json('success', 'Wycofano propozycję zmiany', ['withdrawn' => true]);
    }
    hl_food_json('warning', 'Nic się nie zmieniło – popraw to, co nie zgadza się z etykietą');
}

$base = array_intersect_key($cur, $ch);
$data_json = json_encode($ch, JSON_UNESCAPED_UNICODE);
$base_json = json_encode($base, JSON_UNESCAPED_UNICODE);
if ($photo_drop) {
    hl_food_delete_photo($photo);
    $photo = null;
}
if ($photo_new !== null) $photo = $photo_new;

if ($moderator) {
    hl_food_edit_apply($conn, $p, $ch);
    if ($mine) {
        // moja wcześniejsza propozycja = ta zmiana
        $stmt = $conn->prepare("UPDATE food_product_edits SET data = ?, base = ?, note = ?, label_photo = ?, status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $mid = (int)$mine['id'];
        $stmt->bind_param('ssssii', $data_json, $base_json, $note, $photo, $user_id, $mid);
    } else {
        $stmt = $conn->prepare("INSERT INTO food_product_edits (product_id, user_id, data, base, note, label_photo, status, reviewed_by, reviewed_at)
                                VALUES (?, ?, ?, ?, ?, ?, 'approved', ?, NOW())");
        $stmt->bind_param('iissssi', $id, $user_id, $data_json, $base_json, $note, $photo, $user_id);
    }
    $stmt->execute();
    $edit_id = $mine ? (int)$mine['id'] : $stmt->insert_id;
    $stmt->close();
    [$was, $now] = hl_food_edit_log_text($cur, $ch, (bool)$p['per_ml']);
    $title = $p['name'] . ($p['brand'] ? ' · ' . $p['brand'] : '') . ' (#' . $id . ')<br/>';
    hl_log($conn, $edit_id, 'food_edit', $title . $was, $title . $now, 1, 'Poprawiono dane produktu');

    $fresh = $conn->query("SELECT * FROM food_products WHERE id = $id")->fetch_assoc();
    $row = hl_food_rows($conn, $user_id, [$fresh])[0];
    foreach (['sugars', 'fiber', 'sat_fat', 'salt'] as $k) $row[$k] = $fresh[$k] !== null ? (float)$fresh[$k] : null;
    hl_food_json('success', 'Zapisano zmiany produktu', ['product' => $row, 'applied' => true]);
}

if ($mine) {
    $stmt = $conn->prepare('UPDATE food_product_edits SET data = ?, base = ?, note = ?, label_photo = ?, created_at = NOW() WHERE id = ?');
    $mid = (int)$mine['id'];
    $stmt->bind_param('ssssi', $data_json, $base_json, $note, $photo, $mid);
} else {
    $stmt = $conn->prepare('INSERT INTO food_product_edits (product_id, user_id, data, base, note, label_photo) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iissss', $id, $user_id, $data_json, $base_json, $note, $photo);
}
if (!$stmt->execute()) {
    hl_food_delete_photo($photo_new);
    hl_food_json('error', 'Nie udało się zapisać propozycji');
}
$stmt->close();
hl_food_json('success', $mine ? 'Zaktualizowano propozycję zmiany' : 'Dzięki! Zmiana pojawi się po akceptacji administratora');
