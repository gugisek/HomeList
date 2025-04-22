<?php
$list_id = $_GET['id'];
include '../../../scripts/conn_db.php';
$sql = "SELECT lists.id, lists.full_name, lists.list_code, concat(users.name, ' ', users.sur_name) as 'full_usr_name' FROM lists join users on users.id=lists.owner_id WHERE lists.id = $list_id;";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
?>
<div class="-mt-[43px]">
<div id="info_list_loading"></div>
    <h1 class="font-[poppins] font-semibold text-lg pr-8"><?=$row['full_name']?></h1>
    <p class="pt-4 text-wrap"></p>
    <div class="flex flex-col">
        <div class="flex md:flex-row flex-col gap-2">
            <div class="w-full">
                <label for="" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Kod zaproszenia listy</label>
                <div class="mt-2 flex gap-2">
                    
                    <input type="text" id="code" class="w-full border border-black/30 rounded-xl px-4 py-2" value="<?=$row['list_code']?>" disabled></input>
                    <a onclick="copyClip('code');" class="border border-[#3d3d3d] text-white bg-[#3d3d3d] rounded-xl px-4 py-2 hover:scale-105 active:scale-95 duration-150 hover:text-[#3d3d3d] hover:bg-white cursor-pointer hover:shadow-xl hover:font-medium">Skopiuj</a>
                </div>
                <label for="" class="mt-2 ml-px block pl-2 text-sm leading-6 text-gray-900">Lista stworzona przez: <span class="font-medium"><?=$row['full_usr_name']?></span></label>
            </div>
        </div>
        <div class="mt-6 sm:mt-6 mb-2 sm:flex sm:flex-row-reverse">
            <button onclick="codeList()" class="active:scale-95 duration-150 inline-flex w-full justify-center active:scale-95 rounded-full bg-green-400 duration-150 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-green-500 sm:ml-2 sm:w-auto">Dodaj</button>
            <button onclick="popupCodeListsCloseConfirm()" type="button" class="active:scale-95 duration-150 mt-3 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-inset ring-1 ring-[#3d3d3d] hover:ring-gray-500 hover:bg-gray-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto">Anuluj</button>
        </div>
    </div>
</div>
<!-- usuinęcie niech będzie dla obu wariantów 1!!!!!1!!!!11!!1!!!!!1112!1!!!!!!! -->
