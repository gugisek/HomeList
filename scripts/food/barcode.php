<?php
// Produkt po kodzie kreskowym (GET code): najpierw baza HomeList, potem API Open Food Facts (wynik zapisujemy).
// found = false -> interfejs otwiera formularz własnego produktu z wpisanym kodem (i nazwą, jeśli OFF ją zna).
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$code = preg_replace('/\D/', '', (string)($_GET['code'] ?? ''));
if (strlen($code) < 6 || strlen($code) > 32) hl_food_json('warning', 'Nieprawidłowy kod');

$find = function () use ($conn, $user_id, $code): ?array {
    $stmt = $conn->prepare('SELECT p.* FROM food_products p WHERE p.code = ? AND ' . hl_food_visible_sql($conn, $user_id));
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
};

$product = $find();
if (!$product) {
    $json = hl_food_off_request($conn, 'https://world.openfoodfacts.org/api/v2/product/' . $code . '.json?fields=' . hl_food_off_fields());
    if ($json === null) hl_food_json('error', 'Open Food Facts nie odpowiada – spróbuj za chwilę albo dodaj produkt ręcznie', ['found' => false, 'code' => $code]);
    if (!empty($json['product'])) {
        $mapped = hl_food_off_map(hl_food_off_flatten($json['product']));
        if (isset($mapped['reject'])) {
            // OFF zna produkt, ale bez (wiarygodnych) wartości odżywczych – podpowiadamy nazwę do formularza
            hl_food_json('success', '', ['found' => false, 'code' => $code,
                'name' => trim((string)($json['product']['product_name_pl'] ?? '')) ?: trim((string)($json['product']['product_name'] ?? '')) ?: null]);
        }
        hl_food_off_save($conn, [$mapped], 'live');
        $product = $find();
    }
}
if (!$product) hl_food_json('success', '', ['found' => false, 'code' => $code]);
hl_food_json('success', '', ['found' => true, 'product' => hl_food_rows($conn, $user_id, [$product])[0]]);
