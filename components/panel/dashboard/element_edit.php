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
<section class="font-[poppins]">
    <input type="hidden" id="edit_element_id" value="<?=$row['id']?>">
    <div class="-mt-10">
        <h1 class="text-lg font-semibold font-[poppins]">Edytuj zadanie</h1>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="edit_element_name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Nazwa zadania</label>
            <div class="mt-2">
                <input name="edit_element_name" id="edit_element_name" type="text" value="<?=$row['title']?>" placeholder="Wymagane" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white" required>
            </div>
        </div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="edit_element_deadline" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Deadline</label>
            <div class="mt-2">
                <input name="edit_element_deadline" id="edit_element_deadline" type="datetime-local" value="<?=$row['deadline_date']?>" placeholder="" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white">
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
            <label for="edit_element_description" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Opis</label>
            <div class="mt-2">
                <textarea name="edit_element_description" id="edit_element_description" type="text" placeholder="Nic tu nie ma..." class="border rounded-2xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white"><?=$row['description']?></textarea>
            </div>
        </div>
    </div>

    <div class="mt-6 sm:mt-6 mb-2 flex flex-row-reverse items-center gap-2">
        <button onclick="updateElement()" class="active:scale-95 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl bg-[#3d3d3d] duration-150 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-green-500">Zapisz</button>
        <button onclick="popupEditElementCloseConfirm()" type="button" class="active:scale-95 duration-150 inline-flex w-full justify-center rounded-2xl px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-inset ring-1 ring-[#3d3d3d] hover:ring-gray-500 hover:bg-gray-500 hover:text-white hover:shadow-xl duration-150">Nie zapisuj</button>
        <button onclick="popupEditElementDelete()" type="button" class="active:scale-95 duration-150 inline-flex justify-center rounded-2xl px-4 py-2 text-sm font-medium text-rose-400 shadow-sm ring-inset ring-1 ring-rose-400 hover:ring-rose-400 hover:bg-rose-400 hover:text-white hover:shadow-xl duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 ">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
        </button>
    </div>
</section>
