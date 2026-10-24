<?php
  // Ochrona stron: przy wygasłej sesji próbuje zalogować z remember_me, inaczej przekierowuje do logowania
  include_once __DIR__.'/session_restore.php';
  if (!hl_session_start()) {
    $_SESSION['alert'] = 'Hola hola! Zaloguj się!';
    $_SESSION['alert_type'] = 'warning';

    header('Location: ../../login.php');
    exit();
  }
?>