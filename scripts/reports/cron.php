<?php
// Powiadomienia administratorów o nowych zgłoszeniach błędów (mail + push) – uruchamiać np. co 5 minut:
//   */5 * * * * php /sciezka/HomeList/scripts/reports/cron.php
// albo przez HTTP: scripts/reports/cron.php?key=KLUCZ   (klucz: php scripts/reports/cron.php --key)
// --dry: tylko wypisuje, co zostałoby wysłane.
//
// Wszystkie zgłoszenia od ostatniego przebiegu scalane w JEDEN mail i JEDEN push na administratora.
// Odbiorcy: aktywne konta z dostępem do modułu "Zgłoszenia błędów" (user_roles.reports).
// Push i mail mają osobne znaczniki (pushed_at / notified_at) – push działa także bez skonfigurowanego SMTP.
include __DIR__ . '/../conn_db.php';
require_once __DIR__ . '/../secrets.php';
require_once __DIR__ . '/../mailer.php';
require_once __DIR__ . '/../planner/planner_lib.php';
require_once __DIR__ . '/../push/push_lib.php';
require_once __DIR__ . '/report_lib.php';

$cron_key = substr(hash('sha256', 'hl-reports-cron' . hl_secret('cron_key')), 0, 32);
if (PHP_SAPI === 'cli') {
    if (in_array('--key', $argv ?? [])) exit($cron_key . "\n");
} elseif (!hash_equals($cron_key, (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
$dry = PHP_SAPI === 'cli' && in_array('--dry', $argv ?? []);

$reports = [];
$result = $conn->query('SELECT bug_reports.*, users.name, users.sur_name, users.mail FROM bug_reports
                        LEFT JOIN users ON users.id = bug_reports.user_id
                        WHERE bug_reports.notified_at IS NULL OR bug_reports.pushed_at IS NULL
                        ORDER BY bug_reports.id ASC LIMIT 50');
while ($row = $result->fetch_assoc()) {
    $row['author'] = $row['name'] !== null ? trim($row['name'] . ' ' . $row['sur_name']) : 'Usunięte konto';
    $reports[] = $row;
}
if (!$reports) exit("Brak nowych zgłoszeń\n");

$recipients = hl_report_recipients($conn);
if (!$recipients) exit("Brak odbiorców (nikt nie ma dostępu do modułu Zgłoszenia błędów)\n");
$ids_sql = fn(array $list) => implode(',', array_map(fn($r) => (int)$r['id'], $list));

// --- push ---
$to_push = array_values(array_filter($reports, fn($r) => $r['pushed_at'] === null));
if ($to_push) {
    $n = count($to_push);
    if ($n === 1) {
        $title = 'Nowe zgłoszenie błędu';
        $body = $to_push[0]['author'] . ': ' . preg_replace('/\s+/', ' ', $to_push[0]['description']);
    } else {
        $title = "Nowe zgłoszenia błędów ($n)";
        $body = 'Od: ' . implode(', ', array_unique(array_column($to_push, 'author')));
    }
    $pushed = 0;
    foreach ($recipients as $to) {
        $s = hl_push_settings($conn, $to['id']);
        if (!$s['enabled'] || !$s['notify_reports']) continue;
        if ($dry) {
            echo "Push do {$to['name']}: $title – $body\n";
            continue;
        }
        $pushed += hl_push_notify($conn, $to['id'], $title, $body, 'bug_report', 'panel.php?site=reports');
    }
    // push bez ponawiania – urządzenie offline i tak dostanie go po połączeniu (TTL)
    if (!$dry) $conn->query('UPDATE bug_reports SET pushed_at = NOW() WHERE id IN (' . $ids_sql($to_push) . ')');
    echo "Push: zgłoszeń $n, urządzeń, które przyjęły: $pushed\n";
}

// --- mail ---
$to_mail = array_values(array_filter($reports, fn($r) => $r['notified_at'] === null));
$mail_to = array_values(array_filter($recipients, fn($r) => filter_var($r['mail'], FILTER_VALIDATE_EMAIL)));
if (!$to_mail || !$mail_to) exit();
if (!$dry && !hl_mail_configured($conn)) exit("Brak konfiguracji SMTP (Ustawienia > Email) – mail pominięty\n");

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$count = count($to_mail);
$subject = $count === 1 ? 'Nowe zgłoszenie błędu w HomeList' : "Nowe zgłoszenia błędów w HomeList ($count)";

$content = '<p>' . ($count === 1 ? 'Użytkownik zgłosił błąd w aplikacji:' : "Nowe zgłoszenia błędów w aplikacji ($count):") . '</p>';
foreach ($to_mail as $r) {
    $desc = mb_strlen($r['description']) > 400 ? mb_substr($r['description'], 0, 400) . '…' : $r['description'];
    $shots = count(hl_report_shots($r['screenshots']));
    $meta = array_filter([
        date('d.m.Y H:i', strtotime($r['created_at'])),
        $r['app_version'] ? 'v' . $r['app_version'] : '',
        hl_report_device($r['user_agent']),
        $shots ? $shots . ' ' . ($shots === 1 ? 'zrzut ekranu' : 'zrzuty ekranu') : '',
    ]);
    $content .= "<div style='background:#f3f4f6;border-radius:16px;padding:14px 16px;margin:12px 0;'>"
        . "<div style='font-weight:600;color:#111827;'>" . $e($r['author']) . "</div>"
        . "<div style='font-size:12px;color:#6b7280;margin-bottom:8px;'>" . $e(implode(' · ', $meta)) . "</div>"
        . "<div style='white-space:pre-line;'>" . $e($desc) . "</div></div>";
}
$app_url = (string)(end($to_mail)['app_url'] ?? '');
if ($app_url !== '') $content .= hl_mail_button($app_url . 'panel.php?site=reports', 'Otwórz zgłoszenia');

if ($dry) {
    echo "Temat: $subject\nDo: " . implode(', ', array_column($mail_to, 'mail')) . "\n\n" . strip_tags(str_replace('</div>', "\n", $content)) . "\n";
    exit();
}

$sent = 0;
foreach ($mail_to as $to) {
    $mail = send_app_mail(['to' => $to['mail'], 'to_name' => $to['name'], 'subject' => $subject, 'body' => $content]);
    if ($mail['status'] === 'success') $sent++;
    else echo 'Błąd wysyłki do ' . $to['mail'] . ': ' . $mail['message'] . "\n";
}

// przy całkowitej porażce zostawiamy zgłoszenia na następny przebieg
if ($sent > 0) $conn->query('UPDATE bug_reports SET notified_at = NOW() WHERE id IN (' . $ids_sql($to_mail) . ')');
echo "Mail: zgłoszeń $count, wysłano maili: $sent/" . count($mail_to) . "\n";
