<div class="-mt-[43px]">
<div id="element_loading"></div>
    <h1 class="font-[poppins] font-semibold text-lg pr-8 dark:text-gray-200">Edytuj listy</h1>
    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Przytrzymaj i przeciągnij aby zmienić kolejność</p>
    <div id="items-container" class="flex flex-col mt-4 gap-1 overflow-y-auto max-h-[55vh] pr-1">
        <?php
        session_start();
        include '../../../scripts/conn_db.php';
        $user_id = $_SESSION['login_id'];
        $sql = "SELECT lists.id, lists.name, lists.full_name, list_invities.id as 'invite_id' FROM `lists` left join list_invities on list_invities.list_id=lists.id where lists.owner_id='$user_id' or list_invities.user_id='$user_id' ORDER BY lists.id asc;";
        $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0)
            {
                while($row = mysqli_fetch_assoc($result))
                    {
                        echo '
                        <div class="lists_lenght list_'.$row['id'].' draggable-item flex items-center gap-3 py-3 px-3 rounded-2xl select-none touch-manipulation bg-white dark:bg-[#2c2c2c] hover:bg-gray-50 dark:hover:bg-[#333] transition-colors" data-id="'.$row['id'].'">
                            <div class="drag-handle flex-shrink-0 text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 transition-colors cursor-grab active:cursor-grabbing p-1 -ml-1 touch-none">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </div>
                            <h1 class="font-medium text-sm text-gray-800 dark:text-gray-200 flex-1 truncate">'.$row['full_name'].'</h1>
                            <div onclick="openPopupInfoLists(`'.$row['id'].'`)" class="flex-shrink-0 p-2 text-gray-400 dark:text-gray-500 hover:bg-[#3d3d3d] hover:text-white cursor-pointer duration-150 rounded-xl">
                        ';
                        if($row['invite_id'] == null) {
                           echo '
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                           ';
                        } else {
                            echo '
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                            </div>
                            ';
                        }
                        echo '
                            </div>
                        </div>
                        ';
                    }
            }
        ?>
    </div>
</div>

<script>
applyTodoNavOrder('items-container');
hightlight3Element();

function initSortable() {
    new Sortable(document.getElementById('items-container'), {
        handle: '.drag-handle',
        animation: 180,
        easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function() {
            saveOrder();
            hightlight3Element();
        }
    });
}

if (typeof Sortable !== 'undefined') {
    initSortable();
} else {
    var _s = document.createElement('script');
    _s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
    _s.onload = initSortable;
    document.head.appendChild(_s);
}

function saveOrder() {
    var container = document.getElementById('items-container');
    var order = Array.from(container.querySelectorAll('.draggable-item'))
        .map(function(el) { return el.dataset.id; });
    localStorage.setItem(storageKey, JSON.stringify(order));
    applyTodoNavOrder('todo-nav');
    applyTodoNavOrder('more_nav_body');
    fixRoundedCorners();
}
</script>

<style>
.sortable-ghost {
    opacity: 0.35;
    background: rgba(74, 222, 128, 0.12) !important;
    border-radius: 1rem;
}
.sortable-chosen {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
}
</style>
