<?php
// Mail do administratorów o nowych zgłoszeniach błędów – uruchamiać np. co 5 minut:
//   */5 * * * * php /sciezka/HomeList/scripts/reports/cron.php
// albo przez HTTP: scripts/reports/cron.php?key=KLUCZ   (klucz: php scripts/reports/cron.php --key)
// --dry: tylko wypisuje, co zostałoby wysłane.
//
// Wszystkie zgłoszenia od ostatniego przebiegu scalane w JEDEN mail na administratora.
// Odbiorcy: aktywne konta z dostępem do modułu "Zgłoszenia błędów" (user_roles.reports).
include __DIR__ . '/../conn_db.php';
require_once __DIR__ . '/../secrets.php';
require_once __DIR__ . '/../mailer.php';
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
                        WHERE bug_reports.notified_at IS NULL ORDER BY bug_reports.id ASC LIMIT 50');
while ($row = $result->fetch_assoc()) $reports[] = $row;
if (!$reports) exit("Brak nowych zgłoszeń\n");

$recipients = hl_report_recipients($conn);
if (!$recipients) exit("Brak odbiorców (nikt nie ma dostępu do modułu Zgłoszenia błędów)\n");
if (!$dry && !hl_mail_configured($conn)) exit("Brak konfiguracji SMTP (Ustawienia > Email)\n");

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$count = count($reports);
$subject = $count === 1 ? 'Nowe zgłoszenie błędu w HomeList' : "Nowe zgłoszenia błędów w HomeList ($count)";

$content = '<p>' . ($count === 1 ? 'Użytkownik zgłosił błąd w aplikacji:' : "Nowe zgłoszenia błędów w aplikacji ($count):") . '</p>';
foreach ($reports as $r) {
    $author = $r['name'] !== null ? trim($r['name'] . ' ' . $r['sur_name']) : 'Usunięte konto';
    $desc = mb_strlen($r['description']) > 400 ? mb_substr($r['description'], 0, 400) . '…' : $r['description'];
    $shots = count(hl_report_shots($r['screenshots']));
    $meta = array_filter([
        date('d.m.Y H:i', strtotime($r['created_at'])),
        $r['app_version'] ? 'v' . $r['app_version'] : '',
        hl_report_device($r['user_agent']),
        $shots ? $shots . ' ' . ($shots === 1 ? 'zrzut ekranu' : 'zrzuty ekranu') : '',
    ]);
    $content .= "<div style='background:#f3f4f6;border-radius:16px;padding:14px 16px;margin:12px 0;'>"
        . "<div style='font-weight:600;color:#111827;'>" . $e($author) . "</div>"
        . "<div style='font-size:12px;color:#6b7280;margin-bottom:8px;'>" . $e(implode(' · ', $meta)) . "</div>"
        . "<div style='white-space:pre-line;'>" . $e($desc) . "</div></div>";
}
$app_url = (string)(end($reports)['app_url'] ?? '');
if ($app_url !== '') $content .= hl_mail_button($app_url . 'panel.php?site=reports', 'Otwórz zgłoszenia');

if ($dry) {
    echo "Temat: $subject\nDo: " . implode(', ', array_column($recipients, 'mail')) . "\n\n" . strip_tags(str_replace('</div>', "\n", $content)) . "\n";
    exit();
}

$sent = 0;
foreach ($recipients as $to) {
    $mail = send_app_mail(['to' => $to['mail'], 'to_name' => $to['name'], 'subject' => $subject, 'body' => $content]);
    if ($mail['status'] === 'success') $sent++;
    else echo 'Błąd wysyłki do ' . $to['mail'] . ': ' . $mail['message'] . "\n";
}

// przy całkowitej porażce zostawiamy zgłoszenia na następny przebieg
if ($sent > 0) {
    $ids = implode(',', array_map(fn($r) => (int)$r['id'], $reports));
    $conn->query("UPDATE bug_reports SET notified_at = NOW() WHERE id IN ($ids)");
}
echo "Zgłoszeń: $count, wysłano maili: $sent/" . count($recipients) . "\n";
