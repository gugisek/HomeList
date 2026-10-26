<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
?>
<section id="dashboard_body" data-aos="fade-up" data-aos-delay="100" class="mb-10 max-w-5xl mx-auto">
    
</section>
<section onclick="moreMenu()" id="more_menu_bg" class="hl-scrim z-[48] md:hidden"></section>
<div id="lists" class="flex items-center justify-center z-50">
<?php include '../dashboard/lists.php'; ?>
</div>
<input type="hidden" id="list_hold">

<script>
    
    var storageKey = "todoOrder_<?php echo $_SESSION['login_id']; ?>";

    function applyTodoNavOrder(body) {
        const container = document.getElementById(body);
        const savedOrder = JSON.parse(localStorage.getItem(storageKey));
        if (!savedOrder || !Array.isArray(savedOrder)) return;

        savedOrder.forEach(id => {
            const el = container.querySelector(`.list_${id}`);
            if (el) container.appendChild(el);
        });
        //dla elementów powyżej 3 ukryj je
        const items = container.querySelectorAll(".lists_main_nav");
        if (body == "more_nav_body") {
            items.forEach((item, index) => {
            if (index < 3) {
                item.classList.add("hidden");
            } else {
                item.classList.remove("hidden");
            }
        });
        }else{
        items.forEach((item, index) => {
            if (index > 2) {
                item.classList.add("hidden");
            } else {
                item.classList.remove("hidden");
            }
        });
        }
    }
    function hightlight3Element(){
        const container = document.getElementById("items-container");
        const items = container.querySelectorAll(".lists_lenght");
        items.forEach(item => {
            item.classList.remove("border_important");
        });
        if (items.length > 2) {
            items[2].classList.add("border_important");
        }
    }
</script>

<script>
function fixRoundedCorners() {
    // navbar jest pigułką – każdy element ma rounded-full, więc tylko czyścimy stare zaokrąglenia
    const container = document.getElementById("todo-nav");
    const items = container.querySelectorAll("a");
    items.forEach(item => {
        item.classList.remove("rounded-l-2xl", "rounded-r-2xl");
    });
}


</script>
<script>
applyTodoNavOrder('todo-nav');
applyTodoNavOrder('more_nav_body');
fixRoundedCorners();
</script>
<script>
    // menu „więcej” dolnego paska (telefon): szkło wysuwa się nad pigułką, kropki zamieniają się w X
    function moreMenu(open) {
        var menu = document.getElementById("more_menu");
        if (!menu) return;
        if (open === undefined) open = !menu.classList.contains("is-open");
        menu.classList.toggle("is-open", open);
        document.getElementById("more_menu_bg")?.classList.toggle("is-open", open);
        document.getElementById("more_btn")?.classList.toggle("is-open", open);
    }
    function moreMenuClose() {
        moreMenu(false);
    }
</script>

<script>
    
    function openFirstList() {
    // Po wejściu do aplikacji startujemy od planu dnia, przy przeładowaniu panelu w tej samej sesji wracamy do ostatniej zakładki
    var started = null;
    try { started = sessionStorage.getItem("hl_started"); } catch (e) {}
    try { sessionStorage.setItem("hl_started", "1"); } catch (e) {}

    var current_todo = localStorage.getItem("current_todo");
    if (!started || current_todo === null) {
        openDetailTab('today', 'list=today');
    } else {
        openDetailTabReload();
    }
}

    // która strona z components/panel/dashboard obsługuje dany link zakładki
    function tabSiteFor(link) {
        var list = new URLSearchParams(link).get('list');
        if (list === 'today') return 'today';
        if (list === 'settings') return 'settings_app';
        return 'todo';
    }

    openFirstList();

    function openDetailTab(site, link, restore_scroll = null) {

        if(restore_scroll === 'restore_scroll'){
                //pobranie wartości scrolla przed odświeżeniem od elementu html o tagu html
                var body_scroll = document.getElementsByTagName("html")[0];
                var scrollPosition = body_scroll.scrollTop;
                console.log("Current scroll position: " + scrollPosition);

        }else {
            var body_scroll = document.getElementsByTagName("html")[0];
        }

    var tab_list = new URLSearchParams(link).get('list');
    document.getElementById("list_hold").value = tab_list;
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
    var removeButtons = document.querySelectorAll(".lists_main_nav");
    for (var i = 0; i < removeButtons.length; i++) {
      removeButtons[i].classList.remove("bg-white/15");
        removeButtons[i].classList.remove("text-green-400");
    }
    var activeButtons = document.querySelectorAll(".lists_main_nav.list_" + tab_list);
    for(var i = 0; i < activeButtons.length; i++) {
      activeButtons[i].classList.add("bg-white/15");
        activeButtons[i].classList.add("text-green-400");
    }

    localStorage.setItem("current_todo", link);
    if (typeof hlRailSync === "function") hlRailSync();
    if(restore_scroll === 'restore_scroll'){
        // Przywrócenie pozycji scrolla po odświeżeniu
        // poczekaj 150ms na animacje
        setTimeout(() => {
            body_scroll.scrollTop = scrollPosition;
            console.log("Restored scroll position: " + scrollPosition);
        }, 500);
    }else {
        //scroll to top
        //poczekaj 150ms na animacje
        setTimeout(() => {
            body_scroll.scrollTop = 0; // For Safari
            body_scroll.scrollTo(0, 0); // For Chrome, Firefox, IE and Opera
            console.log("Restored scroll position: " + 0);
        }, 10);
    }
}

    function openDetailTabReload() {
    var current_todo = localStorage.getItem("current_todo");
    if (current_todo) {
        // plan dnia zawsze otwieramy na dziś, a nie na dzień oglądany ostatnio
        if (tabSiteFor(current_todo) === 'today') current_todo = 'list=today';
        openDetailTab(tabSiteFor(current_todo), current_todo);
    }
}

    // odświeżenie bieżącej zakładki (lista / plan dnia / ustawienia)
    function refreshCurrentTab(restore_scroll = null) {
        var link = localStorage.getItem("current_todo") || 'list=today';
        if (tabSiteFor(link) === 'today') {
            reloadTabSilently();
        } else if (tabSiteFor(link) === 'todo') {
            hlMorphTab();
        } else {
            openDetailTab(tabSiteFor(link), link, restore_scroll);
        }
    }

    // przeładowanie zakładki bez spinnera i animacji, z zachowaniem scrolla
    function reloadTabSilently() {
        var link = localStorage.getItem("current_todo") || 'list=today';
        var body = document.getElementById("dashboard_body");
        var html = document.getElementsByTagName("html")[0];
        var scroll = html.scrollTop;
        return fetch("components/panel/dashboard/" + tabSiteFor(link) + ".php?" + link)
            .then(response => response.text())
            .then(data => {
                var parsedDocument = new DOMParser().parseFromString(data, "text/html");
                parsedDocument.querySelectorAll("[data-aos]").forEach(el => el.removeAttribute("data-aos"));
                body.innerHTML = parsedDocument.body.innerHTML;
                document.querySelectorAll(".popup-script").forEach(script => script.remove());
                parsedDocument.querySelectorAll("script").forEach(script => {
                    var scriptElement = document.createElement("script");
                    scriptElement.textContent = script.textContent;
                    scriptElement.classList.add("popup-script");
                    document.body.appendChild(scriptElement);
                });
                html.scrollTop = scroll;
            });
    }

    // aktualizacja listy zadań w miejscu: bez spinnera i skoku scrolla; podmieniany jest tylko nagłówek
    // i #hl_morph_list (popup szczegółów w tej samej zakładce dokańcza animację zamknięcia)
    function hlMorphTab() {
        var link = localStorage.getItem("current_todo") || 'list=today';
        var oldList = document.getElementById('hl_morph_list');
        if (!oldList) return reloadTabSilently();
        var seq = window.hlMorphSeq = (window.hlMorphSeq || 0) + 1;
        // chwila na zamknięcie popupu, żeby zmiana była widoczna, a nie schowana pod tłem
        var wait = new Promise(resolve => setTimeout(resolve, 180));
        var load = fetch("components/panel/dashboard/" + tabSiteFor(link) + ".php?" + link).then(response => response.text());
        return Promise.all([load, wait])
            .then(([data]) => {
                if (seq !== window.hlMorphSeq || localStorage.getItem("current_todo") !== link || !document.body.contains(oldList)) return;
                var parsedDocument = new DOMParser().parseFromString(data, "text/html");
                var newList = parsedDocument.getElementById('hl_morph_list');
                if (!newList) return reloadTabSilently();
                var oldHead = document.getElementById('sticky_nav_body');
                var newHead = parsedDocument.getElementById('sticky_nav_body');
                if (oldHead && newHead && oldHead.innerHTML !== newHead.innerHTML) oldHead.innerHTML = newHead.innerHTML;
                return hlMorphList(oldList, newList);
            })
            .catch(error => {
                console.error('Błąd odświeżania listy:', error);
                reloadTabSilently();
            });
    }

    // podmiana elementów z data-key: usunięte się zwijają, nowe wjeżdżają z poświatą,
    // zmienione błyskają, a przesunięte płyną na nowe miejsce (FLIP)
    function hlMorphList(oldUl, newUl) {
        var keyed = ul => {
            var map = {};
            ul.querySelectorAll('[data-key]').forEach(el => { map[el.dataset.key] = el; });
            return map;
        };
        var oldItems = keyed(oldUl);
        var newItems = keyed(newUl);
        var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var removed = Object.keys(oldItems).filter(key => !newItems[key]);

        removed.forEach(key => {
            var el = oldItems[key];
            el.style.maxHeight = el.offsetHeight + 'px';
            el.style.pointerEvents = 'none';
            requestAnimationFrame(() => el.classList.add('hl-morph-out'));
        });

        return new Promise(resolve => setTimeout(resolve, removed.length && !calm ? 340 : 0)).then(() => {
            var rects = {};
            var before = {};
            Object.keys(oldItems).forEach(key => {
                if (!newItems[key]) return;
                rects[key] = oldItems[key].getBoundingClientRect();
                before[key] = hlMorphSig(oldItems[key]);
            });
            var hadItems = Object.keys(oldItems).length > 0;

            oldUl.innerHTML = newUl.innerHTML;
            if (calm) return;

            oldUl.querySelectorAll('[data-key]').forEach(el => {
                var key = el.dataset.key;
                if (!rects[key]) {
                    if (hadItems) hlMorphMark(el, 'hl-morph-in');
                    return;
                }
                if (before[key] !== hlMorphSig(el)) hlMorphMark(el, 'hl-morph-changed');
                var now = el.getBoundingClientRect();
                var dx = rects[key].left - now.left;
                var dy = rects[key].top - now.top;
                if (Math.abs(dx) < 1 && Math.abs(dy) < 1) return;
                el.style.transition = 'none';
                el.style.transform = 'translate(' + dx + 'px, ' + dy + 'px)';
                el.offsetWidth;
                el.style.transition = 'transform .5s cubic-bezier(.16, 1, .3, 1)';
                el.style.transform = '';
                setTimeout(() => { el.style.transition = ''; }, 520);
            });
        });
    }

    // treść elementu bez śladów animacji (klasy hl-morph-*, style FLIP), żeby dało się porównać stary z nowym
    function hlMorphSig(el) {
        var clone = el.cloneNode(true);
        clone.classList.remove('hl-morph-in', 'hl-morph-changed');
        clone.removeAttribute('style');
        return clone.outerHTML;
    }

    function hlMorphMark(el, cls) {
        el.classList.add(cls);
        setTimeout(() => el.classList.remove(cls), 1600);
    }

    // kilka szybkich zmian (np. planowanie z backlogu) = jedno przeładowanie
    function hlDebouncedReload() {
        clearTimeout(window.hlReloadTimer);
        window.hlReloadTimer = setTimeout(reloadTabSilently, 900);
    }

    // POST + parsowanie odpowiedzi (może zawierać kilka sklejonych JSON-ów)
    function hlPost(url, data) {
        return fetch(url, { method: 'POST', body: data })
            .then(response => response.text())
            .then(text => text.trim().split(/(?<=\})\s*(?=\{)/).map(jsonString => {
                try {
                    return JSON.parse(jsonString);
                } catch (e) {
                    console.error('Błąd parsowania JSON:', e, 'Fragment:', jsonString);
                    return null;
                }
            }).filter(obj => obj !== null));
    }

    function hlEnsureSortable(callback) {
        if (typeof Sortable !== 'undefined') {
            callback();
            return;
        }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
        s.onload = callback;
        document.head.appendChild(s);
    }

    // przełączniki (chipy) – te same klasy co hl_chip_class() w planner_lib.php
    var HL_CHIP_ON = ['bg-[#1c1c1e]', 'text-white', 'dark:bg-white', 'dark:text-black', 'shadow-lg'];
    var HL_CHIP_OFF = ['bg-gray-100/80', 'dark:bg-[#3d3d3d]', 'text-gray-700', 'dark:text-gray-300'];
    function hlChip(el, on) {
        el.classList.remove(...(on ? HL_CHIP_OFF : HL_CHIP_ON));
        el.classList.add(...(on ? HL_CHIP_ON : HL_CHIP_OFF));
        el.dataset.on = on ? '1' : '0';
    }
    function hlChipSelect(el) {
        el.parentElement.querySelectorAll('.hl-chip').forEach(c => hlChip(c, c === el));
    }



</script>
<script>
    function updateElement() {
    // Pobranie danych z pól formularza
    var element_id = document.getElementById('edit_element_id').value;
    var name = document.getElementById('edit_element_name').value;
    var description = document.getElementById('edit_element_description').value;
    var deadline = document.getElementById('edit_element_deadline') ? document.getElementById('edit_element_deadline').value : '';
    var link_url = document.getElementById('edit_element_link_url') ? document.getElementById('edit_element_link_url').value : '';
    const postData = new FormData();
    postData.append('element_id', element_id);
    postData.append('name', name);
    postData.append('description', description);
    postData.append('deadline', deadline);
    postData.append('link_url', link_url);
    // pola rutyny (tylko w formularzu listy rutyn)
    if (document.getElementById('edit_element_recurrence_type')) {
        var routine_error = rfCollect('edit', postData);
        if (routine_error) {
            showAlert('warning', routine_error);
            return;
        }
    }

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
                          refreshCurrentTab();
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


function moveElement() {
    // Pobranie danych z pól formularza
    var element_id = document.getElementById('move_element_id').value;
    var list_id = document.getElementById('move_element_list').value;
    const postData = new FormData();
    postData.append('element_id', element_id);
    postData.append('list_id', list_id);

    // Pokazanie kółka ładowania
    var delivery_loading = document.getElementById('move_element_loading');
    delivery_loading.innerHTML = "<div class='w-full duartion-150 flex items-center justify-center z-[999]'><div class='z-[30] fixed bg-black/90 p-4 mt-40 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";

    // Wysyłanie żądania POST do skryptu PHP
    fetch('scripts/dashboard/move.php', {
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
                          popupMoveElementOpenClose();
                          refreshCurrentTab();
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

    function refreshLists() {
    var body = document.getElementById("lists");
    
    const url = "components/panel/dashboard/lists.php";
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
            applyTodoNavOrder('todo-nav');
            applyTodoNavOrder('more_nav_body');
            fixRoundedCorners();
        });
    if (typeof hlRailRefresh === "function") hlRailRefresh();
    }

    function addList() {
    // Pobranie danych z pól formularza
    var name = document.getElementById('add_list_name').value;
    var type = document.getElementById('add_list_type') ? document.getElementById('add_list_type').value : 'normal';

    const postData = new FormData();
    postData.append('name', name);
    postData.append('type', type);

    // Pokazanie kółka ładowania
    var delivery_loading = document.getElementById('add_list_loading');
    delivery_loading.innerHTML = "<div class='w-full duartion-150 flex items-center justify-center z-[999]'><div class='z-[30] fixed bg-black/90 p-4 mt-40 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";

    // Wysyłanie żądania POST do skryptu PHP
    fetch('scripts/dashboard/list_add.php', {
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
                          var list = data.id;
                          if(list == null || list == ""){
                            list = document.getElementById("list_hold").value;
                          }
                          popupAddListsOpenClose();
                          openDetailTab('todo', 'list='+list);
                          refreshLists();
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
function codeList() {
    // Pobranie danych z pól formularza
    var name = document.getElementById('add_list_code').value;

    const postData = new FormData();
    postData.append('code', name);

    // Pokazanie kółka ładowania
    var delivery_loading = document.getElementById('code_list_loading');
    delivery_loading.innerHTML = "<div class='w-full duartion-150 flex items-center justify-center z-[999]'><div class='z-[30] fixed bg-black/90 p-4 mt-40 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";

    // Wysyłanie żądania POST do skryptu PHP
    fetch('scripts/dashboard/list_code.php', {
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
                          var list = data.id;
                          if(list == null || list == ""){
                            list = document.getElementById("list_hold").value;
                          }
                          popupCodeListsOpenClose();
                          openDetailTab('todo', 'list='+list);
                          refreshLists();
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
    var deadline = document.getElementById('add_element_deadline') ? document.getElementById('add_element_deadline').value : '';
    var list = document.getElementById('add_element_list_name').value;
    var link_url = document.getElementById('add_element_link_url') ? document.getElementById('add_element_link_url').value : '';
    const postData = new FormData();
    postData.append('name', name);
    postData.append('description', description);
    postData.append('deadline', deadline);
    postData.append('list', list);
    postData.append('link_url', link_url);
    // pola rutyny (tylko w formularzu listy rutyn)
    if (document.getElementById('add_element_recurrence_type')) {
        var routine_error = rfCollect('add', postData);
        if (routine_error) {
            showAlert('warning', routine_error);
            return;
        }
    }

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
                          refreshCurrentTab();
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

function copyElement() {
    // Pobranie danych z pól formularza
    var name = document.getElementById('add_element_name').value;
    var description = document.getElementById('add_element_description').value;
    var deadline = document.getElementById('add_element_deadline').value;
    var list = document.getElementById('add_element_list_name').value;
    var link_url = document.getElementById('add_element_link_url') ? document.getElementById('add_element_link_url').value : '';
    const postData = new FormData();
    postData.append('name', name);
    postData.append('description', description);
    postData.append('deadline', deadline);
    postData.append('list', list);
    postData.append('link_url', link_url);

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
                          popupCopyElementOpenClose();
                          refreshCurrentTab();
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
                          refreshCurrentTab();
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


function copyClip(input) {
    var copyText = document.getElementById(input);
    copyText.select();
    copyText.setSelectionRange(0, 99999); // Dla mobilnych urządzeń
    navigator.clipboard.writeText(copyText.value);
    showAlert('success', 'Skopiowano kod do schowka!');
}


function updateListName() {
    // Pobranie danych z pól formularza
    var id = document.getElementById('edit_list_id').value;
    var name = document.getElementById('edit_list_name').value;
    const postData = new FormData();
    postData.append('id', id);
    postData.append('name', name);

    // Pokazanie kółka ładowania
    var delivery_loading = document.getElementById('info_list_loading');
    delivery_loading.innerHTML = "<div class='w-full duartion-150 flex items-center justify-center z-[999]'><div class='z-[30] fixed bg-black/90 p-4 mt-40 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";

    // Wysyłanie żądania POST do skryptu PHP
    fetch('scripts/dashboard/list_edit.php', {
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
                          
                          popupInfoListsOpenClose();
                          openPanelSite(`dashboard`); hlRailRefresh();
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

<?php 
$name_in_scripts = 'ElementAdd';
$delete_path = '';
$delete_v2 = '';
$path = 'components/panel/dashboard/element_add.php';
$docs_text = '';
$docs_path = '';
include "../../popup_center.php";
?>

<?php 
$name_in_scripts = 'EditElement';
$delete_path = 'scripts/dashboard/delete.php';
$delete_v2 = 'true';
$path = 'components/panel/dashboard/element_edit.php';
$close="";
include "../../popup_center.php";
?>

<?php 
$name_in_scripts = 'MoveElement';
$delete_path = '';
$delete_v2 = 'true';
$path = 'components/panel/dashboard/element_move.php';
$close="true";
include "../../popup_center.php";
?>

<?php 
$name_in_scripts = 'EditLists';
$delete_path = '';
$delete_v2 = 'true';
$path = 'components/panel/dashboard/lists_edit.php';
$close="true";
include "../../popup.php";
?>

<?php 
$name_in_scripts = 'AddLists';
$delete_path = '';
$delete_v2 = 'true';
$path = 'components/panel/dashboard/lists_add.php';
$close="";
include "../../popup.php";
?>

<?php 
$name_in_scripts = 'CopyElement';
$delete_path = '';
$delete_v2 = 'true';
$path = 'components/panel/dashboard/element_add.php';
$close="";
include "../../popup_variables.php";
?>

<?php 
$name_in_scripts = 'CodeLists';
$delete_path = '';
$delete_v2 = 'true';
$path = 'components/panel/dashboard/lists_code.php';
$close="true";
include "../../popup.php";
?>

<?php
$name_in_scripts = 'PlanSheet';
$delete_path = '';
$delete_v2 = '';
$path = 'components/panel/dashboard/plan_sheet.php';
$close = "true";
include "../../popup_center.php";
?>

<?php
$name_in_scripts = 'Backlog';
$delete_path = '';
$delete_v2 = '';
$path = 'components/panel/dashboard/backlog.php';
$close = "true";
include "../../popup_center.php";
?>

<?php
$name_in_scripts = 'Month';
$delete_path = '';
$delete_v2 = '';
$path = 'components/panel/dashboard/month.php';
$close = "true";
include "../../popup_center.php";
?>

<?php
$name_in_scripts = 'TodayAdd';
$delete_path = '';
$delete_v2 = '';
$path = 'components/panel/dashboard/today_add.php';
$close = "true";
include "../../popup_center.php";
?>

<?php
$name_in_scripts = 'InfoLists';
$delete_path = 'scripts/dashboard/list_delete.php';
$delete_v2 = 'true';
$path = 'components/panel/dashboard/lists_info.php';
$close="true";
include "../../popup_l2.php";
?>

