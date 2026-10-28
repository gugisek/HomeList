<?php
// Stan opłaty (POST id, action):
//   end      – zakończona (koniec subskrypcji/umowy; historia zostaje w statystykach), date = ostatni dzień (domyślnie dziś)
//   pause / resume – wstrzymanie (np. zamrożony karnet) – bez terminów i przypomnień
//   delete   – usunięcie razem z historią płatności (UI pyta o potwierdzenie)
$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'bills_lib.php';

$user_id = (int)$_SESSION['login_id'];
$b = hl_bill_get($conn, $user_id, (int)($_POST['id'] ?? 0));
if (!$b) hl_bill_json('error', 'Nie znaleziono opłaty');
$id = $b['id'];

switch ((string)($_POST['action'] ?? '')) {
    case 'end':
        $date = hl_bill_valid_date($_POST['date'] ?? '') ? $_POST['date'] : date('Y-m-d');
        $stmt = $conn->prepare("UPDATE bills SET status = 'ended', ended_at = ? WHERE id = ?");
        $stmt->bind_param('si', $date, $id);
        $stmt->execute();
        hl_bill_json('success', 'Opłata zakończona – zostaje w statystykach');
    case 'pause':
        $conn->query("UPDATE bills SET status = 'paused' WHERE id = $id");
        hl_bill_json('success', 'Wstrzymano');
    case 'resume':
        $conn->query("UPDATE bills SET status = 'active', ended_at = NULL WHERE id = $id");
        hl_bill_json('success', 'Wznowiono');
    case 'delete':
        $conn->begin_transaction();
        $conn->query("DELETE e FROM bill_payment_extras e JOIN bill_payments p ON p.id = e.payment_id WHERE p.bill_id = $id");
        foreach (['bill_payments', 'bill_prices', 'bill_visits'] as $t) $conn->query("DELETE FROM $t WHERE bill_id = $id");
        $conn->query("DELETE FROM bills WHERE id = $id");
        $conn->commit();
        hl_bill_json('success', 'Usunięto opłatę');
}
hl_bill_json('warning', 'Nieznana akcja');
