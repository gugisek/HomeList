<div class="-mt-[43px]">
<div id="add_list_loading"></div>
    <h1 class="font-[poppins] font-semibold text-lg pr-8 dark:text-gray-200">Utwórz listę</h1>
    <p class="pt-4 text-wrap"></p>
    <div class="flex flex-col">
        <div class="flex md:flex-row flex-col gap-2">
            <div class="w-full">
                <label for="add_list_name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Nazwa listy</label>
                <div class="mt-2">
                    <input name="add_list_name" id="add_list_name" type="text" value="" placeholder="Wymagane" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 dark:bg-[#3d3d3d] dark:text-gray-200 dark:border-[#3d3d3d] focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white" required>
                </div>
            </div>
        </div>
        <div class="flex flex-row-reverse gap-2">
            <button onclick="addList()" class="mt-4 active:scale-95 duration-150 flex w-full justify-center shadow-xl cursor-pointer rounded-2xl bg-green-400 dark:bg-green-400/20 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-white ring-1 font-medium ring-green-400 duration-150 px-4 py-2 text-sm text-white items-center gap-1 shadow-sm hover:shadow-xl hover:bg-white hover:text-green-400 sm:ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                </svg>
            Zapisz
            </button>
            <button onclick="popupAddListsCloseConfirm()"  class="mt-4 active:scale-95 duration-150 flex w-full justify-center cursor-pointer rounded-2xl font-medium ring-rose-400 ring-1 duration-150 px-4 py-2 text-sm text-rose-400 items-center gap-1 shadow-sm hover:shadow-xl hover:bg-rose-400 hover:text-white sm:ml-2 ">

            Anuluj
            </button>
        </div>
        </div>
    </div>
</div>


