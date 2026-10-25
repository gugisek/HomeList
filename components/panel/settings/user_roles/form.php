<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = $SECURITY_REQUIRED_MODULE ?? ['settings', 'read']; include __DIR__.'/../../../../scripts/security.php';

// Wspólny formularz roli (add.php / edit.php). Przed include: $role_row (null = nowa rola).
include "../../../../scripts/conn_db.php";
require_once __DIR__ . '/../../../../scripts/users/user_admin_lib.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$is_new = $role_row === null;
$can_save = $is_new ? hl_can('settings', 'add') : (hl_can('settings', 'edit') && hl_role_within_mine($role_row));
$can_delete = !$is_new && hl_can('settings', 'full') && hl_role_within_mine($role_row);
$input = 'border rounded-xl w-full py-1.5 px-4 text-sm border-gray-400 dark:bg-[#2c2c2c] dark:text-gray-200 dark:border-[#2c2c2c] focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white disabled:opacity-60';
$label = 'ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200';
$dis = $can_save ? '' : 'disabled';

$levels = [];
$result = $conn->query('SELECT id, name, description FROM user_role_privileges ORDER BY FIELD(id, 5, 1, 2, 3, 4)');
while ($row = $result->fetch_assoc()) $levels[] = $row;

$users_count = 0;
if (!$is_new) {
    $stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM users WHERE role_id = ?');
    $role_id = (int)$role_row['id'];
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $users_count = (int)$stmt->get_result()->fetch_assoc()['cnt'];
}
$popup = $is_new ? 'UserRolesAdd' : 'UserRoles';
?>
<form action="scripts/settings/users/roles/<?=$is_new ? 'add' : 'edit'?>.php" method="POST">
    <?php if (!$is_new): ?><input type="hidden" name="id" value="<?=(int)$role_row['id']?>"><?php endif; ?>
    <div class="-mt-4">
        <h1 class="text-md font-bold dark:text-gray-200"><?=$is_new ? 'Dodaj rolę użytkownika' : 'Edytuj rolę użytkownika'?></h1>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <?php if ($is_new): ?>Nowa rola z poziomami dostępu do modułów.
            <?php elseif (!$can_save): ?>Podgląd – ta rola ma wyższe uprawnienia niż Twoje albo nie masz prawa edycji.
            <?php else: ?>Edytujesz rolę: <?=$e($role_row['role'])?> · kont z tą rolą: <?=$users_count?>
            <?php endif; ?>
        </p>
    </div>

    <div class="mt-4">
        <label for="name" class="<?=$label?>">Nazwa</label>
        <div class="mt-2"><input name="name" id="name" type="text" maxlength="60" value="<?=$e($role_row['role'] ?? '')?>" placeholder="Wpisz nazwę" class="<?=$input?>" required <?=$dis?>></div>
    </div>
    <div class="mt-4">
        <label for="description" class="<?=$label?>">Opis</label>
        <div class="mt-2"><textarea name="description" id="description" placeholder="Nic tu nie ma..." class="<?=str_replace('rounded-xl', 'rounded-2xl', $input)?>" <?=$dis?>><?=$e($role_row['description'] ?? '')?></textarea></div>
    </div>

    <p class="mt-5 pl-2 text-sm font-medium text-gray-900 dark:text-gray-200">Dostęp do modułów</p>
    <p class="pl-2 text-xs text-gray-500 dark:text-gray-400">read – podgląd · edit – zmiany · add – dodawanie · full – także usuwanie. Możesz nadać najwyżej swój własny poziom.</p>
    <div class="mt-2 grid sm:grid-cols-2 gap-2">
        <?php foreach (hl_modules() as $module => $module_label):
            $current = (int)($role_row[$module] ?? 5);
            $mine = hl_perm_rank($_SESSION['perms'][$module] ?? 5);
        ?>
        <div>
            <label for="perm_<?=$module?>" class="<?=$label?>"><?=$e($module_label)?></label>
            <div class="mt-1">
                <select name="<?=$module?>" id="perm_<?=$module?>" class="cursor-pointer <?=$input?>" required <?=$dis?>>
                    <?php foreach ($levels as $lvl):
                        $too_high = hl_perm_rank((int)$lvl['id']) > $mine;
                    ?>
                    <option value="<?=(int)$lvl['id']?>" <?=$current === (int)$lvl['id'] ? 'selected' : ''?> <?=$too_high && $current !== (int)$lvl['id'] ? 'disabled' : ''?>><?=$e($lvl['name'] . ' – ' . $lvl['description'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-6 sm:mt-6 mb-2 sm:flex sm:flex-row-reverse">
        <?php if ($can_save): ?>
        <button class="active:scale-95 duration-150 inline-flex w-full justify-center rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-green-500 sm:ml-2 sm:w-auto"><?=$is_new ? 'Dodaj' : 'Zapisz'?></button>
        <?php endif; ?>
        <button onclick="popup<?=$popup?>CloseConfirm()" type="button" class="active:scale-95 mt-3 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-200 shadow-sm ring-inset ring-1 ring-[#3d3d3d] dark:ring-gray-500 hover:bg-gray-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto"><?=$can_save ? 'Nie zapisuj' : 'Zamknij'?></button>
        <?php if ($can_delete): ?>
        <button type="button" onclick="popupUserRolesDelete()" class="active:scale-95 mt-3 sm:mr-2 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-rose-500 shadow-sm ring-inset ring-1 ring-rose-400 hover:bg-rose-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto" <?=$users_count ? 'disabled title="Rola jest przypisana do kont"' : ''?>>Usuń</button>
        <?php endif; ?>
    </div>
</form>
