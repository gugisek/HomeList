<section data-aos="fade-up" data-aos-delay="100">
    <div class="flex items-center justify-between">
        <span class="font-medium text-2xl text-black">Konta użytkowników</span>
        <div onclick="openPopupUsersAdd()"  class="hover:text-white hover:bg-green-500 hover:shadow-xl shadow-green-300 hover:scale-105 active:scale-90 duration-150 group flex gap-x-3 rounded-xl p-3 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </div>
    </div>
    <section class="flex flex-col gap-4">
        <div class="grid grid-cols-7 text-sm text-gray-600 font-[poppins] bg-white rounded-2xl ring-1 ring-black ring-opacity-5 shadow-xl mt-4">
            <div class="font-medium py-5 pl-4 pr-3 sm:pl-6 lg:col-span-2 sm:col-span-3 col-span-5">
                Użytkownik
            </div>
            <div class="col-span-2 font-medium py-5 pl-4 pr-3 sm:pl-6 lg:block hidden">
                Opis
            </div>
            <div class="font-medium py-5 pl-4 pr-3 sm:pl-6 sm:col-span-1 col-span-2 sm:block hidden">
                Konto
            </div>
            <div class="font-medium py-5 pl-4 pr-3 sm:pl-6 sm:col-span-1 col-span-2">
                Rola
            </div>
            <div class="font-medium py-5 pl-4 pr-3 sm:pl-6 lg:col-span-1 col-span-2 sm:block hidden">
                Aktywność
            </div>
        </div>
        <div id="table_body">

        <div data-aos="fade-in" data-aos-delay="100">
            <?php
                            include '../../../scripts/conn_db.php';
                            if (isset($_POST['search'])) {
                                $search = $_POST['search'];
                                $role = $_POST['role'];
                                $status = $_POST['status'];
                            }
                            else {
                                $search = "";
                                $role = "";
                                $status = "";
                            }
                            $sql = "SELECT users.id, users.name, users.sec_name, users.description, users.addres, users.mail, users.profile_picture, users.sur_name, user_roles.role, users.create_date, user_status.status, colors.name as status_color, last_log FROM `users` join user_roles on users.role_id=user_roles.id join user_status on user_status.id=users.status_id join colors on colors.id=user_status.color_id order by users.id asc;";
                            $result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($result) > 0)
                            {
                                //window.location=`?page=użytkownicy&action=edit&id='.$row['id'].'#edit`;
                                while($row = mysqli_fetch_assoc($result))
                                {
                                    $create_date = $row['create_date'];
                                    $login_date = $row['last_log'];
                                    if ($row['profile_picture'] == NULL) {
                                        $row['profile_picture'] = 'default.png';
                                    }
                                    if ($row['description'] == NULL) {
                                        $desc = 'Nic tu nie ma ciekawego...';
                                    }else {
                                        $desc = $row['description'];
                                    }
                                    echo '
                                    <div onclick="openPopupUsers('.$row['id'].')" class="grid grid-cols-7 rounded-2xl hover:bg-gray-200 duration-150 active:scale-95 cursor-pointer">
                                        <div class="font-medium py-2 pl-4 pr-3 sm:pl-6 col-span-5 sm:col-span-3 lg:col-span-2">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 mr-2 flex-shrink-0">
                                                <img class="h-10 w-10 border border-black/10 rounded-full object-cover" src="img/users_images/'.$row['profile_picture'].'" alt="">
                                                </div>
                                                <div class="">
                                                <div class="font-medium text-gray-900">'.$row['name'].' '.$row['sur_name'].'</div>
                                                <div class="text-gray-500 -mt-2 text-sm font-regular">'.$row['mail'].'</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-span-2 font-medium py-2 pl-4 pr-3 sm:pl-6 text-sm text-gray-500 items-center lg:flex hidden">
                                            '.$row['description'].'
                                        </div>
                                        <div class="font-medium py-2 pl-4 pr-3 sm:pl-6 items-center sm:col-span-1 col-span-2 sm:flex hidden">
                                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize text-'.$row['status_color'].'-800 bg-'.$row['status_color'].'-100">'.$row['status'].'</span>
                                        </div>
                                        <div class="font-medium py-2 pl-4 pr-3 sm:pl-6 text-sm text-gray-500 flex items-center sm:col-span-1 col-span-2">
                                            '.$row['role'].'
                                        </div>
                                        <div class="font-medium py-2 pl-4 pr-3 sm:pl-6 text-sm text-gray-500 items-center lg:col-span-1 col-span-2 sm:flex hidden">
                                            '.substr($login_date, 0).'
                                        </div>
                                    </div>
                                    ';
                                }
                            } else {
                                echo "Brak wyników";
                            }
                        ?>
            </div>

        </div>
    </section>
</section>

<?php 
$name_in_scripts = 'Users';
$delete_path = 'scripts/users/delete.php';
$path = 'components/panel/users/users_edit.php';
$docs_text = 'Edycja użytkownika';
$docs_path = 'users_docs_add';
include "../../popup.php";
?>
<?php 
$name_in_scripts = 'UsersAdd';
$delete_path = '';
$path = 'components/panel/users/users_add.php';
$docs_text = '';
$docs_path = '';
include "../../popup.php";
?>