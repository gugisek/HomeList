<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<div class="-mt-[43px]">
<div id="code_list_loading"></div>
    <h1 class="hl-popup-title">Wprowadź kod listy</h1>
    <p class="mt-1 mb-4 text-sm text-gray-400">Dostaniesz go od właściciela listy – 6 znaków, wielkość liter bez znaczenia.</p>
    <div class="flex flex-col">
        <div class="flex md:flex-row flex-col gap-2">
            <div class="w-full">
                <div class="mt-2">
                    <input name="add_list_code" id="add_list_code" type="text" value="" maxlength="6" autocomplete="off" placeholder="ABC123" class="hl-input !text-lg text-center font-mono tracking-[0.4em] uppercase" required>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-row-reverse items-center gap-2">
            <button onclick="codeList()" class="hl-btn hl-btn-primary flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                </svg>
            Zapisz
            </button>
            <button onclick="popupCodeListsCloseConfirm()" class="hl-btn hl-btn-ghost flex-1">

            Anuluj
            </button>
    </div>
</div>


