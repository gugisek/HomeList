<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'edit']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include '../../../scripts/conn_db.php';
include_once '../../../scripts/planner/planner_lib.php';
include_once '../../../scripts/access.php';
$row = hl_element_access($conn, (int)$_SESSION['login_id'], (int)($_GET['id'] ?? 0));
if (!$row) {
    echo "Nie znaleziono elementu.";
    exit;
}
?>
<div id="edit_element_loading"></div>
<section class="font-[poppins] p-1 text-gray-800 dark:text-gray-200">
    <input type="hidden" id="edit_element_id" value="<?=$row['id']?>">
    <div class="-mt-10">
        <h1 class="hl-popup-title"><?=$row['list_type'] === 'routine' ? 'Edytuj rutynę' : 'Edytuj zadanie'?></h1>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="edit_element_name" class="hl-label">Nazwa zadania</label>
            <div class="mt-2">
                <input name="edit_element_name" id="edit_element_name" type="text" value="<?=hl_e($row['title'])?>" placeholder="Wymagane" class="hl-input w-full" required>
            </div>
        </div>
    </div>

    <?php if ($row['list_type'] === 'routine'): ?>
    <?php $rf_prefix = 'edit'; $rf = $row; include 'routine_fields.php'; ?>
    <?php else: ?>
    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="edit_element_deadline" class="hl-label">Deadline</label>
            <div class="mt-2">
                <input name="edit_element_deadline" id="edit_element_deadline" type="datetime-local" value="<?=$row['deadline_date']?>" placeholder="" class="hl-input w-full cursor-pointer">
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
            <label for="edit_element_description" class="hl-label">Opis</label>
            <div class="mt-2">
                <textarea name="edit_element_description" rows="4" id="edit_element_description" type="text" placeholder="Nic tu nie ma..." class="hl-input w-full resize-none"><?=hl_e($row['description'])?></textarea>
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
            <label for="edit_element_link_url" class="hl-label">Link / Załącznik</label>
            <div class="mt-2 relative">
                <input name="edit_element_link_url" id="edit_element_link_url" type="url" value="<?=htmlspecialchars($row['link_url'] ?? '')?>" placeholder="https://..." class="hl-input w-full pr-10">
                <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex flex-row-reverse items-center gap-2">
    <button onclick="updateElement()" class="hl-btn hl-btn-primary flex-1">Zapisz</button>
    <button onclick="popupEditElementCloseConfirm()" type="button" class="hl-btn hl-btn-ghost flex-1">Anuluj</button>
    <button onclick="popupEditElementDelete()" type="button" title="Usuń" class="hl-btn hl-btn-danger hl-btn-square">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 ">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
        </button>
    </div>
</section>
