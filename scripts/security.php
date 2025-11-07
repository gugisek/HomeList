<?php
  session_start();
  if(!isset($_SESSION['logged']))
  {
    if (isset($_COOKIE['remember_me'])) {
      include 'scripts/conn_db.php';
  
      [$selector, $token] = explode(':', $_COOKIE['remember_me']);
      $stmt = $conn->prepare("SELECT * FROM remember_tokens WHERE selector = ?");
      $stmt->bind_param("s", $selector);
      $stmt->execute();
      $result = $stmt->get_result();
  
      if ($row = $result->fetch_assoc()) {
          if (hash_equals($row['token_hash'], hash('sha256', $token)) && strtotime($row['expires']) > time()) {
              // Token OK – zaloguj użytkownika
              $_SESSION['logged'] = true;
              $_SESSION['login_id'] = $row['user_id'];
              $_SESSION['last_refresh'] = time();
  
              // Pobierz dodatkowe dane użytkownika jeśli chcesz
              // np. z tabeli `users` i ustaw $_SESSION['user'], itd.
              $sql = "SELECT users.status_id, users.profile_picture, users.name, users.sur_name, status_privileges.login, users.id, users.role_id, user_roles.role, user_roles.dashboard FROM users join user_status on users.status_id=user_status.id join status_privileges on status_privileges.id=user_status.privileges join user_roles on user_roles.id=users.role_id WHERE users.id = '" . $_SESSION['login_id'] . "'";
              $result = mysqli_query($conn, $sql);
              $row2 = mysqli_fetch_assoc($result);
              $_SESSION['user'] = $row2['name'] . ' ' . $row2['sur_name'];
              $_SESSION['role'] = $row2['role'];
              $_SESSION['role_id'] = $row2['role_id'];
              $_SESSION['dashboard'] = $row2['dashboard'];
              $_SESSION['alert'] = 'Zalogowano pomyślnie.';
              $_SESSION['alert_type'] = 'success';
              $_SESSION['profile_picture'] = $row2['profile_picture'];
              header('Location: panel.php');
          } else {
              // Token nieważny – usuń
              setcookie("remember_me", "", time() - 3600, "/");
              $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
              $stmt->bind_param("s", $selector);
              $stmt->execute();
          }
      }
  }else{
    $_SESSION['alert'] = 'Hola hola! Zaloguj się!';
    $_SESSION['alert_type'] = 'warning';

    header('Location: ../../login.php');
    exit();
  }
  }
?>