<?php // $reset_token i $reset_valid ustawia reset_password.php (nagłówek strony zależy od ważności linku) ?>
<?php if (!$reset_valid): ?>
      <div class="lp-auth-state">
        <span class="lp-auth-state-ico is-amber"><?=lp_icon('clock', 'lp-i')?></span>
        <p>Link jest nieprawidłowy, został już użyty albo wygasł.</p>
        <a href="forgot_password" class="<?=$hl_btn_cls?>">Wyślij nowy link</a>
      </div>
<?php else: ?>
      <form id="reset_form" class="lp-auth-form" onsubmit="event.preventDefault(); resetSubmit(this);">
        <?=csrf_input()?>
        <input type="hidden" name="token" value="<?=htmlspecialchars($reset_token)?>">
        <?=hl_password_input('new_password', 'Nowe hasło', 'new-password', $hl_input_cls)?>
        <ul class="lp-pass-rules" id="pass_rules" aria-live="polite">
          <li data-rule="len">8 znaków</li><li data-rule="up">wielka litera</li><li data-rule="low">mała litera</li><li data-rule="num">cyfra</li>
        </ul>
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
        document.getElementById('new_password').addEventListener('input', function () {
          const p = this.value;
          const ok = { len: p.length >= 8, up: /[A-ZĄĆĘŁŃÓŚŹŻ]/.test(p), low: /[a-ząćęłńóśźż]/.test(p), num: /\d/.test(p) };
          document.querySelectorAll('#pass_rules [data-rule]').forEach(li => li.classList.toggle('is-done', ok[li.dataset.rule]));
        });
      </script>
<?php endif; ?>
