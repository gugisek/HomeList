<?php if (!empty($_SESSION['pending_2fa'])): ?>
      <form class="lp-auth-form" action="scripts/login/verify_2fa.php" method="POST">
        <?=csrf_input()?>
        <div class="lp-auth-info">
          <?=lp_icon('shield', 'lp-i')?>
          <p>Wpisz 6-cyfrowy kod z aplikacji uwierzytelniającej albo jeden z kodów zapasowych.</p>
        </div>
        <div class="lp-field">
          <label for="two_factor_code" class="<?=$hl_label_cls?>">Kod 2FA lub kod zapasowy</label>
          <input id="two_factor_code" name="two_factor_code" type="text" inputmode="text" autocomplete="one-time-code" autofocus required maxlength="14" placeholder="123456" class="<?=$hl_input_cls?> lp-input-code">
        </div>
        <button type="submit" class="<?=$hl_btn_cls?>">Potwierdź</button>
        <a href="scripts/login/logout.php?cancel=1" class="lp-text-btn">Anuluj i wróć do logowania</a>
      </form>
<?php else: ?>
      <form class="lp-auth-form" action="scripts/login/login_script.php" method="POST">
        <?=csrf_input()?>
        <div class="lp-field">
          <label for="email" class="<?=$hl_label_cls?>">Adres email</label>
          <input id="email" name="email" type="email" autocomplete="email" required autofocus class="<?=$hl_input_cls?>">
        </div>

        <?=hl_password_input('password', 'Hasło', 'current-password', $hl_input_cls)?>

        <div class="lp-auth-row">
          <label class="lp-checkbox"><input id="remember-me" name="remember-me" type="checkbox" checked><span>Zapamiętaj mnie</span></label>
          <a href="forgot_password.php" class="lp-link-muted">Nie pamiętam hasła</a>
        </div>

        <button type="submit" class="<?=$hl_btn_cls?>">Zaloguj się <?=lp_icon('arrow', 'lp-i-sm', '2')?></button>
        <p class="lp-auth-legal">Logując się, zgadzasz się na używanie plików cookie.</p>
      </form>
<?php endif; ?>
