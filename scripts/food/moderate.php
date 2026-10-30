<?php
// Moderacja bazy produktów (moduł „Baza produktów”) – produkty użytkowników i zgłoszone jednostki porcji (type = unit):
//   GET  – lista oczekujących produktów (read)
//   POST id, type?, action = approve | reject (edit) | delete (full), note? = powód odrzucenia (autor widzi go w „Twój wkład”)
// Decyzja zapisuje reviewed_by / reviewed_at (historia na stronie) i wpis w archiwum zmian (logs).
// Usunięcie nie rusza dziennika – wpisy mają własną kopię nazwy i makro.
$SECURITY_REQUIRED_MODULE = ['food', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
require_once '../log_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $res = $conn->query("SELECT p.*, u.name AS owner_name FROM food_products p LEFT JOIN users u ON u.id = p.owner_id
                         WHERE p.source = 'user' AND p.status = 'pending' ORDER BY p.id LIMIT 200");
    $items = [];
    while ($p = $res->fetch_assoc()) {
        $items[] = ['id' => (int)$p['id'], 'name' => $p['name'], 'brand' => $p['brand'], 'code' => $p['code'], 'owner' => $p['owner_name'],
                    'per_ml' => (bool)$p['per_ml'], 'kcal' => (float)$p['kcal'], 'protein' => (float)$p['protein'], 'fat' => (float)$p['fat'],
                    'carbs' => (float)$p['carbs'], 'created_at' => $p['created_at'],
                    'macros_match' => hl_food_macros_match((float)$p['kcal'], (float)$p['protein'], (float)$p['fat'], (float)$p['carbs'], 0.15, 25, 2 * (float)$p['fiber'])];
    }
    hl_food_json('success', '', ['items' => $items]);
}

$id = (int)($_POST['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$need = ['approve' => 'edit', 'reject' => 'edit', 'delete' => 'full'][$action] ?? null;
if ($need === null) hl_food_json('error', 'Nieznana akcja');
if (!hl_can('food', $need)) hl_food_json('error', 'Brak uprawnień');
$me = (int)$_SESSION['login_id'];

// propozycja zmiany produktu (type = edit): akceptacja zmienia produkt dla wszystkich (fields? = tylko wybrane pola),
// odrzucenie zostawia autorowi powód; produkt bez zmian
if (($_POST['type'] ?? '') === 'edit') {
    $e = $conn->query("SELECT * FROM food_product_edits WHERE id = $id")->fetch_assoc();
    if (!$e) hl_food_json('error', 'Nie znaleziono propozycji');
    $p = $conn->query('SELECT * FROM food_products WHERE id = ' . (int)$e['product_id'])->fetch_assoc();
    $title = ($p ? $p['name'] . ($p['brand'] ? ' · ' . $p['brand'] : '') : 'Usunięty produkt') . ' (#' . (int)$e['product_id'] . ')<br/>Autor: ' . (int)$e['user_id'] . '<br/>';
    if ($action === 'delete') {
        $conn->query("DELETE FROM food_product_edits WHERE id = $id");
        hl_food_delete_photo($e['label_photo']);
        hl_log($conn, $id, 'food_edit', $title . 'Status: ' . $e['status'], '', 3, 'Usunięto propozycję zmiany produktu');
        hl_food_json('success', 'Usunięto propozycję');
    }
    if ($e['status'] === 'approved') hl_food_json('warning', 'Ta zmiana jest już w produkcie');
    $note = null;
    if ($action === 'approve') {
        if (!$p) hl_food_json('error', 'Produkt został usunięty z bazy');
        $proposed = json_decode($e['data'], true) ?: [];
        if (isset($_POST['fields'])) $proposed = array_intersect_key($proposed, array_flip(explode(',', (string)$_POST['fields'])));
        $cur = hl_food_edit_values(hl_food_edit_data($conn, $p));
        // tylko to, co nadal różni się od produktu (ktoś mógł go w międzyczasie poprawić)
        $ch = hl_food_edit_diff($cur, array_merge($cur, $proposed));
        if (array_key_exists('code', $ch) && $ch['code'] !== null) {
            $stmt = $conn->prepare('SELECT id FROM food_products WHERE code = ? AND id <> ?');
            $pid = (int)$p['id'];
            $stmt->bind_param('si', $ch['code'], $pid);
            $stmt->execute();
            $taken = $stmt->get_result()->fetch_row();
            $stmt->close();
            if ($taken) hl_food_json('warning', 'Ten kod kreskowy ma już inny produkt – odznacz kod i zaakceptuj resztę');
        }
        if ($ch) hl_food_edit_apply($conn, $p, $ch);
        [$was, $now] = $ch ? hl_food_edit_log_text($cur, $ch, (bool)$p['per_ml']) : ['', 'Bez zmian – produkt miał już te wartości'];
    } else {
        $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 300, 'UTF-8') ?: null;
        $was = 'Status: ' . $e['status'];
        $now = 'Status: rejected' . ($note !== null ? '<br/>Powód: ' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') : '');
    }
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $stmt = $conn->prepare('UPDATE food_product_edits SET status = ?, review_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
    $stmt->bind_param('ssii', $status, $note, $me, $id);
    $stmt->execute();
    $stmt->close();
    hl_log($conn, $id, 'food_edit', $title . $was, $title . $now, 1, $action === 'approve' ? 'Zaakceptowano zmianę produktu' : 'Odrzucono zmianę produktu');
    hl_food_json('success', $action === 'approve' ? 'Zmiana jest już w produkcie' : 'Odrzucono propozycję');
}

$unit = ($_POST['type'] ?? '') === 'unit';

// jednostka porcji zgłoszona przez użytkownika (type = unit); odrzucona zostaje tylko u autora
if ($unit) {
    $row = $conn->query("SELECT un.*, p.name FROM food_product_units un JOIN food_products p ON p.id = un.product_id
                         WHERE un.id = $id AND un.owner_id IS NOT NULL")->fetch_assoc();
    if (!$row) hl_food_json('error', 'Nie znaleziono jednostki');
    $what = $row['label'] . ' = ' . (float)$row['grams'] . ' g/ml<br/>Produkt: ' . $row['name'] . ' (#' . $row['product_id'] . ')<br/>Autor: ' . $row['owner_id'];
} else {
    $row = $conn->query("SELECT * FROM food_products WHERE id = $id AND (source = 'user' OR (source = 'dish' AND public = 1))")->fetch_assoc();
    if (!$row) hl_food_json('error', 'Nie znaleziono produktu');
    // danie udostępnione przez dom: moderator decyduje tylko o widoczności dla innych – przepis zostaje w domu
    if ($row['source'] === 'dish' && $action === 'delete') hl_food_json('warning', 'Danie domowe można tylko odrzucić – zostanie wtedy w domu autora');
    $what = ($row['source'] === 'dish' ? 'Danie: ' : '') . $row['name'] . ($row['brand'] ? ' · ' . $row['brand'] : '') . '<br/>' . (float)$row['kcal'] . ' kcal / 100 ' . ($row['per_ml'] ? 'ml' : 'g') . '<br/>Autor: ' . $row['owner_id'];
}
$table = $unit ? 'food_product_units' : 'food_products';

if ($action === 'delete') {
    $conn->query("DELETE FROM $table WHERE id = $id");
    if (!$unit) {
        $conn->query("DELETE FROM food_product_units WHERE product_id = $id");
        $conn->query("DELETE FROM food_product_details WHERE product_id = $id");
        hl_food_delete_photo($row['label_photo']);
    }
    // usunięty wiersz znika z historii na stronie – zostaje ślad w archiwum zmian
    hl_log($conn, $id, $unit ? 'food_unit' : 'food_product', $what . '<br/>Status: ' . $row['status'], '', 3, $unit ? 'Usunięto jednostkę porcji' : 'Usunięto produkt z bazy');
} else {
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE $table SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
    $stmt->bind_param('sii', $status, $me, $id);
    $stmt->execute();
    $stmt->close();
    if (!$unit) {
        // akceptacja czyści dawną uwagę; odrzucenie bez powodu też ją czyści (nieaktualna)
        $note = $action === 'reject' ? (mb_substr(trim((string)($_POST['note'] ?? '')), 0, 300) ?: null) : null;
        $stmt = $conn->prepare('UPDATE food_products SET review_note = ? WHERE id = ?');
        $stmt->bind_param('si', $note, $id);
        $stmt->execute();
        $stmt->close();
        if ($note !== null) $what .= '<br/>Powód: ' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8');
    }
    hl_log($conn, $id, $unit ? 'food_unit' : 'food_product', 'Status: ' . $row['status'], $what . '<br/>Status: ' . $status, 1,
        ($action === 'approve' ? 'Zaakceptowano ' : 'Odrzucono ') . ($unit ? 'jednostkę porcji' : 'produkt'));
}
$msg = ['approve' => 'Zaakceptowano', 'reject' => 'Odrzucono', 'delete' => 'Usunięto'][$action] . ($unit ? ' jednostkę' : ($row['source'] === 'dish' ? ' danie' : ' produkt'));
hl_food_json('success', $msg);
