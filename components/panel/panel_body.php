  <!-- boczna pigułka nawigacji (tablet / komputer) -->
  <?php include 'components/panel/panel_sidebar.php'; ?>

  <section>
      <aside id="panelBody" class="inset-y-0 w-full min-h-screen overflow-y-visible bg-[#fdfdfd] dark:bg-[#3d3d3d] px-4 pb-6 sm:px-6 md:pl-[100px] lg:pl-[108px] lg:pr-8 block">

     </aside>
  </section>

  <!-- telefon: powrót z podstron (konto, administracja) do list – na listach jest dolny pasek -->
  <button type="button" id="hl_back_pill" onclick="hlNav(localStorage.getItem('current_todo') || 'list=today')" class="hidden md:!hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-[49] liquid-glass rounded-full pl-3.5 pr-5 py-3 items-center gap-2 text-sm font-medium font-[poppins] text-white/80 hover:text-white active:scale-95 duration-150">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
      Wróć do list
  </button>




<script>
    var HL_SITE_TITLES = { dashboard: "Twoja lista zadań", users: "Użytkownicy", faq: "Pytania na stronie głównej", logs: "Archiwum zmian", reports: "Zgłoszenia błędów", settings: "Ustawienia", profile: "Konto i ustawienia" };

    // after – opcjonalna funkcja wywołana po wstawieniu podstrony i wykonaniu jej skryptów
    function openPanelSite(site, get, after) {
    get = get || "";
    var body = document.getElementById("panelBody");
    body.innerHTML = "<div class='w-full flex items-center mt-[40vh] justify-center'><div><div class='lds-dual-ring'></div></div></div>";
    const url = "components/panel/pages/" + site + ".php?" + get;
    fetch(url)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const parsedDocument = parser.parseFromString(data, "text/html");
            // brak uprawnień do zapamiętanej podstrony (np. po zmianie roli) – wracamy na listy
            if (parsedDocument.querySelector('[data-panel-forbidden]') && site !== 'dashboard') {
                openPanelSite('dashboard', '');
                return;
            }
            body.innerHTML = parsedDocument.body.innerHTML;

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
            // AOS nie zawsze sam zauważa elementy zagnieżdżone głębiej we wstawionej podstronie (np. konto)
            if (window.AOS) AOS.refreshHard();
            if (typeof after === "function") after();
        });

    body.classList.toggle('py-4', site != 'dashboard');
    var back = document.getElementById('hl_back_pill');
    if (back) { back.classList.toggle('hidden', site == 'dashboard'); back.classList.toggle('flex', site != 'dashboard'); }
    document.title = (HL_SITE_TITLES[site] || "Lista ToDo w Twoim domu!") + " - HomeList - RGBpc.pl";

    localStorage.setItem("PanelSite", site);
    localStorage.setItem("PanelSiteGet", get);
    hlRailMenu(false);
    hlRailSync();
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

// ---------- boczna pigułka (components/panel/nav_rail.php) ----------

// przejście do zakładki list (Dziś / lista / archiwum) – z podstrony administracyjnej najpierw wracamy na listy
function hlNav(link) {
    hlRailMenu(false);
    if (document.getElementById("dashboard_body") && typeof openDetailTab === "function" && typeof tabSiteFor === "function") {
        openDetailTab(tabSiteFor(link), link);
        return;
    }
    localStorage.setItem("current_todo", link);
    try { sessionStorage.setItem("hl_started", "1"); } catch (e) {}
    openPanelSite("dashboard", "");
}

// akcja z menu „więcej” (popupy list żyją w pages/dashboard.php)
function hlRailAction(fn) {
    hlRailMenu(false);
    if (document.getElementById("dashboard_body") && typeof window[fn] === "function") {
        window[fn]();
        return;
    }
    try { sessionStorage.setItem("hl_started", "1"); } catch (e) {}
    openPanelSite("dashboard", "", function () {
        if (typeof window[fn] === "function") window[fn]();
    });
}

function hlRailMenu(open) {
    var menu = document.getElementById("rail_menu");
    var btn = document.getElementById("rail_more_btn");
    if (!menu || !btn) return;
    if (open === undefined) open = !menu.classList.contains("is-open");
    menu.classList.toggle("is-open", open);
    btn.classList.toggle("is-open", open);
    btn.setAttribute("aria-expanded", open ? "true" : "false");
}

// podświetlenie aktywnej pozycji: podstrona panelu albo zakładka list
function hlRailSync() {
    var rail = document.getElementById("hl_rail");
    if (!rail) return;
    var key = localStorage.getItem("PanelSite") || "dashboard";
    if (key === "dashboard") {
        var list = new URLSearchParams(localStorage.getItem("current_todo") || "list=today").get("list");
        key = list === "settings" ? "profile" : "list_" + list;
    }
    rail.querySelectorAll("[data-rail]").forEach(function (el) {
        el.classList.toggle("is-active", el.dataset.rail === key);
        // aktywna lista schowana w przewijanej części pigułki – przewijamy do niej
        if (el.dataset.rail === key && el.parentElement.id === "rail_lists") {
            var box = el.parentElement;
            if (el.offsetTop < box.scrollTop || el.offsetTop + el.offsetHeight > box.scrollTop + box.clientHeight) box.scrollTop = el.offsetTop - box.clientHeight / 2;
        }
    });
}

// wygaszone krawędzie listy list, gdy jest co przewijać
function hlRailFade() {
    var box = document.getElementById("rail_lists");
    if (!box) return;
    if (!box.dataset.fadeBound) {
        box.dataset.fadeBound = "1";
        box.addEventListener("scroll", hlRailFade, { passive: true });
    }
    box.classList.toggle("fade-top", box.scrollTop > 2);
    box.classList.toggle("fade-bottom", box.scrollTop + box.clientHeight < box.scrollHeight - 2);
}
window.addEventListener("resize", hlRailFade);

// kolejność list z „Edytuj listy” (ten sam klucz co storageKey w pages/dashboard.php)
function hlRailOrder() {
    var box = document.getElementById("rail_lists");
    if (!box) return;
    var order = null;
    try { order = JSON.parse(localStorage.getItem("todoOrder_<?=(int)$_SESSION['login_id']?>")); } catch (e) {}
    if (Array.isArray(order)) order.forEach(function (id) {
        var el = box.querySelector('[data-list-id="' + id + '"]');
        if (el) box.appendChild(el);
    });
    hlRailFade();
}

// po dodaniu / zmianie nazwy / usunięciu listy
function hlRailRefresh() {
    var rail = document.getElementById("hl_rail");
    if (!rail) return;
    fetch("components/panel/nav_rail.php")
        .then(response => response.text())
        .then(html => {
            var fresh = new DOMParser().parseFromString(html, "text/html").getElementById("hl_rail");
            if (!fresh) return;
            fresh.style.animation = "none";
            rail.replaceWith(fresh);
            hlRailOrder();
            hlRailSync();
        });
}

document.addEventListener("click", function (e) {
    var menu = document.getElementById("rail_menu");
    if (menu && menu.classList.contains("is-open") && !e.target.closest(".hl-rail-more")) hlRailMenu(false);
});
document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") hlRailMenu(false);
});
hlRailOrder();

var panelSite = localStorage.getItem("PanelSite");
var panelSiteGet = localStorage.getItem("PanelSiteGet");
// panel.php?site=reports (np. link z maila) – otwiera wskazaną podstronę zamiast zapamiętanej
var panelSiteUrl = new URLSearchParams(window.location.search).get("site");
if (panelSiteUrl && /^[a-z_]+$/.test(panelSiteUrl)) {
    panelSite = panelSiteUrl;
    panelSiteGet = "";
    history.replaceState(null, "", window.location.pathname);
}
openPanelSite(panelSite || 'dashboard', panelSiteGet || "");
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
