<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['users', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include "../../../scripts/conn_db.php";
require_once __DIR__.'/../../../scripts/users/user_admin_lib.php';
require_once __DIR__.'/../../../scripts/users/avatar_lib.php';
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
$input = 'hl-input';
$label = 'hl-label';
$dis = $can_edit ? '' : 'disabled';
?>
<form action="scripts/users/edit.php" method="POST" enctype="multipart/form-data" class="sm:w-[540px] max-w-full">
    <input type="hidden" name="id" value="<?=$id?>">
    <div class="-mt-4">
        <h1 class="hl-popup-title">Edytuj użytkownika</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <?=$can_edit ? 'Edytujesz profil użytkownika: ' . $e($u['name'] . ' ' . $u['sur_name']) . '.' : 'Podgląd – nie masz uprawnień do edycji tego konta.'?>
        </p>
    </div>
    <div id="usr_tabs" data-id="<?=$id?>" class="mt-4 -mx-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <div class="inline-flex rounded-full bg-gray-100 dark:bg-white/5 p-1 text-xs sm:text-sm font-medium whitespace-nowrap">
            <?php foreach (['profile' => 'Profil', 'activity' => 'Aktywność', 'security' => 'Bezpieczeństwo', 'notifications' => 'Powiadomienia'] as $k => $t): ?>
            <button type="button" id="usr_tab_<?=$k?>" onclick="usrTab('<?=$k?>')" class="usr-tab px-3 sm:px-4 py-1.5 rounded-full active:scale-95 duration-150 <?=$k === 'profile' ? 'bg-white dark:bg-[#3d3d3d] shadow text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'?>"><?=$t?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php foreach (['activity', 'security', 'notifications'] as $k): ?>
    <div id="usr_panel_<?=$k?>" class="usr-panel hidden mt-4"></div>
    <?php endforeach; ?>

    <div id="usr_panel_profile" class="usr-panel">
    <div class="mt-4 flex flex-col items-center justify-center">
        <label for="photo_img" class="h-24 w-24 flex-shrink-0 hover:scale-105 hover:shadow-xl duration-150 active:scale-95 cursor-pointer rounded-full">
            <img id="popup_img_inpt_photo_img" class="h-24 w-24 aspect-square rounded-full object-cover border border-black/10" src="img/users_images/<?=$e($img)?>" alt="">
        </label>
        <input onchange="imgPrevProduct('photo_img')" type="file" <?=hl_avatar_input_attrs()?> name="photo_img" id="photo_img" class="hidden" <?=$dis?>>
        <?php if ($can_edit): ?><p class="mt-2 text-xs text-gray-400 dark:text-gray-500"><?=$e(hl_avatar_rules())?></p><?php endif; ?>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="name" class="<?=$label?>">Imię</label>
            <div><input name="name" id="name" type="text" value="<?=$e($u['name'])?>" placeholder="Wpisz imię" class="<?=$input?>" required <?=$dis?>></div>
        </div>
        <div class="w-full">
            <label for="sur_name" class="<?=$label?>">Nazwisko</label>
            <div><input name="sur_name" id="sur_name" type="text" value="<?=$e($u['sur_name'])?>" placeholder="Wpisz nazwisko" class="<?=$input?>" <?=$dis?>></div>
        </div>
    </div>

    <div class="mt-4">
        <label for="email" class="<?=$label?>">Email (login)</label>
        <div><input name="email" id="email" type="email" value="<?=$e($u['mail'])?>" placeholder="Wpisz email" class="<?=$input?>" required <?=$dis?>></div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="md:w-1/2 w-full">
            <label for="role" class="<?=$label?>">Rola</label>
            <div>
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
            <div>
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
        <div><textarea name="description" id="description" placeholder="Nic tu nie ma..." class="<?=str_replace('rounded-xl', 'rounded-2xl', $input)?>" <?=$dis?>><?=$e($u['description'])?></textarea></div>
    </div>

    <?php if ($can_edit): ?>
    <div class="mt-4 flex flex-row gap-2">
        <div class="w-1/2">
            <label for="pswd" class="<?=$label?>">Nowe hasło</label>
            <div><input name="pswd" id="pswd" type="password" autocomplete="new-password" placeholder="Bez zmian" class="<?=$input?>"></div>
        </div>
        <div class="w-1/2">
            <label for="repeat_pswd" class="<?=$label?>">Powtórz hasło</label>
            <div><input name="repeat_pswd" id="repeat_pswd" type="password" autocomplete="new-password" placeholder="Bez zmian" class="<?=$input?>"></div>
        </div>
    </div>

    <div class="mt-4 flex flex-col gap-2">
        <?php if ((int)$u['tfa'] === 1): ?>
        <label class="hl-toggle-row">
            <span class="text-sm text-gray-800 dark:text-gray-200">Wyłącz 2FA <span class="block text-xs text-gray-500 dark:text-gray-400">Gdy użytkownik stracił telefon i kody zapasowe</span></span>
            <input type="checkbox" name="disable_2fa" value="1" class="size-5 accent-green-400">
        </label>
        <?php endif; ?>
        <label class="hl-toggle-row">
            <span class="text-sm text-gray-800 dark:text-gray-200">Wyloguj ze wszystkich urządzeń <span class="block text-xs text-gray-500 dark:text-gray-400">Kończy sesje i usuwa zapamiętane logowania</span></span>
            <input type="checkbox" name="revoke_sessions" value="1" class="size-5 accent-green-400">
        </label>
    </div>
    <?php endif; ?>

    <div class="mt-6 flex flex-col sm:flex-row-reverse gap-2">
        <?php if ($can_edit): ?>
        <button class="hl-btn hl-btn-primary sm:flex-1">Zapisz</button>
        <?php endif; ?>
        <button onclick="popupUsersCloseConfirm()" type="button" class="hl-btn hl-btn-ghost sm:flex-1"><?=$can_edit ? 'Nie zapisuj' : 'Zamknij'?></button>
        <?php if (hl_can('users', 'full') && !$is_self && $can_edit): ?>
        <button type="button" onclick="popupUsersDelete()" class="hl-btn hl-btn-danger sm:flex-1">Usuń konto</button>
        <?php endif; ?>
    </div>
    </div>
</form>

<script>
function usrTab(tab) {
    document.querySelectorAll('.usr-tab').forEach(function (b) {
        var on = b.id === 'usr_tab_' + tab;
        b.classList.toggle('bg-white', on); b.classList.toggle('dark:bg-[#3d3d3d]', on); b.classList.toggle('shadow', on);
        b.classList.toggle('text-gray-900', on); b.classList.toggle('dark:text-gray-100', on);
        b.classList.toggle('text-gray-500', !on); b.classList.toggle('dark:text-gray-400', !on);
    });
    document.querySelectorAll('.usr-panel').forEach(function (p) { p.classList.toggle('hidden', p.id !== 'usr_panel_' + tab); });
    var panel = document.getElementById('usr_panel_' + tab);
    var scroller = panel.closest('.overflow-y-auto');
    if (scroller) scroller.scrollTop = 0;
    if (tab === 'profile' || panel.dataset.loaded) return;
    panel.dataset.loaded = '1';
    panel.innerHTML = '<div class="animate-pulse space-y-2"><div class="grid grid-cols-3 gap-2"><div class="h-16 rounded-2xl bg-gray-200 dark:bg-white/10"></div><div class="h-16 rounded-2xl bg-gray-200 dark:bg-white/10"></div><div class="h-16 rounded-2xl bg-gray-200 dark:bg-white/10"></div></div><div class="h-32 rounded-2xl bg-gray-200 dark:bg-white/10"></div><div class="h-48 rounded-2xl bg-gray-200 dark:bg-white/10"></div></div>';
    fetch('components/panel/users/users_info.php?id=' + document.getElementById('usr_tabs').dataset.id + '&tab=' + tab)
        .then(function (r) { if (!r.ok) throw r; return r.text(); })
        .then(function (html) { panel.innerHTML = new DOMParser().parseFromString(html, 'text/html').body.innerHTML; })
        .catch(function () {
            delete panel.dataset.loaded;
            panel.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">Nie udało się wczytać danych.</p>';
        });
}
</script>
