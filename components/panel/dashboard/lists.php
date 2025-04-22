<?php
session_start();
?><section style="box-shadow: 0px 8px 24px 0px rgba(66, 68, 90, 1);" class="bg-[#3d3d3d] font-[poppins] xs:scale-100 scale-90 text-white rounded-2xl fixed sm:bottom-10 bottom-4 flex items-center justify-center">
<section id="more_menu" class="min-w-[200px] absolute bottom-14 z-[99] scale-y-0 w-full flex flex-col gap-2 duration-150">
    <?php
    include '../../../scripts/conn_db.php';
    $user_id = $_SESSION['login_id'];
    $sql = "SELECT lists.id, lists.name, lists.full_name FROM `lists` left join list_invities on list_invities.list_id=lists.id where lists.owner_id='$user_id' or list_invities.user_id='$user_id' ORDER BY lists.id asc;";
    $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0)
             {
                $i = 0;
                $ilosc = mysqli_num_rows($result);
                echo '<div id="more_nav_body" class="';
                if ($ilosc <= 3) {
                    echo 'hidden';
                }
                echo ' bg-[#3d3d3d] rounded-2xl py-3">';
                while($row = mysqli_fetch_assoc($result)) {
                    echo '<a onclick="moreMenuClose();openDetailTab(`todo`,`list='.$row['id'].'`)" id="nav_button_details" class="lists_main_nav list_'.$row['id'].' flex gap-2 py-3 px-4 mx-2 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl ';
                    if ($i <= 2) {
                        echo 'hidden';
                    }
                        echo '">
                                <span>'.$row['full_name'].'</span>
                         </a>';
                    $i++;
                }
                echo '</div>';
            }
    ?>
    <div class="bg-[#3d3d3d] rounded-2xl py-3">
        <a onclick="moreMenuClose();openPopupAddLists();" class="flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <span>Utwórz nową listę</span>
        </a>
        <a onclick="moreMenuClose();openPopupCodeLists();" class="flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5" />
            </svg>
            <span>Wprowadź kod listy</span>
        </a>
        <a onclick="openPopupEditLists()" class="flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
            <span>Edytuj listy</span>
        </a>
        <a onclick="moreMenuClose();openDetailTab(`todo`,`list=archive`)" id="nav_button_details" class="archive flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <span>Archiwum</span>
        </a>
        <a onclick="moreMenu()" class="flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
            <span>Zamknij</span>
        </a>
    </div>
    
</section>
<div id="todo-nav" class="contents">


<?php
include '../../../scripts/conn_db.php';
$user_id = $_SESSION['login_id'];
$sql = "SELECT lists.id, lists.name, lists.full_name FROM `lists` left join list_invities on list_invities.list_id=lists.id where lists.owner_id='$user_id' or list_invities.user_id='$user_id' ORDER BY lists.id asc;";
$result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0)
         {
            $i = 0;
            
            while($row = mysqli_fetch_assoc($result)) {
                echo '
                    <a onclick="moreMenuClose();openDetailTab(`todo`,`list='.$row['id'].'`)" id="nav_button_details" class="lists_main_nav list_'.$row['id'].' transform inline-block hover:scale-105 active:scale-95 cursor-pointer hover:shadow-xl hover:text-gray-800 focus:scale-95 px-6 py-3 hover:bg-gray-300 duration-150 ';
                if ($i > 2) {
                    echo 'hidden';
                }
                    echo '">'.$row['full_name'].'</a>';
                $i++;
            }
            }
?>
</div>
<a onclick="moreMenu()" id="nav_button_details" class="rounded-r-2xl hover:scale-105 cursor-pointer active:scale-95 hover:shadow-xl hover:text-gray-800 focus:scale-95 px-6 py-3 hover:bg-gray-300 duration-150">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
    </svg>

</a>
</section>