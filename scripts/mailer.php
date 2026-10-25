<?php
// Wysyłka pojedynczych maili aplikacji (aktywacja konta, reset hasła, powiadomienia o bezpieczeństwie).
// Wzorzec send_app_mail() z panels-mOsiedle-v2, konfiguracja SMTP w app_settings (Ustawienia > Email).
// PHPMailer leży w lib/PHPMailer (bez composera).
//
// $result = send_app_mail(['to' => $email, 'subject' => 'Reset hasła', 'body' => $html]);
// if ($result['status'] !== 'success') error_log($result['message']);

require_once __DIR__ . '/app_settings.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

if (!function_exists('send_app_mail')) {
    // prosty szablon w stylu HomeList (zielony akcent, zaokrąglona karta)
    function hl_mail_template(string $title, string $content): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        return "<!DOCTYPE html><html lang='pl'><body style='margin:0;background:#f3f4f6;font-family:Poppins,Segoe UI,Arial,sans-serif;'>
            <div style='max-width:520px;margin:0 auto;padding:32px 16px;'>
              <div style='text-align:center;margin-bottom:16px;font-size:14px;color:#4ade80;font-weight:600;'>HomeList</div>
              <div style='background:#ffffff;border-radius:24px;padding:28px;box-shadow:0 10px 30px rgba(0,0,0,.06);color:#1f2937;font-size:14px;line-height:1.6;'>
                <h2 style='margin:0 0 12px;font-size:18px;color:#111827;'>$title</h2>
                $content
              </div>
              <p style='text-align:center;font-size:12px;color:#9ca3af;margin-top:16px;'>Wiadomość wysłana automatycznie – nie odpowiadaj na nią.</p>
            </div></body></html>";
    }

    // duży kod (aktywacja) w szarej ramce
    function hl_mail_code(string $code): string
    {
        return "<div style='background:#f3f4f6;border-radius:16px;padding:18px;text-align:center;margin:18px 0;font-size:30px;letter-spacing:8px;font-weight:700;color:#111827;'>"
            . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    function hl_mail_button(string $url, string $label): string
    {
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        return "<div style='text-align:center;margin:22px 0;'><a href='$url' style='display:inline-block;background:#4ade80;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:14px;font-weight:600;'>"
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</a></div>
            <p style='font-size:12px;color:#6b7280;word-break:break-all;'>Jeśli przycisk nie działa, skopiuj link do przeglądarki:<br>$url</p>";
    }

    /**
     * @return array{status: 'success'|'error', message: string}
     */
    function send_app_mail(array $options): array
    {
        global $conn;
        if (!($conn instanceof mysqli)) {
            return ['status' => 'error', 'message' => 'Brak połączenia z bazą danych'];
        }

        $to = trim((string)($options['to'] ?? ''));
        $subject = (string)($options['subject'] ?? 'HomeList');
        $body = (string)($options['body'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Nieprawidłowy adres email odbiorcy'];
        }
        if (!hl_mail_configured($conn)) {
            return ['status' => 'error', 'message' => 'Brak konfiguracji SMTP (Ustawienia > Email)'];
        }

        $mail = new PHPMailer(true);
        $transcript = [];
        try {
            // zapis rozmowy SMTP do logów (diagnostyka "sukces, ale mail nie dochodzi")
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function ($line) use (&$transcript) {
                $transcript[] = rtrim((string)$line);
            };

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host = hl_setting($conn, 'smtp_server');
            $mail->SMTPAuth = true;
            // login SMTP bywa innym ciągiem niż adres nadawcy (np. Amazon SES); puste = adres email
            $username = trim(hl_setting($conn, 'smtp_username'));
            $mail->Username = $username !== '' ? $username : hl_setting($conn, 'smtp_email');
            $mail->Password = hl_setting($conn, 'smtp_auth_token');
            $secure = hl_setting($conn, 'smtp_secure', 'tls');
            $mail->SMTPSecure = $secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : ($secure === 'none' ? '' : PHPMailer::ENCRYPTION_STARTTLS);
            $mail->SMTPAutoTLS = $secure !== 'none';
            $mail->Port = (int)hl_setting($conn, 'smtp_port', '587');
            $mail->Timeout = 15;

            $mail->setFrom(hl_setting($conn, 'smtp_email'), hl_setting($conn, 'smtp_name', 'HomeList'));
            $mail->addAddress($to, (string)($options['to_name'] ?? ''));
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = ($options['wrap_template'] ?? true) ? hl_mail_template($subject, $body) : $body;
            $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</div>'], "\n", $body)), ENT_QUOTES, 'UTF-8'));
            $mail->addCustomHeader('Auto-Submitted', 'auto-generated');

            $mail->send();
            hl_mail_log($conn, $mail, $to, $subject, true, '', $transcript);
            return ['status' => 'success', 'message' => 'Wiadomość została wysłana'];
        } catch (PHPMailerException $e) {
            error_log('send_app_mail error: ' . $mail->ErrorInfo);
            hl_mail_log($conn, $mail, $to, $subject, false, $mail->ErrorInfo, $transcript);
            return ['status' => 'error', 'message' => 'Błąd wysyłki: ' . $mail->ErrorInfo];
        }
    }

    // Wpis w logs (object_type 'mail'): przed = konfiguracja SMTP, po = wynik + przebieg rozmowy z serwerem.
    // Dane uwierzytelniające z rozmowy są maskowane, błąd zapisu logu nie psuje wysyłki.
    function hl_mail_log(mysqli $conn, PHPMailer $mail, string $to, string $subject, bool $ok, string $error, array $transcript): void
    {
        try {
            $lines = [];
            $inAuth = false;
            $serverId = '';
            foreach ($transcript as $line) {
                if (preg_match('~^CLIENT -> SERVER: AUTH\b~i', $line)) {
                    $inAuth = true;
                    $line = preg_replace('~^(CLIENT -> SERVER: AUTH \S+).*$~i', '$1 [ukryto]', $line);
                } elseif ($inAuth && str_starts_with($line, 'CLIENT -> SERVER:')) {
                    $line = 'CLIENT -> SERVER: [ukryto]';
                } elseif ($inAuth && preg_match('~^SERVER -> CLIENT: (235|5\d\d)~', $line)) {
                    $inAuth = false;
                }
                // odpowiedź na DATA, np. Amazon SES: "250 Ok 0100018f...-000000" = identyfikator wiadomości w SES
                if (preg_match('~^SERVER -> CLIENT: 250 Ok (\S+)~i', $line, $m)) {
                    $serverId = $m[1];
                }
                $lines[] = $line;
            }
            $log = implode('<br/>', $lines);
            if (strlen($log) > 50000) {
                $log = substr($log, 0, 50000) . '<br/>[...]';
            }

            $before = 'Serwer: ' . $mail->Host . ':' . $mail->Port . ' (' . ($mail->SMTPSecure ?: 'bez szyfrowania') . ')'
                . ' <br/>Login: ' . $mail->Username
                . ' <br/>Nadawca: ' . $mail->From . ' (' . $mail->FromName . ')';
            $after = 'Do: ' . $to
                . ' <br/>Temat: ' . $subject
                . ' <br/>Wynik: ' . ($ok ? 'przyjęty przez serwer SMTP' : 'błąd – ' . $error)
                . ($serverId !== '' ? ' <br/>ID wiadomości na serwerze: ' . $serverId : '')
                . ' <br/>Message-ID: ' . $mail->getLastMessageID()
                . ' <br/><br/>Przebieg SMTP:<br/>' . $log;

            require_once __DIR__ . '/log_lib.php';
            hl_log($conn, 0, 'mail', $before, $after, 2, $ok ? 'Wysłano email: ' . $subject : 'Błąd wysyłki email: ' . $subject);
        } catch (Throwable $e) {
            error_log('hl_mail_log error: ' . $e->getMessage());
        }
    }
}
