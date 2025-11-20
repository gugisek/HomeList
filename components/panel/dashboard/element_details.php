<?php
$id = $_GET['id'];
include '../../../scripts/conn_db.php';
$sql = "SELECT list_elements.title, list_elements.id, list_elements.description, list_elements.create_date, list_elements.deadline_date, list_elements.status_id, list_elements.done_date, users.name, users.sur_name FROM list_elements left join users on users.id=list_elements.creator_id WHERE list_elements.id = $id";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    //zmienie description aby działały entery z bazy danych
    $row['description'] = str_replace("\n", "<br>", $row['description']);
    $row['description'] = str_replace("\r", "", $row['description']);
} else {
    echo "Nie znaleziono elementu.";
    exit;
}
?>
<div class="-mt-10 font-[poppins] p-1 text-gray-800 dark:text-gray-200">
<div id="element_loading"></div>
    <h1 class="font-[poppins] font-medium pr-8 text-gray-800 dark:text-gray-200"><?=$row['title']?></h1>
    <?php
    if($row['deadline_date'] != null && $row['deadline_date'] != "0000-00-00 00:00:00") {
        $date = date_create($row['deadline_date']);
    $date = date_format($date, "H:i d.m");
    echo '<span class="text-sm text-red-500 dark:text-red-400">Deadline: '.$date.'</span>';
    }
    ?>
    <p class="pt-4 text-wrap text-gray-800 dark:text-gray-300"><?=$row['description']?></p>
    <a class="mt-6 sm:mt-6 text-xs flex items-center justify-center mb-4 w-full text-gray-600 dark:text-gray-400">Dodano <?=date_format(date_create($row['create_date']), "H:i d.m")?> przez <?=$row['name']?> <?=$row['sur_name']?></a>
    
    <div class=" flex gap-2">
            <?php
            switch ($row['status_id']) {
                case 1:
                    echo '
                    <button onclick="popupDetailsElementOpenClose();openPopupEditElement('.$row['id'].')" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" class="active:scale-95 items-center duration-150 inline-flex justify-center active:scale-95 rounded-2xl duration-150 px-2 py-2 hover:scale-110 font-medium text-[#3d3d3d] dark:text-gray-200 shadow-sm hover:bg-[#3d3d3d] hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>
                    </button>
                    <button onclick="popupDetailsElementOpenClose();openPopupMoveElement('.$row['id'].')" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" class="active:scale-95 items-center duration-150 inline-flex justify-center active:scale-95 rounded-2xl duration-150 px-2 py-2 hover:scale-110 font-medium text-[#3d3d3d] dark:text-gray-200 shadow-sm hover:bg-sky-400 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                    </button>
                    <button onclick="updateStatus('.$row['id'].', `3`)" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" class="active:scale-95 items-center gap-2 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl bg-[#3d3d3d] dark:bg-[#2b2b2b] duration-150 px-4 py-2 hover:scale-105 font-medium text-white xs:text-normal text-sm shadow-sm hover:shadow-xl hover:bg-gray-300 hover:text-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.1" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                        </svg>

                        <span class="sm:block hidden">W trakcie</span>
                    </button>
                    <button onclick="updateStatus('.$row['id'].', `2`)" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" class="active:scale-95 items-center gap-2 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl bg-green-400 dark:bg-green-400/20 dark:text-green-400 duration-150 px-4 py-2 hover:scale-105 font-medium text-white shadow-sm xs:text-normal text-sm hover:shadow-xl hover:bg-gray-200 hover:text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>

                        Zrobione!
                    </button>
                    ';
                    break;
                case 2:
                    echo '
                    <button onclick="updateStatus('.$row['id'].', `3`)" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" class="active:scale-95 items-center gap-2 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl bg-[#3d3d3d] dark:bg-[#2b2b2b] duration-150 px-4 py-2 hover:scale-105 font-medium text-white xs:text-normal text-sm shadow-sm hover:shadow-xl hover:bg-gray-300 hover:text-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.1" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                        </svg>

                        <span class="sm:block hidden">W trakcie</span>
                    </button>
                    <button onclick="updateStatus('.$row['id'].', `1`)" class="active:scale-95 items-center gap-2 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl hover:bg-green-400 duration-150 px-4 py-2 hover:scale-105 font-medium hover:text-white xs:text-normal text-sm shadow-sm hover:shadow-xl bg-gray-200 dark:bg-[#3d3d3d] dark:text-gray-300 text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>

                        Zrobione!
                    </button>
                    ';
                    break;
                case 3:
                    echo '
                    <button onclick="popupDetailsElementOpenClose();openPopupEditElement('.$row['id'].')" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" class="active:scale-95 items-center duration-150 inline-flex justify-center active:scale-95 rounded-2xl duration-150 px-2 py-2 hover:scale-110 font-medium text-[#3d3d3d] dark:text-gray-200 shadow-sm hover:bg-[#3d3d3d] hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>
                    </button>
                    <button onclick="popupDetailsElementOpenClose();openPopupMoveElement('.$row['id'].')" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" class="active:scale-95 items-center duration-150 inline-flex justify-center active:scale-95 rounded-2xl duration-150 px-2 py-2 hover:scale-110 font-medium text-[#3d3d3d] dark:text-gray-200 shadow-sm hover:bg-sky-400 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                    </button>
                    <button onclick="updateStatus('.$row['id'].', `1`)" class="active:scale-95 items-center gap-2 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl hover:bg-[#3d3d3d] duration-150 px-4 py-2 hover:scale-105 font-medium hover:text-white xs:text-normal text-sm shadow-sm hover:shadow-xl bg-gray-300 dark:bg-[#3d3d3d] dark:text-gray-300 text-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.1" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                        </svg>

                        <span class="sm:block hidden">W trakcie</span>
                    </button>
                    <button onclick="updateStatus('.$row['id'].', `2`)" style="box-shadow: 0px 5px 15px 0px rgba(66, 68, 90, 0.6);" class="active:scale-95 items-center gap-2 duration-150 inline-flex w-full justify-center active:scale-95 rounded-2xl bg-green-400 dark:bg-green-400/20 dark:text-green-400 duration-150 px-4 py-2 hover:scale-105 font-medium text-white xs:text-normal text-sm shadow-sm hover:shadow-xl hover:bg-gray-200 hover:text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>

                        Zrobione!
                    </button>
                    ';
                    break;
            }
            ?>
    </div>
</div>
<script>
    function updateStatus(element_id, status_id){
        const postData = new FormData();
        postData.append('element_id', element_id);
        postData.append('status_id', status_id);

    // Pokazanie kółka ładowania
    var delivery_loading = document.getElementById('element_loading');
    delivery_loading.innerHTML = "<div class='w-full duartion-150 flex items-center justify-center z-[999]'><div class='z-[30] fixed bg-black/90 p-4 mt-40 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";


    // Wysyłanie żądania POST do skryptu PHP
    fetch('scripts/dashboard/update_status.php', {
        method: 'POST',
        body: postData
    })

    .then(response => response.text())
    .then(text => {
        // Rozbijanie tekstu na fragmenty JSON-owe
        const rawJsonParts = text.trim().split(/(?<=\})\s*(?=\{)/); // Dzielenie na podstawie zakończenia jednego JSON-a i początku następnego

        const jsonObjects = rawJsonParts.map(jsonString => {
            try {
                return JSON.parse(jsonString);  // Parsowanie każdego fragmentu jako JSON
            } catch (e) {
                console.error('Błąd parsowania JSON:', e, 'Fragment:', jsonString);
                return null;
            }
        }).filter(obj => obj !== null);

        var i = 1;

        jsonObjects.forEach(data => {
            if (data.status) {  // Jeśli dane posiadają status
                switch (data.status) {
                    case 'success':
                        showAlert('success', data.message);
                        if(i==1){
                          var list = document.getElementById("list_hold").value;
                          popupDetailsElementOpenClose();
                          openDetailTab('todo', 'list='+list, 'restore_scroll');
                        }
                        break;
                    case 'error':
                        showAlert('error', data.message);
                        break;
                    case 'warning':
                        showAlert('warning', data.message);
                        break;
                    default:
                        showAlert('error', 'Nieznany status odpowiedzi');
                }
            }
            i = i+1;
        });
    })
    .catch(error => {
        showAlert('error', 'Wystąpił problem połączenia z serwerem');
        console.error('Błąd:', error);
    })

    .finally(() => {
        delivery_loading.innerHTML = ""; // Ukrycie kółka ładowania po zakończeniu żądania
    });

    }
</script>