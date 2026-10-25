<?php
// token sprawdzamy już przy wyświetleniu, żeby nie kazać wpisywać hasła do martwego linku
$reset_token = trim((string)($_GET['token'] ?? ''));
$reset_valid = false;
if ($reset_token !== '') {
    include_once __DIR__ . '/../../scripts/conn_db.php';
    $reset_hash = hash('sha256', $reset_token);
    $stmt = $conn->prepare('SELECT id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $stmt->bind_param('s', $reset_hash);
    $stmt->execute();
    $reset_valid = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<?php if (!$reset_valid): ?>
      <div class="text-center text-sm text-gray-600 space-y-4">
        <p class="text-4xl">⌛</p>
        <p>Link jest nieprawidłowy, został już użyty albo wygasł.</p>
        <a href="forgot_password.php" class="<?=$hl_btn_cls?>">Wyślij nowy link</a>
      </div>
<?php else: ?>
      <form id="reset_form" class="space-y-6" onsubmit="event.preventDefault(); resetSubmit(this);">
        <?=csrf_input()?>
        <input type="hidden" name="token" value="<?=htmlspecialchars($reset_token)?>">
        <?=hl_password_input('new_password', 'Nowe hasło', 'new-password', $hl_input_cls)?>
        <p class="-mt-4 pl-1 text-xs text-gray-500">Min. 8 znaków, wielka i mała litera oraz cyfra.</p>
        <?=hl_password_input('confirm_password', 'Powtórz nowe hasło', 'new-password', $hl_input_cls)?>
        <button type="submit" class="<?=$hl_btn_cls?>">Ustaw nowe hasło</button>
      </form>
      <script>
        function resetSubmit(form) {
          if (form.querySelector('#new_password').value !== form.querySelector('#confirm_password').value) {
            showAlert('warning', 'Hasła nie są identyczne!');
            return;
          }
          authSubmit(form, 'scripts/login/reset_password.php');
        }
      </script>
<?php endif; ?>
