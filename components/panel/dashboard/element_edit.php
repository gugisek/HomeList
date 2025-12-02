<?php
$element_id = $_GET['id'];
$sql = "SELECT * FROM list_elements WHERE id = '$element_id'";
include '../../../scripts/conn_db.php';
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
} else {
    echo "Nie znaleziono elementu.";
    exit;
}
?>
<div id="edit_element_loading"></div>
<section class="font-[poppins] p-1 text-gray-800 dark:text-gray-200">
    <input type="hidden" id="edit_element_id" value="<?=$row['id']?>">
    <div class="-mt-10">
        <h1 class="text-lg font-semibold font-[poppins] text-gray-800 dark:text-gray-200">Edytuj zadanie</h1>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="edit_element_name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-700 dark:text-gray-300">Nazwa zadania</label>
            <div class="mt-2">
                <input name="edit_element_name" id="edit_element_name" type="text" value="<?=$row['title']?>" placeholder="Wymagane" class="w-full rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm text-gray-800 dark:text-gray-300 font-medium" required>
            </div>
        </div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="edit_element_deadline" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-700 dark:text-gray-300">Deadline</label>
            <div class="mt-2">
                <input name="edit_element_deadline" id="edit_element_deadline" type="datetime-local" value="<?=$row['deadline_date']?>" placeholder="" class="w-full cursor-pointer rounded-xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] dark:focus:bg-[#3d3d3d] focus:bg-white focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-300">
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
            <label for="edit_element_description" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-700 dark:text-gray-300">Opis</label>
            <div class="mt-2">
                <textarea name="edit_element_description" rows="4" id="edit_element_description" type="text" placeholder="Nic tu nie ma..." class="w-full rounded-2xl border-none bg-gray-100/80 dark:bg-[#3d3d3d] focus:bg-white dark:focus:bg-[#3d3d3d] focus:ring-2 focus:ring-green-400 focus:outline-none transition-all px-4 py-2 text-sm text-gray-800 dark:text-gray-300 font-medium resize-none"><?=$row['description']?></textarea>
            </div>
        </div>
    </div>

    <div class="mt-6 sm:mt-6 mb-2 flex flex-row-reverse items-center gap-2">
    <button onclick="updateElement()" class="mt-0 active:scale-95 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl bg-green-400 dark:bg-green-400/20 dark:text-green-400 ring-1 ring-green-400 font-medium duration-150 px-4 py-2 text-sm text-white items-center gap-1 shadow-sm hover:shadow-xl hover:bg-white dark:hover:bg-green-400 dark:hover:text-white hover:text-green-400">Zapisz</button>
    <button onclick="popupEditElementCloseConfirm()" type="button" class="mt-0 active:scale-95 duration-150 inline-flex w-full justify-center cursor-pointer rounded-2xl font-medium dark:ring-gray-400 ring-[#3d3d3d] ring-1 duration-150 px-4 py-2 text-sm dark:text-gray-400 text-[#3d3d3d] items-center gap-1 shadow-sm hover:shadow-xl dark:hover:bg-gray-400 hover:bg-[#3d3d3d] hover:text-white">Anuluj</button>
    <button onclick="popupEditElementDelete()" type="button" class="active:scale-95 duration-150 inline-flex justify-center rounded-2xl px-4 py-2 text-sm font-medium text-rose-400 shadow-sm ring-inset ring-1 ring-rose-400 hover:ring-rose-400 hover:bg-rose-400 hover:text-white hover:shadow-xl duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 ">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
        </button>
    </div>
</section>
