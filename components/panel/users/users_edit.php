<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['users', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include "../../../scripts/conn_db.php";
require_once __DIR__.'/../../../scripts/users/user_admin_lib.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT users.*, (SELECT is_enabled FROM user_two_factor_settings WHERE user_id = users.id) AS tfa FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
if (!$u) {
    echo '<p class="text-sm text-gray-500">Nie znaleziono użytkownika.</p>';
    exit;
}
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$img = $u['profile_picture'] ?: 'default.png';
$is_self = $id === (int)$_SESSION['login_id'];
$current_role = hl_role_row($conn, (int)$u['role_id']);
$can_edit = hl_can('users', 'edit') && ($is_self || !$current_role || hl_role_within_mine($current_role));
$input = 'border rounded-xl w-full py-1.5 px-4 text-sm border-gray-400 dark:bg-[#2c2c2c] dark:text-gray-200 dark:border-[#2c2c2c] focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white disabled:opacity-60';
$label = 'ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200';
$dis = $can_edit ? '' : 'disabled';
?>
<form action="scripts/users/edit.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?=$id?>">
    <div class="-mt-4">
        <h1 class="text-md font-bold dark:text-gray-200">Edytuj użytkownika</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <?=$can_edit ? 'Edytujesz profil użytkownika: ' . $e($u['name'] . ' ' . $u['sur_name']) . '.' : 'Podgląd – nie masz uprawnień do edycji tego konta.'?>
        </p>
    </div>
    <div class="mt-4 flex flex-col items-center justify-center">
        <label for="photo_img" class="h-24 w-24 flex-shrink-0 hover:scale-105 hover:shadow-xl duration-150 active:scale-95 cursor-pointer rounded-full">
            <img id="popup_img_inpt_photo_img" class="h-24 w-24 aspect-square rounded-full object-cover border border-black/10" src="img/users_images/<?=$e($img)?>" alt="">
        </label>
        <input onchange="imgPrevProduct('photo_img')" type="file" accept="image/*" name="photo_img" id="photo_img" class="hidden" <?=$dis?>>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="name" class="<?=$label?>">Imię</label>
            <div class="mt-2"><input name="name" id="name" type="text" value="<?=$e($u['name'])?>" placeholder="Wpisz imię" class="<?=$input?>" required <?=$dis?>></div>
        </div>
        <div class="w-full">
            <label for="sur_name" class="<?=$label?>">Nazwisko</label>
            <div class="mt-2"><input name="sur_name" id="sur_name" type="text" value="<?=$e($u['sur_name'])?>" placeholder="Wpisz nazwisko" class="<?=$input?>" <?=$dis?>></div>
        </div>
    </div>

    <div class="mt-4">
        <label for="email" class="<?=$label?>">Email (login)</label>
        <div class="mt-2"><input name="email" id="email" type="email" value="<?=$e($u['mail'])?>" placeholder="Wpisz email" class="<?=$input?>" required <?=$dis?>></div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="md:w-1/2 w-full">
            <label for="role" class="<?=$label?>">Rola</label>
            <div class="mt-2">
                <select name="role" id="role" class="cursor-pointer <?=$input?>" required <?=($dis || $is_self) ? 'disabled' : ''?>>
                    <?php
                    $roles = hl_assignable_roles($conn);
                    $listed = array_column($roles, 'id');
                    if ($current_role && !in_array($current_role['id'], $listed)) array_unshift($roles, $current_role);
                    foreach ($roles as $r) {
                        echo '<option value="' . (int)$r['id'] . '"' . ((int)$r['id'] === (int)$u['role_id'] ? ' selected' : '') . '>' . $e($r['role']) . '</option>';
                    }
                    ?>
                </select>
                <?php if ($is_self): ?><input type="hidden" name="role" value="<?=(int)$u['role_id']?>"><?php endif; ?>
            </div>
        </div>
        <div class="md:w-1/2 w-full">
            <label for="status_id" class="<?=$label?>">Status konta</label>
            <div class="mt-2">
                <select name="status_id" id="status_id" class="cursor-pointer <?=$input?>" required <?=($dis || $is_self) ? 'disabled' : ''?>>
                    <?php
                    $result = $conn->query('SELECT id, status FROM user_status ORDER BY id');
                    while ($s = $result->fetch_assoc()) {
                        echo '<option value="' . (int)$s['id'] . '"' . ((int)$s['id'] === (int)$u['status_id'] ? ' selected' : '') . '>' . $e($s['status']) . '</option>';
                    }
                    ?>
                </select>
                <?php if ($is_self): ?><input type="hidden" name="status_id" value="<?=(int)$u['status_id']?>"><?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($is_self): ?><p class="mt-1 pl-2 text-xs text-gray-500 dark:text-gray-400">Własnej roli i statusu nie możesz zmienić.</p><?php endif; ?>

    <div class="mt-4">
        <label for="description" class="<?=$label?>">Opis</label>
        <div class="mt-2"><textarea name="description" id="description" placeholder="Nic tu nie ma..." class="<?=str_replace('rounded-xl', 'rounded-2xl', $input)?>" <?=$dis?>><?=$e($u['description'])?></textarea></div>
    </div>

    <?php if ($can_edit): ?>
    <div class="mt-4 flex flex-row gap-2">
        <div class="w-1/2">
            <label for="pswd" class="<?=$label?>">Nowe hasło</label>
            <div class="mt-2"><input name="pswd" id="pswd" type="password" autocomplete="new-password" placeholder="Bez zmian" class="<?=$input?>"></div>
        </div>
        <div class="w-1/2">
            <label for="repeat_pswd" class="<?=$label?>">Powtórz hasło</label>
            <div class="mt-2"><input name="repeat_pswd" id="repeat_pswd" type="password" autocomplete="new-password" placeholder="Bez zmian" class="<?=$input?>"></div>
        </div>
    </div>

    <div class="mt-4 flex flex-col gap-2">
        <?php if ((int)$u['tfa'] === 1): ?>
        <label class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50 dark:bg-[#2c2c2c] p-3 cursor-pointer">
            <span class="text-sm text-gray-800 dark:text-gray-200">Wyłącz 2FA <span class="block text-xs text-gray-500 dark:text-gray-400">Gdy użytkownik stracił telefon i kody zapasowe</span></span>
            <input type="checkbox" name="disable_2fa" value="1" class="size-5 accent-green-400">
        </label>
        <?php endif; ?>
        <label class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50 dark:bg-[#2c2c2c] p-3 cursor-pointer">
            <span class="text-sm text-gray-800 dark:text-gray-200">Wyloguj ze wszystkich urządzeń <span class="block text-xs text-gray-500 dark:text-gray-400">Kończy sesje i usuwa zapamiętane logowania</span></span>
            <input type="checkbox" name="revoke_sessions" value="1" class="size-5 accent-green-400">
        </label>
    </div>
    <?php endif; ?>

    <div class="mt-6 sm:mt-6 mb-2 sm:flex sm:flex-row-reverse">
        <?php if ($can_edit): ?>
        <button class="active:scale-95 duration-150 inline-flex w-full justify-center rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-green-500 sm:ml-2 sm:w-auto">Zapisz</button>
        <?php endif; ?>
        <button onclick="popupUsersCloseConfirm()" type="button" class="active:scale-95 mt-3 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-200 shadow-sm ring-inset ring-1 ring-[#3d3d3d] dark:ring-gray-500 hover:bg-gray-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto"><?=$can_edit ? 'Nie zapisuj' : 'Zamknij'?></button>
        <?php if (hl_can('users', 'full') && !$is_self && $can_edit): ?>
        <button type="button" onclick="popupUsersDelete()" class="active:scale-95 mt-3 sm:mr-2 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-rose-500 shadow-sm ring-inset ring-1 ring-rose-400 hover:bg-rose-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto">Usuń konto</button>
        <?php endif; ?>
    </div>
</form>
