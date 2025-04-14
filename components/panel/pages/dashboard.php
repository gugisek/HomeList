<section id="dashboard_body" data-aos="fade-up" data-aos-delay="100" class="mb-10">
    
</section>
<section onclick="moreMenu()" id="more_menu_bg" class="bg-black/40 z-1 fixed top-0 left-0 h-screen w-screen hidden duration-150"></section>
<div class="flex items-center justify-center">
<section style="box-shadow: 0px 8px 24px 0px rgba(66, 68, 90, 1);" class="bg-[#3d3d3d] font-[poppins] xs:scale-100 scale-90 text-white rounded-2xl fixed sm:bottom-10 bottom-4 flex items-center justify-center">
    
    <section id="more_menu" class="min-w-[200px] absolute bottom-14 z-[99] bg-[#3d3d3d] scale-y-0 w-full flex flex-col py-3 rounded-2xl duration-150">
        <a class="flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <span>Dodaj nową listę</span>
        </a>
        <a class="flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5" />
            </svg>
            <span>Wprowadź kod listy</span>
        </a>
        <a class="flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
            <span>Zmień kolejność</span>
        </a>
        <a onclick="moreMenuClose();openDetailTab(`todo`,`list=archive`)" id="nav_button_details" class="archive flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <span>Archiwum</span>
        </a>
        <a onclick="moreMenu()" class="flex gap-2 py-3 px-6 hover:scale-105 active:scale-95 cursor-pointer hover:bg-gray-300 hover:text-gray-800 duration-150 rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
            <span>Zamknij</span>
        </a>
    </section>
    <?php
    include '../../../scripts/conn_db.php';
    session_start();
    $user_id = $_SESSION['login_id'];
    $sql = "SELECT id, name, full_name FROM `lists` where owner_id='$user_id' ORDER BY `id` asc";
    $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0)
             {
                $i = 0;
                $lenght = mysqli_num_rows($result);
                while($row = mysqli_fetch_assoc($result))
                    {
                        echo '
                            <a onclick="moreMenuClose();openDetailTab(`todo`,`list='.$row['id'].'`)" id="nav_button_details" class="'.$row['id'].' hover:scale-105 active:scale-95 cursor-pointer hover:shadow-xl hover:text-gray-800 focus:scale-95 px-6 py-3 hover:bg-gray-300 duration-150 ';
                            if($i == 0){
                                $first = $row['id'];
                                echo 'rounded-l-2xl';
                            }
                            echo '">'.$row['full_name'].'</a>
                        ';
                        $i++;
                    }
                }
    ?>
    <a onclick="moreMenu()" id="nav_button_details" class="rounded-r-2xl hover:scale-105 cursor-pointer active:scale-95 hover:shadow-xl hover:text-gray-800 focus:scale-95 px-6 py-3 hover:bg-gray-300 duration-150">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
        </svg>

    </a>
    </section>
</div>
<input type="hidden" id="list_hold">

<script>
    function moreMenu() {
        var menu = document.getElementById("more_menu");
        menu.classList.toggle("scale-y-0");
        var menu_bg = document.getElementById("more_menu_bg");
        menu_bg.classList.toggle("hidden");
    }
    function moreMenuClose() {
        var menu = document.getElementById("more_menu");
        if (menu.classList.contains("scale-y-0")) {
            return;
        }
        menu.classList.add("scale-y-0");
        var menu_bg = document.getElementById("more_menu_bg");
        menu_bg.classList.add("hidden");
    }
</script>

<script>
    openDetailTab('todo', 'list=<?=$first?>');

    function openDetailTab(site, link) {
    document.getElementById("list_hold").value = link.split('=')[1];
    var body = document.getElementById("dashboard_body");
    body.innerHTML = "<div data-aos='fade-up' data-aos-delay='100' class='w-full duartion-150 flex items-center mt-[20vh] justify-center z-[999]'><div class='z-[30] bg-black/90 p-4 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";
    const url = "components/panel/dashboard/" + site + ".php?" + link;
    fetch(url)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const parsedDocument = parser.parseFromString(data, "text/html");
            body.innerHTML = parsedDocument.body.innerHTML;
            // Przechodź przez znalezione skrypty i wykonuj je
            const scripts = parsedDocument.querySelectorAll("script");
            // Usunięcie starych skryptów, jeśli istnieją
            document.querySelectorAll(".popup-script").forEach(script => script.remove());
           

            scripts.forEach(script => {
                const scriptElement = document.createElement("script");
                scriptElement.textContent = script.textContent;
                scriptElement.classList.add("popup-script"); // Dodaj klasę, by łatwiej usuwać
                document.body.appendChild(scriptElement);
            });
        });
    var removeButtons = document.querySelectorAll("#nav_button_details");
    for (var i = 0; i < removeButtons.length; i++) {
      removeButtons[i].classList.remove("bg-gray-300");
        removeButtons[i].classList.remove("text-gray-800");
    }
    var activeButtons = document.querySelectorAll("." + link.split('=')[1]);
    for(var i = 0; i < activeButtons.length; i++) {  
      activeButtons[i].classList.add("bg-gray-300");
        activeButtons[i].classList.add("text-gray-800");
    }
}
</script>
<script>
    function updateElement() {
    // Pobranie danych z pól formularza
    var element_id = document.getElementById('edit_element_id').value;
    var name = document.getElementById('edit_element_name').value;
    var description = document.getElementById('edit_element_description').value;
    var deadline = document.getElementById('edit_element_deadline').value;
    const postData = new FormData();
    postData.append('element_id', element_id);
    postData.append('name', name);
    postData.append('description', description);
    postData.append('deadline', deadline);

    // Pokazanie kółka ładowania
    var delivery_loading = document.getElementById('edit_element_loading');
    delivery_loading.innerHTML = "<div class='w-full duartion-150 flex items-center justify-center z-[999]'><div class='z-[30] fixed bg-black/90 p-4 mt-40 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";

    // Wysyłanie żądania POST do skryptu PHP
    fetch('scripts/dashboard/edit.php', {
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
                          popupEditElementOpenClose();
                          openDetailTab('todo', 'list='+list);
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
<script>
    function addElement() {
    // Pobranie danych z pól formularza
    var name = document.getElementById('add_element_name').value;
    var description = document.getElementById('add_element_description').value;
    var deadline = document.getElementById('add_element_deadline').value;
    var list = document.getElementById('add_element_list_name').value;
    const postData = new FormData();
    postData.append('name', name);
    postData.append('description', description);
    postData.append('deadline', deadline);
    postData.append('list', list);

    // Pokazanie kółka ładowania
    var delivery_loading = document.getElementById('add_element_loading');
    delivery_loading.innerHTML = "<div class='w-full duartion-150 flex items-center justify-center z-[999]'><div class='z-[30] fixed bg-black/90 p-4 mt-40 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";

    // Wysyłanie żądania POST do skryptu PHP
    fetch('scripts/dashboard/add.php', {
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
                          popupElementAddOpenClose();
                          openDetailTab('todo', 'list='+list);
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
<script>
    function EditElementDelete() {
    // Pobranie danych z pól formularza
    var id = document.getElementById('id_for_delete_EditElement').value;
    const postData = new FormData();
    postData.append('id', id);
    postData.append('delete', 'true');

    // Pokazanie kółka ładowania
    // var delivery_loading = document.getElementById('delete_element_loading');
    // delivery_loading.innerHTML = "<div class='w-full duartion-150 flex items-center justify-center z-[999]'><div class='z-[30] fixed bg-black/90 p-4 mt-40 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";

    // Wysyłanie żądania POST do skryptu PHP
    fetch('scripts/dashboard/delete.php', {
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
                          popupEditElementOpenClose();
                          popupEditElementDelete()
                          openDetailTab('todo', 'list='+list);
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
        // delivery_loading.innerHTML = ""; // Ukrycie kółka ładowania po zakończeniu żądania
    });
}
</script>

<?php 
$name_in_scripts = 'ElementAdd';
$delete_path = '';
$path = 'components/panel/dashboard/element_add.php';
$docs_text = '';
$docs_path = '';
include "../../popup.php";
?>

<?php 
$name_in_scripts = 'EditElement';
$delete_path = 'scripts/dashboard/delete.php';
$delete_v2 = 'true';
$path = 'components/panel/dashboard/element_edit.php';
$close="";
include "../../popup.php";
?>