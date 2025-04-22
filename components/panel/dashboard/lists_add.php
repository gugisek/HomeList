<div class="-mt-[43px]">
<div id="add_list_loading"></div>
    <h1 class="font-[poppins] font-semibold text-lg pr-8">Utwórz listę</h1>
    <p class="pt-4 text-wrap"></p>
    <div class="flex flex-col">
        <div class="flex md:flex-row flex-col gap-2">
            <div class="w-full">
                <label for="add_list_name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Nazwa listy</label>
                <div class="mt-2">
                    <input name="add_list_name" id="add_list_name" type="text" value="" placeholder="Wymagane" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white" required>
                </div>
            </div>
        </div>
        <div class="mt-6 sm:mt-6 mb-2 sm:flex sm:flex-row-reverse">
            <button onclick="addList()" class="active:scale-95 duration-150 inline-flex w-full justify-center active:scale-95 rounded-full bg-green-400 duration-150 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-green-500 sm:ml-2 sm:w-auto">Dodaj</button>
            <button onclick="popupAddListsCloseConfirm()" type="button" class="active:scale-95 duration-150 mt-3 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-inset ring-1 ring-[#3d3d3d] hover:ring-gray-500 hover:bg-gray-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto">Nie zapisuj</button>
        </div>
    </div>
</div>


