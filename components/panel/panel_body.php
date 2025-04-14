<!--  -->

  <!-- Static sidebar for desktop -->
  <?php include 'components/panel/panel_sidebar.php'; ?>

  



  <section style="background-image: url('ximg/users_images/pp-1_1734508643.png');"  class="lg:ml-20 bg-fixed bg-cover bg-center">
      <aside id="panelBody" class=" inset-y-0 w-full min-h-screen overflow-y-auto border-r border-gray-200 bg-gray-50/90 px-4 py-6 sm:px-6 lg:px-8 block">
     
     </aside>

  </section>
</div>




<script>
    function openPanelSite(site, get) {
    var body = document.getElementById("panelBody");
     body.innerHTML = "<div data-aos='fade-up' data-aos-delay='100' class='w-full duartion-150 flex items-center mt-[40vh] justify-center z-[999]'><div class='z-[30] bg-black/90 p-4 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";
    const url = "components/panel/pages/" + site + ".php?" + get;
    fetch(url)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const parsedDocument = parser.parseFromString(data, "text/html");
            body.innerHTML = parsedDocument.body.innerHTML;

            // Wywołaj funkcję do wykonania skryptów
            const scripts = parsedDocument.querySelectorAll("script");
            // Usunięcie starych skryptów, jeśli istnieją
            document.querySelectorAll(".panel-site-script").forEach(script => script.remove());
            document.querySelectorAll(".popup-script").forEach(script => script.remove());

            scripts.forEach(script => {
                const scriptElement = document.createElement("script");
                scriptElement.textContent = script.textContent;
                scriptElement.classList.add("panel-site-script"); // Dodaj klasę, by łatwiej usuwać
                document.body.appendChild(scriptElement);
            });

            // Dodaj nowy wpis do historii przeglądarki
            //const newUrl = window.location.origin + window.location.pathname + "?" + site;
            //history.pushState({ path: newUrl }, "", newUrl);
        });
    // Zapisz URL w localStorage
    //przetłumaczenie site na tekst normalny
    var nazwa = "Lista ToDo w Twoim domu!";

    if(site == 'dashboard'){
      nazwa = "Strona główna"
    } else if(site == 'users'){
      nazwa = "Użytkownicy"
    } else if(site == 'requests'){
      nazwa = "Zatwierdzenia"
    } else if(site == 'raports'){
      nazwa = "Raporty"
    }else if(site == 'doms'){
      nazwa = "Stan magazynowy"
    }else if(site == 'orders'){
      nazwa = "Zamówienia"
    }else if(site == 'docs'){
      nazwa = "Dokumentacja"
    }else if(site == 'logs'){
      nazwa = "Archiwum zamian"
    }else if(site == 'settings'){
      nazwa = "Ustawienia"
    }else if(site == 'devices'){
      nazwa = "Urządzenia"
    }
    document.title = nazwa + " - HomeList - RGBpc.pl";

    localStorage.setItem("PanelSite", site);
    localStorage.setItem("PanelSiteGet", get);
    var removeButtons = document.querySelectorAll("#nav_button");
    for (var i = 0; i < removeButtons.length; i++) {
      removeButtons[i].classList.remove("sidenav-button-active");
    }

    var activeButtons = document.querySelectorAll("." + site);
    for(var i = 0; i < activeButtons.length; i++) {  
      activeButtons[i].classList.add("sidenav-button-active");
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

var panelSite = localStorage.getItem("PanelSite");
var panelSiteGet = localStorage.getItem("PanelSiteGet");
if (panelSite == null) {
    openPanelSite('dashboard');
} else {
    if (panelSiteGet == null) {
        panelSiteGet = "";
    }
    openPanelSite(panelSite, panelSiteGet);
    var removeButtons = document.querySelectorAll("#nav_button");
    for (var i = 0; i < removeButtons.length; i++) {
      removeButtons[i].classList.remove("sidenav-button-active");
    }

    var activeButtons = document.querySelectorAll("." + panelSite);
    for(var i = 0; i < activeButtons.length; i++) {  
      activeButtons[i].classList.add("sidenav-button-active");
    }
}
</script>
<?php include 'components/panel/search.php'; ?>
<script>
  function openSearch() {

       var popup = document.getElementById("searchMain")
       var popupBg = document.getElementById("searchMainBg")
       popupBg.classList.toggle("opacity-0")
       popupBg.classList.toggle("h-0")
       popup.classList.toggle("scale-0")
       popup.classList.add("duration-200")

  }
  
</script>
<script>
    function imgPrevProduct(type) {
        const file = document.getElementById(`${type}`).files[0];
        const reader = new FileReader();
        reader.onloadend = function() {
            //ustawienie dla wszystkich img o id popup_img_inpt src
            document.getElementById(`popup_img_inpt_${type}`).src = reader.result;
        }
        if (file) {
            reader.readAsDataURL(file);
        } else {
            document.getElementById(`popup_img_inpt_${type}`).src = "";
        }
    }
</script>
