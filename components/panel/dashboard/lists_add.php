<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<div class="-mt-[43px]">
<div id="add_list_loading"></div>
    <h1 class="hl-popup-title">Utwórz listę</h1>
    <p class="mt-1 mb-4 text-sm text-gray-400">Nazwij ją i wybierz rodzaj – zwykłe zadania albo rutyny.</p>
    <div class="flex flex-col">
        <div class="flex md:flex-row flex-col gap-2">
            <div class="w-full">
                <label for="add_list_name" class="hl-label">Nazwa listy</label>
                <div class="mt-2">
                    <input name="add_list_name" id="add_list_name" type="text" value="" placeholder="Wymagane" class="hl-input w-full" required>
                </div>
            </div>
        </div>
        <input type="hidden" id="add_list_type" value="normal">
        <label class="hl-label mt-4">Rodzaj listy</label>
        <div class="mt-2 grid grid-cols-2 gap-2">
            <button type="button" onclick="addListType(this, 'normal')" class="list-type-card hl-chip bg-[#1c1c1e] text-white dark:bg-white dark:text-black shadow-lg text-left rounded-2xl p-3 active:scale-95 duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-green-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="mt-2 block text-sm font-semibold">Zadania</span>
                <span class="block text-xs opacity-60">Zwykła lista rzeczy do zrobienia</span>
            </button>
            <button type="button" onclick="addListType(this, 'routine')" class="list-type-card hl-chip bg-gray-100/80 dark:bg-[#3d3d3d] text-gray-700 dark:text-gray-300 text-left rounded-2xl p-3 active:scale-95 duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-orange-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span class="mt-2 block text-sm font-semibold">Rutyny</span>
                <span class="block text-xs opacity-60">Powtarzalne czynności, same trafiają do planu dnia</span>
            </button>
        </div>
        <script>
        function addListType(el, type) {
            hlChipSelect(el);
            document.getElementById('add_list_type').value = type;
        }
        </script>
        <div class="mt-6 flex flex-row-reverse items-center gap-2">
            <button onclick="addList()" class="hl-btn hl-btn-primary flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                </svg>
            Zapisz
            </button>
            <button onclick="popupAddListsCloseConfirm()" class="hl-btn hl-btn-ghost flex-1">

            Anuluj
            </button>
        </div>
        </div>
    </div>
</div>


