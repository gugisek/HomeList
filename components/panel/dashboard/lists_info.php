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
    <h1 class="font-[poppins] dark:text-gray-200 font-semibold text-lg pr-8"><?=hl_e($row['full_name'])?></h1>
    <p class="pt-4 text-wrap"></p>
    <div class="flex flex-col">
        <div class="flex md:flex-row flex-col gap-2">
            <div class="w-full">
                <label for="" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Kod zaproszenia listy</label>
                <div class="mt-2 flex gap-2">

                    <input type="text" id="code" class="w-full border border-black/30 rounded-xl px-4 py-2" value="<?=hl_e($row['list_code'])?>" disabled></input>
                    <a onclick="copyClip('code');" class="border border-[#3d3d3d] text-white bg-[#3d3d3d] rounded-xl px-4 py-2 hover:scale-105 active:scale-95 duration-150 hover:text-[#3d3d3d] hover:bg-white cursor-pointer hover:shadow-xl hover:font-medium">Skopiuj</a>
                </div>
                <label for="" class="mt-2 ml-px block pl-2 text-sm leading-6 text-gray-900 dark:text-gray-200">Lista stworzona przez: <span class="font-medium"><?=hl_e($owner_name)?></span></label>
            </div>
        </div>
        <?php if ($is_owner): ?>
        <input type="hidden" id="edit_list_id" name="edit_list_id" value="<?=(int)$row['id']?>" hidden></input>
        <div class="mt-4 flex md:flex-row flex-col gap-2">
            <div class="w-full">
                <label for="edit_list_name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Nazwa listy</label>
                <div class="mt-2">
                    <input name="edit_list_name" id="edit_list_name" type="text" value="<?=hl_e($row['full_name'])?>" placeholder="Wymagane" class="border dark:bg-[#3d3d3d] dark:text-gray-200 dark:border-[#3d3d3d] rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white" required>
                </div>
            </div>
        </div>
        <?php if ($members): ?>
        <div class="mt-4 flex md:flex-row flex-col gap-2">
            <div class="w-full">
                <label class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Osoby mające dostęp</label>
            </div>
        </div>
        <?php foreach ($members as $member): ?>
        <div class="flex items-center gap-2 dark:text-gray-200"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg><span class="text-sm font-medium"><?=hl_e($member)?></span></div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>
        <div class="mt-6 sm:mt-6 mb-2 flex flex-row items-center gap-2">

            <button onclick="popupInfoListsDelete()" type="button" class="active:scale-95 duration-150 inline-flex justify-center rounded-2xl px-4 py-2 text-sm font-medium text-rose-400 shadow-sm ring-inset ring-1 ring-rose-400 hover:ring-rose-400 hover:bg-rose-400 hover:text-white hover:shadow-xl duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 ">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg><?=$is_owner ? '' : '*'?>
            </button>

            <?php if (!$is_owner): ?><span class="text-xs text-gray-600 dark:text-gray-400">* usuwasz powiązanie listy z Twoim kontem</span><?php endif; ?>

            <?php if ($is_owner): ?>
            <button onclick="popupInfoListsOpenClose()" type="button" class="active:scale-95 duration-150 inline-flex w-full justify-center rounded-2xl px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-inset ring-1 ring-[#3d3d3d] hover:ring-gray-500 hover:bg-gray-500 hover:text-white dark:text-gray-200 dark:ring-gray-200 hover:shadow-xl duration-150">Nie zapisuj</button>
            <button onclick="updateListName()" class="active:scale-95 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl bg-[#3d3d3d] duration-150 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-green-500">Zapisz</button>
            <?php endif; ?>
        </div>
    </div>
</div>
