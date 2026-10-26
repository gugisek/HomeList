<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['users', 'add']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include "../../../scripts/conn_db.php";
require_once __DIR__.'/../../../scripts/users/user_admin_lib.php';
require_once __DIR__.'/../../../scripts/app_settings.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$input = 'hl-input';
$label = 'hl-label';
$default_role = (int)hl_setting($conn, 'registration_role', '3');
?>
<form action="scripts/users/add.php" method="POST">
    <div class="-mt-4">
        <h1 class="hl-popup-title">Dodaj użytkownika</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400">Konto będzie od razu gotowe do logowania podanym hasłem.</p>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="name" class="<?=$label?>">Imię</label>
            <div><input name="name" id="name" type="text" placeholder="Wpisz imię" class="<?=$input?>" required></div>
        </div>
        <div class="w-full">
            <label for="sur_name" class="<?=$label?>">Nazwisko</label>
            <div><input name="sur_name" id="sur_name" type="text" placeholder="Wpisz nazwisko" class="<?=$input?>"></div>
        </div>
    </div>

    <div class="mt-4">
        <label for="email" class="<?=$label?>">Email (login)</label>
        <div><input name="email" id="email" type="email" placeholder="Wpisz email" class="<?=$input?>" required></div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="md:w-1/2 w-full">
            <label for="role" class="<?=$label?>">Rola</label>
            <div>
                <select name="role" id="role" class="cursor-pointer <?=$input?>" required>
                    <?php foreach (hl_assignable_roles($conn) as $r): ?>
                    <option value="<?=(int)$r['id']?>" <?=(int)$r['id'] === $default_role ? 'selected' : ''?>><?=$e($r['role'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="md:w-1/2 w-full">
            <label for="status_id" class="<?=$label?>">Status konta</label>
            <div>
                <select name="status_id" id="status_id" class="cursor-pointer <?=$input?>" required>
                    <?php
                    $result = $conn->query('SELECT id, status FROM user_status ORDER BY id');
                    while ($s = $result->fetch_assoc()) {
                        echo '<option value="' . (int)$s['id'] . '"' . ((int)$s['id'] === 1 ? ' selected' : '') . '>' . $e($s['status']) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <label for="description" class="<?=$label?>">Opis</label>
        <div><textarea name="description" id="description" placeholder="Nic tu nie ma..." class="<?=str_replace('rounded-xl', 'rounded-2xl', $input)?>"></textarea></div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-1/2">
            <label for="pswd" class="<?=$label?>">Hasło</label>
            <div><input name="pswd" id="pswd" type="password" autocomplete="new-password" placeholder="Wpisz hasło" class="<?=$input?>" required></div>
        </div>
        <div class="w-1/2">
            <label for="repeat_pswd" class="<?=$label?>">Powtórz hasło</label>
            <div><input name="repeat_pswd" id="repeat_pswd" type="password" autocomplete="new-password" placeholder="Powtórz hasło" class="<?=$input?>" required></div>
        </div>
    </div>
    <p class="mt-1 pl-2 text-xs text-gray-500 dark:text-gray-400">Min. 8 znaków, wielka i mała litera oraz cyfra.</p>

    <div class="mt-6 flex flex-col sm:flex-row-reverse gap-2">
        <button class="hl-btn hl-btn-primary sm:flex-1">Dodaj</button>
        <button onclick="popupUsersAddCloseConfirm()" type="button" class="hl-btn hl-btn-ghost sm:flex-1">Nie zapisuj</button>
    </div>
</form>
