<?php
$list = $_GET['list'];
include '../../../scripts/conn_db.php';
if ($list == "archive"){
  $list_full_name = "Archiwum";
  $list_id = 0;
  $list_name = "archive";
}else{
  $sql = "SELECT * FROM `lists` WHERE `id` = '$list'";
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
}
?>
<section id="dashboard_body" data-aos="fade-up" data-aos-delay="100">
    <div class="flex items-center justify-between">
        <div class="text-gray-400">
            <span class="font-medium text-2xl text-black font-[poppins]"><?=$list_full_name?></span>
        </div>
        <div onclick="openPopupElementAdd('<?=$list_id?>')"  class="<?php if($list == "archive"){echo 'hidden ';}?> hover:text-white hover:bg-green-400 hover:shadow-xl shadow-green-300 hover:scale-105 active:scale-90 duration-150 group flex gap-x-3 rounded-xl p-3 cursor-pointer">
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
    if($list=="archive"){
      session_start();
      $user_id = $_SESSION['login_id'];
        //warunek taki, że aby pokazywało elementy ze statusem zrobione oraz te, które mają done_date większe niż 1 dzień temu
        $sql = "SELECT list_elements.id, list_elements.list_id, list_elements.title, list_elements.create_date, list_elements.deadline_date, list_elements.status_id, list_elements.done_date, users.name FROM `list_elements` left join users on users.id=list_elements.creator_id left join lists on lists.id=list_elements.list_id 
                WHERE lists.owner_id = $user_id and list_elements.status_id = 2 and list_elements.done_date < DATE_SUB(NOW(), INTERVAL 1 DAY)
                ORDER BY 
                NULLIF(`done_date`, '0000-00-00 00:00:00') IS NULL,
                `done_date` DESC,
                `create_date` DESC
                ";
        $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0) {
          $grouped = [];
          while($row = mysqli_fetch_assoc($result)) {
              $done_date_key = date('Y-m-d', strtotime($row['done_date']));
              $grouped[$done_date_key][] = $row;
          }
      
          echo '<div class="bg-white">
                  <div class="mx-auto max-w-7xl px-6 pb-12 sm:pb-16 lg:px-8 lg:py-20">
                    <div class="mx-auto max-w-4xl divide-y divide-gray-900/10">
                      <dl class="space-y-6 divide-y divide-gray-900/10">';
      
          $index = 0;
          foreach ($grouped as $date => $records) {
              $formattedDate = strftime('%d.%m.%Y', strtotime($date)); // np. "Środa 28.04.2024"
              echo '<div class="pt-6">
                      <dt>
                        <button type="button" onclick="toggleAccordion(\'faq-'.$index.'\', this)" class="flex w-full items-start justify-between text-left text-gray-900" aria-controls="faq-'.$index.'" aria-expanded="false">
                          <span class="text-base font-semibold leading-7">';
                          //przetłumaczenie dnia tygodnia
                          $day = strftime('%A', strtotime($date));
                          if($day == "Monday") $day = "Poniedziałek";
                          else if($day == "Tuesday") $day = "Wtorek";
                          else if($day == "Wednesday") $day = "Środa";
                          else if($day == "Thursday") $day = "Czwartek";
                          else if($day == "Friday") $day = "Piątek";
                          else if($day == "Saturday") $day = "Sobota";
                          else if($day == "Sunday") $day = "Niedziela";
                          echo $day.' '.$formattedDate.' ('.count($records).')</span>
                          <span class="ml-6 flex h-7 items-center">
                            <svg class="plus-icon h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                            </svg>
                            <svg class="minus-icon hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                            </svg>
                          </span>
                        </button>
                      </dt>
                      <dd class="accordion-content" id="faq-'.$index.'">
                        <ul class="list-disc ml-5 space-y-2">';
              
              foreach ($records as $row) {
                  echo '<li onclick="openPopupDetailsElement('.$row['id'].')" class="cursor-pointer hover:text-green-400 text-sm">';
                  echo htmlspecialchars($row['title']);
                  echo '</li>';
              }
      
              echo '    </ul>
                      </dd>
                    </div>';
              $index++;
          }
      
          echo '    </dl>
                  </div>
                </div>
              </div>';
      }else{
        echo '
                            <li class="flex ictems-center justify-center py-4 h-full">
                              <div class="text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto h-12 w-12 text-green-400">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">Brak zadań w archiwum</h3>
                                <p class="mt-1 text-sm text-gray-500">Trafiają tu zadania po jednym dniu od daty ich wykonania.</p>
                              </div>
    
                            </li>
                        ';
  
      }
      
    }else{
    $sql = "SELECT list_elements.id, list_elements.list_id, list_elements.title, list_elements.create_date, list_elements.deadline_date, list_elements.status_id, list_elements.done_date, users.name, users.profile_picture FROM `list_elements` left join users on users.id=list_elements.creator_id 
            WHERE `list_id` = '$list_id' and (list_elements.status_id != 2 or (list_elements.status_id = 2 and list_elements.done_date > DATE_SUB(NOW(), INTERVAL 1 DAY)))
            ORDER BY 
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
                                        <p class="truncate"><span class="sm:inline hidden">Dodane przez </span>
                                        
                                        '.$row['name'].'</p>
                                        <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300">
                                        <circle cx="1" cy="1" r="1" />
                                        </svg>';
                                        if($row['deadline_date'] != "" && $row['deadline_date'] != "0000-00-00 00:00:00"){
                                          echo '<p class="whitespace-nowrap ';
                                          //jeżeli deadline jest za 2 dni lub mniej
                                          if($row['deadline_date'] < date('Y-m-d H:i:s') && $row['status_id'] != 2){
                                            echo 'text-red-400 underline decoration-2 decoration-red-400';
                                          }else if($row['deadline_date'] < date('Y-m-d H:i:s', strtotime('+2 days')) && $row['status_id'] != 2){
                                            echo 'text-rose-400';
                                          }else if($row['deadline_date'] < date('Y-m-d H:i:s', strtotime('+7 days')) && $row['status_id'] != 2){
                                            echo 'text-yellow-400';
                                            //jeżeli jest po deadline
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
    }
    
    ?>
    </ul>
    <div class="text-xs text-gray-400 text-center w-full flex items-center justify-center pb-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-green-400 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
          </svg>
      HomeList - version 0.2, powered by <a href="https://rgbpc.pl/" target="_blank" class="ml-1 duration-300"><span class="text-red-600">R</span><span class="text-green-600">G</span><span class="text-blue-600">B</span>pc.pl</span></a>
    </div>
</section>

<script>
function toggleAccordion(id, button) {
    const content = document.getElementById(id);
    const isOpen = content.classList.contains('open');

    // Zwiń wszystko
    document.querySelectorAll('.accordion-content').forEach(el => el.classList.remove('open'));
    document.querySelectorAll('.plus-icon').forEach(el => el.classList.remove('hidden'));
    document.querySelectorAll('.minus-icon').forEach(el => el.classList.add('hidden'));

    // Jeśli nie było otwarte, otwórz kliknięty
    if (!isOpen) {
        content.classList.add('open');
        const icons = button.querySelectorAll('svg');
        icons[0].classList.add('hidden');  // plus
        icons[1].classList.remove('hidden');  // minus
    }
}
</script>



<?php 
$name_in_scripts = 'DetailsElement';
$delete_path = '';
$path = 'components/panel/dashboard/element_details.php';
$close="true";
include "../../popup.php";
?>