<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['users', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include '../../../scripts/conn_db.php';
$u_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$users = [];
$result = mysqli_query($conn, "SELECT users.id, users.name, users.description, users.mail, users.profile_picture, users.sur_name, COALESCE(user_roles.role, '—') AS role, user_status.status, colors.name AS status_color, last_log FROM `users` LEFT JOIN user_roles ON users.role_id = user_roles.id JOIN user_status ON user_status.id = users.status_id JOIN colors ON colors.id = user_status.color_id ORDER BY users.id ASC");
while ($row = mysqli_fetch_assoc($result)) $users[] = $row;
// kolory statusów konta z tabeli colors – pełne nazwy klas, żeby Tailwind CDN je wygenerował
$u_status = [
    'green' => 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-400/15',
    'red' => 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-400/15',
    'yellow' => 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-400/15',
    'amber' => 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-400/15',
    'orange' => 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-400/15',
    'sky' => 'text-sky-600 bg-sky-100 dark:text-sky-400 dark:bg-sky-400/15',
    'blue' => 'text-sky-600 bg-sky-100 dark:text-sky-400 dark:bg-sky-400/15',
];
?>
<section data-aos="fade-up" data-aos-delay="100" class="max-w-6xl mx-auto mb-28 md:mb-10">
    <div class="flex items-center justify-between gap-3 pt-2">
        <div class="min-w-0">
            <h1 class="hl-title">Konta użytkowników</h1>
            <p class="hl-sub"><?=count($users)?> <?=count($users) == 1 ? 'konto' : (count($users) % 10 >= 2 && count($users) % 10 <= 4 && (count($users) % 100 < 10 || count($users) % 100 >= 20) ? 'konta' : 'kont')?> w HomeList</p>
        </div>
        <button type="button" onclick="openPopupUsersAdd()" title="Dodaj konto" class="<?=hl_can('users', 'add') ? '' : 'hidden '?>hl-icon-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        </button>
    </div>

    <div class="hl-card mt-6 p-2">
        <div class="hidden lg:grid grid-cols-12 gap-4 px-4 pt-3 pb-2">
            <span class="hl-eyebrow col-span-4">Użytkownik</span>
            <span class="hl-eyebrow col-span-3">Opis</span>
            <span class="hl-eyebrow col-span-2">Rola</span>
            <span class="hl-eyebrow col-span-1">Konto</span>
            <span class="hl-eyebrow col-span-2 text-right">Ostatnio</span>
        </div>
        <div id="table_body" class="hl-divide">
            <?php if (!$users): ?>
            <p class="py-10 text-center text-sm text-gray-400">Brak kont.</p>
            <?php endif; ?>
            <?php foreach ($users as $row): ?>
            <div onclick="openPopupUsers(<?=(int)$row['id']?>)" class="hl-row lg:grid lg:grid-cols-12 lg:gap-4">
                <div class="flex items-center gap-3 min-w-0 flex-1 lg:col-span-4">
                    <img class="size-10 flex-none rounded-full object-cover ring-1 ring-black/10 dark:ring-white/10" src="img/users_images/<?=$u_e($row['profile_picture'] ?: 'default.png')?>" alt="" onerror="this.onerror=null;this.src='img/users_images/default.png'">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"><?=$u_e(trim($row['name'].' '.$row['sur_name']))?></p>
                        <p class="text-xs text-gray-400 truncate"><?=$u_e($row['mail'])?></p>
                    </div>
                </div>
                <p class="hidden lg:block lg:col-span-3 text-sm text-gray-500 dark:text-gray-400 truncate"><?=$u_e($row['description'] ?: '—')?></p>
                <span class="hl-badge flex-none lg:col-span-2 lg:justify-self-start"><?=$u_e($row['role'])?></span>
                <span class="hidden sm:inline-flex hl-badge flex-none capitalize lg:col-span-1 lg:justify-self-start <?=$u_status[$row['status_color']] ?? ''?>"><?=$u_e($row['status'])?></span>
                <span class="hidden md:block lg:col-span-2 text-xs text-gray-400 text-right whitespace-nowrap"><?=$row['last_log'] ? $u_e(date('d.m.Y H:i', strtotime($row['last_log']))) : 'nigdy'?></span>
                <span class="lg:hidden text-gray-300 dark:text-gray-500"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php 
$name_in_scripts = 'Users';
$delete_path = 'scripts/users/delete.php';
$delete_v2 = '';
$path = 'components/panel/users/users_edit.php';
$docs_text = 'Edycja użytkownika';
$docs_path = 'users_docs_add';
include "../../popup.php";
?>
<?php 
$name_in_scripts = 'UsersAdd';
$delete_path = '';
$delete_v2 = '';
$path = 'components/panel/users/users_add.php';
$docs_text = '';
$docs_path = '';
include "../../popup.php";
?>