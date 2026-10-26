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
        <h1 class="hl-popup-title">Przenieś na inną listę</h1>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="move_element_name" class="hl-label">Zadanie</label>
            <div class="mt-2">
                <p id="move_element_name" class="hl-input truncate"><?=hl_e($row['title'])?></p>
            </div>
        </div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
        <div class="w-full">
            <label for="move_element_list" class="hl-label">Lista</label>
            <div class="mt-2">
                <select name="move_element_list" id="move_element_list" class="hl-input w-full">
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


    <div class="mt-6 flex flex-row-reverse items-center gap-2">
        <button onclick="moveElement()" class="hl-btn hl-btn-primary flex-1">Przenieś</button>
        <button onclick="popupMoveElementOpenClose()" type="button" class="hl-btn hl-btn-ghost flex-1">Nie zapisuj</button>
    </div>
</section>
