<?php include_once __DIR__ . '/../../scripts/conn_db.php'; require_once __DIR__ . '/../../scripts/app_settings.php'; ?>
<?php if (hl_setting($conn, 'registration_open', '1') !== '1'): ?>
      <div class="lp-auth-state">
        <span class="lp-auth-state-ico"><?=lp_icon('shield', 'lp-i')?></span>
        <p>Rejestracja nowych kont jest obecnie wyłączona.<br>Poproś administratora o założenie konta.</p>
        <a href="login" class="<?=$hl_btn_cls?>">Mam już konto</a>
      </div>
<?php else: ?>
      <form id="register_form" class="lp-auth-form" onsubmit="event.preventDefault(); registerSubmit(this);">
        <?=csrf_input()?>
        <div class="lp-field">
          <label for="name" class="<?=$hl_label_cls?>">Jak mamy Cię nazywać?</label>
          <input id="name" name="name" type="text" autocomplete="name" required maxlength="60" autofocus class="<?=$hl_input_cls?>">
        </div>
        <div class="lp-field">
          <label for="email" class="<?=$hl_label_cls?>">Adres email</label>
          <input id="email" name="email" type="email" autocomplete="email" required maxlength="100" class="<?=$hl_input_cls?>">
        </div>

        <?=hl_password_input('password', 'Hasło', 'new-password', $hl_input_cls)?>
        <ul class="lp-pass-rules" id="pass_rules" aria-live="polite">
          <li data-rule="len">8 znaków</li><li data-rule="up">wielka litera</li><li data-rule="low">mała litera</li><li data-rule="num">cyfra</li>
        </ul>
        <?=hl_password_input('re_password', 'Powtórz hasło', 'new-password', $hl_input_cls)?>

        <button type="submit" class="<?=$hl_btn_cls?>">Załóż konto <?=lp_icon('arrow', 'lp-i-sm', '2')?></button>
        <p class="lp-auth-legal">Rejestrując się, zgadzasz się na używanie plików cookie.</p>
      </form>
      <script>
        function registerSubmit(form) {
          const v = id => form.querySelector('#' + id).value;
          if (v('password') !== v('re_password')) {
            showAlert('warning', 'Hasła nie są identyczne!');
            return;
          }
          authSubmit(form, 'scripts/login/register_script.php');
        }
        // wymagania hasła odhaczane na bieżąco, jak zadania w planie
        document.getElementById('password').addEventListener('input', function () {
          const p = this.value;
          const ok = { len: p.length >= 8, up: /[A-ZĄĆĘŁŃÓŚŹŻ]/.test(p), low: /[a-ząćęłńóśźż]/.test(p), num: /\d/.test(p) };
          document.querySelectorAll('#pass_rules [data-rule]').forEach(li => li.classList.toggle('is-done', ok[li.dataset.rule]));
        });
      </script>
<?php endif; ?>
