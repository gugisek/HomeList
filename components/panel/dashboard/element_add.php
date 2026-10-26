<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
$list_name = $_GET['id'];

if (isset($_GET['name'])) {
    $element_name = $_GET['name'];
} else {
    $element_name = '';
}

if (isset($_GET['description'])) {
    $element_description = $_GET['description'];
} else {
    $element_description = '';
}

if (isset($_GET['deadline'])) {
    $element_deadline = $_GET['deadline'];
} else {
    $element_deadline = '';
}

include '../../../scripts/conn_db.php';
include '../../../scripts/planner/planner_lib.php';
$list_type_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT list_type FROM lists WHERE id = '".(int)$list_name."'"));
$is_routine = $list_type_row && $list_type_row['list_type'] === 'routine';

?>
<div id="add_element_loading"></div>
<section class="font-[poppins] p-1">
    <input type="hidden" id="add_element_list_name" value="<?=(int)$list_name?>">
    <div class="-mt-[45px] flex items-center justify-between">
        <h1 class="hl-popup-title"><?=$is_routine ? 'Dodaj rutynę' : 'Dodaj zadanie'?></h1>
        <a title="Zapisz" class="sm:mr-9 z-10 hl-icon-btn hl-icon-btn-sm !text-green-500 hover:!text-white" onclick="addElement()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>

        </a>
    </div>

    <div class="mt-6 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="add_element_name" class="hl-label">
                Nazwa zadania <span class="text-red-400">*</span>
            </label>
            <input id="add_element_name" name="add_element_name" type="text" placeholder="Podaj nazwę zadania" value="<?=htmlspecialchars($element_name)?>" class="hl-input w-full" required="">
        </div>
    </div>

    <?php if ($is_routine): ?>
    <?php $rf_prefix = 'add'; $rf = []; include 'routine_fields.php'; ?>
    <?php else: ?>
    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
        <label for="add_element_deadline" class="hl-label">
            Deadline
        </label>
        <input id="add_element_deadline" name="add_element_deadline" type="datetime-local" value="<?=htmlspecialchars($element_deadline)?>" class="hl-input w-full cursor-pointer">
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
        <label for="add_element_description" class="hl-label">
            Opis
        </label>
        <textarea id="add_element_description" name="add_element_description" placeholder="Opcjonalny opis zadania..." rows="4" class="hl-input w-full resize-none"><?=htmlspecialchars($element_description)?></textarea>
        </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
        <label for="add_element_link_url" class="hl-label">
            Link / Załącznik
        </label>
        <div class="relative">
            <input id="add_element_link_url" name="add_element_link_url" type="url" placeholder="https://..." class="hl-input w-full pr-10">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
            </div>
        </div>
        </div>
    </div>

    <div class="mt-6 flex flex-row-reverse items-center gap-2">
        <button onclick="<?php if($element_name == '') echo 'addElement()'; else echo 'copyElement()'; ?>" class="hl-btn hl-btn-primary flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
            </svg>
        Zapisz
        </button>
        <button onclick="<?php if($element_name == '') echo 'popupElementAddCloseConfirm()'; else echo 'popupCopyElementCloseConfirm()'; ?>" class="hl-btn hl-btn-ghost flex-1">

        Anuluj
        </button>
    </div>
</section>
