<section data-aos="fade-up" data-aos-delay="100">
    <h1 class="font-medium text-2xl">Ustawienia</h1>

    <div class="mx-auto lg:flex lg:gap-x-16 mt-2">
  <aside class=" overflow-x-auto border-b border-gray-900/5 py-4 lg:block lg:w-64 lg:flex-none lg:border-0">
    <nav class="px-4 sm:px-6 lg:px-0">
      <ul role="list" class="flex gap-x-3 gap-y-1 whitespace-nowrap lg:flex-col">
        <li>
          <!-- Current: "bg-gray-50 text-indigo-600", Default: "text-gray-700 hover:text-indigo-600 hover:bg-gray-50" -->
          <a href="#ur" class="text-gray-700 hover:text-violet-500 duration-150 hover:bg-gray-50 group flex gap-x-3 rounded-md py-2 pl-2 pr-3 text-sm leading-6 font-semibold">
            <svg class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-violet-500 duration-150" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Role użytkowników
          </a>
        </li>
        
      </ul>
    </nav>
  </aside>

  <main class="px-4 sm:px-6 lg:flex-auto lg:px-0 ">
    <div class="mx-auto max-w-2xl space-y-8 sm:space-y-20 lg:mx-0 pb-16 lg:max-w-none">
      <div id="ur" class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-base font-semibold leading-7 text-gray-900">Role użytkowników</h2>
        <p class="mt-1 text-sm leading-6 text-gray-500">Role jakie możesz przypisać dla kont. Lepiej nie edytować domyślnej roli user.</p>

        <dl class="mt-6 divide-y divide-gray-100 border-t border-gray-150 text-sm leading-6">
            <?php
                include '../../../scripts/conn_db.php';
                $sql = "SELECT id, role, description FROM user_roles";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<div onclick="openPopupUserRoles('.$row['id'].')" class="py-5 px-4 sm:flex hover:bg-gray-200 duration-150 cursor-pointer rounded-2xl active:scale-[98%]">
                        <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">'.$row["role"].'</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                          <div class="text-gray-900">'.$row["description"].'</div>
                        </dd>
                      </div>';
                    }
                } else {
                    echo "Brak wyników";
                }
            ?>
          <div class="flex border-t border-gray-100 pt-6">
            <button type="button" onclick="openPopupUserRolesAdd()" class="text-sm px-4 font-semibold leading-6 text-violet-500 hover:text-violet-300 duration-150"><span aria-hidden="true">+</span> Dodaj nową rolę</button>
          </div>
        </dl>
      </div>



    </div>
  </main>
</div>
</section>


<?php 
$name_in_scripts = 'UserRoles';
$delete_path = 'scripts/settings/users/roles/delete.php';
$path = 'components/panel/settings/user_roles/edit.php';
include "../../popup.php";

$name_in_scripts = 'UserRolesAdd';
$delete_path = '';
$path = 'components/panel/settings/user_roles/add.php';
include "../../popup.php";


?>
