<section data-aos="fade-up" data-aos-delay="100">
    <div class="flex items-center justify-between">
        <span class="font-medium text-2xl dark:text-gray-200 text-black">Często zadawane pytania</span>
        <div onclick="openPopupFaqAdd()"  class="hover:text-white hover:bg-green-400 dark:text-gray-200 hover:shadow-xl shadow-green-300 hover:scale-105 active:scale-90 duration-150 group flex gap-x-3 rounded-xl p-3 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </div>
    </div>
    <?php
            include "../../../scripts/conn_db.php";
            $sql = "SELECT * FROM faq";
            $result = mysqli_query($conn, $sql);
            while($row = mysqli_fetch_assoc($result)){
                if(mb_strlen($row['answer'], "UTF-8")>250){
                    $row['answer'] = substr($row['answer'], 0, 250)."...";
                    
                }
                echo '<div onclick="openPopupFaqEdit('.$row['id'].')" class=" border-t border-white/10 hover:bg-black/10 duration-150 rounded-2xl hover:scale-[1.01] active:scale-[0.98] px-4 cursor-pointer duration-150">
                <dl class="divide-y divide-white/10">
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm flex items-center font-medium leading-6 py-4 text-gray-900 dark:text-gray-200">'.$row['question'].'</dt>
                    <dd class="flex items-center text-sm text-gray-600 md:mt-0 md:mb-0 mb-2 sm:col-span-2 dark:text-gray-400"><div>'.$row['answer'].'</div></dd>
                </div>
                </dl>
            </div>';
            }
            ?>
    </section>

<?php 
$name_in_scripts = 'FaqEdit';
$delete_path = 'scripts/settings/faq/delete.php';
$delete_v2 = 'false';
$path = 'components/panel/faq/edit.php';
$close="";
include "../../popup.php";
?>

<?php 
$name_in_scripts = 'FaqAdd';
$delete_path = '';
$delete_v2 = 'true';
$path = 'components/panel/faq/add.php';
$close="";
include "../../popup.php";
?>