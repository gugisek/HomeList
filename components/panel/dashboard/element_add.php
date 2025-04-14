<?php
$list_name = $_GET['id'];
?>
<div id="add_element_loading"></div>
<section class="font-[poppins]">
    <input type="hidden" id="add_element_list_name" value="<?=$list_name?>">
    <div class="-mt-[42px] flex items-center justify-between">
        <h1 class="text-lg font-semibold font-[poppins]">Dodaj zadanie</h1>
        <a class="flex items-center z-10 gap-2 bg-green-400 rounded-xl mr-9 p-1 px-3 text-white hover:bg-green-500 duration-150 text-xs" onclick="addElement()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            Dodaj
        </a>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="add_element_name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Nazwa zadania</label>
            <div class="mt-2">
                <input name="add_element_name" id="add_element_name" type="text" value="" placeholder="Wymagane" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white" required>
            </div>
        </div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="add_element_deadline" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Deadline</label>
            <div class="mt-2">
                <input name="add_element_deadline" id="add_element_deadline" type="datetime-local" value="" placeholder="" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white">
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
            <label for="add_element_description" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Opis</label>
            <div class="mt-2">
                <textarea name="add_element_description" id="add_element_description" type="text" placeholder="Nic tu nie ma..." class="border rounded-2xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white"></textarea>
            </div>
        </div>
    </div>

    <div class="mt-6 sm:mt-6 mb-2 sm:flex sm:flex-row-reverse">
        <button onclick="addElement()" class="active:scale-95 duration-150 inline-flex w-full justify-center active:scale-95 rounded-full bg-[#3d3d3d] duration-150 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-green-500 sm:ml-2 sm:w-auto">Dodaj</button>
        <button onclick="popupElementAddCloseConfirm()" type="button" class="active:scale-95 duration-150 mt-3 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-inset ring-1 ring-[#3d3d3d] hover:ring-gray-500 hover:bg-gray-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto">Nie zapisuj</button>
    </div>
</section>
