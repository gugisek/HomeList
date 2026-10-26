<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['settings', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include '../../../scripts/conn_db.php';
require_once '../../../scripts/app_settings.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$can_edit = hl_can('settings', 'edit');
$dis = $can_edit ? '' : 'disabled';
$card = 'hl-card hl-card-pad scroll-mt-6';
$input = 'hl-input';
$label = 'hl-label';
$btn = 'hl-btn hl-btn-primary';
$nav = 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 group flex items-center gap-x-3 rounded-full py-2.5 px-4 text-sm font-medium active:scale-95 duration-150';
$s = hl_settings($conn);
$mail_ok = hl_mail_configured($conn);
?>
<section data-aos="fade-up" data-aos-delay="100" class="max-w-6xl mx-auto mb-28 md:mb-10">
    <div class="pt-2"><h1 class="hl-title">Ustawienia aplikacji</h1><p class="hl-sub">Role, rejestracja nowych kont i poczta wychodząca.</p></div>

    <div class="mx-auto lg:flex lg:gap-x-16 mt-2">
  <aside class="overflow-x-auto py-4 lg:block lg:w-56 lg:flex-none lg:sticky lg:top-6 lg:self-start [scrollbar-width:none]">
    <nav class="px-4 sm:px-6 lg:px-0">
      <ul role="list" class="flex gap-x-3 gap-y-1 whitespace-nowrap lg:flex-col">
        <li><a href="#ur" class="<?=$nav?>">
            <svg class="size-5 shrink-0 text-gray-400 group-hover:text-green-500 duration-150" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            Role użytkowników</a></li>
        <li><a href="#reg" class="<?=$nav?>">
            <svg class="size-5 shrink-0 text-gray-400 group-hover:text-green-500 duration-150" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
            Rejestracja</a></li>
        <li><a href="#mail" class="<?=$nav?>">
            <svg class="size-5 shrink-0 text-gray-400 group-hover:text-green-500 duration-150" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
            Email (SMTP)</a></li>
      </ul>
    </nav>
  </aside>

  <main class="px-4 sm:px-6 lg:flex-auto lg:px-0">
    <div class="mx-auto max-w-2xl space-y-8 sm:space-y-12 lg:mx-0 pb-16 lg:max-w-none">

      <div id="ur" class="<?=$card?>">
        <h2 class="hl-h2">Role użytkowników</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Role z poziomami dostępu do modułów. Serwer sprawdza je przy każdym widoku i akcji.</p>
        <dl class="mt-5 -mx-2 hl-divide text-sm">
            <?php
                $result = $conn->query("SELECT user_roles.id, role, description, (SELECT COUNT(*) FROM users WHERE users.role_id = user_roles.id) AS cnt FROM user_roles ORDER BY id");
                while ($row = $result->fetch_assoc()) {
                    echo '<div onclick="openPopupUserRoles(' . (int)$row['id'] . ')" class="hl-row !block sm:!flex">
                        <dt class="font-medium text-gray-900 dark:text-gray-100 sm:w-64 sm:flex-none sm:pr-6">' . $e($row['role']) . '</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                          <div class="text-gray-900 dark:text-gray-300">' . $e($row['description']) . '</div>
                          <div class="text-xs text-gray-400 whitespace-nowrap">kont: ' . (int)$row['cnt'] . '</div>
                        </dd>
                      </div>';
                }
            ?>
          <?php if (hl_can('settings', 'add')): ?>
          <div class="flex pt-4 px-2">
            <button type="button" onclick="openPopupUserRolesAdd()" class="hl-btn hl-btn-accent hl-btn-sm"><span aria-hidden="true">+</span> Dodaj nową rolę</button>
          </div>
          <?php endif; ?>
        </dl>
      </div>

      <form id="reg" class="<?=$card?>" onsubmit="appSettingsSave(event, this)">
        <input type="hidden" name="section" value="registration">
        <h2 class="hl-h2">Rejestracja</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Czy nowe osoby mogą same zakładać konta i z jaką rolą startują.</p>
        <div class="mt-6 flex flex-col gap-3">
            <label class="hl-toggle-row">
                <span class="text-sm text-gray-800 dark:text-gray-200">Rejestracja otwarta<span class="block text-xs text-gray-500 dark:text-gray-400">Wyłączona = konta zakłada tylko administrator</span></span>
                <input type="checkbox" name="registration_open" value="1" <?=($s['registration_open'] ?? '1') === '1' ? 'checked' : ''?> class="size-5 accent-green-400" <?=$dis?>>
            </label>
            <label class="hl-toggle-row">
                <span class="text-sm text-gray-800 dark:text-gray-200">Aktywacja kodem z maila<span class="block text-xs text-gray-500 dark:text-gray-400"><?=$mail_ok ? 'Konto działa dopiero po wpisaniu 6-cyfrowego kodu' : 'Wymaga skonfigurowanego SMTP – bez niego konta są aktywne od razu'?></span></span>
                <input type="checkbox" name="registration_activation" value="1" <?=($s['registration_activation'] ?? '1') === '1' ? 'checked' : ''?> class="size-5 accent-green-400" <?=$dis?>>
            </label>
            <div>
                <label for="registration_role" class="<?=$label?>">Rola nowych kont</label>
                <div>
                    <select name="registration_role" id="registration_role" class="cursor-pointer <?=$input?>" <?=$dis?>>
                        <?php
                        $result = $conn->query('SELECT id, role FROM user_roles ORDER BY id');
                        while ($r = $result->fetch_assoc()) {
                            echo '<option value="' . (int)$r['id'] . '"' . ((string)$r['id'] === ($s['registration_role'] ?? '3') ? ' selected' : '') . '>' . $e($r['role']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <?php if ($can_edit): ?><div class="mt-6 flex justify-end"><button class="<?=$btn?>">Zapisz</button></div><?php endif; ?>
      </form>

      <form id="mail" class="<?=$card?>" onsubmit="appSettingsSave(event, this)" autocomplete="off">
        <input type="hidden" name="section" value="mail">
        <h2 class="hl-h2">Email (SMTP)</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Serwer, przez który wychodzą kody aktywacyjne i linki do resetu hasła.
            <span class="<?=$mail_ok ? 'text-green-500' : 'text-amber-500'?> font-medium"><?=$mail_ok ? 'Skonfigurowano.' : 'Nieskonfigurowano – reset hasła i aktywacja mailem są wyłączone.'?></span></p>
        <div class="mt-6 grid sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <label for="smtp_server" class="<?=$label?>">Serwer</label>
                <div><input name="smtp_server" id="smtp_server" type="text" value="<?=$e($s['smtp_server'] ?? '')?>" placeholder="smtp.gmail.com" class="<?=$input?>" <?=$dis?>></div>
            </div>
            <div>
                <label for="smtp_port" class="<?=$label?>">Port</label>
                <div><input name="smtp_port" id="smtp_port" type="number" min="1" max="65535" value="<?=$e($s['smtp_port'] ?? '587')?>" class="<?=$input?>" <?=$dis?>></div>
            </div>
            <div>
                <label for="smtp_secure" class="<?=$label?>">Szyfrowanie</label>
                <div>
                    <select name="smtp_secure" id="smtp_secure" class="cursor-pointer <?=$input?>" <?=$dis?>>
                        <?php foreach (['tls' => 'STARTTLS (587)', 'ssl' => 'SSL/TLS (465)', 'none' => 'Brak'] as $k => $v): ?>
                        <option value="<?=$k?>" <?=($s['smtp_secure'] ?? 'tls') === $k ? 'selected' : ''?>><?=$v?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sm:col-span-2">
                <label for="smtp_email" class="<?=$label?>">Adres nadawcy</label>
                <div><input name="smtp_email" id="smtp_email" type="email" value="<?=$e($s['smtp_email'] ?? '')?>" placeholder="homelist@twojadomena.pl" class="<?=$input?>" <?=$dis?>></div>
            </div>
            <div>
                <label for="smtp_name" class="<?=$label?>">Nazwa nadawcy</label>
                <div><input name="smtp_name" id="smtp_name" type="text" value="<?=$e($s['smtp_name'] ?? 'HomeList')?>" class="<?=$input?>" <?=$dis?>></div>
            </div>
            <div>
                <label for="smtp_username" class="<?=$label?>">Login SMTP</label>
                <div><input name="smtp_username" id="smtp_username" type="text" value="<?=$e($s['smtp_username'] ?? '')?>" placeholder="puste = adres nadawcy" class="<?=$input?>" <?=$dis?>></div>
            </div>
            <div class="sm:col-span-2">
                <label for="smtp_auth_token" class="<?=$label?>">Hasło / token aplikacji</label>
                <div><input name="smtp_auth_token" id="smtp_auth_token" type="password" autocomplete="new-password" value="" placeholder="<?=($s['smtp_auth_token'] ?? '') !== '' ? '•••••••• (zapisane – puste pole zostawia bez zmian)' : 'Wpisz hasło'?>" class="<?=$input?>" <?=$dis?>></div>
            </div>
        </div>
        <?php if ($can_edit): ?>
        <div class="mt-6 flex flex-col sm:flex-row sm:justify-end gap-2">
            <button type="button" onclick="appSettingsTestMail(this)" class="hl-btn hl-btn-ghost">Wyślij testowy mail do mnie</button>
            <button class="<?=$btn?>">Zapisz</button>
        </div>
        <?php endif; ?>
      </form>

    </div>
  </main>
</div>
</section>

<script>
function appSettingsSave(e, form) {
    e.preventDefault();
    hlJson('scripts/settings/app_save.php', new FormData(form)).then(r => {
        showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
        if (r.status === 'success') openPanelSite('settings', '');
    });
}
function appSettingsTestMail(btn) {
    btn.disabled = true;
    hlJson('scripts/settings/test_mail.php').then(r => {
        showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
        btn.disabled = false;
    });
}
</script>

<?php
$name_in_scripts = 'UserRoles';
$delete_path = 'scripts/settings/users/roles/delete.php';
$delete_v2 = '';
$path = 'components/panel/settings/user_roles/edit.php';
include "../../popup.php";

$name_in_scripts = 'UserRolesAdd';
$delete_path = '';
$delete_v2 = '';
$path = 'components/panel/settings/user_roles/add.php';
include "../../popup.php";
?>
