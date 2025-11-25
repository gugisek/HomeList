<?php
$list_name = $_GET['id'];
?>
<div id="add_element_loading"></div>
<section class="font-[poppins] p-1">
    <input type="hidden" id="add_element_list_name" value="<?=$list_name?>">
    <div class="-mt-[45px] flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Dodaj zadanie</h1>
        <a class="flex items-center z-10 gap-2 hover:bg-green-400 dark:bg-green-400/20 cursor-pointer rounded-xl mr-9 p-1 px-3 ring-1 ring-green-400 text-green-400 hover:text-white duration-150 text-xs" onclick="addElement()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>

        </a>
    </div>

    <div class="mt-6 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="add_element_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Nazwa zadania <span class="text-red-400">*</span>
            </label>
            <input id="add_element_name" name="add_element_name" type="text" placeholder="Podaj nazwę zadania" class="w-full rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm text-gray-800 dark:text-gray-300 font-medium placeholder-gray-400" required="">
        </div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
        <label for="add_element_deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Deadline
        </label>
        <input id="add_element_deadline" name="add_element_deadline" type="datetime-local" class="w-full cursor-pointer rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] dark:focus:bg-[#3d3d3d] focus:bg-white focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-300">
        </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
        <label for="add_element_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Opis
        </label>
        <textarea id="add_element_description" name="add_element_description" placeholder="Opcjonalny opis zadania..." rows="4" class="w-full rounded-2xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-300 resize-none"></textarea>
        </div>
    </div>

    <div class="flex flex-row-reverse gap-2">
        <button onclick="addElement()" class="mt-4 active:scale-95 duration-150 flex w-full justify-center shadow-xl cursor-pointer rounded-2xl bg-green-400 dark:bg-green-400/20 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-white ring-1 font-medium ring-green-400 duration-150 px-4 py-2 text-sm text-white items-center gap-1 shadow-sm hover:shadow-xl hover:bg-white hover:text-green-400 sm:ml-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
            </svg>
        Zapisz
        </button>
        <button onclick="popupElementAddCloseConfirm()"  class="mt-4 active:scale-95 duration-150 flex w-full justify-center cursor-pointer rounded-2xl font-medium ring-rose-400 ring-1 duration-150 px-4 py-2 text-sm text-rose-400 items-center gap-1 shadow-sm hover:shadow-xl hover:bg-rose-400 hover:text-white sm:ml-2 ">

        Anuluj
        </button>
    </div>
</section>
