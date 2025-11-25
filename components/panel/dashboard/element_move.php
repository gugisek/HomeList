<?php
include '../../../scripts/security.php';
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
$user_id = $_SESSION['login_id'];
?>
<div id="move_element_loading"></div>
<section class="font-[poppins]">
    <input type="hidden" id="move_element_id" value="<?=$row['id']?>">
    <div class="-mt-10">
        <h1 class="text-lg font-semibold font-[poppins] dark:text-gray-200">Przenieś na inną listę</h1>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="move_element_name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Zadanie</label>
            <div class="mt-2">
                <h1 name="move_element_name" id="move_element_name" class="py-1.5 w-full px-4 text-md font-medium focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] rounded-xl dark:bg-[#3d3d3d] dark:text-gray-200 focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white"><?=$row['title']?></h1>
            </div>
        </div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="move_element_list" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Lista</label>
            <div class="mt-2">
                <select name="move_element_list" id="move_element_list" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] dark:bg-[#3d3d3d] dark:text-gray-200 dark:border-[#3d3d3d] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white">
                    <option value="">Wybierz listę</option>
                    <?php
                    $sql = "SELECT lists.id, lists.name, lists.full_name, list_invities.id as 'invite_id' FROM `lists` left join list_invities on list_invities.list_id=lists.id where lists.owner_id='$user_id' or list_invities.user_id='$user_id' ORDER BY lists.id asc;";
                    $result = mysqli_query($conn, $sql);
                    while($list = mysqli_fetch_assoc($result)) {
                        echo '<option ';
                        if($list['id'] == $row['list_id']) {
                            echo 'selected ';
                        }
                        echo 'value="'.$list['id'].'">'.$list['name'].'</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>


    <div class="mt-6 sm:mt-6 mb-1 flex flex-row-reverse items-center gap-2">
        <button onclick="moveElement()" class="active:scale-95 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl bg-sky-400 duration-150 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-sky-200">Przenieś</button>
        <button onclick="popupMoveElementOpenClose()" type="button" class="active:scale-95 duration-150 flex w-full justify-center cursor-pointer rounded-2xl font-medium ring-gray-400 ring-1 duration-150 px-4 py-2 text-sm text-gray-400 dark:text-gray-200 dark:border-gray-200 dark:hover:bg-gray-200 dark:hover:text-gray-800 items-center gap-1 shadow-sm hover:shadow-xl hover:bg-gray-400 hover:text-white sm:ml-2">Nie zapisuj</button>
    </div>
</section>
