<?php include_once __DIR__ . '/../../scripts/conn_db.php'; require_once __DIR__ . '/../../scripts/app_settings.php'; ?>
<?php if (hl_setting($conn, 'registration_open', '1') !== '1'): ?>
      <div class="text-center text-sm text-gray-600">
        <p class="text-4xl mb-4">🔒</p>
        Rejestracja nowych kont jest obecnie wyłączona.<br>Poproś administratora o założenie konta.
      </div>
<?php else: ?>
      <form id="register_form" class="space-y-6" onsubmit="event.preventDefault(); registerSubmit(this);">
        <?=csrf_input()?>
        <div>
          <label for="name" class="<?=$hl_label_cls?>">Nazwa Twojego konta</label>
          <div class="mt-2">
            <input id="name" name="name" type="text" autocomplete="name" required maxlength="60" class="<?=$hl_input_cls?>">
          </div>
        </div>
        <div>
          <label for="email" class="<?=$hl_label_cls?>">Adres email</label>
          <div class="mt-2">
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="100" class="<?=$hl_input_cls?>">
          </div>
        </div>

        <?=hl_password_input('password', 'Hasło', 'new-password', $hl_input_cls)?>
        <p class="-mt-4 pl-1 text-xs text-gray-500">Min. 8 znaków, wielka i mała litera oraz cyfra.</p>
        <?=hl_password_input('re_password', 'Powtórz hasło', 'new-password', $hl_input_cls)?>

        <div class="flex flex-col items-center justify-center">
          <button type="submit" class="<?=$hl_btn_cls?> mb-3">Zarejestruj</button>
          <span class="text-center text-sm">Rejestrując się <span class="text-green-400">zgadzasz</span> się na używanie plików cookie 🍪</span>
        </div>
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
      </script>
<?php endif; ?>
