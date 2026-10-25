<?php $activate_email = trim((string)($_GET['email'] ?? '')); ?>
      <form id="activate_form" class="space-y-6" onsubmit="event.preventDefault(); authSubmit(this, 'scripts/login/activate.php');">
        <?=csrf_input()?>
        <p class="text-sm text-gray-600">Na podany adres wysłaliśmy 6-cyfrowy kod. Wpisz go poniżej, żeby aktywować konto. Kod jest ważny 24 godziny.</p>
        <div>
          <label for="email" class="<?=$hl_label_cls?>">Adres email</label>
          <div class="mt-2">
            <input id="email" name="email" type="email" autocomplete="email" required value="<?=htmlspecialchars($activate_email)?>" class="<?=$hl_input_cls?>">
          </div>
        </div>
        <div>
          <label for="code" class="<?=$hl_label_cls?>">Kod aktywacyjny</label>
          <div class="mt-2">
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" required <?=$activate_email !== '' ? 'autofocus' : ''?> placeholder="000000" class="<?=$hl_input_cls?> text-center text-lg tracking-[0.5em] font-semibold">
          </div>
        </div>
        <button type="submit" class="<?=$hl_btn_cls?>">Aktywuj konto</button>
        <button type="button" id="resend_btn" onclick="resendCode()" class="block w-full text-center text-sm text-gray-500 hover:text-green-400 duration-150 disabled:opacity-50">Nie dostałeś kodu? Wyślij ponownie</button>
      </form>
      <script>
        function resendCode() {
          const form = document.getElementById('activate_form');
          const data = new FormData();
          data.append('email', form.querySelector('#email').value);
          data.append('csrf_token', form.querySelector('[name=csrf_token]').value);
          const btn = document.getElementById('resend_btn');
          btn.disabled = true;
          fetch('scripts/login/resend_code.php', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => showAlert(d.status || 'error', d.message || 'Nieznana odpowiedź serwera'))
            .catch(() => showAlert('error', 'Wystąpił problem połączenia z serwerem'))
            .finally(() => setTimeout(() => { btn.disabled = false; }, 60000));
        }
      </script>
