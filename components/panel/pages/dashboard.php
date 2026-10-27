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
        // na pasku mieszczą się 3 listy (2, gdy jest też ikona Jedzenia) – reszta w menu „więcej”
        const navBox = document.getElementById("todo-nav");
        const max = navBox && navBox.dataset.max ? +navBox.dataset.max : 3;
        const items = container.querySelectorAll(".lists_main_nav:not(.list_food)");
        if (body == "more_nav_body") {
            items.forEach((item, index) => {
            if (index < max) {
                item.classList.add("hidden");
            } else {
                item.classList.remove("hidden");
            }
        });
        }else{
        items.forEach((item, index) => {
            if (index >= max) {
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
        // ostatnia lista mieszcząca się na pasku (3, a przy ikonie Jedzenia 2)
        const navBox = document.getElementById("todo-nav");
        const max = navBox && navBox.dataset.max ? +navBox.dataset.max : 3;
        if (items.length >= max) {
            items[max - 1].classList.add("border_important");
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
    // Synchronizacja na żywo (lista wspólna, plan dnia, drugie urządzenie). Widok niesie token stanu
    // (<meta id="hl_sync">, scripts/sync/sync_lib.php), a hlSync co kilka sekund pyta lekki
    // scripts/sync/state.php, czy token się zmienił – dopiero wtedy widok jest aktualizowany w miejscu.
    // Pyta tylko przy widocznej karcie: często na listach wspólnych, rzadko na prywatnych i przy bezczynności,
    // od razu po powrocie aplikacji z tła. Skrypt może się wykonać ponownie (openPanelSite), więc stan
    // trzymamy w window.hlSync, timer jest jeden, a nasłuchy na window/document podpinamy raz.
    window.hlSync = window.hlSync || { scope: null, token: null, nav: null, shared: false, timer: 0, applyTimer: 0, due: 0, ctrl: null, seq: 0, fails: 0, writes: 0, lastWrite: 0, lastCheck: 0, lastInput: Date.now(), lastRemote: 0 };

    function hlSyncReset() {
        var s = window.hlSync;
        s.scope = s.token = null;
        s.seq++;
        if (s.ctrl) s.ctrl.abort();
        s.ctrl = null;
        clearTimeout(s.timer);
        clearTimeout(s.applyTimer);
    }

    // token z właśnie wyrenderowanego widoku (bez znacznika = zakładka bez synchronizacji, np. ustawienia)
    function hlSyncAdopt(doc) {
        var s = window.hlSync;
        var tag = doc && doc.getElementById('hl_sync');
        if (!tag) return hlSyncReset();
        if (tag.dataset.scope !== s.scope) hlSyncReset();
        s.seq++; // odpowiedź sprzed renderu niesie starszy stan – nie może wywołać ponownego odświeżenia
        s.scope = tag.dataset.scope;
        s.token = tag.dataset.token;
        s.shared = tag.dataset.shared === '1';
        if (!s.nav) s.nav = tag.dataset.nav;
        hlSyncSchedule();
    }

    function hlSyncActive() {
        return !!(window.hlSync.scope && document.visibilityState === 'visible' && navigator.onLine !== false && document.getElementById('dashboard_body'));
    }

    function hlSyncDelay() {
        var s = window.hlSync;
        var now = Date.now();
        if (s.fails) return Math.min(60000, 4000 * Math.pow(2, s.fails));
        var idle = now - s.lastInput;
        var delay = s.shared ? 5000 : 30000;
        if (s.shared && now - s.lastRemote < 90000) delay = 3000; // ktoś właśnie coś zmienia – śledzimy ściślej
        if (idle > 600000) delay = Math.max(delay, 60000);
        else if (idle > 120000) delay = Math.max(delay, s.shared ? 15000 : 45000);
        return delay;
    }

    function hlSyncSchedule(delay) {
        var s = window.hlSync;
        clearTimeout(s.timer);
        s.timer = 0;
        if (!hlSyncActive()) return;
        if (delay === undefined) delay = hlSyncDelay();
        s.due = Date.now() + delay;
        s.timer = setTimeout(hlSyncCheck, delay);
    }

    // sprawdzenie „wkrótce”, chyba że i tak jest zaplanowane wcześniej
    function hlSyncSoon(delay) {
        var s = window.hlSync;
        if (s.ctrl || (s.timer && s.due - Date.now() <= delay)) return;
        hlSyncSchedule(delay);
    }

    function hlSyncCheck() {
        var s = window.hlSync;
        clearTimeout(s.timer);
        s.timer = 0;
        if (!hlSyncActive() || s.ctrl) return;
        var seq = s.seq;
        var scope = s.scope;
        var ctrl = s.ctrl = new AbortController();
        s.lastCheck = Date.now();
        fetch('scripts/sync/state.php?scope=' + encodeURIComponent(scope), { signal: ctrl.signal, cache: 'no-store' })
            .then(response => {
                if (response.status === 401 || response.status === 403) {
                    // sesja wygasła – wrapper fetch (csrf_js.php) przeniesie do logowania
                    s.scope = null;
                    return null;
                }
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                if (!data || seq !== s.seq || scope !== s.scope) return;
                s.fails = 0;
                s.shared = !!data.s;
                if (data.n && s.nav && data.n !== s.nav) {
                    s.nav = data.n;
                    refreshLists();
                }
                if (data.t !== s.token) {
                    s.lastRemote = Date.now();
                    hlSyncApply();
                }
            })
            .catch(error => {
                if (error.name !== 'AbortError') s.fails++;
            })
            .finally(() => {
                if (s.ctrl !== ctrl) return;
                s.ctrl = null;
                hlSyncSchedule();
            });
    }

    // w trakcie własnej akcji, przeciągania albo przełączania dnia nie podmieniamy widoku pod palcami
    function hlSyncBusy() {
        var s = window.hlSync;
        return s.writes > 0 || Date.now() - s.lastWrite < 700 || window.feedDragging || window.feedNavBusy
            || !!document.querySelector('.sortable-chosen, .sortable-drag');
    }

    // pełna podmiana zakładki skasowałaby otwarty popup albo wpisywany tekst
    function hlSyncCanReplace() {
        var active = document.activeElement;
        var typing = active && active.matches('input, textarea, select, [contenteditable]') && active.closest('#dashboard_body');
        return !typing && !document.querySelector('section[id^="popup"][id$="Bg"]:not(.h-0)');
    }

    function hlSyncApply() {
        var s = window.hlSync;
        clearTimeout(s.applyTimer);
        if (!s.scope) return;
        if (hlSyncBusy()) {
            s.applyTimer = setTimeout(hlSyncApply, 800);
            return;
        }
        var site = tabSiteFor(localStorage.getItem("current_todo") || 'list=today');
        if (site === 'todo') hlMorphTab();
        else if (site === 'today') hlMorphFeed();
        else if (site === 'food' && window.hlFood) window.hlFood.refresh();
    }

    // powrót z tła / z innej karty / po odzyskaniu sieci – sprawdzamy od razu
    function hlSyncWake() {
        var s = window.hlSync;
        s.lastInput = Date.now();
        if (!hlSyncActive()) return hlSyncSchedule();
        hlSyncSoon(Date.now() - s.lastCheck > 1500 ? 0 : 1500);
    }

    if (!window.hlSyncBound) {
        window.hlSyncBound = true;
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') hlSyncWake();
            else clearTimeout(window.hlSync.timer);
        });
        window.addEventListener('focus', () => hlSyncWake());
        window.addEventListener('online', () => hlSyncWake());
        window.addEventListener('pageshow', e => { if (e.persisted) hlSyncWake(); });
        document.addEventListener('resume', () => hlSyncWake());
        // użytkownik wrócił do aplikacji po bezczynności – nie czekamy na długi interwał
        var onInput = () => {
            var s = window.hlSync;
            var wasIdle = Date.now() - s.lastInput > 120000;
            s.lastInput = Date.now();
            if (wasIdle && s.timer) hlSyncSoon(hlSyncDelay());
        };
        document.addEventListener('pointerdown', onInput, { capture: true, passive: true });
        document.addEventListener('keydown', onInput, { capture: true, passive: true });

        // własne zapisy (POST): w trakcie nie podmieniamy widoku, a po nich dajemy znać innym kartom tej przeglądarki
        try {
            window.hlSyncChannel = new BroadcastChannel('hl-sync');
            window.hlSyncChannel.onmessage = () => hlSyncSoon(400);
        } catch (e) {}
        var baseFetch = window.fetch;
        window.fetch = function (input, init) {
            var method = ((init && init.method) || (input instanceof Request ? input.method : 'GET')).toUpperCase();
            if (method === 'GET' || method === 'HEAD') return baseFetch.apply(this, arguments);
            var s = window.hlSync;
            s.writes++;
            var settle = () => {
                s.writes = Math.max(0, s.writes - 1);
                s.lastWrite = Date.now();
                try { window.hlSyncChannel && window.hlSyncChannel.postMessage(1); } catch (e) {}
            };
            return baseFetch.apply(this, arguments).then(response => { settle(); return response; }, error => { settle(); throw error; });
        };
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
        if (list === 'food') return 'food';
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
    var from_site = tabSiteFor(localStorage.getItem("current_todo") || '');
    var seq = window.hlTabSeq = (window.hlTabSeq || 0) + 1;
    document.getElementById("list_hold").value = tab_list;
    hlSyncReset();
    if (restore_scroll !== 'restore_scroll' && site !== from_site && hlTabSwapPair(site, from_site)) {
        hlNavHighlight(tab_list);
        localStorage.setItem("current_todo", link);
        if (typeof hlRailSync === "function") hlRailSync();
        return hlTabSwap(site, link, seq);
    }
    var body = document.getElementById("dashboard_body");
    body.innerHTML = "<div data-aos='fade-up' data-aos-delay='100' class='w-full duartion-150 flex items-center mt-[20vh] justify-center z-[999]'><div class='z-[30] bg-black/90 p-4 rounded-2xl'><div class='lds-dual-ring'></div></div></div>";
    const url = "components/panel/dashboard/" + site + ".php?" + link;
    fetch(url)
        .then(response => response.text())
        .then(data => {
            if (seq !== window.hlTabSeq) return; // w międzyczasie otwarto inną zakładkę
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
            if (localStorage.getItem("current_todo") === link) hlSyncAdopt(parsedDocument);
        });
    hlNavHighlight(tab_list);

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

    // podświetlenie aktywnej listy w dolnym pasku i menu „więcej”
    function hlNavHighlight(tab_list) {
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
    }

    // zakładki z tym samym nagłówkiem i paskiem dni – między nimi przechodzimy płynnie (hlTabSwap) zamiast przez spinner
    function hlTabSwapPair(a, b) {
        var days = ['today', 'food'];
        return days.indexOf(a) >= 0 && days.indexOf(b) >= 0 && !!document.querySelector('#dashboard_body [data-vt]');
    }

    // przejście bez przeładowania: stary widok zostaje, nowy ładuje się w tle i podmienia przez View Transitions.
    // Elementy z data-vt (tytuł, podtytuł, akcje, pasek dni) mają w obu widokach to samo miejsce, więc przenikają się w miejscu –
    // na pasku zmieniają się tylko wskaźniki pod dniami; reszta treści łagodnie wjeżdża (style .hl-vt w app.css).
    function hlTabSwap(site, link, seq) {
        var body = document.getElementById("dashboard_body");
        var root = document.documentElement;
        var text = (scope, key) => (scope.querySelector('[data-vt="' + key + '"]') || {}).textContent;
        var before = { title: text(body, 'title'), sub: text(body, 'sub') };
        var wait = setTimeout(() => body.classList.add('hl-tab-wait'), 250);
        var done = () => { clearTimeout(wait); body.classList.remove('hl-tab-wait'); };
        return fetch("components/panel/dashboard/" + site + ".php?" + link)
            .then(response => response.text())
            .then(data => {
                if (seq !== window.hlTabSeq) return;
                var parsedDocument = new DOMParser().parseFromString(data, "text/html");
                parsedDocument.querySelectorAll("[data-aos]").forEach(el => el.removeAttribute("data-aos"));
                var update = () => {
                    done();
                    // dzień-kotwica: wybrany dzień starego paska; nowy pasek ustawi go w tym samym miejscu (today.php / food.js)
                    var strip = body.querySelector('[data-vt="strip"]');
                    var sel = strip && strip.querySelector('[data-selected], .is-sel');
                    window.feedStripAnchor = sel ? { date: sel.dataset.date, x: sel.getBoundingClientRect().left - strip.getBoundingClientRect().left } : null;
                    body.innerHTML = parsedDocument.body.innerHTML;
                    root.scrollTo({ top: 0, behavior: 'instant' });
                    window.hlTabReady = null;
                    document.querySelectorAll(".popup-script").forEach(script => script.remove());
                    parsedDocument.querySelectorAll("script").forEach(script => {
                        var scriptElement = document.createElement("script");
                        scriptElement.textContent = script.textContent;
                        scriptElement.classList.add("popup-script");
                        document.body.appendChild(scriptElement);
                    });
                    if (localStorage.getItem("current_todo") === link) hlSyncAdopt(parsedDocument);
                    var ready = Promise.resolve(window.hlTabReady).catch(() => {});
                    return Promise.race([ready, new Promise(resolve => setTimeout(resolve, 1500))]).then(() => {
                        window.feedStripAnchor = null;
                        // ten sam tekst (np. data w podtytule) ma stać w miejscu, a nie mrugać
                        ['title', 'sub'].forEach(key => root.classList.toggle('hl-vt-same-' + key, text(body, key) === before[key]));
                    });
                };
                var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (!document.startViewTransition || calm || document.visibilityState !== 'visible') {
                    return Promise.resolve(update()).then(() => {
                        if (!calm && body.animate) body.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 220, easing: 'ease-out' });
                    });
                }
                root.classList.add('hl-vt');
                // szklana nawigacja nad treścią: własne warstwy, inaczej warstwa treści by ją zasłoniła (styl hl-glass-N w app.css)
                // tylko faktycznie widoczne – schowany toast (opacity 0) dostałby warstwę z rozmyciem w pustym miejscu
                var shown = g => g.checkVisibility ? g.checkVisibility({ opacityProperty: true, visibilityProperty: true }) : g.getClientRects().length > 0;
                var glass = [...document.querySelectorAll('.liquid-glass')].filter(g => !body.contains(g) && shown(g)).slice(0, 6);
                glass.forEach((g, i) => { g.style.viewTransitionName = 'hl-glass-' + i; });
                var transition = document.startViewTransition(update);
                return transition.finished.catch(() => {}).then(() => {
                    root.classList.remove('hl-vt', 'hl-vt-same-title', 'hl-vt-same-sub');
                    glass.forEach(g => { g.style.viewTransitionName = ''; });
                });
            })
            .catch(error => {
                done();
                if (seq !== window.hlTabSeq) return;
                console.error('Błąd przejścia między zakładkami:', error);
                openDetailTab(site, link);
            });
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
                if (localStorage.getItem("current_todo") === link) hlSyncAdopt(parsedDocument);
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
                hlSyncAdopt(parsedDocument);
                return hlMorphList(oldList, newList);
            })
            .catch(error => {
                console.error('Błąd odświeżania listy:', error);
                reloadTabSilently();
            });
    }

    // plan dnia w miejscu (zmiana z innego urządzenia / od innej osoby): przy tym samym układzie sekcji
    // podmieniamy tylko karty w każdej sekcji – instancje Sortable, otwarty popup i scroll paska dni zostają.
    // Inny układ (np. doszła sekcja Zaległe) = ciche przeładowanie, ale tylko gdy nic nie jest otwarte.
    function hlMorphFeed() {
        var link = localStorage.getItem("current_todo") || 'list=today';
        var feed = document.getElementById('feed');
        if (!feed) return hlSyncCanReplace() ? reloadTabSilently() : null;
        var seq = window.hlMorphSeq = (window.hlMorphSeq || 0) + 1;
        return fetch("components/panel/dashboard/" + tabSiteFor(link) + ".php?" + link)
            .then(response => response.text())
            .then(data => {
                if (seq !== window.hlMorphSeq || localStorage.getItem("current_todo") !== link || !document.body.contains(feed) || hlSyncBusy()) return;
                var parsedDocument = new DOMParser().parseFromString(data, "text/html");
                var next = parsedDocument.getElementById('feed');
                var layout = el => el.dataset.date + '|' + [...el.querySelectorAll('[data-feed-section]')].map(sec => sec.dataset.feedSection).join();
                if (!next || layout(feed) !== layout(next)) {
                    // bez adopcji tokenu – przy zablokowanej podmianie kolejne sprawdzenie spróbuje znowu
                    return hlSyncCanReplace() ? reloadTabSilently() : null;
                }
                [['sticky_nav_body', false], ['day-strip', true]].forEach(([id, keepScroll]) => {
                    var oldEl = document.getElementById(id);
                    var newEl = parsedDocument.getElementById(id);
                    if (!oldEl || !newEl || oldEl.innerHTML === newEl.innerHTML) return;
                    var left = oldEl.scrollLeft;
                    oldEl.innerHTML = newEl.innerHTML;
                    if (keepScroll) oldEl.scrollLeft = left;
                });
                var morphs = [];
                feed.querySelectorAll('[data-feed-section]').forEach(section => {
                    var oldUl = section.querySelector('.feed-list');
                    var newUl = next.querySelector('[data-feed-section="' + section.dataset.feedSection + '"] .feed-list');
                    if (oldUl && newUl && oldUl.innerHTML !== newUl.innerHTML) morphs.push(hlMorphList(oldUl, newUl, true));
                });
                hlSyncAdopt(parsedDocument);
                return Promise.all(morphs).then(() => feedUpdateProgress());
            })
            .catch(error => console.error('Błąd odświeżania planu dnia:', error));
    }

    // podmiana elementów z data-key: usunięte się zwijają, nowe wjeżdżają z poświatą,
    // zmienione błyskają, a przesunięte płyną na nowe miejsce (FLIP); animateFirst = animuj też pierwszy element w pustej liście
    function hlMorphList(oldUl, newUl, animateFirst) {
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
            var hadItems = animateFirst || Object.keys(oldItems).length > 0;

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
            hlNavHighlight(document.getElementById("list_hold").value);
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


// ===== wspólne helpery: lists_info.php (QR do udostępniania) i lists_code.php (skaner) =====
// jednorazowe wczytanie skryptu z CDN (kolejne wywołania czekają na to samo)
function hlLoadScript(src) {
    window.hlScripts = window.hlScripts || {};
    if (!window.hlScripts[src]) {
        window.hlScripts[src] = new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = function () { delete window.hlScripts[src]; reject(new Error('load ' + src)); };
            document.head.appendChild(s);
        });
    }
    return window.hlScripts[src];
}

// kod QR jako <svg> (moduły w currentColor) – biblioteka qrcode-generator
function hlQrSvg(text) {
    return hlLoadScript('https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js').then(function () {
        var qr = qrcode(0, 'M');
        qr.addData(text);
        qr.make();
        var n = qr.getModuleCount(), d = '';
        for (var r = 0; r < n; r++) {
            for (var c = 0; c < n; c++) if (qr.isDark(r, c)) d += 'M' + c + ' ' + r + 'h1v1h-1z';
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + n + ' ' + n + '" shape-rendering="crispEdges" role="img" aria-label="Kod QR"><path fill="currentColor" d="' + d + '"/></svg>';
    });
}

// schowek z obejściem dla http (navigator.clipboard działa tylko w bezpiecznym kontekście)
function hlCopy(text) {
    if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
    var area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
    document.body.appendChild(area);
    area.select();
    area.setSelectionRange(0, 99999);
    try { document.execCommand('copy'); } catch (e) {}
    area.remove();
    return Promise.resolve();
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
include "../../popup.php";
?>
<style>
/* szczegóły listy otwierane też nad „Edytuj listy” – warstwa wyżej niż zwykłe popupy */
#popupInfoListsBg { z-index: 75 !important; }
#popupInfoLists { z-index: 80 !important; }
</style>

