<?php
$id = $_GET['id'];
include "../../../../scripts/conn_db.php";
$sql = "SELECT id, role, description, dashboard, users, logs, settings from user_roles where id = $id";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0)
{
    while($row = mysqli_fetch_assoc($result))
    {
        //rozdziel imie i nazwisko
        $role = $row['role'];
        $description = $row['description'];
        $role_dashboard = $row['dashboard'];
        $role_users = $row['users'];
        $role_logs = $row['logs'];
        $role_settings = $row['settings'];
    }
}
?>
<form action="scripts/settings/users/roles/edit.php" method="POST">
    <input type="hidden" name="id" value="<?=$id?>">
    <div class="-mt-4">
        <h1 class="text-md font-bold">Edytuj role użytkownika</h1>
        <p class=" text-xs text-gray-500">Edytujesz role: <?=$role?></p>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
    <div class="w-full">
        <label for="name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Nazwa</label>
        <div class="mt-2">
            <input name="name" id="name" type="text" value="<?=$role?>" placeholder="Wpisz nazwę" class="border rounded-xl w-full py-1.5 px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white" required>
        </div>
    </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
            <label for="description" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Opis</label>
            <div class="mt-2">
                <textarea name="description" id="description" type="text" placeholder="Nic tu nie ma..." class="border rounded-2xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white" required><?=$description?></textarea>
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="md:w-1/2 w-full">
            <label for="dashboard" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Dashboard</label>
            <div class="mt-2">
                <select name="dashboard" id="dashboard" type="text" placeholder="Wybierz status" class=" cursor-pointer border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white" required>
                    <option value="" class="hidden">Wybierz status</option>
                    <?php
                    $sql = "SELECT id, name, description FROM user_role_privileges";
                    $result = mysqli_query($conn, $sql); 
                    if(mysqli_num_rows($result) > 0)
                    {
                        while($row = mysqli_fetch_assoc($result))
                        {
                            echo '<option ';
                            if ($role_dashboard == $row['id']) {
                                echo 'selected ';
                            }
                            echo ' value="'.$row['id'].'">'.$row['name'].' - '. $row['description'] .'</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="md:w-1/2 w-full">
            <label for="users" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Users</label>
            <div class="mt-2">
                <select name="users" id="users" type="text" placeholder="Wybierz status" class=" cursor-pointer border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white" required>
                    <option value="" class="hidden">Wybierz status</option>
                    <?php
                    $sql = "SELECT id, name, description FROM user_role_privileges";
                    $result = mysqli_query($conn, $sql); 
                    if(mysqli_num_rows($result) > 0)
                    {
                        while($row = mysqli_fetch_assoc($result))
                        {
                            echo '<option ';
                            if ($role_users == $row['id']) {
                                echo 'selected ';
                            }
                            echo ' value="'.$row['id'].'">'.$row['name'].' - '. $row['description'] .'</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div class="mt-4 flex flex-row gap-2">
        <div class="md:w-1/2 w-full">
            <label for="logs" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Archiwum</label>
            <div class="mt-2">
                <select name="logs" id="logs" type="text" placeholder="Wybierz status" class=" cursor-pointer border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white" required>
                    <option value="" class="hidden">Wybierz status</option>
                    <?php
                    $sql = "SELECT id, name, description FROM user_role_privileges";
                    $result = mysqli_query($conn, $sql); 
                    if(mysqli_num_rows($result) > 0)
                    {
                        while($row = mysqli_fetch_assoc($result))
                        {
                            echo '<option ';
                            if ($role_logs == $row['id']) {
                                echo 'selected ';
                            }
                            echo ' value="'.$row['id'].'">'.$row['name'].' - '. $row['description'] .'</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="md:w-1/2 w-full">
            <label for="settings" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900">Ustawienia RGBpc.pl</label>
            <div class="mt-2">
                <select name="settings" id="settings" type="text" placeholder="Wybierz status" class=" cursor-pointer border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white" required>
                    <option value="" class="hidden">Wybierz status</option>
                    <?php
                    $sql = "SELECT id, name, description FROM user_role_privileges";
                    $result = mysqli_query($conn, $sql); 
                    if(mysqli_num_rows($result) > 0)
                    {
                        while($row = mysqli_fetch_assoc($result))
                        {
                            echo '<option ';
                            if ($role_settings == $row['id']) {
                                echo 'selected ';
                            }
                            echo ' value="'.$row['id'].'">'.$row['name'].' - '. $row['description'] .'</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <div class="mt-6 sm:mt-6 mb-2 sm:flex sm:flex-row-reverse">
        <button class="active:scale-95 duration-150 inline-flex w-full justify-center rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-xl hover:bg-green-500 sm:ml-2 sm:w-auto">Zapisz</button>
        <button onclick="popupUserRolesCloseConfirm()" type="button" class="active:scale-95 mt-3 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-inset ring-1 ring-[#3d3d3d] hover:ring-gray-500 hover:bg-gray-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto">Nie zapisuj</button>
        <!-- <button type="button" onclick="popupUserRolesDelete()" class="active:scale-95 mt-3 sm:mr-2 inline-flex w-full justify-center rounded-full px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-inset ring-1 ring-[#3d3d3d] hover:ring-red-500 hover:bg-red-500 hover:text-white hover:shadow-xl duration-150 sm:mt-0 sm:w-auto">Usuń</button> -->
    </div>
</form>