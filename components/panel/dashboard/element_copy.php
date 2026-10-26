<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
$list_name = $_GET['id'];
?>
<div id="add_element_loading"></div>
<section class="font-[poppins] p-1">
    <input type="hidden" id="add_element_list_name" value="<?=(int)$list_name?>">
    <div class="-mt-[45px] flex items-center justify-between">
        <h1 class="hl-popup-title">Dodaj zadanie</h1>
        <a class="flex items-center z-10 gap-2 hover:bg-green-400 dark:bg-green-400/20 cursor-pointer rounded-xl mr-9 p-1 px-3 ring-1 ring-green-400 text-green-400 hover:text-white duration-150 text-xs" onclick="addElement()">
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
            <input id="add_element_name" name="add_element_name" type="text" placeholder="Podaj nazwę zadania" class="hl-input w-full" required="">
        </div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
        <label for="add_element_deadline" class="hl-label">
            Deadline
        </label>
        <input id="add_element_deadline" name="add_element_deadline" type="datetime-local" class="hl-input w-full cursor-pointer">
        </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
        <label for="add_element_description" class="hl-label">
            Opis
        </label>
        <textarea id="add_element_description" name="add_element_description" placeholder="Opcjonalny opis zadania..." rows="4" class="hl-input w-full resize-none"></textarea>
        </div>
    </div>

    <div class="mt-6 flex flex-row-reverse items-center gap-2">
        <button onclick="addElement()" class="hl-btn hl-btn-primary flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
            </svg>
        Zapisz
        </button>
        <button onclick="popupElementAddCloseConfirm()" class="hl-btn hl-btn-ghost flex-1">

        Anuluj
        </button>
    </div>
</section>
