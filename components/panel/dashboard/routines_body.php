<?php
// Zawartość listy typu 'routine' – includowane z todo.php wewnątrz <ul>. Wymaga $list_id, $conn.
include_once __DIR__.'/../../../scripts/planner/planner_lib.php';
include_once __DIR__.'/feed_parts.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = (int)$_SESSION['login_id'];
$today = date('Y-m-d');
$routines = [];
$result = mysqli_query($conn, "SELECT list_elements.*, lists.full_name AS list_name FROM list_elements JOIN lists ON lists.id=list_elements.list_id
                               WHERE list_elements.list_id = '".(int)$list_id."'
                               ORDER BY FIELD(list_elements.day_part, 1, 2, 3, 0), list_elements.plan_time IS NULL, list_elements.plan_time, list_elements.id");
while ($r = mysqli_fetch_assoc($result)) $routines[] = $r;

if (!$routines) {
    echo '
        <li class="flex items-center justify-center py-4 h-full">
          <div class="text-center max-w-xs">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-400/10 text-orange-400">'.hl_icon('repeat', 'size-7').'</div>
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-200">Brak rutyn</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dodaj powtarzalne czynności – same pojawią się w planie dnia w swoje dni.</p>
            <div class="mt-6">
              <button onclick="openPopupElementAdd(`'.$list_id.'`)" type="button" class="inline-flex items-center rounded-full bg-[#3d3d3d] dark:bg-[#2f2f2f] px-4 py-2 text-sm text-white shadow-sm hover:bg-green-400 hover:scale-105 active:scale-95 duration-150 hover:shadow-xl">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Nowa rutyna
              </button>
            </div>
          </div>
        </li>';
    return;
}

$done_map = hl_routine_done_map($conn, $routines, $user_id, hl_add_days($today, -400), $today);
$parts = hl_parts();
$current_part = null;

echo '<div id="feed">';
foreach ($routines as $r) {
    $rid = (int)$r['id'];
    $part = (int)$r['day_part'];
    if ($part !== $current_part) {
        $current_part = $part;
        echo '<li class="flex items-center gap-2 px-1 mt-5 mb-1 list-none"><span class="'.$parts[$part]['color'].'">'.hl_icon($parts[$part]['icon'], 'size-5').'</span><h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">'.$parts[$part]['label'].'</h3></li>';
    }
    $week = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = hl_add_days($today, -$i);
        $week[] = ['date' => $d, 'state' => !hl_routine_occurs($r, $d) ? 'off' : (isset($done_map[$rid][$d]) ? 'done' : 'missed')];
    }
    $it = [
        'key' => 'r'.$rid,
        'type' => 'routine',
        'id' => $rid,
        'title' => $r['title'],
        'list_name' => $r['list_name'],
        'list_id' => (int)$r['list_id'],
        'done' => isset($done_map[$rid][$today]),
        'status_id' => 1,
        'part' => $part,
        'time' => !empty($r['plan_time']) ? substr($r['plan_time'], 0, 5) : null,
        'position' => null,
        'plan_date' => $today,
        'deadline' => null,
        'link' => !empty($r['link_url']),
        'streak' => hl_streak($r, $done_map[$rid], $today),
        'shared' => (int)$r['shared_done'] === 1,
        'checkable' => hl_routine_occurs($r, $today),
    ];
    hl_render_feed_item($it, $today, $today, 'routine_list', ['summary' => hl_routine_summary($r), 'week' => $week]);
}
echo '</div>';
hl_feed_assets();
