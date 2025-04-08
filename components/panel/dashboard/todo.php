<?php
$list = $_GET['list'];
include '../../../scripts/conn_db.php';
$sql = "SELECT * FROM `lists` WHERE `name` = '$list'";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0)
    {
        $row = mysqli_fetch_assoc($result);
        $list_id = $row['id'];
        $list_name = $row['name'];
        $list_full_name = $row['full_name'];
    }
else {
    echo 'Błąd listy';
}
?>
<section id="dashboard_body" data-aos="fade-up" data-aos-delay="100">
    <div class="flex items-center justify-between">
        <div class="text-gray-400">
            <span class="font-medium text-2xl text-black font-[poppins]"><?=$list_full_name?></span>
        </div>
        <div onclick="openPopupElementAdd('<?=$list_id?>')"  class="hover:text-white hover:bg-green-400 hover:shadow-xl shadow-green-300 hover:scale-105 active:scale-90 duration-150 group flex gap-x-3 rounded-xl p-3 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </div>
    </div>
</section>
<section class="mt-2" data-aos="fade-up" data-aos-delay="150">
<ul role="list" class="divide-y divide-white/5 h-full">
    <?php
    include '../../../scripts/conn_db.php';
    $sql = "SELECT list_elements.id, list_elements.list_id, list_elements.title, list_elements.create_date, list_elements.deadline_date, list_elements.status_id, list_elements.done_date, users.name FROM `list_elements` left join users on users.id=list_elements.creator_id WHERE `list_id` = '$list_id' ORDER BY 
            NULLIF(`deadline_date`, '0000-00-00 00:00:00') IS NULL,
            `deadline_date` ASC,
            `create_date` ASC
            ";
    $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0)
             {
                while($row = mysqli_fetch_assoc($result))
                    {
                        echo '
                            <li onclick="openPopupDetailsElement('.$row['id'].')" class="hover:scale-[1.01] active:scale-[0.98] duration-150 cursor-pointer relative flex items-center space-x-4 py-4">
                                <div class="min-w-0 flex-auto">
                                <div class="flex items-center gap-x-3">
                                    <div class="flex-none rounded-full p-1 ';
                                    if($row['status_id'] == 1) echo 'text-gray-500 bg-gray-600/10';
                                    else if($row['status_id'] == 2) echo 'text-green-400 bg-green-400/10';
                                    else if($row['status_id'] == 3) echo 'text-sky-400 bg-sky-400/10';
                                    echo '">
                                    <div class="h-2 w-2 rounded-full bg-current"></div>
                                    </div>
                                    <h2 class="min-w-0 text-sm font-semibold leading-6">
                                    <a href="#" class="flex gap-x-2">
                                        <span class="truncate ';
                                        if($row['status_id'] == 2) echo 'line-through text-gray-500';
                                        echo '">'.$row['title'].'</span>
                                    </a>
                                    </h2>
                                </div>
                                <div class="mt-2 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
                                    <p class="truncate">Dodane przez '.$row['name'].'</p>
                                    <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
                                    <circle cx="1" cy="1" r="1" />
                                    </svg>';
                                    if($row['deadline_date'] != "" && $row['deadline_date'] != "0000-00-00 00:00:00"){
                                      echo '<p class="whitespace-nowrap ';
                                      //jeżeli deadline jest za 2 dni lub mniej
                                      if($row['deadline_date'] < date('Y-m-d H:i:s', strtotime('+2 days')) && $row['status_id'] != 2){
                                        echo 'text-rose-400';
                                      }else if($row['deadline_date'] < date('Y-m-d H:i:s', strtotime('+7 days')) && $row['status_id'] != 2){
                                        echo 'text-yellow-400';
                                      }else{
                                          echo 'text-gray-400';
                                        }
                                      echo '">Deadline '.date_format(date_create($row['deadline_date']), "H:i d.m").'</p>';
                                    }else{
                                      echo '<p class="whitespace-nowrap">Dodano '.date_format(date_create($row['create_date']), "H:i d.m").'</p>';
                                    }
                                echo '</div>
                                </div>
                                <div class="rounded-lg flex-none py-1 px-2 text-xs font-medium ring-1 ring-inset ';
                                if($row['status_id'] == 1) echo 'bg-gray-400/10 ring-gray-400 text-gray-400';
                                else if($row['status_id'] == 2) echo 'bg-green-50 ring-green-400 text-green-400';
                                else if($row['status_id'] == 3) echo 'bg-sky-400/10 ring-sky-400 text-sky-400';
                                echo '">';
                                if($row['status_id'] == 1) echo 'Oczekuje';
                                else if($row['status_id'] == 2) echo 'Zrobione!';
                                else if($row['status_id'] == 3) echo 'W trakcie';
                                echo '</div>
                                <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                  <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </li>
                        ';
                    }
                }
                else {
                    echo '
                        <li class="flex ictems-center justify-center py-4 h-full">
                          <div class="text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto h-12 w-12 text-green-400">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900">Wszystko zrobione!</h3>
                            <p class="mt-1 text-sm text-gray-500">Dodaj nowe zadania na tej liście i będą tu widoczne.</p>
                            <div class="mt-6">
                              <button onclick="openPopupElementAdd(`'.$list_id.'`)" type="button" class="inline-flex items-center rounded-full bg-[#3d3d3d] px-4 py-2 text-sm text-white shadow-sm hover:bg-green-400 hover:scale-105 active:scale-95 duration-150 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                  <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                </svg>
                                Nowe zadanie
                              </button>
                            </div>
                          </div>

                        </li>
                    ';
                }
    ?>
    </ul>
<!-- 
  <li class="relative flex items-center space-x-4 py-4">
    <div class="min-w-0 flex-auto">
      <div class="flex items-center gap-x-3">
        <div class="flex-none rounded-full p-1 text-green-400 bg-green-400/10">
          <div class="h-2 w-2 rounded-full bg-current"></div>
        </div>
        <h2 class="min-w-0 text-sm font-semibold leading-6 text-white">
          <a href="#" class="flex gap-x-2">
            <span class="truncate">Planetaria</span>
            <span class="text-gray-400">/</span>
            <span class="whitespace-nowrap">mobile-api</span>
            <span class="absolute inset-0"></span>
          </a>
        </h2>
      </div>
      <div class="mt-3 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
        <p class="truncate">Deploys from GitHub</p>
        <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
          <circle cx="1" cy="1" r="1" />
        </svg>
        <p class="whitespace-nowrap">Deployed 3m ago</p>
      </div>
    </div>
    <div class="rounded-full flex-none py-1 px-2 text-xs font-medium ring-1 ring-inset text-indigo-400 bg-indigo-400/10 ring-indigo-400/30">Production</div>
    <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
    </svg>
  </li>
  <li class="relative flex items-center space-x-4 py-4">
    <div class="min-w-0 flex-auto">
      <div class="flex items-center gap-x-3">
        <div class="flex-none rounded-full p-1 text-gray-500 bg-gray-100/10">
          <div class="h-2 w-2 rounded-full bg-current"></div>
        </div>
        <h2 class="min-w-0 text-sm font-semibold leading-6 text-white">
          <a href="#" class="flex gap-x-2">
            <span class="truncate">Tailwind Labs</span>
            <span class="text-gray-400">/</span>
            <span class="whitespace-nowrap">tailwindcss.com</span>
            <span class="absolute inset-0"></span>
          </a>
        </h2>
      </div>
      <div class="mt-3 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
        <p class="truncate">Deploys from GitHub</p>
        <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
          <circle cx="1" cy="1" r="1" />
        </svg>
        <p class="whitespace-nowrap">Deployed 3h ago</p>
      </div>
    </div>
    <div class="rounded-full flex-none py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-400 bg-gray-400/10 ring-gray-400/20">Preview</div>
    <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
    </svg>
  </li>
  <li class="relative flex items-center space-x-4 py-4">
    <div class="min-w-0 flex-auto">
      <div class="flex items-center gap-x-3">
        <div class="flex-none rounded-full p-1 text-rose-400 bg-rose-400/10">
          <div class="h-2 w-2 rounded-full bg-current"></div>
        </div>
        <h2 class="min-w-0 text-sm font-semibold leading-6 text-white">
          <a href="#" class="flex gap-x-2">
            <span class="truncate">Protocol</span>
            <span class="text-gray-400">/</span>
            <span class="whitespace-nowrap">api.protocol.chat</span>
            <span class="absolute inset-0"></span>
          </a>
        </h2>
      </div>
      <div class="mt-3 flex items-center gap-x-2.5 text-xs leading-5 text-gray-400">
        <p class="truncate">Deploys from GitHub</p>
        <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
          <circle cx="1" cy="1" r="1" />
        </svg>
        <p class="whitespace-nowrap">Failed to deploy 6d ago</p>
      </div>
    </div>
    <div class="rounded-full flex-none py-1 px-2 text-xs font-medium ring-1 ring-inset text-gray-400 bg-gray-400/10 ring-gray-400/20">Preview</div>
    <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
    </svg>
  </li>
</ul> -->

</section>

<?php 
$name_in_scripts = 'DetailsElement';
$delete_path = '';
$path = 'components/panel/dashboard/element_details.php';
$close="true";
include "../../popup.php";
?>