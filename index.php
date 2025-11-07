<?php
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
}
?>
<!DOCTYPE html>
<html lang="pl" style="overflow-x: hidden!important;">
    <?php include 'components/landing_page/header.php'; ?>
    <body style="overflow-x: hidden;" class="bg-[#fdfdfd]">
            <?php include 'components/landing_page/hero.php'; ?>
    <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
      <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-violet-800 to-red-800 opacity-20 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
    </div>
    <svg viewBox="0 0 1208 1024" aria-hidden="true" class="-z-10 absolute md:top-[2100px] top-[2100px] left-1/2 md:h-[64rem] h-[20rem] -translate-x-1/2 translate-y-1/2 [mask-image:radial-gradient(closest-side,white,transparent)] lg:bottom-auto lg:translate-y-0">
          <ellipse cx="604" cy="512" fill="url(#d25c25d4-6d43-4bf9-b9ac-1842a30a4867)" rx="604" ry="512" />
          <defs>
            <radialGradient id="d25c25d4-6d43-4bf9-b9ac-1842a30a4867">
              <stop stop-color="#67ff80" />
              <stop offset="1" stop-color="#67ff80" />
            </radialGradient>
          </defs>
        </svg>
        <?php include 'components/landing_page/stats.php'; ?>
        <?php include 'components/landing_page/pricing.php'; ?>
        <?php include 'components/landing_page/faq.php'; ?>
        <?php include 'components/landing_page/cta.php'; ?>
        <svg viewBox="0 0 1024 1024" class="absolute left-[0px] -z-10 md:h-[34rem] h-[20rem] w-[20rem] md:w-[34rem] blur-[300px] top-[3200px] [mask-image:radial-gradient(closest-side,white,transparent)]" aria-hidden="true">
        <circle cx="512" cy="512" r="512" fill="url(#827591b1-ce8c-4110-b064-7cb85a0b1217)" fill-opacity="0.7" />
        <defs>
          <radialGradient id="827591b1-ce8c-4110-b064-7cb85a0b1217">
            <stop stop-color="#67ff80" />
            <stop offset="1" stop-color="#67ff80" />
          </radialGradient>
        </defs>
      </svg>
        
            <?php include 'components/landing_page/footer.php'; ?>
        <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
        <script>
            AOS.init();
            var theme = localStorage.getItem("theme");
        </script>
    </body>
</html>