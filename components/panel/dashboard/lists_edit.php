<div class="-mt-[43px]">
<div id="element_loading"></div>
    <h1 class="font-[poppins] font-semibold text-lg pr-8">Edytuj listy</h1>
    <p class="pt-4 text-wrap"></p>
    <div id="items-container" class="flex flex-col divide-y divide-gray-300">
        <?php
        session_start();
        include '../../../scripts/conn_db.php';
        $user_id = $_SESSION['login_id'];
        $sql = "SELECT * FROM lists WHERE owner_id = $user_id;";
        $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0)
                {
                    while($row = mysqli_fetch_assoc($result))
                        {
                            echo '
                            <div class="flex items-center justify-between draggable-item" data-id="'.$row['id'].'">
                                <h1 class="font-medium text-md">'.$row['full_name'].'</h1>
                                <div class="flex">
                                    <div class="py-2 px-1 hover:bg-[#3d3d3d] hover:text-white cursor-pointer duration-150 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                        </svg>
                                    </div>
                                    <div class="py-2 px-1 hover:bg-[#3d3d3d] hover:text-white cursor-pointer duration-150 rounded-lg" onclick="moveUp(this)">
                                        <!-- strzałka w górę -->
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                                        </svg>
                                    </div>
                                    <div class="py-2 px-1 hover:bg-[#3d3d3d] hover:text-white cursor-pointer duration-150 rounded-lg" onclick="moveDown(this)">
                                        <!-- strzałka w dół -->
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </div>
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

function moveUp(button) {
    const item = button.closest(".draggable-item");
    const prev = item.previousElementSibling;
    if (prev) {
        animateMove(item, -1, () => {
            item.parentNode.insertBefore(item, prev);
            saveOrder();
        });
    }
}

function moveDown(button) {
    const item = button.closest(".draggable-item");
    const next = item.nextElementSibling;
    if (next) {
        animateMove(item, 1, () => {
            item.parentNode.insertBefore(next, item);
            saveOrder();
        });
    }
}

function animateMove(item, direction, callback) {
    const offset = item.offsetHeight + 8; // 8px marginesu
    item.style.transform = `translateY(${direction * offset}px)`;
    item.style.opacity = "0.6";

    setTimeout(() => {
        item.style.transition = "none";
        item.style.transform = "none";
        item.style.opacity = "1";
        item.offsetHeight; // trigger reflow
        item.style.transition = "";
        callback();
    }, 200); // musi się pokrywać z CSS: transition: 0.2s
}


function saveOrder() {
    const container = document.getElementById("items-container");
    const order = Array.from(container.querySelectorAll(".draggable-item"))
        .map(el => el.dataset.id);
    localStorage.setItem(storageKey, JSON.stringify(order));
    console.log("Order saved:", order);
}

function applySavedOrder() {
    const container = document.getElementById("items-container");
    const savedOrder = JSON.parse(localStorage.getItem(storageKey));

    if (!savedOrder) return;

    savedOrder.forEach(id => {
        const el = container.querySelector(`[data-id="${id}"]`);
        if (el) container.appendChild(el);
    });
}

document.addEventListener("DOMContentLoaded", applySavedOrder);
</script>
