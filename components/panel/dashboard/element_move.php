<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'edit']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include '../../../scripts/conn_db.php';
include_once '../../../scripts/planner/planner_lib.php';
include_once '../../../scripts/access.php';
$user_id = (int)$_SESSION['login_id'];
$row = hl_element_access($conn, $user_id, (int)($_GET['id'] ?? 0));
if (!$row) {
    echo "Nie znaleziono elementu.";
    exit;
}
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
                <h1 name="move_element_name" id="move_element_name" class="py-1.5 w-full px-4 text-md font-medium focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] rounded-xl dark:bg-[#3d3d3d] dark:text-gray-200 focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:font-normal focus:text-white"><?=hl_e($row['title'])?></h1>
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
                    // tylko listy tego samego typu (zadanie <-> rutyna mają inne pola) – to samo sprawdza scripts/dashboard/move.php
                    $stmt = $conn->prepare("SELECT DISTINCT lists.id, lists.name FROM `lists` left join list_invities on list_invities.list_id=lists.id where (lists.owner_id=? or list_invities.user_id=?) and lists.list_type=? ORDER BY lists.id asc");
                    $stmt->bind_param('iis', $user_id, $user_id, $row['list_type']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while($list = mysqli_fetch_assoc($result)) {
                        echo '<option ';
                        if($list['id'] == $row['list_id']) {
                            echo 'selected ';
                        }
                        echo 'value="'.(int)$list['id'].'">'.hl_e($list['name']).'</option>';
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
