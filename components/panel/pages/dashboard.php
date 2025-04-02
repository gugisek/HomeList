<section id="dashboard_body" data-aos="fade-up" data-aos-delay="100">
    
</section>
<div data-aos="fade-in" data-aos-delay="200" class="flex items-center justify-center">
<section style="box-shadow: 0px 8px 24px 0px rgba(66, 68, 90, 1);" class="bg-[#3d3d3d] font-[poppins] text-white rounded-full absolute bottom-10 flex items-center justify-center">
    <a onclick="openDetailTab('todo')" id="nav_button_details" class="todo bg-gray-300 text-gray-800 hover:scale-105 cursor-pointer hover:shadow-xl hover:text-gray-800 focus:scale-95 px-8 rounded-l-full py-3 hover:bg-gray-300 duration-150">ToDo</a>
    <!-- <div class="h-[30px] border-r border-white/20 w-[1px]"></div> -->
    <a onclick="openDetailTab('urgent')" id="nav_button_details" class="urgent hover:scale-105 cursor-pointer hover:shadow-xl hover:text-gray-800 focus:scale-95 px-6 py-3 hover:bg-gray-300 duration-150">Pilne</a>
    <a onclick="openDetailTab('projects')" id="nav_button_details" class="projects hover:scale-105 cursor-pointer hover:shadow-xl hover:text-gray-800 focus:scale-95 px-8 rounded-r-full py-3 hover:bg-gray-300 duration-150">Projekty</a>
</section>
</div>

<script>
    openDetailTab('todo');

    function openDetailTab(site, link) {
        
    var body = document.getElementById("dashboard_body");
    body.innerHTML = "<div data-aos='fade-up' data-aos-delay='100' class='w-full duartion-150 flex items-center mt-[20vh] justify-center z-[999]'><div class='z-[30] bg-black/90 p-4 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";
    const url = "components/panel/dashboard/" + site + ".php?id=none&" + link;
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
      removeButtons[i].classList.remove("bg-gray-300");
        removeButtons[i].classList.remove("text-gray-800");
    }
    var activeButtons = document.querySelectorAll("." + site);
    for(var i = 0; i < activeButtons.length; i++) {  
      activeButtons[i].classList.add("bg-gray-300");
        activeButtons[i].classList.add("text-gray-800");
    }
}
</script>