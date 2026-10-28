<?php
// Decyzje z kart „Wymaga uwagi” (POST id, action):
//   plan (value renew|quit|open|'') – co dalej z umową; quit = ostatnia płatność przed końcem umowy
//   trial_keep   – zostaję po okresie próbnym (alert znika)
//   trial_cancel – rezygnuję: opłata kończy się w dniu końca okresu próbnego, bez płatności
//   review       – przegląd subskrypcji („nadal korzystam”) – następne pytanie za kwartał
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'bills_lib.php';

$user_id = (int)$_SESSION['login_id'];
$b = hl_bill_get($conn, $user_id, (int)($_POST['id'] ?? 0));
if (!$b) hl_bill_json('error', 'Nie znaleziono opłaty');
$id = $b['id'];
$today = date('Y-m-d');

switch ((string)($_POST['action'] ?? '')) {
    case 'plan':
        if (!in_array($b['type'], ['plan', 'ins'], true)) break;
        $plan = in_array($_POST['value'] ?? '', ['renew', 'quit', 'open'], true) ? $_POST['value'] : null;
        // wypowiedzenie ustawia koniec płatności na koniec umowy; zmiana decyzji go zdejmuje
        $end = $plan === 'quit' ? hl_bill_add_days($b['contract_until'] ?: $today, -1) : ($b['contract_plan'] === 'quit' ? null : $b['end_date']);
        $stmt = $conn->prepare('UPDATE bills SET contract_plan = ?, end_date = ? WHERE id = ?');
        $stmt->bind_param('ssi', $plan, $end, $id);
        $stmt->execute();
        $messages = ['renew' => 'Po podpisaniu aneksu wpisz nową cenę i datę końca', 'quit' => 'Przypomnimy o wysłaniu wypowiedzenia', 'open' => 'Przypomnimy przy pierwszej wyższej płatności'];
        hl_bill_json('success', $messages[$plan] ?? 'Wyczyszczono decyzję', ['bill' => hl_bill_public(hl_bill_get($conn, $user_id, $id))]);
    case 'trial_keep':
        $conn->query("UPDATE bills SET trial_until = NULL WHERE id = $id");
        hl_bill_json('success', 'Zostajesz – płatność pojawi się w nadchodzących');
    case 'trial_cancel':
        $end = $b['trial_until'] ?: $today;
        $stmt = $conn->prepare("UPDATE bills SET status = 'ended', ended_at = ? WHERE id = ?");
        $stmt->bind_param('si', $end, $id);
        $stmt->execute();
        hl_bill_json('success', 'Zrezygnowano – pamiętaj, żeby anulować też w serwisie');
    case 'review':
        $stmt = $conn->prepare('UPDATE bills SET review_at = ? WHERE id = ?');
        $stmt->bind_param('si', $today, $id);
        $stmt->execute();
        hl_bill_json('success', 'Zapisano – zapytamy znowu za kwartał');
}
hl_bill_json('warning', 'Nieznana akcja');
