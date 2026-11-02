<?php
// Włączenie / wyłączenie planu dnia (zakładka Dziś) – POST enabled = 0|1. Zaplanowane zadania i rutyny zostają w bazie.
header('Content-Type: application/json');

$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
include 'planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
if (!isset($_POST['enabled'])) { hl_json('warning', 'Brak ustawienia'); exit; }
$enabled = (int)(bool)(int)$_POST['enabled'];

$stmt = $conn->prepare('UPDATE users SET feed_enabled = ? WHERE id = ?');
$stmt->bind_param('ii', $enabled, $user_id);
if (!$stmt->execute()) { hl_json('error', 'Nie udało się zapisać ustawień'); exit; }
$stmt->close();
hl_json('success', $enabled ? 'Plan dnia włączony' : 'Plan dnia wyłączony', ['enabled' => (bool)$enabled]);
