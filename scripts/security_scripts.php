<?php
  // Ochrona endpointów AJAX: przy wygasłej sesji próbuje zalogować z remember_me, inaczej zwraca JSON z ostrzeżeniem
  include_once __DIR__.'/session_restore.php';
  if (!hl_session_start()) {
    header('Content-Type: application/json'); // Nagłówek JSON

    echo json_encode([
        'status' => 'warning',
        'session_expired' => true,
        'message' => 'Niewprowadzono zmian, sesja wygasła. Zaloguj się ponownie.'
    ]);

    exit();
  }
?>