<?php
// Lekkie sprawdzenie, czy widok się zmienił (GET ?scope=list:ID|today|archive|food) – zwraca tylko tokeny.
// Wywoływane cyklicznie przez hlSync (pages/dashboard.php), więc żadnych logów ani zapisów.
header('Content-Type: application/json');
header('Cache-Control: no-store');

$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
include 'sync_lib.php';

$user_id = (int)$_SESSION['login_id'];
// sesja tylko do odczytu – zwolnienie blokady pliku sesji, żeby sprawdzenie nie wstrzymywało równoległych akcji
session_write_close();

$scope = (string)($_GET['scope'] ?? '');
if (!preg_match('/^(list:\d+|today|archive|food)$/', $scope)) hl_sync_bad();

$state = hl_sync_state($conn, $user_id, $scope);
echo json_encode(['status' => 'success', 't' => $state['token'], 'n' => $state['nav'], 's' => $state['shared'] ? 1 : 0]);

function hl_sync_bad(): void
{
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowy zakres']);
    exit();
}
