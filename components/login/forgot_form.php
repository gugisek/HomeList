      <form id="forgot_form" class="space-y-6" onsubmit="event.preventDefault(); authSubmit(this, 'scripts/login/forgot_password.php').then(d => { if (d && d.status === 'success') this.reset(); });">
        <?=csrf_input()?>
        <p class="text-sm text-gray-600">Podaj adres email konta – wyślemy link do ustawienia nowego hasła. Link jest ważny 45 minut.</p>
        <div>
          <label for="email" class="<?=$hl_label_cls?>">Adres email</label>
          <div class="mt-2">
            <input id="email" name="email" type="email" autocomplete="email" required autofocus class="<?=$hl_input_cls?>">
          </div>
        </div>
        <button type="submit" class="<?=$hl_btn_cls?>">Wyślij link</button>
      </form>
