<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
$list = $_GET['list'] ?? '';
include '../../../scripts/conn_db.php';
include_once '../../../scripts/planner/planner_lib.php';
include_once '../../../scripts/access.php';
$list_type = 'normal';
if ($list == "archive"){
  $list_full_name = "Archiwum";
  $list_id = 0;
  $list_name = "archive";
}else{
  $list_access = hl_list_access($conn, (int)$_SESSION['login_id'], (int)$list);
  if (!$list_access) {
      echo '<div class="mt-[30vh] text-center text-sm text-gray-500 dark:text-gray-400">Nie znaleziono listy albo nie masz do niej dostępu.</div>';
      exit;
  }
  $row = $list_access['list'];
  $list_id = (int)$row['id'];
  $list_name = $row['name'];
  $list_full_name = hl_e($row['full_name']);
  $list_type = $row['list_type'];
  $list_shared = hl_list_is_shared($conn, $list_id);
}
include_once '../../../scripts/sync/sync_lib.php';
echo hl_sync_tag($conn, (int)$_SESSION['login_id'], $list == "archive" ? 'archive' : 'list:'.$list_id);
?>
<section id="sticky_nav_body" data-aos="fade-up" data-aos-delay="100" class="sticky top-0 z-[1] pt-6 bg-gradient-to-b -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 from-[#fdfdfd] via-[#fdfdfd]/70 via-70% to-white/0 dark:from-[#3d3d3d] dark:via-[#3d3d3d]/70 dark:to-[#3d3d3d]/0">
    <div class="flex items-center justify-between">
        <?php if ($list == "archive"): ?>
        <div class="flex items-center gap-3 min-w-0 text-gray-400">
            <span class="font-medium text-2xl text-black dark:text-white font-[poppins] truncate"><?=$list_full_name?></span>
        <?php else: ?>
        <div onclick="openPopupInfoLists('<?=$list_id?>')" role="button" title="Szczegóły i udostępnianie listy" class="group flex items-center gap-3 min-w-0 -ml-2 pl-2 pr-3 py-1 rounded-2xl text-gray-400 cursor-pointer hover:bg-black/[0.04] dark:hover:bg-white/5 active:scale-[0.98] duration-150">
            <span class="font-medium text-2xl text-black dark:text-white font-[poppins] truncate"><?=$list_full_name?></span>
        <?php endif; ?>
            <?php if (!empty($list_shared)):
                $members = hl_list_members($conn, $list_id);
                $shown = array_slice($members, 0, 4);
                $rest = count($members) - count($shown);
            ?>
            <span class="flex-none inline-flex items-center pl-1.5" title="<?=hl_e(implode(', ', array_column($members, 'name')))?>">
                <?php foreach ($shown as $member): ?>
                <span class="-ml-1.5 inline-flex rounded-full duration-150 hover:-translate-y-0.5 hover:z-[1]" title="<?=hl_e(trim($member['name'].' '.$member['sur_name']))?>"><?=hl_avatar_img($member['profile_picture'], 'size-7 ring-2 ring-[#fdfdfd] dark:ring-[#3d3d3d]')?></span>
                <?php endforeach; ?>
                <?php if ($rest > 0): ?>
                <span class="-ml-1.5 inline-grid place-items-center size-7 rounded-full bg-gray-200 text-[11px] font-semibold text-gray-600 ring-2 ring-[#fdfdfd] dark:bg-[#525252] dark:text-gray-200 dark:ring-[#3d3d3d]">+<?=$rest?></span>
                <?php endif; ?>
            </span>
            <?php endif; ?>
            <?php if ($list_type == 'routine'): ?>
            <span class="flex-none inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium text-orange-500 bg-orange-100 dark:text-orange-400 dark:bg-orange-400/15">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Rutyny
            </span>
            <?php endif; ?>
            <?php if ($list != "archive"): ?>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="size-4 flex-none -ml-1 text-gray-300 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300 group-hover:translate-y-0.5 duration-150"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            <?php endif; ?>
        </div>
        <div onclick="openPopupElementAdd('<?=$list_id?>')" title="Dodaj zadanie" class="<?php if($list == "archive"){echo 'hidden ';}?>hl-icon-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </div>
    </div>
</section>

<section class="mt-2" data-aos="fade-right" data-aos-delay="150">
<ul id="hl_morph_list" role="list" class=" h-full">
    <?php
    
    include '../../../scripts/conn_db.php';
    if($list=="archive"){
      include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
      $user_id = $_SESSION['login_id'];
        //warunek taki, że aby pokazywało elementy ze statusem zrobione oraz te, które mają done_date większe niż 1 dzień temu
        $sql = "SELECT list_elements.id, list_elements.list_id, list_elements.title, list_elements.create_date, list_elements.deadline_date, list_elements.status_id, list_elements.done_date, users.name FROM `list_elements` left join users on users.id=list_elements.creator_id left join lists on lists.id=list_elements.list_id 
                WHERE lists.owner_id = $user_id and ((list_elements.status_id = 2 and list_elements.done_date < DATE_SUB(NOW(), INTERVAL 1 DAY)) or list_elements.status_id = 4)
                ORDER BY
                (`done_date` IS NULL OR `done_date` = 0),
                `done_date` DESC,
                `create_date` DESC
                ";
        $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0) {
          // grupy według dnia wykonania / archiwizacji; najnowszy dzień rozwinięty
          $grouped = [];
          while($row = mysqli_fetch_assoc($result)) {
              $grouped[date('Y-m-d', strtotime($row['done_date']))][] = $row;
          }
          $today_ymd = date('Y-m-d');
          $index = 0;
          foreach ($grouped as $date => $records) {
              $open = $index === 0;
              echo '<li class="arch-group mt-3">
                      <button type="button" onclick="archToggle(this)" aria-expanded="'.($open ? 'true' : 'false').'" class="w-full flex items-center gap-2 px-1 py-2 text-left rounded-2xl group">
                        <span class="text-green-400">'.hl_icon('check', 'size-5', '2').'</span>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 first-letter:uppercase">'.hl_e(hl_relative_day($date, $today_ymd) === hl_day_name($date) ? hl_long_date($date) : hl_relative_day($date, $today_ymd).', '.(int)date('j', strtotime($date)).' '.hl_month_name(date('n', strtotime($date)))).'</h3>
                        <span class="text-xs text-gray-400">'.count($records).'</span>
                        <span class="arch-chevron ml-auto text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 duration-200'.($open ? ' rotate-90' : '').'">'.hl_icon('right', 'size-4', '2').'</span>
                      </button>
                      <ul class="arch-list'.($open ? '' : ' hidden').'">';
              foreach ($records as $row) {
                  $archived = (int)$row['status_id'] === 4;
                  echo '<li data-key="e'.(int)$row['id'].'" onclick="openPopupDetailsElement('.(int)$row['id'].')" class="group relative flex items-center gap-3 my-1.5 py-3 pl-3 pr-4 sm:mx-0 -mx-4 rounded-2xl bg-white dark:bg-[#2f2f2f] border border-black/10 dark:border-white/5 shadow-sm hover:shadow-md transition-[box-shadow,transform] duration-200 md:hover:scale-[1.01] active:scale-[0.98] cursor-pointer">
                          <span class="flex size-[1.6rem] flex-none items-center justify-center rounded-full '.($archived ? 'bg-amber-400/15 text-amber-500' : 'bg-green-400/90 text-white').'">'.hl_icon($archived ? 'archive' : 'check', 'size-3.5', '2.5').'</span>
                          <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">'.hl_e($row['title']).'</h2>
                            <p class="text-xs text-gray-400 mt-0.5 truncate">'.($archived ? 'Zarchiwizowane' : 'Zrobione').' · '.hl_e($row['name']).'</p>
                          </div>
                          <span class="text-gray-300 dark:text-gray-500 group-hover:text-gray-500 duration-150">'.hl_icon('right', 'size-4', '2').'</span>
                        </li>';
              }
              echo '</ul></li>';
              $index++;
          }
      }else{
        echo '<li class="hl-empty">
                <span class="hl-empty-ico">'.hl_icon('archive', '').'</span>
                <h3>Archiwum jest puste</h3>
                <p>Trafiają tu zadania po jednym dniu od ich wykonania i te zarchiwizowane ręcznie.</p>
              </li>';
      }
      
    }else if($list_type == "routine"){
      include 'routines_body.php';
    }else{
    // plan dnia zalogowanego użytkownika dla zadań z tej listy
    include_once '../../../scripts/planner/planner_lib.php';
    include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
    $plans = [];
    $plan_result = mysqli_query($conn, "SELECT day_plan.element_id, day_plan.plan_date, day_plan.day_part FROM day_plan JOIN list_elements ON list_elements.id=day_plan.element_id WHERE day_plan.user_id = '".(int)$_SESSION['login_id']."' AND list_elements.list_id = '".(int)$list_id."'");
    while ($plan_row = mysqli_fetch_assoc($plan_result)) $plans[(int)$plan_row['element_id']] = $plan_row;
    $sql = "SELECT list_elements.id, list_elements.list_id, list_elements.title, list_elements.create_date, list_elements.deadline_date, list_elements.status_id, list_elements.done_date, list_elements.link_url, users.name, users.profile_picture FROM `list_elements` left join users on users.id=list_elements.creator_id
            WHERE `list_id` = '$list_id' and (list_elements.status_id not in (2, 4) or (list_elements.status_id = 2 and list_elements.done_date > DATE_SUB(NOW(), INTERVAL 1 DAY)))
            ORDER BY
            (`deadline_date` IS NULL OR `deadline_date` = 0),
            `deadline_date` ASC,
            `create_date` ASC
            ";
            $result = mysqli_query($conn, $sql);
            if(mysqli_num_rows($result) > 0)
                 {
                    while($row = mysqli_fetch_assoc($result))
                        {
                            echo '
                                <li data-key="e'.(int)$row['id'].'" onclick="openPopupDetailsElement('.$row['id'].')" class="group border ';
                                        if($row['deadline_date'] != "" && $row['deadline_date'] != "0000-00-00 00:00:00"){
                                          if($row['deadline_date'] < date('Y-m-d H:i:s') && $row['status_id'] != 2){
                                            echo 'border-red-400/30';
                                          }else if($row['deadline_date'] < date('Y-m-d H:i:s', strtotime('+2 days')) && $row['status_id'] != 2){
                                            echo 'border-rose-400/30';
                                          }else if($row['deadline_date'] < date('Y-m-d H:i:s', strtotime('+7 days')) && $row['status_id'] != 2){
                                            echo 'border-yellow-400/30';
                                            //jeżeli jest po deadline
                                          }else{
                                              if($row['status_id'] == 2) echo 'border-green-400/30';
                                              else if($row['status_id'] == 3) echo 'border-sky-400/20';
                                              else echo 'border-black/10';
                                            }
                                        }else{
                                          if($row['status_id'] == 2) echo 'border-green-400/30';
                                          else if($row['status_id'] == 3) echo 'border-sky-400/20';
                                          else echo 'border-black/10';
                                        }
                                        echo ' relative flex my-1.5 py-3.5 items-center justify-between gap-4 sm:px-4 px-4 sm:mx-0 -mx-4 rounded-2xl bg-white dark:bg-[#2f2f2f] shadow-sm hover:shadow-md transition-all duration-200 md:hover:scale-[1.01] md:active:scale-[0.98] active:scale-[0.95] cursor-pointer">
                                  <div class="flex items-center gap-4 min-w-0">
                                    <div class="flex items-center justify-center h-3 min-w-3 rounded-full transition-colors ';
                                        if($row['deadline_date'] != "" && $row['deadline_date'] != "0000-00-00 00:00:00"){
                                          if($row['deadline_date'] < date('Y-m-d H:i:s') && $row['status_id'] != 2){
                                            echo 'bg-red-400/80 group-hover:bg-red-400 animate-ping';
                                          }else if($row['deadline_date'] < date('Y-m-d H:i:s', strtotime('+2 days')) && $row['status_id'] != 2){
                                            echo 'bg-rose-400/80 group-hover:bg-rose-400 animate-pulse';
                                          }else if($row['deadline_date'] < date('Y-m-d H:i:s', strtotime('+7 days')) && $row['status_id'] != 2){
                                            echo 'bg-yellow-400/80 group-hover:bg-yellow-400';
                                            //jeżeli jest po deadline
                                          }else{
                                              if($row['status_id'] == 2) echo 'bg-green-400/80 group-hover:bg-green-400';
                                              else if($row['status_id'] == 3) echo 'bg-sky-400/80 group-hover:bg-sky-400';
                                              else echo 'bg-gray-300/80 group-hover:bg-gray-400';
                                            }
                                        }else{
                                          if($row['status_id'] == 2) echo 'bg-green-400/80 group-hover:bg-green-400';
                                          else if($row['status_id'] == 3) echo 'bg-sky-400/80 group-hover:bg-sky-400';
                                          else echo 'bg-gray-300/80 group-hover:bg-gray-400';
                                        }
                                        echo '
                                    
                                    "></div>
                                    <div class="min-w-0 flex flex-col">
                                      <h2 class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate group-hover:text-gray-900 dark:group-hover:text-gray-300 transition-colors">
                                        <span class="truncate ';
                                            if($row['status_id'] == 2) echo 'line-through text-gray-600 dark:text-gray-500';
                                            echo '">'.hl_e($row['title']).'</span>
                                      </h2>
                                      <div class="flex items-center gap-2 text-xs text-gray-400 mt-1">
                                        <span class="flex items-center gap-1.5 min-w-0">'.($list_shared ? hl_avatar_img($row['profile_picture']) : '').'<span class="truncate"><span class="hidden sm:inline">Dodane przez </span>'.hl_e($row['name']).'</span></span>
                                        <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-gray-300">
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
                                          echo '">Deadline '.hl_short_date($row['deadline_date'], true).'</p>';
                                        }else{
                                          echo '<p class="whitespace-nowrap">'.hl_short_date($row['create_date'], true).'</p>';
                                        }
                                        if (isset($plans[(int)$row['id']]) && $row['status_id'] != 2) {
                                          $p = $plans[(int)$row['id']];
                                          echo '<svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-gray-300"><circle cx="1" cy="1" r="1" /></svg>
                                                <p class="flex items-center gap-1 whitespace-nowrap '.($p['plan_date'] < date('Y-m-d') ? 'text-amber-500' : 'text-green-500').'">'.hl_icon('calendar', 'size-3.5').hl_plan_label($p['plan_date'], $p['day_part'], date('Y-m-d')).'</p>';
                                        }
                                        echo '
                                      </div>
                                    </div>
                                  </div>
                                    
                                      <!-- Prawa sekcja -->
                                    <div class="flex items-center gap-3 flex-none">
                                      ';
                                    if (!empty($row['link_url'])) echo '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 text-sky-400 flex-shrink-0 hidden sm:block" title="Ma dołączony link"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>';
                                    echo '<span class="px-2 py-1 flex items-center justify-center rounded-full text-xs font-medium ';
                                    if($row['status_id'] == 1) echo 'text-gray-500 bg-gray-100 dark:bg-black/20 dark:border-gray-600 dark:border group-hover:bg-gray-200 ';
                                    else if($row['status_id'] == 2) echo 'text-green-500 bg-green-100 dark:bg-green-400/20 dark:border-green-600 dark:border group-hover:bg-green-200 ';
                                    else if($row['status_id'] == 3) echo 'text-sky-500 bg-sky-100 dark:bg-sky-400/20 dark:border-sky-600 dark:border group-hover:bg-sky-200 ';
                                    echo 'transition-colors">
                                        ';
                                    if($row['status_id'] == 1) echo 'Oczekuje';
                                    else if($row['status_id'] == 2) echo 'Zrobione!';
                                    else if($row['status_id'] == 3) echo 'W trakcie';
                                    echo '
                                      </span>
                                      <svg 
                                        class="h-5 w-5 text-gray-400 group-hover:text-gray-500 transition-colors hidden sm:block" 
                                        viewBox="0 0 20 20" fill="currentColor"
                                      >
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"></path>
                                      </svg>
                                    </div>
                                </li>
                            ';
                        }
                    }
                    else {
                        echo '<li class="hl-empty">
                                <span class="hl-empty-ico">'.hl_icon('check', '', '1.8').'</span>
                                <h3>Wszystko zrobione!</h3>
                                <p>Dodaj nowe zadania na tej liście i będą tu widoczne.</p>
                                <button onclick="openPopupElementAdd(`'.$list_id.'`)" type="button" class="hl-btn hl-btn-primary mt-6">'.hl_icon('plus', '', '2').'Nowe zadanie</button>
                              </li>';
                    }
    }
    
    ?>
    </ul>
    <div class="text-xs text-gray-400 text-center w-full flex items-center justify-center mt-8 md:pb-12 pb-8 ">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-green-400 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
          </svg>
      HomeList - version <?php include '../../../version.php'?>, powered by <a href="https://rgbpc.pl/" target="_blank" class="ml-1 duration-300"><span class="text-red-600">R</span><span class="text-green-600">G</span><span class="text-blue-600">B</span>pc.pl</span></a>
      <br/><br/><br/><br/><br/>
    </div>
</section>

<script>
// dzień w archiwum: zwijanie / rozwijanie listy zadań
function archToggle(btn) {
    var list = btn.parentElement.querySelector('.arch-list');
    var open = list.classList.toggle('hidden') === false;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.querySelector('.arch-chevron').classList.toggle('rotate-90', open);
}
</script>



<?php 
$name_in_scripts = 'DetailsElement';
$delete_v2 = '';
$delete_path = '';
$path = 'components/panel/dashboard/element_details.php';
$close="true";
include "../../popup_center.php";
?>