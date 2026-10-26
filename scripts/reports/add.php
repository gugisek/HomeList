<?php
// Zgłoszenie błędu z Konto i ustawienia > Zgłoś błąd. Bez obsługi ticketów – zapis + mail do administratorów (cron.php).
include '../security_scripts.php';
require_once '../access.php';
require_once 'report_lib.php';

$uid = (int)$_SESSION['login_id'];
$description = trim((string)($_POST['description'] ?? ''));
if (mb_strlen($description) < 5) {
    hl_json_exit('warning', 'Opisz krótko, co się stało');
}
if (mb_strlen($description) > 2000) {
    hl_json_exit('warning', 'Opis może mieć maksymalnie 2000 znaków');
}

// ochrona przed zalaniem skrzynki administratorów
$stmt = $conn->prepare('SELECT COUNT(*) AS c FROM bug_reports WHERE user_id = ? AND created_at > NOW() - INTERVAL 1 HOUR');
$stmt->bind_param('i', $uid);
$stmt->execute();
if ((int)$stmt->get_result()->fetch_assoc()['c'] >= 5) {
    hl_json_exit('warning', 'Wysłano już kilka zgłoszeń w ciągu ostatniej godziny – spróbuj później');
}

$shots = [];
$uploads = $_FILES['screenshots'] ?? null;
if ($uploads && is_array($uploads['name'])) {
    $count = 0;
    foreach ($uploads['name'] as $i => $name) {
        if (($uploads['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (++$count > HL_REPORT_MAX_SHOTS) break;
        $saved = hl_report_save_shot([
            'name' => $name, 'tmp_name' => $uploads['tmp_name'][$i], 'error' => $uploads['error'][$i], 'size' => $uploads['size'][$i],
        ], $uid, $count);
        if (!$saved['ok']) {
            foreach ($shots as $file) @unlink(hl_report_dir() . $file);
            hl_json_exit('error', $saved['error']);
        }
        $shots[] = $saved['file'];
    }
}

$shots_json = $shots ? json_encode($shots) : null;
ob_start();
include __DIR__ . '/../../version.php';
$version = substr(trim((string)ob_get_clean()), 0, 20);
$viewport = mb_substr(trim((string)($_POST['viewport'] ?? '')), 0, 40);
$user_agent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$app_url = substr(appBaseUrl(), 0, 255);

$stmt = $conn->prepare('INSERT INTO bug_reports (user_id, description, screenshots, app_version, viewport, user_agent, app_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('issssss', $uid, $description, $shots_json, $version, $viewport, $user_agent, $app_url);
if (!$stmt->execute()) {
    foreach ($shots as $file) @unlink(hl_report_dir() . $file);
    hl_json_exit('error', 'Nie udało się wysłać zgłoszenia');
}

hl_json_exit('success', 'Dzięki! Zgłoszenie trafiło do twórców aplikacji');
