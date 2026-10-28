<?php
// token sprawdzamy już przy wyświetleniu, żeby nie kazać wpisywać hasła do martwego linku
$reset_token = trim((string)($_GET['token'] ?? ''));
$reset_valid = false;
if ($reset_token !== '') {
    include_once __DIR__ . '/scripts/conn_db.php';
    $reset_hash = hash('sha256', $reset_token);
    $stmt = $conn->prepare('SELECT id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $stmt->bind_param('s', $reset_hash);
    $stmt->execute();
    $reset_valid = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$title = "nowe hasło";
$auth_heading = $reset_valid ? 'Ustaw nowe hasło' : 'Ten link już nie działa';
$auth_subheading = $reset_valid ? 'I gotowe – wracasz do swoich list.' : 'Linki do zmiany hasła są ważne 45 minut i działają tylko raz.';
$auth_form = 'reset_form.php';
$auth_visual = 'lock';
$auth_note = $reset_valid ? ['HomeList', 'Nowe hasło, ten sam plan dnia'] : ['Poczta · HomeList', 'Link wygasł – poproś o nowy'];
// link z maila ma działać także w przeglądarce, w której ktoś jest zalogowany
$auth_allow_logged = true;
$auth_footer = '<a href="login" class="lp-link">Wróć do logowania</a>';
include 'components/login/auth_layout.php';
