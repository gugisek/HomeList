<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = $SECURITY_REQUIRED_MODULE ?? ['settings', 'read']; include __DIR__.'/../../../../scripts/security.php';

// Wspólny formularz roli (add.php / edit.php). Przed include: $role_row (null = nowa rola).
include "../../../../scripts/conn_db.php";
require_once __DIR__ . '/../../../../scripts/users/user_admin_lib.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$is_new = $role_row === null;
$can_save = $is_new ? hl_can('settings', 'add') : (hl_can('settings', 'edit') && hl_role_within_mine($role_row));
$can_delete = !$is_new && hl_can('settings', 'full') && hl_role_within_mine($role_row);
$input = 'hl-input';
$label = 'hl-label';
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
        <h1 class="hl-popup-title"><?=$is_new ? 'Dodaj rolę użytkownika' : 'Edytuj rolę użytkownika'?></h1>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <?php if ($is_new): ?>Nowa rola z poziomami dostępu do modułów.
            <?php elseif (!$can_save): ?>Podgląd – ta rola ma wyższe uprawnienia niż Twoje albo nie masz prawa edycji.
            <?php else: ?>Edytujesz rolę: <?=$e($role_row['role'])?> · kont z tą rolą: <?=$users_count?>
            <?php endif; ?>
        </p>
    </div>

    <div class="mt-4">
        <label for="name" class="<?=$label?>">Nazwa</label>
        <div><input name="name" id="name" type="text" maxlength="60" value="<?=$e($role_row['role'] ?? '')?>" placeholder="Wpisz nazwę" class="<?=$input?>" required <?=$dis?>></div>
    </div>
    <div class="mt-4">
        <label for="description" class="<?=$label?>">Opis</label>
        <div><textarea name="description" id="description" placeholder="Nic tu nie ma..." class="<?=str_replace('rounded-xl', 'rounded-2xl', $input)?>" <?=$dis?>><?=$e($role_row['description'] ?? '')?></textarea></div>
    </div>

    <p class="mt-6 hl-h2 !text-sm pl-1">Dostęp do modułów</p>
    <p class="pl-1 mt-0.5 text-xs text-gray-500 dark:text-gray-400">read – podgląd · edit – zmiany · add – dodawanie · full – także usuwanie. Możesz nadać najwyżej swój własny poziom.</p>
    <div class="mt-2 grid sm:grid-cols-2 gap-2">
        <?php foreach (hl_modules() as $module => $module_label):
            $current = (int)($role_row[$module] ?? 5);
            $mine = hl_perm_rank($_SESSION['perms'][$module] ?? 5);
        ?>
        <div>
            <label for="perm_<?=$module?>" class="<?=$label?>"><?=$e($module_label)?></label>
            <div>
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

    <div class="mt-6 flex flex-col sm:flex-row-reverse gap-2">
        <?php if ($can_save): ?>
        <button class="hl-btn hl-btn-primary sm:flex-1"><?=$is_new ? 'Dodaj' : 'Zapisz'?></button>
        <?php endif; ?>
        <button onclick="popup<?=$popup?>CloseConfirm()" type="button" class="hl-btn hl-btn-ghost sm:flex-1"><?=$can_save ? 'Nie zapisuj' : 'Zamknij'?></button>
        <?php if ($can_delete): ?>
        <button type="button" onclick="popupUserRolesDelete()" class="hl-btn hl-btn-danger sm:flex-1" <?=$users_count ? 'disabled title="Rola jest przypisana do kont"' : ''?>>Usuń</button>
        <?php endif; ?>
    </div>
</form>
