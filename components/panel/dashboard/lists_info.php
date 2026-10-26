<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include '../../../scripts/conn_db.php';
include_once '../../../scripts/planner/planner_lib.php';
include_once '../../../scripts/access.php';
$list_id = (int)($_GET['id'] ?? 0);
$access = hl_list_access($conn, (int)$_SESSION['login_id'], $list_id);
if (!$access) {
    echo '<div class="-mt-[43px] text-sm text-gray-500">Nie znaleziono listy.</div>';
    exit;
}
$row = $access['list'];
$is_owner = $access['role'] === 'owner';

$stmt = $conn->prepare("SELECT concat(name, ' ', sur_name) AS full_usr_name FROM users WHERE id = ?");
$owner_id = (int)$row['owner_id'];
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$owner_name = $stmt->get_result()->fetch_assoc()['full_usr_name'] ?? '—';

$members = [];
if ($is_owner) {
    $stmt = $conn->prepare("SELECT concat(users.name, ' ', users.sur_name) AS full_usr_name FROM list_invities JOIN users ON users.id = list_invities.user_id WHERE list_invities.list_id = ?");
    $stmt->bind_param('i', $list_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($m = $result->fetch_assoc()) $members[] = $m['full_usr_name'];
}
?>
<div class="-mt-[43px]">
<div id="info_list_loading"></div>
    <h1 class="hl-popup-title"><?=hl_e($row['full_name'])?></h1>
    <p class="mt-1 text-sm text-gray-400">Właściciel: <span class="font-medium text-gray-600 dark:text-gray-300"><?=hl_e($owner_name)?></span></p>

    <label for="code" class="hl-label mt-5">Kod zaproszenia</label>
    <div class="flex gap-2">
        <input type="text" id="code" class="hl-input font-mono tracking-[0.3em] uppercase" value="<?=hl_e($row['list_code'])?>" readonly>
        <button type="button" onclick="copyClip('code');" class="hl-btn hl-btn-soft flex-none"><?=hl_icon('copy')?>Kopiuj</button>
    </div>
    <p class="hl-hint">Domownik wpisuje go w „Wprowadź kod listy”, żeby dołączyć.</p>

    <?php if ($is_owner): ?>
    <input type="hidden" id="edit_list_id" name="edit_list_id" value="<?=(int)$row['id']?>">
    <label for="edit_list_name" class="hl-label mt-5">Nazwa listy</label>
    <input name="edit_list_name" id="edit_list_name" type="text" value="<?=hl_e($row['full_name'])?>" placeholder="Wymagane" class="hl-input" required>

    <?php if ($members): ?>
    <span class="hl-label mt-5">Osoby z dostępem</span>
    <div class="flex flex-wrap gap-1.5">
        <?php foreach ($members as $member): ?>
        <span class="hl-badge !py-1.5"><?=hl_icon('user', 'size-4')?><?=hl_e($member)?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="mt-6 flex items-center gap-2">
        <button onclick="popupInfoListsDelete()" type="button" title="<?=$is_owner ? 'Usuń listę' : 'Odłącz listę od konta'?>" class="hl-btn hl-btn-danger <?=$is_owner ? 'hl-btn-square' : 'flex-1'?>">
            <?=hl_icon('trash')?><?=$is_owner ? '' : 'Odłącz od mojego konta'?>
        </button>
        <?php if ($is_owner): ?>
        <button onclick="popupInfoListsOpenClose()" type="button" class="hl-btn hl-btn-ghost flex-1">Anuluj</button>
        <button onclick="updateListName()" class="hl-btn hl-btn-primary flex-1">Zapisz</button>
        <?php endif; ?>
    </div>
</div>
