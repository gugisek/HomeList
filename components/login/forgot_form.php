      <form id="forgot_form" class="lp-auth-form" onsubmit="event.preventDefault(); authSubmit(this, 'scripts/login/forgot_password.php').then(d => { if (d && d.status === 'success') this.reset(); });">
        <?=csrf_input()?>
        <div class="lp-field">
          <label for="email" class="<?=$hl_label_cls?>">Adres email konta</label>
          <input id="email" name="email" type="email" autocomplete="email" required autofocus class="<?=$hl_input_cls?>">
        </div>
        <button type="submit" class="<?=$hl_btn_cls?>">Wyślij link <?=lp_icon('arrow', 'lp-i-sm', '2')?></button>
      </form>
