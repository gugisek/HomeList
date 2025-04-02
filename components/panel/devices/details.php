<?php
$id = $_GET['id'];
include "../../../scripts/conn_db.php";
$sql = "SELECT rfid_gates.id, gates_models.model, rfid_gates.place, rfid_gates.type from rfid_gates join gates_models on rfid_gates.model_id=gates_models.id where rfid_gates.id = $id";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0)
$row = mysqli_fetch_assoc($result);
?>
<div class="transition-all duration-150">
    <input type="hidden" name="id" value="<?=$id?>">
    <div class="-mt-4 flex gap-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
        </svg>
        <div class="flex flex-row justify-between items-center gap-4 w-full">
            <div>
                <p class="text-sm text-gray-600"><?=$row['place']?></p>
                <h1 class="text-lg font-[poppins] font-medium leading-none"><?=$row['model']?></h1>
            </div>
            <div>
                <!-- <span class="text-sm text-gray-600">Status:</span> -->
                <div class="flex items-center gap-x-3 border border-green-500/50 rounded-full px-3 py-1">
                    <div class="flex-none rounded-full p-1 text-green-500 bg-green-200/50">
                        <div class="h-2 w-2 rounded-full bg-current"></div>
                    </div>
                <h2 class="min-w-0 text-sm font-semibold leading-6">
                    <a href="#" class="flex gap-x-2">
                    <span class="truncate">Online</span>
                    <!-- <span class="text-gray-400">/</span>
                    <span class="whitespace-nowrap">dział</span> -->
                    
                    </a>
                </h2>
                </div>
            </div>
                
                              
            </div>
        </div>
    <section class="min-w-[550px]">
        <div class="border-y border-black/20 text-gray-700 font-[poppins] flex items-center justify-center text-xs my-4 py-2 divide-x divide-black/40">
            <a id="nav_button_details" onclick="openDetailTab('activity', 'category=')" class="activity px-2 text-sky-500 hover:text-sky-600 duration-150 cursor-pointer">
                Aktywność
            </a>
            <a id="nav_button_details" onclick="openDetailTab('archive_prod')" class="archive_prod px-2 hover:text-sky-600 duration-150 cursor-pointer">
                Ostatnie modyfikacje
            </a>
            
            <a id="nav_button_details" onclick="openDetailTab('specs', 'category=')" class="specs px-2 hover:text-sky-600 duration-150 cursor-pointer">
                Specyfikacja
            </a>
            
        </div>
        <div id="details_body">
            
        </div>
        
    </section>
    
    <div class="mt-6 sm:mt-6 mb-2 sm:flex justify-between flex-row-reverse">
        <button type="button" onclick="popupProductsOpenClose();openPanelSite('product_edit','id=<?=$id?>')" class="text-xs px-4 font-semibold leading-6 text-violet-500 hover:text-violet-300 duration-150 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
            Edytuj urządzenie
        </button>
    </div>
</div>
<input type="hidden" id="hold_site">
<script>
if (!window.popupInterval) {
    window.popupInterval = setInterval(refreshActivity, 5000);
}


    function refreshActivity(){
        var site = document.getElementById('hold_site').value;
        if(site == 'activity' ){
        var body = document.getElementById("details_body");
        //  body.innerHTML = "<div data-aos='fade-up' data-aos-delay='100' class='w-full duartion-150 flex items-center mt-[20vh] justify-center z-[999]'><div class='z-[30] bg-black/90 p-4 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";
        const url = "components/panel/devices/details_tabs/activity.php?id=<?=$id?>";
        fetch(url)
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const parsedDocument = parser.parseFromString(data, "text/html");
                body.innerHTML = parsedDocument.body.innerHTML;
            });
        }
    }

    openDetailTab('activity');

    function openDetailTab(site, link) {
        document.getElementById('hold_site').value = site;
    var body = document.getElementById("details_body");
    //  body.innerHTML = "<div data-aos='fade-up' data-aos-delay='100' class='w-full duartion-150 flex items-center mt-[20vh] justify-center z-[999]'><div class='z-[30] bg-black/90 p-4 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";
    const url = "components/panel/devices/details_tabs/" + site + ".php?id=" + <?=$id?> + "&" + link;
    fetch(url)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const parsedDocument = parser.parseFromString(data, "text/html");
            body.innerHTML = parsedDocument.body.innerHTML;
            // Przechodź przez znalezione skrypty i wykonuj je
            const scripts = parsedDocument.querySelectorAll("script");
            // Usunięcie starych skryptów, jeśli istnieją
           

            scripts.forEach(script => {
                const scriptElement = document.createElement("script");
                scriptElement.textContent = script.textContent;
                scriptElement.classList.add("popup-script"); // Dodaj klasę, by łatwiej usuwać
                document.body.appendChild(scriptElement);
            });
        });
    var removeButtons = document.querySelectorAll("#nav_button_details");
    for (var i = 0; i < removeButtons.length; i++) {
      removeButtons[i].classList.remove("text-sky-500");
    }
    var activeButtons = document.querySelectorAll("." + site);
    for(var i = 0; i < activeButtons.length; i++) {  
      activeButtons[i].classList.add("text-sky-500");
    }
}

function executeScripts(parsedDocument) {
    // Przechodź przez znalezione skrypty i wykonuj je
    const scripts = parsedDocument.querySelectorAll("script");
    scripts.forEach(script => {
        const scriptElement = document.createElement("script");
        scriptElement.textContent = script.textContent;
        document.body.appendChild(scriptElement);
    });
}
</script>


<?php 
$name_in_scripts = 'Doms';
$delete_path = 'scripts/products/delete.php';
$path = 'components/panel/doms/details.php';
$close= 'true';
include "../../popup.php";
?>