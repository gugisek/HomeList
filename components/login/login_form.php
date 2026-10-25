<?php if (!empty($_SESSION['pending_2fa'])): ?>
      <form class="space-y-6" action="scripts/login/verify_2fa.php" method="POST">
        <?=csrf_input()?>
        <div class="flex items-start gap-3 rounded-2xl bg-green-50 p-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 flex-none text-green-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
          </svg>
          <p class="text-sm text-gray-700">Konto jest chronione weryfikacją dwuetapową. Wpisz 6-cyfrowy kod z aplikacji uwierzytelniającej albo jeden z kodów zapasowych.</p>
        </div>
        <div>
          <label for="two_factor_code" class="<?=$hl_label_cls?>">Kod 2FA lub kod zapasowy</label>
          <div class="mt-2">
            <input id="two_factor_code" name="two_factor_code" type="text" inputmode="text" autocomplete="one-time-code" autofocus required maxlength="14" placeholder="123456" class="<?=$hl_input_cls?> text-center tracking-[0.3em] font-semibold">
          </div>
        </div>
        <button type="submit" class="<?=$hl_btn_cls?>">Potwierdź</button>
        <a href="scripts/login/logout.php?cancel=1" class="block text-center text-sm text-gray-500 hover:text-green-400 duration-150">Anuluj i wróć do logowania</a>
      </form>
<?php else: ?>
      <form class="space-y-6" action="scripts/login/login_script.php" method="POST">
        <?=csrf_input()?>
        <div>
          <label for="email" class="<?=$hl_label_cls?>">Adres email</label>
          <div class="mt-2">
            <input id="email" name="email" type="email" autocomplete="email" required class="<?=$hl_input_cls?>">
          </div>
        </div>

        <?=hl_password_input('password', 'Hasło', 'current-password', $hl_input_cls)?>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 rounded duration-150 accent-green-400" checked>
            <label for="remember-me" class="ml-3 block text-sm leading-6 text-gray-900 cursor-pointer">Zapamiętaj mnie</label>
          </div>
          <div class="text-sm leading-6">
            <a href="forgot_password.php" class="font-semibold text-gray-600 hover:text-green-300 duration-150">Nie pamiętam hasła</a>
          </div>
        </div>

        <div class="flex flex-col items-center justify-center">
          <button type="submit" class="<?=$hl_btn_cls?> mb-3">Zaloguj</button>
          <span class="text-center text-sm">Logując <span class="text-green-400">zgadzasz</span> się na używanie plików cookie 🍪</span>
        </div>
      </form>
<?php endif; ?>
