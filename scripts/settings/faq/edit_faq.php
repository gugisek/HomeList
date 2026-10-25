<?php
// Dodanie (id = "add") / edycja pytania FAQ ze strony głównej. Odpowiedź to HTML z edytora Quill –
// trafia na publiczną stronę, więc przepuszczamy tylko bezpieczne znaczniki z edytora.
$SECURITY_REQUIRED_MODULE = ['faq', ($_POST['id'] ?? '') === 'add' ? 'add' : 'edit'];
include "../../security.php";
include "../../conn_db.php";
require_once "../../users/user_admin_lib.php";
require_once "../../log_lib.php";

function hl_faq_html(string $html): string
{
    $html = strip_tags($html, '<p><br><strong><b><em><i><u><s><ol><ul><li><a><h1><h2><h3><blockquote><span><pre><code>');
    $html = preg_replace('/\s(on\w+|style)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    return preg_replace('/\shref\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'>\s]*\1/i', '', $html);
}

$id = $_POST['id'] ?? '';
$question = trim((string)($_POST['question'] ?? ''));
$answer = hl_faq_html((string)($_POST['answer'] ?? ''));

if ($question === '' || trim(strip_tags($answer)) === '') {
    hl_admin_back('Nie wprowadzono wszystkich danych', 'error');
}

if ($id === 'add') {
    $stmt = $conn->prepare('INSERT INTO faq (question, answer) VALUES (?, ?)');
    $stmt->bind_param('ss', $question, $answer);
    if (!$stmt->execute()) {
        hl_admin_back('Wystąpił błąd podczas dodawania pytania', 'error');
    }
    hl_log($conn, $stmt->insert_id, 'FAQ', '', "Pytanie: $question,<br/> Odpowiedź: $answer", 2, 'Dodano pytanie');
    hl_admin_back('Pomyślnie dodano pytanie', 'success');
}

$id = (int)$id;
$stmt = $conn->prepare('SELECT * FROM faq WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    hl_admin_back('Nie znaleziono pytania', 'error');
}
if ($row['question'] === $question && $row['answer'] === $answer) {
    hl_admin_back('Nie wprowadzono żadnych zmian', 'warning');
}
$stmt = $conn->prepare('UPDATE faq SET question = ?, answer = ? WHERE id = ?');
$stmt->bind_param('ssi', $question, $answer, $id);
if (!$stmt->execute()) {
    hl_admin_back('Wystąpił błąd podczas edytowania pytania', 'error');
}
hl_log($conn, $id, 'FAQ', "Pytanie: " . $row['question'] . ",<br/> Odpowiedź: " . $row['answer'], "Pytanie: $question,<br/> Odpowiedź: $answer", 1, 'Edytowano pytanie');
hl_admin_back('Pomyślnie edytowano pytanie', 'success');
