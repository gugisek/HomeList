// HomeList – Jedzenie: ekran dnia + arkusz (wyszukiwarka z zakładkami, porcja, szybkie kcal, cel, „Policz ze zdjęcia” przez czat AI).
// Ładowany raz (hlLoadScript) przez components/panel/dashboard/food.php, który przy każdym otwarciu woła hlFood.init(dane).
// Endpointy: scripts/food/*.php (JSON). Arkusz i toast żyją w <body>, więc przetrwają odświeżenie zakładki.
(function () {
    if (window.hlFood) return;

    var MEALS = { 1: 'Śniadanie', 2: 'II śniadanie', 3: 'Obiad', 4: 'Podwieczorek', 5: 'Kolacja' };
    var MEAL_TO = { 1: 'do śniadania', 2: 'do II śniadania', 3: 'do obiadu', 4: 'do podwieczorku', 5: 'do kolacji' };
    var MACROS = [
        ['protein', 'Białko', 'var(--fd-p)'],
        ['fat', 'Tłuszcz', 'var(--fd-f)'],
        ['carbs', 'Węglowodany', 'var(--fd-c)']
    ];
    var DAY_NAMES = ['nd', 'pn', 'wt', 'śr', 'cz', 'pt', 'sb'];
    var DAY_LONG = ['niedziela', 'poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota'];
    var MONTHS = ['stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia'];
    var TAGS = { base: 'Podstawowy', off: 'Open Food Facts', dish: 'Danie domowe', user: 'Własny' };

    var P = 'stroke-linecap="round" stroke-linejoin="round"';
    var svg = function (d, w) { return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="' + (w || 2) + '" stroke="currentColor" aria-hidden="true"><path ' + P + ' d="' + d + '"/></svg>'; };
    var I = {
        plus: svg('M12 4.5v15m7.5-7.5h-15', 2.2),
        minus: svg('M5 12h14', 2.2),
        x: svg('M6 18 18 6M6 6l12 12'),
        back: svg('M15.75 19.5 8.25 12l7.5-7.5'),
        search: svg('m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z'),
        repeat: svg('M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99', 1.8),
        trash: svg('m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0', 1.8),
        check: svg('m4.5 12.75 6 6 9-13.5', 2.4),
        chev: svg('m19.5 8.25-7.5 7.5-7.5-7.5'),
        bolt: svg('m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z', 1.8),
        scan: svg('M3.75 7.5V5.25a1.5 1.5 0 0 1 1.5-1.5H7.5M16.5 3.75h2.25a1.5 1.5 0 0 1 1.5 1.5V7.5M20.25 16.5v2.25a1.5 1.5 0 0 1-1.5 1.5H16.5M7.5 20.25H5.25a1.5 1.5 0 0 1-1.5-1.5V16.5M8 8.5v7M11 8.5v7M13.5 8.5v7M16 8.5v7', 1.8),
        pot: svg('M4 10h16v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-6ZM2.5 10h19M9 6.5c0-1 .8-1.5.8-2.5M12.5 6.5c0-1 .8-1.5.8-2.5', 1.8),
        target: svg('M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 1.8),
        list: svg('M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z', 1.8),
        sparkles: svg('M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z', 1.8),
        copy: svg('M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75', 1.8),
        paste: svg('M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z', 1.8),
        out: svg('M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25', 1.8),
        chat: svg('M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z', 1.8),
        swap: svg('M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H4.5', 1.8),
        camera: svg('M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316ZM16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z', 1.8)
    };

    var S = { data: null, date: null, sheet: null, fresh: null, dirty: false, navSeq: 0 };
    var F = window.hlFood = {};

    // ---------- pomocnicze ----------
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }
    function fmt(n) { return Math.round(n || 0).toLocaleString('pl-PL'); }
    function dec(n) {
        var r = Math.round((n || 0) * 10) / 10;
        return (r >= 10 ? Math.round(r) : r).toLocaleString('pl-PL');
    }
    function qty(n) { // 1,5 × kubek
        var r = Math.round(n * 100) / 100;
        return r.toLocaleString('pl-PL', { maximumFractionDigits: 2 });
    }
    function d2s(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
    function s2d(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function addDays(s, n) { var d = s2d(s); d.setDate(d.getDate() + n); return d2s(d); }
    function el(id) { return document.getElementById(id); }
    function getJSON(url) {
        return fetch(url, { cache: 'no-store' }).then(function (r) { return r.json(); });
    }
    function post(url, obj) {
        var fd = new FormData();
        Object.keys(obj).forEach(function (k) { if (obj[k] !== null && obj[k] !== undefined) fd.append(k, obj[k]); });
        return fetch(url, { method: 'POST', body: fd }).then(function (r) { return r.json(); });
    }
    function fail(res) {
        if (typeof showAlert === 'function') showAlert(res && res.status === 'warning' ? 'warning' : 'error', (res && res.message) || 'Coś poszło nie tak – spróbuj ponownie');
    }
    function vib() { try { if (navigator.vibrate) navigator.vibrate(4); } catch (e) {} }
    function per(grams, p100) { return { kcal: p100.kcal * grams / 100, protein: p100.protein * grams / 100, fat: p100.fat * grams / 100, carbs: p100.carbs * grams / 100 }; }
    function unitOf(e) { return e.ml ? 'ml' : 'g'; }

    // posiłek „teraz” wg godziny (tylko dzisiaj), z pominięciem wyłączonych
    function nowMeal() {
        var h = new Date().getHours() + new Date().getMinutes() / 60;
        var m = h < 10.5 ? 1 : h < 12.5 ? 2 : h < 16 ? 3 : h < 18.5 ? 4 : 5;
        var on = (S.data && S.data.settings.meals) || [1, 2, 3, 4, 5];
        if (on.indexOf(m) >= 0) return m;
        var best = on[0], dist = 9;
        on.forEach(function (x) { if (Math.abs(x - m) < dist) { dist = Math.abs(x - m); best = x; } });
        return best;
    }

    F.today = function () { return d2s(new Date()); };

    // ---------- start / odświeżanie ----------
    F.init = function (data) {
        if (S.sheet) closeSheet(true);
        S.data = data;
        S.date = data.day.date;
        ensureShell();
        renderAll();
        scrollStrip();
        var pending = null;
        try { pending = sessionStorage.getItem('hl_food_open'); sessionStorage.removeItem('hl_food_open'); } catch (e) {}
        if (pending === 'goal' || pending === 'dishes') setTimeout(function () { openSheet({ step: pending }); }, 300);
        // „Popraw” w profilu (Twój wkład): own:ID
        else if (pending && /^own:\d+$/.test(pending)) setTimeout(function () { openSheet({ step: 'loading' }); editOwn(+pending.slice(4), 'close'); }, 300);
    };

    // synchronizacja (hlSyncApply) i powrót po zapisie: dane bieżącego dnia bez spinnera
    F.refresh = function () {
        if (!el('fd_root')) return Promise.resolve();
        if (S.sheet) { S.dirty = true; return Promise.resolve(); }
        return load(S.date, true);
    };

    function load(date, withStrip) {
        var seq = ++S.navSeq;
        return getJSON('scripts/food/day.php?date=' + date + (withStrip ? '&strip=1' : '')).then(function (res) {
            if (seq !== S.navSeq || res.status !== 'success') return;
            if (!withStrip) res.strip = S.data.strip;
            S.data = res;
            S.date = date;
            renderAll();
            if (typeof hlSyncAdopt === 'function') {
                // token po własnej zmianie – żeby synchronizacja nie odświeżała drugi raz tego samego
                fetch('scripts/sync/state.php?scope=food', { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (st) {
                    var s = window.hlSync;
                    if (s && s.scope === 'food' && st && st.t) s.token = st.t;
                }).catch(function () {});
            }
        });
    }

    F.go = function (date) {
        if (date === S.date) return;
        var body = el('fd_body');
        var dir = date > S.date ? 1 : -1;
        var strip = S.data.strip || [];
        var inStrip = strip.length && date >= strip[0].date && date <= strip[strip.length - 1].date;
        // style inline, nie klasa – inaczej wygrywa z nimi AOS (.aos-animate) po zwykłym wejściu na zakładkę
        if (body) {
            clipX(true);
            body.style.transition = 'opacity .15s ease, transform .15s ease';
            body.style.opacity = '0';
            body.style.transform = 'translateX(' + (-dir * 24) + 'px)';
        }
        S.date = date;
        markStripSel();
        var wait = new Promise(function (r) { setTimeout(r, 160); });
        var restore = function () {
            if (!body) return;
            body.style.opacity = '';
            body.style.transform = '';
            clipX();
        };
        Promise.all([getJSON('scripts/food/day.php?date=' + date + (inStrip ? '' : '&strip=1')), wait]).then(function (arr) {
            var res = arr[0];
            if (S.date !== date) return;
            if (res.status !== 'success') { restore(); return fail(res); }
            if (inStrip) res.strip = S.data.strip;
            S.data = res;
            renderAll();
            if (body) {
                // wjazd nowego dnia z kierunku, w którym przełączono
                body.style.transition = 'none';
                body.style.transform = 'translateX(' + (dir * 24) + 'px)';
                requestAnimationFrame(function () { requestAnimationFrame(function () {
                    body.style.transition = 'opacity .2s ease, transform .2s ease';
                    restore();
                }); });
            }
            if (!inStrip) requestAnimationFrame(scrollStrip);
        }).catch(function () {
            if (S.date !== date) return;
            restore();
            fail();
        });
    };

    // przesunięty dzień wystaje poza stronę – pojawia się poziomy scroll i skacze dolna nawigacja (jak feedClipX w today.php)
    function clipX(hold) {
        var wrap = el('fd_clip');
        if (!wrap) return;
        wrap.style.overflowX = 'clip';
        clearTimeout(S.clipTimer);
        if (!hold) S.clipTimer = setTimeout(function () { wrap.style.overflowX = ''; }, 400);
    }

    function renderAll() {
        renderHead();
        renderStrip();
        renderHero();
        renderMeals();
    }

    // ---------- nagłówek i pasek dni ----------
    function renderHead() {
        var today = F.today(), d = s2d(S.date);
        var rel = S.date === today ? 'Dziś' : S.date === addDays(today, -1) ? 'Wczoraj' : S.date === addDays(today, 1) ? 'Jutro' : null;
        var long = DAY_LONG[d.getDay()] + ', ' + d.getDate() + ' ' + MONTHS[d.getMonth()] + (d.getFullYear() !== new Date().getFullYear() ? ' ' + d.getFullYear() : '');
        el('fd_subtitle').textContent = rel && rel !== 'Dziś' ? rel + ' · ' + long : long;
        el('fd_today_btn').classList.toggle('hidden', S.date === today);
    }

    function renderStrip() {
        var box = el('fd_strip');
        if (!box) return;
        var today = F.today();
        box.innerHTML = (S.data.strip || []).map(function (s) {
            var d = s2d(s.date);
            var pct = s.goal ? Math.min(s.kcal / s.goal, 1) * 100 : (s.kcal ? 100 : 0);
            var over = s.goal && s.kcal > s.goal * 1.1;
            var cls = (s.date === S.date ? ' is-sel' : '') + (s.date === today ? ' is-today' : '') + (s.date < today ? ' is-past' : '') + (d.getDay() === 0 ? ' is-sun' : '');
            return '<button type="button" role="tab" class="fd-day fd-press' + cls + '" data-date="' + s.date + '" aria-selected="' + (s.date === S.date) + '"'
                + ' aria-label="' + esc(DAY_LONG[d.getDay()] + ' ' + d.getDate() + ' ' + MONTHS[d.getMonth()] + (s.kcal ? ', ' + fmt(s.kcal) + ' kcal' : '')) + '">'
                + '<span class="fd-day-name">' + DAY_NAMES[d.getDay()] + '</span><span class="fd-day-num">' + d.getDate() + '</span>'
                + '<span class="fd-day-bar"><span style="width:' + pct.toFixed(0) + '%;' + (over ? 'background:#f59e0b' : (s.goal ? '' : 'opacity:.5')) + '"></span></span></button>';
        }).join('');
    }

    function markStripSel() {
        var box = el('fd_strip');
        if (!box) return;
        box.querySelectorAll('.fd-day').forEach(function (b) {
            b.classList.toggle('is-sel', b.dataset.date === S.date);
            b.setAttribute('aria-selected', b.dataset.date === S.date);
        });
    }

    // wybrany dzień na środku, jak w today.php; feedStripAnchor (przejście z planu dnia) = najpierw ustaw dzień-kotwicę
    // dokładnie tam, gdzie był na poprzednim pasku, a potem płynnie wycentruj
    function scrollStrip() {
        var box = el('fd_strip');
        var sel = box && box.querySelector('.is-sel');
        if (!sel) return;
        var anchor = window.feedStripAnchor;
        window.feedStripAnchor = null;
        var shift = function (node, x, behavior) {
            box.scrollTo({ left: box.scrollLeft + node.getBoundingClientRect().left - box.getBoundingClientRect().left - x, behavior: behavior });
        };
        var center = function (behavior) { shift(sel, (box.clientWidth - sel.offsetWidth) / 2, behavior); };
        var pinned = anchor && box.querySelector('[data-date="' + anchor.date + '"]');
        if (pinned) {
            shift(pinned, anchor.x, 'instant');
            requestAnimationFrame(function () { center('smooth'); });
        } else {
            center('instant');
        }
    }

    // ---------- podsumowanie dnia ----------
    // baza = dzień bez edytowanego wpisu, duch = porcja w otwartym arkuszu
    function preview() {
        var t = S.data.day.total, base = { kcal: t.kcal, protein: t.protein, fat: t.fat, carbs: t.carbs }, ghost = null;
        var sh = S.sheet;
        if (sh && sh.step === 'portion' && sh.p && sh.date === S.date) {
            if (sh.p.entry) ['kcal', 'protein', 'fat', 'carbs'].forEach(function (k) { base[k] -= sh.p.entry[k]; });
            ghost = per(sh.p.grams, sh.p.p100);
        } else if (sh && sh.step === 'aireview' && sh.date === S.date) {
            ghost = aiTotals(sh.ai);
        }
        return { base: base, ghost: ghost };
    }

    function barsHTML(base, ghost, goal, keys, withDelta) {
        return keys.map(function (m) {
            var k = m[0], b = base[k] || 0, a = ghost ? ghost[k] : 0, tot = b + a;
            var G = goal ? goal[k] : 0, unit = k === 'kcal' ? ' kcal' : ' g';
            var fill = G ? Math.min(b / G, 1) * 100 : 0;
            var gw = G ? Math.max(0, Math.min(tot / G, 1) * 100 - fill) : 0;
            var over = G && tot > G * 1.05;
            var color = over && !a ? 'var(--fd-over)' : m[2], gcolor = over ? 'var(--fd-over)' : m[2];
            var right = withDelta && a
                ? fmt(b) + ' <b style="color:' + gcolor + '">+' + fmt(a) + '</b> → <b>' + fmt(tot) + '</b>' + (G ? ' / ' + fmt(G) : '') + unit
                : '<b>' + fmt(tot) + '</b>' + (G ? ' / ' + fmt(G) : '') + unit;
            return '<div><div class="fd-bar-head"><span class="fd-bar-name">' + m[1] + '</span><span class="fd-mut fd-num">' + right + '</span></div>'
                + '<div class="fd-bar"><span style="left:0;width:' + fill.toFixed(1) + '%;background:' + color + '"></span>'
                + '<span class="fd-ghost" style="left:' + fill.toFixed(1) + '%;width:' + gw.toFixed(1) + '%;background-color:' + gcolor + '"></span></div></div>';
        }).join('');
    }

    function renderHero() {
        var box = el('fd_hero');
        if (!box) return;
        var pv = preview(), goal = S.data.day.goal;
        var eaten = pv.base.kcal + (pv.ghost ? pv.ghost.kcal : 0);
        var ring, center;
        if (goal) {
            var left = goal.kcal - eaten;
            var bp = Math.min(pv.base.kcal / goal.kcal, 1) * 100, tp = Math.min(eaten / goal.kcal, 1) * 100;
            var color = left >= -goal.kcal * 0.05 ? 'var(--fd-k)' : 'var(--fd-over)';
            ring = '<circle cx="60" cy="60" r="52" stroke-linecap="round" pathLength="100" stroke-dasharray="' + bp.toFixed(1) + ' 100" style="stroke:' + color + '"/>'
                + '<circle cx="60" cy="60" r="52" pathLength="100" stroke-dasharray="0 ' + bp.toFixed(1) + ' ' + Math.max(0, tp - bp).toFixed(1) + ' 100" style="stroke:' + color + ';opacity:.4"/>';
            center = '<span class="fd-ring-big fd-num">' + fmt(Math.abs(left)) + '</span><span class="fd-ring-sub">' + (left >= 0 ? 'kcal zostało' : 'kcal ponad cel') + '</span>';
        } else {
            ring = '';
            center = '<span class="fd-ring-big fd-num">' + fmt(eaten) + '</span><span class="fd-ring-sub">kcal zjedzone</span>';
        }
        // cała karta otwiera statystyki; przycisk celu ma własną akcję (closest() łapie go pierwszy)
        box.innerHTML = '<div class="fd-hero-wrap" data-act="stats" role="button" tabindex="0" aria-label="Statystyki z 7 / 30 / 90 dni"><div class="fd-hero">'
            + '<div class="fd-ring"><svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" style="stroke:var(--fd-track)"/>' + ring + '</svg>'
            + '<div class="fd-ring-center">' + center + '</div></div>'
            + '<div class="fd-bars">' + barsHTML(pv.base, pv.ghost, goal, MACROS, false)
            + (goal ? '<p class="text-xs fd-mut" style="margin:2px 0 0">Zjedzone <b class="fd-num" style="color:var(--hl-ink);font-weight:600">' + fmt(eaten) + '</b> z ' + fmt(goal.kcal) + ' kcal</p>' : '')
            + '</div></div>'
            + '<div class="fd-hero-foot"><span class="fd-hero-more">Statystyki' + I.chev.replace('<svg', '<svg width="14" height="14" style="transform:rotate(-90deg)"') + '</span>'
            + '<button type="button" class="fd-goal-btn fd-press" data-act="goal">' + I.target.replace('<svg', '<svg width="15" height="15"')
            + (goal ? 'Cel ' + fmt(goal.kcal) + ' kcal' : 'Ustaw dzienny cel') + '</button></div></div>';
    }

    // ---------- posiłki ----------
    function amountText(e) {
        if (e.grams === null) return e.source === 'quick' ? 'Szybkie dodanie' : '';
        var t = dec(e.grams) + ' ' + unitOf(e);
        if (e.unit_label && e.unit_g) t += ' · ' + qty(e.grams / e.unit_g) + ' × ' + e.unit_label;
        return t;
    }

    function renderMeals() {
        var box = el('fd_meals');
        if (!box) return;
        var on = S.data.settings.meals, isToday = S.date === F.today(), now = isToday ? nowMeal() : 0;
        var prevOk = S.date <= F.today();
        box.innerHTML = S.data.day.meals.filter(function (m) { return on.indexOf(m.meal) >= 0 || m.entries.length; }).map(function (m) {
            var prev = S.data.prev && S.data.prev[m.meal];
            var items = m.entries.map(function (e) {
                return '<div class="fd-entry' + (S.fresh === e.id ? ' is-fresh' : '') + '" role="button" tabindex="0" data-entry="' + e.id + '">'
                    + '<div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(e.name) + '</div><div class="text-xs fd-mut">' + esc(amountText(e)) + '</div></div>'
                    + '<span class="fd-num" style="font-size:14px;font-weight:600;white-space:nowrap">' + fmt(e.kcal) + '<span class="fd-mut" style="font-size:11px;font-weight:400"> kcal</span></span></div>';
            }).join('');
            var copy = !m.entries.length && prev && prevOk
                ? '<div style="padding:2px 6px 6px 12px"><button type="button" class="fd-ghost-btn fd-press" data-copy="' + m.meal + '">' + I.repeat
                    + 'Jak wczoraj · ' + prev.count + ' ' + (prev.count === 1 ? 'produkt' : prev.count < 5 ? 'produkty' : 'produktów') + ' · ' + fmt(prev.kcal) + ' kcal</button></div>'
                : '';
            return '<section class="hl-card fd-meal' + (m.meal === now ? ' is-now' : '') + '" aria-label="' + MEALS[m.meal] + '">'
                + '<div class="fd-meal-head"><div style="flex:1;min-width:0"><div style="display:flex;align-items:center;gap:8px"><h2 class="fd-meal-title">' + MEALS[m.meal] + '</h2>'
                + (m.meal === now ? '<span class="fd-tag now">Teraz</span>' : '') + '</div>'
                + '<p class="text-xs fd-mut" style="margin:1px 0 0">' + (m.entries.length ? 'B ' + fmt(m.protein) + ' · T ' + fmt(m.fat) + ' · W ' + fmt(m.carbs) + ' g' : 'Nic jeszcze nie dodano') + '</p></div>'
                + '<span class="fd-num" style="font-size:15px;font-weight:600;white-space:nowrap">' + fmt(m.kcal) + ' <span class="fd-mut" style="font-size:12px;font-weight:400">kcal</span></span>'
                + '<button type="button" class="fd-plus fd-press" data-add="' + m.meal + '" aria-label="Dodaj ' + MEAL_TO[m.meal] + '">' + I.plus + '</button></div>'
                + (items ? '<div style="display:flex;flex-direction:column;margin-top:2px">' + items + '</div>' : '') + copy + '</section>';
        }).join('')
            + '<button type="button" class="fd-day-btn fd-press" data-act="day">' + I.list + '<span style="flex:1;text-align:left">Podsumowanie dnia</span>'
            + '<span class="fd-mut fd-num" style="font-size:13px;font-weight:400">' + fmt(S.data.day.total.kcal) + ' kcal</span>'
            + I.chev.replace('<svg', '<svg width="14" height="14" style="transform:rotate(-90deg);flex:none;color:var(--fd-mut)"') + '</button>';
        if (S.fresh) setTimeout(function () { S.fresh = null; }, 1700);
    }

    function findEntry(id) {
        var found = null;
        S.data.day.meals.forEach(function (m) { m.entries.forEach(function (e) { if (e.id === id) found = e; }); });
        return found;
    }

    // ---------- zdarzenia ekranu (delegacja – elementy rysowane od nowa) ----------
    function bindScreen() {
        var root = el('fd_root');
        if (!root || root.dataset.bound) return;
        root.dataset.bound = '1';
        root.addEventListener('click', function (ev) {
            var t = ev.target.closest('[data-date],[data-add],[data-copy],[data-act],[data-entry]');
            if (!t || !root.contains(t)) return;
            if (t.dataset.date) return F.go(t.dataset.date);
            if (t.dataset.add) return openSheet({ step: 'search', meal: +t.dataset.add });
            if (t.dataset.copy) return copyYesterday(+t.dataset.copy, t);
            if (t.dataset.act === 'goal') return openSheet({ step: 'goal' });
            if (t.dataset.act === 'stats') return openSheet({ step: 'stats' });
            if (t.dataset.act === 'day') return openSheet({ step: 'day' });
            if (t.dataset.entry) return openEntry(+t.dataset.entry);
        });
        root.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Enter' && ev.key !== ' ') return;
            var t = ev.target.closest('[data-entry]') || ev.target;
            if (t.dataset.entry) { ev.preventDefault(); openEntry(+t.dataset.entry); }
            else if (t.dataset.act === 'stats') { ev.preventDefault(); openSheet({ step: 'stats' }); }
        });
        // swipe w lewo = następny dzień, w prawo = poprzedni (jak feedBindSwipe w today.php); pasek dni przewija się sam, więc go pomijamy
        var sw = null;
        root.addEventListener('touchstart', function (ev) {
            sw = ev.touches.length === 1 && !ev.target.closest('#fd_strip') ? { x: ev.touches[0].clientX, y: ev.touches[0].clientY, t: Date.now() } : null;
        }, { passive: true });
        root.addEventListener('touchend', function (ev) {
            if (!sw || S.sheet) { sw = null; return; }
            var dx = ev.changedTouches[0].clientX - sw.x, dy = ev.changedTouches[0].clientY - sw.y;
            var quick = Date.now() - sw.t < 700;
            sw = null;
            if (!quick || Math.abs(dx) < 60 || Math.abs(dx) < Math.abs(dy) * 1.5) return;
            F.go(addDays(S.date, dx < 0 ? 1 : -1));
        }, { passive: true });
        root.addEventListener('touchcancel', function () { sw = null; }, { passive: true });
    }

    // ---------- akcje dziennika ----------
    function afterChange(freshId) {
        S.fresh = freshId || null;
        return load(S.date, true);
    }

    function removeEntry(id) {
        post('scripts/food/log_delete.php', { id: id }).then(function (res) {
            if (res.status !== 'success') { fail(res); return afterChange(); }
            var e = res.entry;
            afterChange();
            toast('Usunięto: ' + e.name, function () {
                post('scripts/food/log_add.php', { date: e.date, meal: e.meal, restore: JSON.stringify(e) }).then(function (r) {
                    r.status === 'success' ? afterChange(r.entry.id) : fail(r);
                });
            });
        });
    }

    function copyYesterday(meal, btn) {
        btn.disabled = true;
        post('scripts/food/log_copy.php', { from_date: addDays(S.date, -1), from_meal: meal, to_date: S.date, to_meal: meal }).then(function (res) {
            if (res.status !== 'success') { btn.disabled = false; return fail(res); }
            afterChange(res.ids[0]);
            toast(res.message, function () {
                post('scripts/food/log_delete.php', { ids: res.ids.join(',') }).then(function () { afterChange(); });
            });
        });
    }

    function addProduct(p, grams, unit, meal, source) {
        var g = Math.round(grams * 10) / 10;
        return post('scripts/food/log_add.php', {
            date: S.date, meal: meal, product_id: p.id, grams: g, source: source || 'manual',
            unit_label: unit ? unit.label : '', unit_g: unit ? unit.grams : ''
        }).then(function (res) {
            if (res.status !== 'success') return fail(res);
            closeSheet(true);
            afterChange(res.entry.id);
            toast('Dodano ' + MEAL_TO[meal] + ': ' + p.name + ', ' + dec(g) + ' ' + (p.per_ml ? 'ml' : 'g'), function () {
                post('scripts/food/log_delete.php', { id: res.entry.id }).then(function () { afterChange(); });
            });
        });
    }

    // ---------- toast z „Cofnij” ----------
    function toast(text, undo) {
        var t = el('fd_toast');
        clearTimeout(S.toastTimer);
        t.querySelector('span').textContent = text;
        var b = t.querySelector('button');
        b.hidden = !undo;
        b.onclick = function () { t.classList.remove('is-show'); if (undo) undo(); };
        t.classList.add('is-show');
        S.toastTimer = setTimeout(function () { t.classList.remove('is-show'); }, 5000);
    }

    // ---------- arkusz ----------
    function ensureShell() {
        bindScreen();
        if (el('fd_sheet')) return;
        document.body.insertAdjacentHTML('beforeend',
            '<div id="fd_scrim" class="fd-scrim"></div>'
            + '<div id="fd_sheet" class="fd-sheet" role="dialog" aria-modal="true" aria-label="Dodawanie jedzenia"><div class="fd-handle"><span></span></div><div id="fd_step" class="fd-step"></div></div>'
            + '<div id="fd_toast" class="fd-toast liquid-glass" role="status">' + I.check.replace('<svg', '<svg width="18" height="18" style="color:#4ade80;flex:none"') + '<span style="flex:1;min-width:0"></span><button type="button">Cofnij</button></div>');
        el('fd_scrim').addEventListener('click', function () { closeSheet(); });
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && S.sheet) closeSheet(); });
        // przeciągnięcie uchwytu w dół zamyka arkusz (telefon)
        var h = el('fd_sheet').querySelector('.fd-handle'), drag = null;
        h.addEventListener('pointerdown', function (ev) { drag = { y: ev.clientY, dy: 0 }; h.setPointerCapture(ev.pointerId); el('fd_sheet').style.transition = 'none'; });
        h.addEventListener('pointermove', function (ev) {
            if (!drag) return;
            drag.dy = Math.max(0, ev.clientY - drag.y);
            el('fd_sheet').style.transform = 'translateY(' + drag.dy + 'px)';
        });
        var up = function () {
            if (!drag) return;
            var dy = drag.dy;
            drag = null;
            var sh = el('fd_sheet');
            sh.style.transition = '';
            sh.style.transform = '';
            if (dy > 120) closeSheet();
        };
        h.addEventListener('pointerup', up);
        h.addEventListener('pointercancel', up);
    }

    function openSheet(st) {
        S.sheet = Object.assign({ meal: nowMeal(), date: S.date }, st);
        if (!st.meal && S.date !== F.today()) S.sheet.meal = S.data.settings.meals[0];
        el('fd_toast').classList.remove('is-show');
        renderStep();
        el('fd_scrim').classList.add('is-open');
        el('fd_sheet').classList.add('is-open');
        document.documentElement.style.overflow = 'hidden';
    }
    F.openAdd = function () { openSheet({ step: 'search' }); };
    // skan tylko do podglądu produktu (przycisk obok plusa); dodanie do posiłku to jeden krok dalej
    F.openScan = function () { openSheet({ step: 'scan', scanFor: 'info' }); };

    function closeSheet(skipRefresh) {
        if (!S.sheet) return;
        stopCamera();
        S.sheet = null;
        el('fd_scrim').classList.remove('is-open');
        el('fd_sheet').classList.remove('is-open');
        document.documentElement.style.overflow = '';
        renderHero();
        if (S.dirty && !skipRefresh) { S.dirty = false; F.refresh(); }
        S.dirty = false;
    }

    function setStep(step, back) {
        S.sheet.step = step;
        renderStep(back);
    }

    function renderStep(back) {
        stopCamera();
        var box = el('fd_step');
        box.className = 'fd-step' + (back ? ' is-back' : '');
        el('fd_sheet').classList.toggle('is-wide', S.sheet.step === 'day'); // tabela produktów potrzebuje miejsca na PC
        void box.offsetWidth; // restart animacji wejścia
        var step = S.sheet.step;
        if (step === 'search') renderSearch(box);
        else if (step === 'portion') renderPortion(box);
        else if (step === 'quick') renderQuick(box);
        else if (step === 'goal') renderGoal(box);
        else if (step === 'stats') renderStats(box);
        else if (step === 'day') renderDay(box);
        else if (step === 'scan') renderScan(box);
        else if (step === 'info') renderInfo(box);
        else if (step === 'own') renderOwn(box);
        else if (step === 'dish') renderDish(box);
        else if (step === 'dishes') renderDishes(box);
        else if (step === 'ai') renderAi(box);
        else if (step === 'aipaste') renderAiPaste(box);
        else if (step === 'aimatch') renderAiWait(box);
        else if (step === 'aireview') renderAiReview(box);
        else if (step === 'loading') box.innerHTML = '<div style="padding:24px 20px">' + '<div class="fd-skel"></div>'.repeat(4) + '</div>';
        // wybrany posiłek widoczny także, gdy chipy się nie mieszczą
        var on = box.querySelector('.fd-chips .is-on');
        if (on) on.parentElement.scrollLeft = on.offsetLeft - on.parentElement.clientWidth / 2 + on.offsetWidth / 2;
        renderHero();
    }

    function mealChips() {
        return '<div class="fd-chips" role="radiogroup" aria-label="Posiłek">' + [1, 2, 3, 4, 5].filter(function (m) {
            return S.data.settings.meals.indexOf(m) >= 0 || m === S.sheet.meal;
        }).map(function (m) {
            return '<button type="button" role="radio" aria-checked="' + (m === S.sheet.meal) + '" class="fd-chip fd-press' + (m === S.sheet.meal ? ' is-on' : '') + '" data-meal="' + m + '">' + MEALS[m] + '</button>';
        }).join('') + '</div>';
    }
    function bindMealChips(box, after) {
        box.querySelectorAll('[data-meal]').forEach(function (b) {
            b.addEventListener('click', function () {
                S.sheet.meal = +b.dataset.meal;
                box.querySelectorAll('[data-meal]').forEach(function (x) {
                    var on = +x.dataset.meal === S.sheet.meal;
                    x.classList.toggle('is-on', on);
                    x.setAttribute('aria-checked', on);
                });
                if (after) after();
            });
        });
    }
    function headHTML(title, backAct) {
        return '<div style="display:flex;align-items:center;gap:4px;padding:4px 12px 0 ' + (backAct ? '12px' : '20px') + '">'
            + (backAct ? '<button type="button" class="fd-icon-btn fd-press" data-back aria-label="Wróć">' + I.back + '</button>' : '')
            + '<h2 class="hl-popup-title" style="flex:1;min-width:0;padding:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + title + '</h2>'
            + '<button type="button" class="fd-icon-btn fd-press fd-mut" data-close aria-label="Zamknij">' + I.x + '</button></div>';
    }
    function bindHead(box, back) {
        var c = box.querySelector('[data-close]');
        if (c) c.addEventListener('click', function () { closeSheet(); });
        var b = box.querySelector('[data-back]');
        if (b) b.addEventListener('click', back);
    }

    // ---------- wyszukiwarka ----------
    // Bez zapytania: zakładki Ostatnio / Twoje dania / Dania innych (przesunięcie palcem też przełącza),
    // z zapytaniem: wyniki z całej bazy + Open Food Facts, własny produkt, same kalorie.
    var TABS = [['recent', 'Ostatnio'], ['dishes', 'Twoje dania'], ['community', 'Dania innych']];
    var TAB_KEY = 'hl_food_tab';

    function tabIndex(tab) {
        for (var i = 0; i < TABS.length; i++) if (TABS[i][0] === tab) return i;
        return 0;
    }

    function renderSearch(box) {
        var sh = S.sheet;
        if (!sh.tab) {
            try { sh.tab = localStorage.getItem(TAB_KEY); } catch (e) {}
            sh.tab = TABS[tabIndex(sh.tab)][0];
        }
        // dane zakładek z poprzedniego wejścia mogą być nieaktualne (nowe danie, dodany wpis) – zostaje tylko bieżący widok
        sh.tabs = {};
        var hasQ = !!(sh.q || '').trim();
        box.innerHTML = headHTML('Dodaj ' + MEAL_TO[sh.meal], false)
            + '<div style="padding:8px 20px 0;flex:none">' + mealChips()
            + '<div style="display:flex;gap:8px;margin-top:12px"><label class="fd-field" style="flex:1">' + I.search + '<input type="search" id="fd_q" placeholder="Szukaj: skyr, jajko, ryż…" aria-label="Szukaj produktu" autocomplete="off" enterkeyhint="search" value="' + esc(sh.q || '') + '"></label>'
            + '<button type="button" class="fd-round fd-sq fd-ai-btn fd-press" data-aibtn aria-label="Policz ze zdjęcia (AI)" title="Policz ze zdjęcia">' + I.sparkles + '</button>'
            + '<button type="button" class="fd-round fd-sq fd-press" data-scanbtn aria-label="Skanuj kod kreskowy" title="Skanuj kod kreskowy">' + I.scan + '</button></div>'
            + '<div class="fd-seg-wrap' + (hasQ ? ' is-hidden' : '') + '" id="fd_segwrap"><div class="fd-seg" role="tablist" aria-label="Pokaż" style="--i:' + tabIndex(sh.tab) + '">'
            + '<span class="fd-seg-ind" aria-hidden="true"></span>'
            + TABS.map(function (t, i) {
                var on = t[0] === sh.tab;
                return '<button type="button" role="tab" aria-selected="' + on + '" aria-controls="fd_results" class="fd-seg-btn' + (on ? ' is-on' : '') + '" data-tab="' + i + '">' + t[1] + '</button>';
            }).join('') + '</div></div></div>'
            + '<div class="fd-scroll" id="fd_results" role="tabpanel" style="padding:12px 14px 24px"></div>';
        bindHead(box);
        bindMealChips(box, function () { box.querySelector('h2').textContent = 'Dodaj ' + MEAL_TO[sh.meal]; sh.tabs = {}; if (!(sh.q || '').trim()) runSearch(); });
        box.querySelector('[data-scanbtn]').addEventListener('click', function () { setStep('scan'); });
        box.querySelector('[data-aibtn]').addEventListener('click', function () { setStep('ai'); });
        box.querySelectorAll('[data-tab]').forEach(function (b) {
            b.addEventListener('click', function () { setTab(+b.dataset.tab); });
        });
        var input = el('fd_q');
        input.addEventListener('input', function () {
            sh.q = input.value;
            sh.off = null;
            el('fd_segwrap').classList.toggle('is-hidden', !!sh.q.trim());
            clearTimeout(S.qTimer);
            S.qTimer = setTimeout(runSearch, 220);
        });
        // przesunięcie w bok po liście = sąsiednia zakładka
        var res = el('fd_results'), sw = null;
        res.addEventListener('touchstart', function (ev) { var t = ev.touches[0]; sw = { x: t.clientX, y: t.clientY }; }, { passive: true });
        res.addEventListener('touchend', function (ev) {
            if (!sw || (sh.q || '').trim()) return;
            var t = ev.changedTouches[0], dx = t.clientX - sw.x, dy = t.clientY - sw.y;
            sw = null;
            if (Math.abs(dx) > 70 && Math.abs(dx) > Math.abs(dy) * 2) setTab(tabIndex(sh.tab) + (dx < 0 ? 1 : -1));
        }, { passive: true });
        if (sh.results && sh.rkey === searchKey()) drawResults(); else runSearch();
        if (window.matchMedia('(hover: hover)').matches) setTimeout(function () { input.focus(); }, 350);
    }

    function searchKey() {
        var sh = S.sheet, q = (sh.q || '').trim();
        return q ? 'q:' + q : 'tab:' + sh.tab;
    }

    function setTab(i) {
        var sh = S.sheet;
        if (i < 0 || i >= TABS.length || TABS[i][0] === sh.tab) return;
        var dir = i > tabIndex(sh.tab) ? 'is-next' : 'is-prev';
        sh.tab = TABS[i][0];
        try { localStorage.setItem(TAB_KEY, sh.tab); } catch (e) {}
        var seg = el('fd_step').querySelector('.fd-seg');
        seg.style.setProperty('--i', i);
        seg.querySelectorAll('[data-tab]').forEach(function (b) {
            var on = +b.dataset.tab === i;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-selected', on);
        });
        vib();
        sh.slide = dir;
        el('fd_results').scrollTop = 0;
        runSearch();
    }

    function runSearch() {
        var sh = S.sheet;
        if (!sh || sh.step !== 'search') return;
        var q = (sh.q || '').trim(), key = searchKey(), seq = sh.seq = (sh.seq || 0) + 1;
        if (!q && sh.tabs[key]) {
            sh.results = sh.tabs[key];
            sh.rq = '';
            sh.rkey = key;
            return drawResults();
        }
        if (!sh.results || sh.rkey !== key) el('fd_results').innerHTML = '<div class="fd-skel"></div>'.repeat(3);
        getJSON('scripts/food/search.php?meal=' + sh.meal + '&tab=' + sh.tab + '&q=' + encodeURIComponent(q)).then(function (res) {
            if (!S.sheet || sh.seq !== seq || sh.step !== 'search') return;
            sh.results = res.groups || [];
            sh.rq = q;
            sh.rkey = key;
            if (!q) sh.tabs[key] = sh.results;
            drawResults();
        });
    }

    // źródło produktu; danie: Twoje / domownika / spoza domu (samo imię autora)
    function tagHTML(p) {
        var t, cls = p.source;
        if (p.source === 'dish') {
            t = p.mine ? 'Danie domowe' : (p.home ? 'Danie domowe' : 'Danie społeczności') + (p.author ? ' · ' + p.author : '');
            if (!p.mine && !p.home) cls += ' community';
        } else if (p.source === 'user' && p.status === 'approved') {
            // sprawdzony przez administratora = część bazy HomeList
            t = 'HomeList';
            cls += ' is-hl';
        } else t = p.status === 'pending' ? 'Czeka na akceptację' : TAGS[p.source] || '';
        return '<span class="fd-tag ' + cls + '">' + esc(t) + '</span>';
    }

    function rowHTML(p, gi, ii) {
        var sub = [p.brand, p.quantity].filter(Boolean).join(' · ');
        var d = p.default, du = d.label ? qty(d.grams / d.unit_g) + ' × ' + d.label : dec(d.grams) + ' ' + (p.per_ml ? 'ml' : 'g');
        return '<div style="display:flex;align-items:center;gap:4px">'
            + '<button type="button" class="fd-row" style="flex:1;min-width:0" data-open="' + gi + ':' + ii + '">'
            + (p.photo ? '<img class="fd-thumb" src="' + esc(p.photo) + '" alt="" loading="lazy">' : '')
            + '<div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(p.name) + '</div>'
            + '<div class="text-xs fd-mut" style="display:flex;align-items:center;gap:6px;margin-top:2px;white-space:nowrap;overflow:hidden">'
            + tagHTML(p) + esc(sub) + '</div></div>'
            + '<div style="text-align:right;flex:none"><div class="fd-num" style="font-size:14px;font-weight:600">' + fmt(p.kcal) + '</div><div class="fd-mut" style="font-size:11px">kcal / 100 ' + (p.per_ml ? 'ml' : 'g') + '</div></div></button>'
            + '<button type="button" class="fd-plus fd-press" data-quick="' + gi + ':' + ii + '" title="Dodaj od razu: ' + esc(du) + '" aria-label="Dodaj od razu: ' + esc(p.name + ', ' + du) + '">' + I.plus + '</button></div>';
    }

    // kafelek akcji (stopka zakładek i wyników)
    function tileHTML(attr, ico, tint, title, sub) {
        return '<button type="button" class="fd-tile fd-press" ' + attr + '><span class="fd-row-ico" style="' + tint + '">' + ico + '</span>'
            + '<b>' + title + '</b><span>' + sub + '</span></button>';
    }

    function emptyHTML(ico, title, text) {
        return '<div class="hl-empty" style="padding:22px 0 10px"><div class="hl-empty-ico">' + ico + '</div><h3>' + title + '</h3><p>' + text + '</p></div>';
    }

    function drawResults() {
        var sh = S.sheet, box = el('fd_results');
        if (!box) return;
        // produkty z OFF zapisane przy wcześniejszych wyszukiwaniach są już w wynikach lokalnych – bez duplikatów
        var seen = {};
        (sh.results || []).forEach(function (g) { g.items.forEach(function (p) { seen[p.id] = 1; }); });
        var offGroups = (sh.rq && sh.off && sh.off.groups ? sh.off.groups : []).map(function (g) {
            return Object.assign({}, g, { items: g.items.filter(function (p) { return !seen[p.id]; }) });
        }).filter(function (g) { return g.items.length; });
        var groups = (sh.results || []).concat(offGroups);
        var q = (sh.rq || '').trim(), tab = q ? 'query' : sh.tab;
        var titled = tab !== 'dishes'; // w „Twoich daniach” nagłówek powtarzałby nazwę zakładki
        var list = groups.map(function (g, gi) {
            return '<div style="margin-bottom:10px">' + (titled ? '<span class="fd-eyebrow" style="padding:6px 8px">' + esc(g.title) + '</span>' : '')
                + g.items.map(function (p, ii) { return rowHTML(p, gi, ii); }).join('') + '</div>';
        }).join('');
        var quickTiles = '<div class="fd-tiles">'
            + tileHTML('data-quickkcal', I.bolt, 'background:rgba(74,222,128,.14);color:#16a34a', 'Same kalorie', 'np. z menu restauracji')
            + tileHTML('data-own', I.plus, 'background:rgba(56,189,248,.14);color:#0284c7', 'Własny produkt', 'wartości z etykiety') + '</div>';
        var html = '';

        if (tab === 'query') {
            html = list || '<p class="fd-mut" style="margin:8px;font-size:13px">W bazie HomeList nie ma „' + esc(q) + '”.</p>';
            if (!sh.off) {
                // lokalna baza ma tylko część OFF – przy niewielu wynikach mocniej zachęcamy do szukania dalej
                html += '<button type="button" class="fd-row fd-press fd-offbtn" data-off>'
                    + '<span class="fd-row-ico">' + I.search + '</span>'
                    + '<div style="flex:1;min-width:0"><div style="font-size:14px;font-weight:600">'
                    + (groups.length ? 'Nie widzisz swojego produktu? Szukaj dalej' : 'Szukaj „' + esc(q) + '” w Open Food Facts') + '</div>'
                    + '<div class="text-xs fd-mut">Miliony produktów z kodami – znalezione zapiszą się w HomeList</div></div>'
                    + '<span class="fd-offbtn-go">' + I.chev + '</span></button>';
            } else if (sh.off === 'loading') {
                html += '<div class="fd-skel"></div><div class="fd-skel"></div>';
            } else if (sh.off.message) {
                html += '<p class="fd-mut" style="margin:8px;font-size:13px">' + esc(sh.off.message) + '</p>';
            } else if (!(sh.off.groups || []).length) {
                html += '<p class="fd-mut" style="margin:8px;font-size:13px">Open Food Facts też tego nie zna.</p>';
            }
            html += '<span class="fd-eyebrow" style="padding:14px 8px 6px">Nie ma tego, czego szukasz?</span>' + quickTiles;
        } else if (tab === 'recent') {
            html = (list || emptyHTML(I.search, 'Czego szukasz?', 'Tu pojawią się produkty, które jadasz najczęściej – najpierw te z tego posiłku.'))
                + '<span class="fd-eyebrow" style="padding:14px 8px 6px">Inaczej</span>' + quickTiles;
        } else if (tab === 'dishes') {
            html = '<button type="button" class="fd-new fd-press" data-dish>' + I.plus + '<span><b>Nowe danie domowe</b><small>Z kilku składników – zjedzą je też domownicy</small></span></button>'
                + (list || emptyHTML(I.pot, 'Nie masz jeszcze dań', 'Zupa, sałatka, ciasto – zapisz przepis raz i dodawaj go jednym kliknięciem.'))
                + (list ? '<button type="button" class="fd-link fd-press" data-mydishes>' + I.list + 'Zarządzaj daniami</button>' : '');
        } else {
            html = list || emptyHTML(I.pot, 'Na razie pusto', 'Tu zobaczysz dania domowników i przepisy udostępnione przez innych w HomeList.');
        }
        box.innerHTML = '<div class="fd-res' + (sh.slide ? ' ' + sh.slide : '') + '">' + html + '</div>';
        sh.slide = null;

        var pick = function (attr) {
            var a = attr.split(':');
            return groups[+a[0]].items[+a[1]];
        };
        var on = function (sel, fn) { var b = box.querySelector(sel); if (b) b.addEventListener('click', fn); };
        box.querySelectorAll('[data-open]').forEach(function (b) {
            b.addEventListener('click', function () { openPortion(pick(b.dataset.open), null); });
        });
        box.querySelectorAll('[data-quick]').forEach(function (b) {
            b.addEventListener('click', function () {
                var p = pick(b.dataset.quick), d = p.default;
                b.disabled = true;
                addProduct(p, d.grams, d.label ? { label: d.label, grams: d.unit_g } : null, sh.meal).then(function () { b.disabled = false; });
            });
        });
        on('[data-off]', function () {
            sh.off = 'loading';
            drawResults();
            getJSON('scripts/food/off_search.php?q=' + encodeURIComponent(q)).then(function (res) {
                if (!S.sheet || S.sheet !== sh) return;
                sh.off = res.status === 'success' ? { groups: res.groups } : { groups: [], message: res.message };
                drawResults();
            }).catch(function () {
                sh.off = { groups: [], message: 'Brak połączenia z Open Food Facts' };
                drawResults();
            });
        });
        var cap = q ? q.charAt(0).toUpperCase() + q.slice(1) : '';
        on('[data-own]', function () { S.sheet.own = { name: cap }; setStep('own'); });
        on('[data-quickkcal]', function () { S.sheet.quick = { name: cap }; setStep('quick'); });
        on('[data-dish]', function () { S.sheet.dish = newDish(); setStep('dish'); });
        on('[data-mydishes]', function () { setStep('dishes'); });
    }

    // ---------- porcja ----------
    function openEntry(id) {
        var e = findEntry(id);
        if (!e) return;
        if (e.product_id === null || e.grams === null) {
            return openSheet({ step: 'quick', meal: e.meal, quick: { entry: e, name: e.name, kcal: e.kcal, protein: e.protein, fat: e.fat, carbs: e.carbs } });
        }
        openSheet({ step: 'loading', meal: e.meal });
        var sh = S.sheet;
        getJSON('scripts/food/product.php?id=' + e.product_id).then(function (res) {
            if (S.sheet !== sh) return;
            // produkt usunięty z bazy – edytujemy samą kopię z dziennika
            var p = res.status === 'success' ? res.product : { id: e.product_id, name: e.name, source: 'user', per_ml: e.ml, units: [{ label: e.ml ? '100 ml' : '100 g', grams: 100, base: true }], quality: null, missing: true };
            openPortion(p, e, res.status === 'success');
        });
    }

    function openPortion(p, entry, detailed, source) {
        var sh = S.sheet;
        // edycja: wartości z kopii w dzienniku (tak liczy serwer), nowy wpis: aktualne wartości produktu
        var p100 = entry
            ? { kcal: entry.kcal / entry.grams * 100, protein: entry.protein / entry.grams * 100, fat: entry.fat / entry.grams * 100, carbs: entry.carbs / entry.grams * 100 }
            : { kcal: p.kcal, protein: p.protein, fat: p.fat, carbs: p.carbs };
        var d = entry ? { grams: entry.grams, label: entry.unit_label, unit_g: entry.unit_g } : p.default;
        var grams = d.grams;
        var scale = grams <= 40 ? { ppg: 8, step: 1, major: 5, label: 10 } : grams <= 450 ? { ppg: 4, step: 5, major: 10, label: 20 } : { ppg: 1.6, step: 10, major: 50, label: 100 };
        // orig = obiekt z listy wyników – nowa jednostka trafia też tam, żeby była po powrocie do wyszukiwarki
        sh.p = { product: p, orig: p, entry: entry, source: source, p100: p100, grams: grams, unit: d.label && d.unit_g ? { label: d.label, grams: d.unit_g } : null, scale: scale, quality: detailed ? p.quality : undefined };
        if (entry) sh.meal = entry.meal;
        setStep('portion', false);
        if (!detailed && !entry) {
            getJSON('scripts/food/product.php?id=' + p.id).then(function (res) {
                if (!S.sheet || S.sheet.p !== sh.p || res.status !== 'success') return;
                sh.p.quality = res.product.quality;
                sh.p.product = Object.assign({}, p, { sugars: res.product.sugars, fiber: res.product.fiber, sat_fat: res.product.sat_fat, salt: res.product.salt, dish: res.product.dish, owner: res.product.owner });
                var q = el('fd_quality');
                if (q) q.innerHTML = qualityHTML(sh.p);
                bindQuality();
                var ns = el('fd_ns_top');
                if (ns) { ns.innerHTML = nsMiniHTML(sh.p.quality); bindNsMini(); }
                if (q && res.product.dish && res.product.dish.mine && !el('fd_step').querySelector('[data-editdish]')) {
                    q.insertAdjacentHTML('beforebegin', '<button type="button" class="fd-ghost-btn fd-press" data-editdish style="margin-top:16px">' + I.pot + 'Edytuj przepis dania</button>');
                    bindEditDish();
                }
            });
        }
    }

    // wielokrotności (5 × wafel) ustawia licznik pod gramaturą, więc chipy to same jednostki
    function unitsFor(pp) {
        var units = (pp.product.units || []).filter(function (u) { return !u.base; });
        var chips = units.map(function (u) { return { label: u.label + ' · ' + dec(u.grams) + ' ' + (pp.product.per_ml ? 'ml' : 'g'), grams: u.grams, unit: u }; });
        chips.push({ label: pp.product.per_ml ? '100 ml' : '100 g', grams: 100, unit: null });
        return chips;
    }

    // jednostka licznika: wybrana albo pierwsza z produktu
    function portionRef(pp) {
        return pp.unit || (pp.product.units || []).filter(function (x) { return !x.base; })[0] || null;
    }

    function setUnits(pp, units) {
        pp.product.units = units;
        if (pp.orig) pp.orig.units = units;
    }

    // ---------- własna jednostka (np. opakowanie, którego nie zna OFF) ----------
    var UNIT_PRESETS = ['opakowanie', 'sztuka', 'plaster', 'kromka', 'łyżka', 'szklanka'];

    function renderAddUnit() {
        var pp = S.sheet && S.sheet.p, box = el('fd_addunit');
        if (!pp || !box) return;
        if (!pp.add) { box.innerHTML = ''; return; }
        var u = pp.product.per_ml ? 'ml' : 'g', a = pp.add;
        var mine = (pp.product.units || []).filter(function (x) { return x.mine; });
        box.innerHTML = '<div class="fd-box" style="margin-top:12px"><span class="fd-eyebrow">Nowa jednostka</span>'
            + '<div class="fd-chips" style="margin-top:10px">' + UNIT_PRESETS.map(function (l) {
                return '<button type="button" class="fd-chip fd-press' + (a.label === l ? ' is-on' : '') + '" data-ulabel="' + esc(l) + '">' + esc(l) + '</button>';
            }).join('') + '</div>'
            + '<div style="display:flex;gap:8px;margin-top:10px"><div class="fd-field" style="flex:1"><input id="fd_ul" type="text" maxlength="30" placeholder="np. opakowanie" aria-label="Nazwa jednostki" value="' + esc(a.label) + '"></div>'
            + '<label class="fd-field" style="width:110px;padding-right:12px"><input id="fd_ug" type="text" inputmode="decimal" placeholder="' + (u === 'ml' ? '500' : '150') + '" aria-label="Waga jednostki" value="' + esc(a.grams) + '"><span class="text-xs fd-mut">' + u + '</span></label></div>'
            + '<p class="fd-mut" style="font-size:12px;margin-top:8px">Waga całej jednostki, np. opakowania z etykiety. U Ciebie od razu, dla innych po akceptacji administratora.</p>'
            + mine.map(function (x) {
                return '<div style="display:flex;align-items:center;gap:8px;margin-top:8px;font-size:13px"><span style="flex:1;min-width:0">' + esc(x.label) + ' · ' + dec(x.grams) + ' ' + u
                    + ' <span class="fd-mut">– ' + (x.status === 'rejected' ? 'odrzucona, tylko u Ciebie' : 'czeka na akceptację') + '</span></span>'
                    + '<button type="button" class="fd-cnt fd-press" data-udel="' + x.id + '" aria-label="Usuń jednostkę">' + I.x + '</button></div>';
            }).join('')
            + '<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px"><button type="button" class="fd-ghost-btn fd-press" data-ucancel>Anuluj</button>'
            + '<button type="button" class="fd-ghost-btn fd-press" data-usave>' + I.check + 'Zapisz</button></div></div>';
        el('fd_ul').addEventListener('input', function () { a.label = this.value; box.querySelectorAll('[data-ulabel]').forEach(function (b) { b.classList.toggle('is-on', b.dataset.ulabel === a.label.trim()); }); });
        el('fd_ug').addEventListener('input', function () { a.grams = this.value; });
        box.querySelectorAll('[data-ulabel]').forEach(function (b) {
            b.addEventListener('click', function () { a.label = b.dataset.ulabel; renderAddUnit(); el('fd_ug').focus(); });
        });
        box.querySelector('[data-ucancel]').addEventListener('click', function () { pp.add = null; renderAddUnit(); });
        box.querySelector('[data-usave]').addEventListener('click', function () { saveUnit(pp, this); });
        box.querySelectorAll('[data-udel]').forEach(function (b) {
            b.addEventListener('click', function () {
                var id = +b.dataset.udel;
                b.disabled = true;
                post('scripts/food/unit_save.php', { product_id: pp.product.id, delete: id }).then(function (res) {
                    if (res.status !== 'success') { b.disabled = false; return fail(res); }
                    setUnits(pp, res.product.units);
                    if (pp.unit && pp.unit.id === id) pp.unit = null;
                    renderAddUnit();
                    updatePortion();
                });
            });
        });
    }

    function saveUnit(pp, btn) {
        var label = pp.add.label.trim().toLowerCase(), grams = parseFloat(String(pp.add.grams).replace(',', '.'));
        btn.disabled = true;
        post('scripts/food/unit_save.php', { product_id: pp.product.id, label: label, grams: isNaN(grams) ? '' : grams }).then(function (res) {
            btn.disabled = false;
            if (res.status !== 'success') return fail(res);
            setUnits(pp, res.product.units);
            var g = Math.round(grams * 10) / 10;
            var nu = res.product.units.filter(function (x) { return !x.base && x.label === label && Math.abs(x.grams - g) < 0.05; })[0];
            pp.add = null;
            renderAddUnit();
            toast(res.message);
            if (nu) { pp.unit = nu; setGrams(nu.grams); } else updatePortion();
        });
    }

    // zdjęcie dania na górze arkusza; podpis = kaloryczność jednej porcji
    function heroPhotoHTML(p) {
        if (!p.photo) return '';
        var unit = (p.units || []).filter(function (x) { return !x.base; })[0];
        return '<figure class="fd-hero-photo"><img src="' + esc(p.photo) + '" alt="' + esc(p.name) + '">'
            + (unit ? '<figcaption><b class="fd-num">' + fmt(p.kcal * unit.grams / 100) + ' kcal</b> · 1 ' + esc(unit.label) + ' (' + fmt(unit.grams) + ' ' + (p.per_ml ? 'ml' : 'g') + ')</figcaption>' : '')
            + '</figure>';
    }

    function renderPortion(box) {
        var sh = S.sheet, pp = sh.p, p = pp.product, u = p.per_ml ? 'ml' : 'g';
        var sub = [p.brand, p.quantity].filter(Boolean).join(' · ');
        box.innerHTML = '<div class="fd-scroll" style="padding:4px 20px 12px">'
            + '<div style="display:flex;align-items:center;gap:4px;margin-left:-8px">'
            + '<button type="button" class="fd-icon-btn fd-press" data-back aria-label="' + (pp.entry ? 'Zamknij' : 'Wróć') + '">' + (pp.entry ? I.x : I.back) + '</button>'
            + '<div style="flex:1;min-width:0"><h2 class="hl-popup-title" style="padding:0;font-size:17px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + esc(p.name) + '</h2>'
            + '<div class="text-xs fd-mut" style="display:flex;align-items:center;gap:6px;white-space:nowrap;overflow:hidden">' + tagHTML(p) + esc(sub) + '</div></div>'
            + '<span id="fd_ns_top" style="display:contents">' + nsMiniHTML(pp.quality) + '</span></div>'
            + heroPhotoHTML(p)
            + '<div style="margin-top:12px">' + mealChips() + '</div>'
            + '<div style="display:flex;align-items:center;justify-content:space-between;margin-top:18px">'
            + '<button type="button" class="fd-round fd-press" data-step="-1" aria-label="Mniej">' + I.minus + '</button>'
            + '<div style="text-align:center"><div style="display:flex;align-items:baseline;justify-content:center;gap:4px">'
            + '<input class="fd-big fd-num" id="fd_grams" type="text" inputmode="decimal" aria-label="Ilość w ' + (u === 'ml' ? 'mililitrach' : 'gramach') + '" value="' + dec(pp.grams) + '">'
            + '<span style="font-size:22px;font-weight:500" class="fd-mut">' + u + '</span></div>'
            + '<div id="fd_unitline" class="fd-mut fd-unitline"></div></div>'
            + '<button type="button" class="fd-round fd-press" data-step="1" aria-label="Więcej">' + I.plus + '</button></div>'
            + '<div class="fd-ruler" id="fd_ruler" style="margin-top:14px" aria-hidden="true"><div id="fd_ticks"></div><span class="fd-needle"></span></div>'
            + '<div class="fd-chips" id="fd_units" style="margin-top:12px"></div>'
            + '<div id="fd_addunit"></div>'
            + '<div class="fd-mini" id="fd_mini" style="margin-top:16px"></div>'
            + '<div class="fd-box" style="margin-top:16px"><span class="fd-eyebrow">Twój dzień po ' + (pp.entry ? 'zmianie' : 'dodaniu') + '</span>'
            + '<div id="fd_preview" style="display:flex;flex-direction:column;gap:10px;margin-top:10px"></div></div>'
            + (p.dish && p.dish.mine ? '<button type="button" class="fd-ghost-btn fd-press" data-editdish style="margin-top:16px">' + I.pot + 'Edytuj przepis dania</button>' : '')
            + '<div id="fd_quality">' + qualityHTML(pp) + '</div>'
            + '</div>'
            + '<div class="fd-foot">' + (pp.entry ? '<button type="button" class="fd-danger fd-press" data-del aria-label="Usuń z dziennika">' + I.trash + '</button>' : '')
            + '<button type="button" class="fd-cta fd-press" data-commit></button></div>';

        box.querySelector('[data-back]').addEventListener('click', function () {
            if (pp.entry) return closeSheet();
            sh.p = null;
            setStep(sh.info ? 'info' : 'search', true);
        });
        bindMealChips(box, updatePortion);
        box.querySelectorAll('[data-step]').forEach(function (b) {
            b.addEventListener('click', function () {
                var s = pp.scale.step, dir = +b.dataset.step;
                setGrams(dir > 0 ? Math.floor(pp.grams / s) * s + s : Math.ceil(pp.grams / s) * s - s);
            });
        });
        var input = el('fd_grams');
        input.addEventListener('input', function () {
            fitGrams(input);
            var v = parseFloat(input.value.replace(',', '.'));
            if (!isNaN(v)) setGrams(v, true);
        });
        input.addEventListener('focus', function () { input.select(); });
        input.addEventListener('blur', function () { input.value = dec(pp.grams); fitGrams(input); });
        // licznik sztuk: kolejna cała jednostka w górę / w dół (poniżej jednej – pół)
        el('fd_unitline').addEventListener('click', function (ev) {
            var b = ev.target.closest('[data-cnt]'), ref = portionRef(pp);
            if (!b || !ref) return;
            var n = pp.grams / ref.grams, eps = 0.02;
            n = +b.dataset.cnt > 0 ? Math.floor(n + eps) + 1 : n > 1 + eps ? Math.ceil(n - eps) - 1 : 0.5;
            pp.unit = ref;
            vib();
            setGrams(n * ref.grams);
        });
        bindRuler();
        box.querySelector('[data-commit]').addEventListener('click', commitPortion);
        var del = box.querySelector('[data-del]');
        if (del) del.addEventListener('click', function () { var id = pp.entry.id; closeSheet(true); removeEntry(id); });
        bindQuality();
        bindNsMini();
        bindEditDish();
        updatePortion();
    }

    // mała skala Nutri-Score A–E w nagłówku (tylko gdy OFF ją podaje); dotknięcie rozwija „Skład i jakość”
    function nsMiniHTML(q) {
        if (!q || !q.nutriscore) return '';
        var g = q.nutriscore;
        return '<button type="button" class="fd-nsmini fd-press" data-nsjump title="Nutri-Score ' + g.toUpperCase() + '" aria-label="Nutri-Score ' + g.toUpperCase() + ' – pokaż skład i jakość">'
            + '<span class="fd-nsmini-k">Nutri-Score</span><span class="fd-nsmini-row" aria-hidden="true">'
            + ['a', 'b', 'c', 'd', 'e'].map(function (x) { return '<i class="' + x + (x === g ? ' is-on' : '') + '">' + x + '</i>'; }).join('')
            + '</span></button>';
    }
    function bindNsMini() {
        var b = document.querySelector('#fd_ns_top [data-nsjump]');
        if (!b) return;
        b.addEventListener('click', function () {
            var box = el('fd_q_box');
            if (!box) return;
            if (!box.classList.contains('is-open')) {
                box.classList.add('is-open');
                box.querySelector('[data-qtoggle]').setAttribute('aria-expanded', 'true');
            }
            setTimeout(function () { box.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 200);
        });
    }

    // pole szerokie na treść, żeby liczba + jednostka stały na środku między − a +
    function fitGrams(input) {
        input.style.width = (Math.max(1, input.value.length) + 0.3) + 'ch';
    }

    function setGrams(g, typing) {
        var pp = S.sheet && S.sheet.p;
        if (!pp) return;
        pp.grams = Math.max(0, Math.min(5000, Math.round(g * 10) / 10));
        // jednostka zostaje, póki ilość to wielokrotność połówek (1,5 × kubek); inaczej wpis w samych gramach
        if (pp.unit) {
            var n = pp.grams / pp.unit.grams;
            if (Math.abs(n * 2 - Math.round(n * 2)) > 0.02 || n <= 0) pp.unit = null;
        }
        updatePortion(typing);
    }

    function updatePortion(typing) {
        var sh = S.sheet, pp = sh && sh.p;
        if (!pp || sh.step !== 'portion') return;
        var p = pp.product, u = p.per_ml ? 'ml' : 'g', m = per(pp.grams, pp.p100);
        var input = el('fd_grams');
        if (input && !typing && document.activeElement !== input) input.value = dec(pp.grams);
        if (input) fitGrams(input);
        var ref = portionRef(pp);
        el('fd_unitline').innerHTML = ref
            ? '<button type="button" class="fd-cnt fd-press" data-cnt="-1" aria-label="Odejmij: ' + esc(ref.label) + '">' + I.minus + '</button>'
                + '<span><b class="fd-num">' + qty(pp.grams / ref.grams) + '</b> × ' + esc(ref.label) + ' (' + dec(ref.grams) + ' ' + u + ')</span>'
                + '<button type="button" class="fd-cnt fd-press" data-cnt="1" aria-label="Dodaj: ' + esc(ref.label) + '">' + I.plus + '</button>'
            : 'na 100 ' + u + ': ' + fmt(pp.p100.kcal) + ' kcal';
        // linijka
        var sc = pp.scale, W = el('fd_ruler').clientWidth || 350, C = W / 2, span = C / sc.ppg + sc.step, html = '';
        for (var v = Math.ceil((pp.grams - span) / sc.step) * sc.step; v <= pp.grams + span; v += sc.step) {
            if (v < 0) continue;
            var x = (C + (v - pp.grams) * sc.ppg).toFixed(1), major = Math.abs(v % sc.major) < 1e-6;
            html += '<span class="fd-tick' + (major ? ' is-major' : '') + '" style="left:' + x + 'px;height:' + (major ? 22 : 12) + 'px"></span>';
            if (Math.abs(v % sc.label) < 1e-6) html += '<span class="fd-tick-lbl" style="left:' + x + 'px">' + v + '</span>';
        }
        el('fd_ticks').innerHTML = html;
        el('fd_units').innerHTML = unitsFor(pp).map(function (c, i) {
            var on = Math.abs(c.grams - pp.grams) < 0.05 && ((c.unit && pp.unit && c.unit.label === pp.unit.label) || (!c.unit && !pp.unit));
            return '<button type="button" class="fd-chip fd-press' + (on ? ' is-on' : '') + '" data-unit="' + i + '">' + esc(c.label) + '</button>';
        }).join('') + (p.missing ? '' : '<button type="button" class="fd-chip fd-chip-add fd-press" data-addunit aria-label="Dodaj jednostkę, np. opakowanie">' + I.plus + 'Jednostka</button>');
        var add = el('fd_units').querySelector('[data-addunit]');
        if (add) add.addEventListener('click', function () {
            var has = (p.units || []).some(function (x) { return x.label === 'opakowanie'; });
            pp.add = pp.add ? null : { label: has ? 'sztuka' : 'opakowanie', grams: '' };
            renderAddUnit();
            if (pp.add) el('fd_ug').focus();
        });
        el('fd_units').querySelectorAll('[data-unit]').forEach(function (b) {
            b.addEventListener('click', function () {
                var c = unitsFor(pp)[+b.dataset.unit];
                pp.unit = c.unit;
                pp.grams = c.grams;
                updatePortion();
            });
        });
        el('fd_mini').innerHTML = '<div><b class="fd-num">' + fmt(m.kcal) + '</b><span>kcal</span></div>'
            + '<div><b class="fd-num" style="color:var(--fd-p)">' + dec(m.protein) + '</b><span>białko g</span></div>'
            + '<div><b class="fd-num" style="color:var(--fd-f)">' + dec(m.fat) + '</b><span>tłuszcz g</span></div>'
            + '<div><b class="fd-num" style="color:var(--fd-c)">' + dec(m.carbs) + '</b><span>węgle g</span></div>';
        var pv = preview();
        el('fd_preview').innerHTML = barsHTML(pv.base, pv.ghost, S.data.day.goal, [['kcal', 'Kalorie', 'var(--fd-k)']].concat(MACROS), true);
        var cta = el('fd_step').querySelector('[data-commit]');
        cta.textContent = (pp.entry ? 'Zapisz zmiany' : 'Dodaj ' + MEAL_TO[sh.meal]) + ' · ' + fmt(m.kcal) + ' kcal';
        cta.disabled = pp.grams <= 0;
        renderHero();
    }

    function bindRuler() {
        var r = el('fd_ruler'), drag = null;
        r.addEventListener('pointerdown', function (ev) {
            var pp = S.sheet.p;
            drag = { x: ev.clientX, g: pp.grams };
            try { r.setPointerCapture(ev.pointerId); } catch (e) {}
        });
        r.addEventListener('pointermove', function (ev) {
            var pp = S.sheet && S.sheet.p;
            if (!drag || !pp) return;
            var g = Math.max(0, drag.g - (ev.clientX - drag.x) / pp.scale.ppg);
            g = pp.scale.step >= 5 ? Math.round(g) : Math.round(g * 10) / 10;
            if (Math.floor(g / pp.scale.major) !== Math.floor(pp.grams / pp.scale.major)) vib();
            if (g !== pp.grams) setGrams(g);
        });
        var up = function () {
            var pp = S.sheet && S.sheet.p;
            if (!drag || !pp) return;
            drag = null;
            var s = pp.scale.step;
            if (!pp.unit) setGrams(Math.max(s, Math.round(pp.grams / s) * s));
        };
        r.addEventListener('pointerup', up);
        r.addEventListener('pointercancel', up);
        r.addEventListener('wheel', function (ev) {
            ev.preventDefault();
            var pp = S.sheet.p, s = pp.scale.step;
            setGrams(Math.round(pp.grams / s) * s + ((ev.deltaY || ev.deltaX) > 0 ? s : -s));
        }, { passive: false });
    }

    function commitPortion() {
        var sh = S.sheet, pp = sh.p, btn = el('fd_step').querySelector('[data-commit]');
        btn.disabled = true;
        if (!pp.entry) return addProduct(pp.product, pp.grams, pp.unit, sh.meal, pp.source).then(function () { if (btn.isConnected) btn.disabled = false; });
        post('scripts/food/log_edit.php', {
            id: pp.entry.id, grams: pp.grams, meal: sh.meal, unit_label: pp.unit ? pp.unit.label : '', unit_g: pp.unit ? pp.unit.grams : ''
        }).then(function (res) {
            if (res.status !== 'success') { btn.disabled = false; return fail(res); }
            closeSheet(true);
            afterChange(res.entry.id);
            toast('Zapisano: ' + res.entry.name + ', ' + dec(res.entry.grams) + ' ' + (pp.product.per_ml ? 'ml' : 'g'));
        });
    }

    // ---------- skład i jakość ----------
    function qualityHTML(pp) {
        var q = pp.quality, p = pp.product;
        if (q === undefined) return '<div class="fd-skel" style="margin-top:16px;height:44px"></div>';
        var u = p.per_ml ? 'ml' : 'g', chips = [], body = '';
        if (q && q.nutriscore) chips.push('<span class="fd-pill"><span class="fd-ns ' + q.nutriscore + '">' + q.nutriscore + '</span>Nutri-Score</span>');
        if (q && q.nova) chips.push('<span class="fd-pill">NOVA ' + q.nova + '</span>');
        var flagged = q && q.additives ? q.additives.filter(function (a) { return a.risk === 'high' || a.risk === 'moderate'; }) : [];
        if (flagged.length) chips.push('<span class="fd-pill"><span class="fd-dot high"></span>' + flagged.length + ' ' + (flagged.length === 1 ? 'dodatek' : 'dodatki') + ' do uwagi</span>');
        if (q && q.palm_oil === 1) chips.push('<span class="fd-pill">olej palmowy</span>');

        // wartości na 100 g/ml
        var p100 = pp.entry ? pp.p100 : p;
        var row = function (label, v, sub) { return v === null || v === undefined ? '' : '<tr' + (sub ? ' class="is-sub"' : '') + '><td>' + label + '</td><td class="fd-num">' + dec(v) + (label === 'Energia' ? ' kcal' : ' g') + '</td></tr>'; };
        body += '<span class="fd-eyebrow" style="margin-top:14px">Na 100 ' + u + '</span><table class="fd-table" style="margin-top:4px"><tbody>'
            + row('Energia', p100.kcal) + row('Tłuszcz', p100.fat) + row('w tym nasycone', p.sat_fat, true)
            + row('Węglowodany', p100.carbs) + row('w tym cukry', p.sugars, true) + row('Błonnik', p.fiber) + row('Białko', p100.protein) + row('Sól', p.salt)
            + '</tbody></table>';

        if (q && q.levels && Object.keys(q.levels).length) {
            var names = { fat: 'tłuszcz', sat_fat: 'tł. nasycone', sugars: 'cukry', salt: 'sól' }, lv = { low: 'mało', moderate: 'średnio', high: 'dużo' };
            body += '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px">' + Object.keys(q.levels).map(function (k) {
                return '<span class="fd-pill"><span class="fd-dot ' + q.levels[k] + '"></span>' + names[k] + ': ' + lv[q.levels[k]] + '</span>';
            }).join('') + '</div>';
        }
        if (q && q.ingredients) {
            body += '<span class="fd-eyebrow" style="margin-top:16px">Skład' + (q.ingredients_lang && q.ingredients_lang !== 'pl' ? ' (' + esc(q.ingredients_lang) + ')' : '') + '</span>'
                + '<p class="fd-ingr" style="margin:4px 0 0">' + ingredientsHTML(q.ingredients, flagged) + '</p>';
        }
        if (q && (q.allergens || []).length) body += '<p style="margin:10px 0 0;font-size:13px"><b>Alergeny:</b> <span class="fd-mut">' + esc(q.allergens.join(', ')) + '</span></p>';
        if (q && (q.traces || []).length) body += '<p style="margin:4px 0 0;font-size:13px"><b>Może zawierać:</b> <span class="fd-mut">' + esc(q.traces.join(', ')) + '</span></p>';
        if (q && (q.additives || []).length) {
            body += '<span class="fd-eyebrow" style="margin-top:16px">Dodatki</span><div style="display:flex;flex-direction:column;gap:6px;margin-top:6px">'
                + q.additives.map(function (a) {
                    var note = a.risk === 'high' ? 'EFSA: ryzyko przekroczenia bezpiecznej dawki przy częstym jedzeniu' : a.risk === 'moderate' ? 'EFSA: umiarkowane ryzyko przekroczenia bezpiecznej dawki' : '';
                    return '<div style="display:flex;gap:8px;font-size:13px"><span class="fd-dot ' + (a.risk === 'high' ? 'high' : a.risk === 'moderate' ? 'moderate' : a.risk === 'none' ? 'low' : 'unknown') + '" style="margin-top:6px"></span>'
                        + '<div><b style="font-weight:600">' + esc(a.code) + '</b> <span class="fd-mut">' + esc(a.name || '') + '</span>'
                        + (note ? '<div class="fd-mut" style="font-size:12px">' + note + (a.url ? ' · <a href="' + esc(a.url) + '" target="_blank" rel="noopener" class="underline">źródło</a>' : '') + '</div>' : '') + '</div></div>';
                }).join('') + '</div>';
        }
        var diet = [];
        if (q && q.vegan === 1) diet.push('wegańskie'); else if (q && q.vegetarian === 1) diet.push('wegetariańskie');
        if (q && q.palm_oil === 0) diet.push('bez oleju palmowego');
        if (diet.length) body += '<p class="fd-mut" style="margin:12px 0 0;font-size:12px">' + diet.join(' · ') + '</p>';
        if (p.dish) {
            body += '<span class="fd-eyebrow" style="margin-top:16px">Składniki dania' + (p.dish.owner ? ' · ' + esc(p.dish.owner) : '') + '</span>'
                + '<table class="fd-table" style="margin-top:4px"><tbody>' + p.dish.items.map(function (it) {
                    return '<tr><td>' + esc(it.name) + ' <span class="fd-mut">' + (it.unit_label && it.unit_g ? qty(it.grams / it.unit_g) + ' × ' + esc(it.unit_label) + ' · ' : '') + dec(it.grams) + ' ' + (it.per_ml ? 'ml' : 'g') + '</span></td><td class="fd-num">' + fmt(it.kcal) + ' kcal</td></tr>';
                }).join('') + '</tbody></table>'
                + (p.dish.cooked_g ? '<p class="fd-mut" style="margin:6px 0 0;font-size:12px">Waga po ugotowaniu: ' + fmt(p.dish.cooked_g) + ' g</p>' : '');
        }
        var foot = [];
        if (p.source === 'off') foot.push('Dane: Open Food Facts (ODbL)');
        if (p.source === 'user') {
            if (p.owner) foot.push('Dodał(a): ' + esc(p.owner));
            foot.push(p.status === 'approved' ? 'sprawdzone przez HomeList' : p.status === 'pending' ? 'czeka na akceptację' : 'odrzucone – widzisz tylko Ty');
        }
        if (p.code) foot.push('kod ' + esc(p.code));
        if (foot.length) body += '<p class="fd-mut" style="margin:14px 0 0;font-size:11px">' + foot.join(' · ') + '</p>';
        if (p.source === 'user' && p.mine && p.status !== 'approved' && !pp.entry) {
            body += '<button type="button" class="fd-ghost-btn fd-press" data-editown style="margin-top:12px">' + I.list + 'Popraw dane produktu</button>';
        }

        return '<div class="fd-q" id="fd_q_box" style="margin-top:16px">'
            + '<button type="button" class="fd-q-head" data-qtoggle aria-expanded="false"><span class="fd-eyebrow">Skład i jakość</span>'
            + '<span style="display:flex;flex-wrap:wrap;gap:6px;margin-left:6px">' + chips.slice(0, 3).join('') + '</span>' + I.chev.replace('<svg', '<svg class="fd-q-chev"') + '</button>'
            + '<div class="fd-q-body"><div>' + body + '</div></div></div>';
    }

    // skład z OFF: _alergen_ -> pogrubienie, dodatki z oceną EFSA delikatnie podkreślone
    function ingredientsHTML(text, flagged) {
        var html = esc(text).replace(/_([^_]{1,60})_/g, '<b>$1</b>');
        flagged.forEach(function (a) {
            var code = a.code.replace(/^E/i, '');
            var re = new RegExp('(E\\s?-?' + code + '[a-z]?\\b' + (a.name ? '|' + a.name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') : '') + ')', 'gi');
            html = html.replace(re, '<span class="fd-flag">$1</span>');
        });
        return html;
    }

    function bindQuality() {
        var e = document.querySelector('#fd_q_box [data-editown]');
        if (e) e.addEventListener('click', function () { editOwn((S.sheet.p || S.sheet.info).product.id, 'portion'); });
        var b = document.querySelector('#fd_q_box [data-qtoggle]');
        if (!b) return;
        b.addEventListener('click', function () {
            var box = el('fd_q_box'), open = !box.classList.contains('is-open');
            box.classList.toggle('is-open', open);
            b.setAttribute('aria-expanded', open);
            if (open) setTimeout(function () { box.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }, 200);
        });
    }

    // ---------- szybkie kcal ----------
    function renderQuick(box) {
        var sh = S.sheet, q = sh.quick || {}, e = q.entry;
        var f = function (k, label, color, val) {
            return '<label class="fd-field" style="padding-right:12px"><span class="fd-dot" style="background:' + color + '"></span>'
                + '<input type="text" inputmode="decimal" data-k="' + k + '" placeholder="0" aria-label="' + label + '" value="' + (val !== undefined && val !== null && val !== '' ? dec(val) : '') + '">'
                + '<span class="text-xs fd-mut" style="white-space:nowrap">' + label + '</span></label>';
        };
        box.innerHTML = headHTML(e ? 'Szybki wpis' : 'Dodaj same kalorie', !e)
            + '<div class="fd-scroll" style="padding:8px 20px 12px">' + mealChips()
            + '<label class="hl-label" for="fd_qname" style="margin-top:16px">Nazwa</label>'
            + '<div class="fd-field"><input id="fd_qname" type="text" placeholder="np. Obiad w restauracji" value="' + esc(q.name || '') + '"></div>'
            + '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:14px">'
            + f('kcal', 'kcal', 'var(--fd-k)', q.kcal) + f('protein', 'białko g', 'var(--fd-p)', q.protein)
            + f('fat', 'tłuszcz g', 'var(--fd-f)', q.fat) + f('carbs', 'węgle g', 'var(--fd-c)', q.carbs) + '</div>'
            + '<p class="hl-hint">Makro jest opcjonalne – bez niego wpis liczy się tylko do kalorii.</p></div>'
            + '<div class="fd-foot">' + (e ? '<button type="button" class="fd-danger fd-press" data-del aria-label="Usuń z dziennika">' + I.trash + '</button>' : '')
            + '<button type="button" class="fd-cta fd-press" data-commit>' + (e ? 'Zapisz zmiany' : 'Dodaj ' + MEAL_TO[sh.meal]) + '</button></div>';
        bindHead(box, function () { setStep('search', true); });
        bindMealChips(box, function () { if (!e) box.querySelector('[data-commit]').textContent = 'Dodaj ' + MEAL_TO[sh.meal]; });
        var val = function (k) { var i = box.querySelector('[data-k="' + k + '"]'); return i.value.trim().replace(',', '.'); };
        box.querySelector('[data-commit]').addEventListener('click', function (ev) {
            var btn = ev.currentTarget;
            if (val('kcal') === '' || isNaN(+val('kcal'))) { box.querySelector('[data-k="kcal"]').focus(); return; }
            btn.disabled = true;
            var data = { name: el('fd_qname').value.trim(), kcal: val('kcal'), protein: val('protein'), fat: val('fat'), carbs: val('carbs') };
            var req = e
                ? post('scripts/food/log_edit.php', Object.assign({ id: e.id, meal: sh.meal }, data))
                : post('scripts/food/log_add.php', Object.assign({ date: S.date, meal: sh.meal }, data));
            req.then(function (res) {
                if (res.status !== 'success') { btn.disabled = false; return fail(res); }
                var meal = sh.meal;
                closeSheet(true);
                afterChange(res.entry.id);
                toast(e ? 'Zapisano' : 'Dodano ' + MEAL_TO[meal] + ': ' + fmt(res.entry.kcal) + ' kcal', e ? null : function () {
                    post('scripts/food/log_delete.php', { id: res.entry.id }).then(function () { afterChange(); });
                });
            });
        });
        var del = box.querySelector('[data-del]');
        if (del) del.addEventListener('click', function () { closeSheet(true); removeEntry(e.id); });
        if (!e && window.matchMedia('(hover: hover)').matches) setTimeout(function () { (q.name ? box.querySelector('[data-k="kcal"]') : el('fd_qname')).focus(); }, 350);
    }

    // ---------- policz ze zdjęcia (AI przez czat) ----------
    // Bez płatnego API: polecenie (scripts/food/ai_prompt.php) trafia do schowka, użytkownik wkleja je w czacie (wybór w Konto > Jedzenie) razem ze zdjęciem,
    // a odpowiedź (JSON) wraca przez schowek. ai_match.php dopasowuje składniki do bazy HomeList, ai_log.php zapisuje je jako osobne wpisy.
    var AI_KEY = 'hl_food_ai'; // czas skopiowania polecenia – po powrocie z czatu (także po przeładowaniu PWA) kreator zaczyna od wklejania

    function aiState() {
        var sh = S.sheet;
        if (!sh.ai) {
            var t = 0;
            try { t = +sessionStorage.getItem(AI_KEY) || 0; } catch (e) {}
            sh.ai = { note: '', prompt: null, copied: Date.now() - t < 30 * 60 * 1000, raw: '', err: '', items: null };
        }
        return sh.ai;
    }

    function aiStepsHTML(n) {
        return '<ol class="fd-ai-steps" aria-label="Krok ' + n + ' z 3">' + ['Polecenie', 'Odpowiedź', 'Sprawdź'].map(function (t, i) {
            return '<li class="' + (i + 1 < n ? 'is-done' : i + 1 === n ? 'is-on' : '') + '"' + (i + 1 === n ? ' aria-current="step"' : '') + '><i></i>' + t + '</li>';
        }).join('') + '</ol>';
    }

    function aiPrompt(a) {
        var lines = ['Ten posiłek to ' + MEALS[S.sheet.meal].toLowerCase() + '.'];
        if (a.note.trim()) lines.push('Czego nie widać na zdjęciu: ' + a.note.trim());
        return a.prompt.replace('{{NOTE}}', '\n' + lines.join('\n') + '\n');
    }

    function renderAi(box) {
        var sh = S.sheet, a = aiState(), chat = S.data.settings;
        var li = function (n, title, sub, act) {
            return '<li class="fd-ai-li' + (n === 1 && a.copied ? ' is-done' : '') + '"><span class="fd-ai-n">' + (n === 1 && a.copied ? I.check : n) + '</span>'
                + '<div style="flex:1;min-width:0"><b>' + title + '</b><span>' + sub + '</span></div>' + (act || '') + '</li>';
        };
        box.innerHTML = headHTML('Policz ze zdjęcia', true)
            + '<div class="fd-scroll" style="padding:8px 20px 16px">' + aiStepsHTML(1)
            + '<div class="fd-ai-hero"><div class="fd-ai-flow" aria-hidden="true"><span>' + I.camera + '</span><i></i><span class="is-ai">' + I.chat + '</span><i></i><span class="is-hl">' + I.check + '</span></div>'
            + '<p><b>Zdjęcie → czat AI → HomeList.</b> Czat rozpozna składniki i oszacuje gramy, a HomeList dopasuje je do swojej bazy produktów. Za darmo, w dowolnym czacie.</p></div>'
            + '<ol class="fd-ai-list">'
            + li(1, 'Skopiuj polecenie', 'Mówi czatowi, jak liczyć i w jakim formacie odpowiedzieć.',
                '<button type="button" class="fd-ai-act fd-press" data-aicopy' + (a.prompt ? '' : ' disabled') + '>' + I.copy + '<span>' + (a.copied ? 'Jeszcze raz' : 'Kopiuj') + '</span></button>')
            + li(2, 'Wklej je w czacie i dodaj zdjęcie', 'Działa w darmowej wersji ' + esc(chat.ai_chat_label) + ', także w aplikacji na telefonie.',
                '<a class="fd-ai-act fd-press" href="' + esc(chat.ai_chat_url) + '" target="_blank" rel="noopener">' + I.out + '<span>' + esc(chat.ai_chat_label) + '</span></a>')
            + li(3, 'Skopiuj odpowiedź i wróć tutaj', 'Przycisk „Kopiuj” jest w rogu bloku z kodem.')
            + '</ol>'
            + '<label class="hl-label" for="fd_ainote" style="margin-top:18px">Czego nie widać na zdjęciu? <span class="fd-mut" style="font-weight:400">(opcjonalnie)</span></label>'
            + '<textarea id="fd_ainote" class="fd-area" rows="2" placeholder="np. smażone na oliwie, kawa z 2 łyżeczkami cukru, zjadłem połowę">' + esc(a.note) + '</textarea>'
            + '<p class="hl-hint">Trafi do polecenia – skopiuj je po wpisaniu.</p>'
            + '</div><div class="fd-foot"><button type="button" class="fd-cta fd-press" data-aigo></button></div>';
        bindHead(box, function () { setStep('search', true); });
        var cta = box.querySelector('[data-aigo]'), copyBtn = box.querySelector('[data-aicopy]');
        var paint = function () {
            cta.innerHTML = a.copied ? I.paste.replace('<svg', '<svg width="20" height="20"') + 'Mam odpowiedź – wklej' : I.copy.replace('<svg', '<svg width="20" height="20"') + 'Skopiuj polecenie';
            cta.disabled = !a.copied && !a.prompt;
        };
        var copy = function () {
            if (!a.prompt) return;
            var text = aiPrompt(a);
            (typeof hlCopy === 'function' ? hlCopy(text) : navigator.clipboard.writeText(text)).then(function () {
                a.copied = true;
                try { sessionStorage.setItem(AI_KEY, String(Date.now())); } catch (e) {}
                vib();
                if (S.sheet === sh && sh.step === 'ai') renderAi(box);
            }).catch(function () { fail({ status: 'warning', message: 'Nie udało się skopiować – spróbuj jeszcze raz' }); });
        };
        copyBtn.addEventListener('click', copy);
        cta.addEventListener('click', function () { if (a.copied) aiFromClipboard(); else copy(); });
        el('fd_ainote').addEventListener('input', function () { a.note = this.value; });
        paint();
        if (!a.prompt) getJSON('scripts/food/ai_prompt.php').then(function (res) {
            if (res.status !== 'success') return fail(res);
            a.prompt = res.prompt;
            if (S.sheet === sh && sh.step === 'ai') { copyBtn.disabled = false; paint(); }
        });
    }

    // „Mam odpowiedź”: najpierw schowek (Chrome/Safari zapytają o zgodę), gdy się nie da – pole do wklejenia
    function aiFromClipboard() {
        var a = S.sheet.ai;
        if (!navigator.clipboard || !navigator.clipboard.readText) return setStep('aipaste');
        navigator.clipboard.readText().then(function (text) {
            var parsed = aiParse(text);
            if (parsed) return aiMatch(parsed);
            // w schowku zostało nasze polecenie – nie wklejamy go do pola, tylko mówimy, co poszło nie tak
            a.raw = text && text.length < 20000 && !aiIsPrompt(text) ? text : '';
            a.err = text && text.trim() ? aiError(text, 'W schowku nie ma odpowiedzi z czatu – skopiuj ją przyciskiem „Kopiuj” nad blokiem kodu.') : '';
            setStep('aipaste');
        }).catch(function () { setStep('aipaste'); });
    }

    // wklejone z powrotem polecenie (np. „Kopiuj” w czacie nie zadziałało) – jego przykładowy JSON nie może przejść jako posiłek
    function aiIsPrompt(text) {
        var t = String(text || '');
        return t.indexOf('SŁOWNIK HOMELIST') >= 0 || t.indexOf('Jesteś dietetykiem') >= 0 || t.indexOf('Krótka nazwa posiłku') >= 0;
    }

    function aiError(text, fallback) {
        return aiIsPrompt(text)
            ? 'To jest polecenie z HomeList, a nie odpowiedź czatu. Wklej je w czacie razem ze zdjęciem, a potem skopiuj to, co odpisze czat – przyciskiem „Kopiuj” nad blokiem kodu.'
            : fallback;
    }

    // JSON z odpowiedzi czatu: blok ```json``` albo od pierwszej { do ostatniej }; wybaczamy typograficzne cudzysłowy i przecinki na końcu
    function aiParse(text) {
        if (aiIsPrompt(text)) return null;
        var t = String(text || '').trim(), m = t.match(/```(?:json)?\s*([\s\S]*?)```/i);
        if (m) t = m[1];
        var i = t.indexOf('{'), j = t.lastIndexOf('}');
        if (i < 0 || j <= i) return null;
        t = t.slice(i, j + 1).replace(/[“”„″]/g, '"').replace(/,\s*([}\]])/g, '$1');
        var data;
        try { data = JSON.parse(t); } catch (e) { return null; }
        if (!data || !Array.isArray(data.items) || !data.items.length) return null;
        return { meal: data.meal ? String(data.meal).slice(0, 120) : '', note: data.note ? String(data.note).slice(0, 300) : '', items: data.items };
    }

    function renderAiPaste(box) {
        var a = aiState(), canRead = !!(navigator.clipboard && navigator.clipboard.readText);
        box.innerHTML = headHTML('Wklej odpowiedź', true)
            + '<div class="fd-scroll" style="padding:8px 20px 16px">' + aiStepsHTML(2)
            + '<p class="fd-mut" style="margin:16px 4px 10px;font-size:13px;line-height:1.5">Wklej całą odpowiedź czatu – HomeList sam znajdzie w niej dane. Nie musisz niczego poprawiać.</p>'
            + '<textarea id="fd_airaw" class="fd-area is-code" rows="9" spellcheck="false" autocomplete="off" placeholder="{ &quot;homelist&quot;: 1, &quot;items&quot;: [ … ] }">' + esc(a.raw) + '</textarea>'
            + '<p id="fd_aierr" class="fd-check is-warn" style="margin-top:10px"' + (a.err ? '' : ' hidden') + '>' + esc(a.err) + '</p>'
            + (canRead ? '<button type="button" class="fd-ghost-btn fd-press" data-airead style="margin-top:12px">' + I.paste + 'Wklej ze schowka</button>' : '')
            + '</div><div class="fd-foot"><button type="button" class="fd-cta fd-press" data-aigo>' + I.sparkles.replace('<svg', '<svg width="20" height="20"') + 'Dopasuj składniki</button></div>';
        bindHead(box, function () { a.err = ''; setStep('ai', true); });
        var area = el('fd_airaw'), cta = box.querySelector('[data-aigo]'), err = el('fd_aierr');
        var sync = function () { cta.disabled = !area.value.trim(); };
        var go = function () {
            var parsed = aiParse(area.value);
            if (parsed) return aiMatch(parsed);
            a.err = aiError(area.value, 'Nie widzę tu danych z HomeList. Upewnij się, że czat dostał całe polecenie, i skopiuj jego odpowiedź przyciskiem „Kopiuj” nad blokiem kodu.');
            err.textContent = a.err;
            err.hidden = false;
        };
        area.addEventListener('input', function () { a.raw = area.value; a.err = ''; err.hidden = true; sync(); });
        // wklejenie poprawnej odpowiedzi od razu przechodzi dalej, wklejone polecenie od razu pokazuje ostrzeżenie
        area.addEventListener('paste', function () { setTimeout(function () { a.raw = area.value; sync(); if (aiParse(area.value) || aiIsPrompt(area.value)) go(); }, 0); });
        cta.addEventListener('click', go);
        var rd = box.querySelector('[data-airead]');
        if (rd) rd.addEventListener('click', function () {
            navigator.clipboard.readText().then(function (text) { area.value = a.raw = text || ''; sync(); go(); }).catch(function () { area.focus(); });
        });
        sync();
        if (!a.raw && window.matchMedia('(hover: hover)').matches) setTimeout(function () { area.focus(); }, 350);
    }

    function renderAiWait(box) {
        var n = S.sheet.ai.parsed.items.length;
        box.innerHTML = headHTML('Sprawdź posiłek', false)
            + '<div style="padding:8px 20px 0">' + aiStepsHTML(3) + '</div>'
            + '<div class="fd-ai-wait"><span class="fd-ai-orb">' + I.sparkles + '</span>'
            + '<b>Dopasowuję ' + n + ' ' + plural(n, 'składnik', 'składniki', 'składników') + '</b><span class="fd-mut">do bazy produktów HomeList…</span></div>';
        bindHead(box);
    }

    function aiMatch(parsed) {
        var sh = S.sheet, a = sh.ai;
        a.parsed = parsed;
        a.err = '';
        setStep('aimatch');
        var started = Date.now();
        post('scripts/food/ai_match.php', { items: JSON.stringify(parsed.items) }).then(function (res) {
            if (S.sheet !== sh || sh.step !== 'aimatch') return;
            if (res.status !== 'success') { a.err = res.message || 'Nie udało się odczytać odpowiedzi'; return setStep('aipaste', true); }
            a.meal = parsed.meal;
            a.aiNote = parsed.note;
            a.items = res.items.map(aiItem);
            try { sessionStorage.removeItem(AI_KEY); } catch (e) {}
            // krótka chwila na animację – natychmiastowy przeskok wygląda jak błąd
            setTimeout(function () { if (S.sheet === sh && sh.step === 'aimatch') setStep('aireview'); }, Math.max(0, 450 - (Date.now() - started)));
        }).catch(function () {
            if (S.sheet !== sh) return;
            a.err = 'Brak połączenia – spróbuj jeszcze raz';
            setStep('aipaste', true);
        });
    }

    function normLabel(s) {
        return String(s || '').toLowerCase().replace(/ł/g, 'l').normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    // składnik w kreatorze: produkt z bazy (mode db) albo wartości z AI (mode ai)
    function aiItem(r) {
        var it = { ai: r.ai, sure: r.sure, on: true, cands: (r.match ? [r.match] : []).concat(r.alts || []) };
        if (r.match) aiUse(it, r.match); else aiUseAi(it);
        it.grams = it.unitFromAi ? it.grams : r.ai.grams;
        return it;
    }

    function aiUse(it, p) {
        var units = (p.units || []).filter(function (u) { return !u.base; });
        it.mode = 'db';
        it.p = p;
        it.name = p.name;
        it.per_ml = !!p.per_ml;
        it.units = units;
        it.p100 = { kcal: p.kcal, protein: p.protein, fat: p.fat, carbs: p.carbs };
        // „2 × sztuka” z AI + ta sama jednostka w HomeList = waga z bazy (spójnie z resztą dziennika), chyba że użytkownik już zmieniał ilość
        var want = normLabel(it.ai.unit), u = null;
        if (want && it.ai.count) u = units.filter(function (x) { var l = normLabel(x.label); return l.indexOf(want) === 0 || want.indexOf(l) === 0; })[0] || null;
        it.unit = u;
        it.unitFromAi = !!u && !it.touched;
        if (it.unitFromAi) it.grams = Math.round(it.ai.count * u.grams * 10) / 10;
    }

    function aiUseAi(it) {
        it.mode = 'ai';
        it.p = null;
        it.name = it.ai.name;
        it.per_ml = false;
        it.p100 = it.ai.per100;
        // liczba sztuk z AI jako jednostka – licznik działa w sztukach, nawet bez produktu w bazie
        it.units = it.ai.count && it.ai.unit ? [{ label: it.ai.unit, grams: Math.round(it.ai.grams / it.ai.count * 10) / 10 }] : [];
        it.unit = it.units[0] && !it.touched ? it.units[0] : null;
        it.unitFromAi = false;
    }

    function aiTotals(a) {
        var t = { kcal: 0, protein: 0, fat: 0, carbs: 0 };
        (a && a.items || []).forEach(function (it) {
            if (!it.on) return;
            Object.keys(t).forEach(function (k) { t[k] += (it.p100[k] || 0) * it.grams / 100; });
        });
        return t;
    }

    function renderAiReview(box) {
        var sh = S.sheet, a = sh.ai;
        box.innerHTML = headHTML('Sprawdź posiłek', true)
            + '<div class="fd-scroll" id="fd_aiscroll" style="padding:8px 20px 16px">' + aiStepsHTML(3)
            + '<div style="margin-top:14px">' + mealChips() + '</div>'
            + '<div class="fd-ai-sum" id="fd_aisum"></div>'
            + (a.aiNote ? '<p class="fd-ai-note">' + I.sparkles + '<span>' + esc(a.aiNote) + '</span></p>' : '')
            + '<div style="display:flex;align-items:baseline;justify-content:space-between;gap:8px;margin:20px 4px 4px"><span class="fd-eyebrow" id="fd_aicount"></span>'
            + '<span class="fd-ai-legend"><i class="is-db"></i>z bazy <i class="is-ai"></i>szacunek AI</span></div>'
            + '<div id="fd_aiitems"></div>'
            + '<div style="position:relative;display:flex;gap:8px;margin-top:10px"><label class="fd-field" style="flex:1">' + I.plus
            + '<input id="fd_aiadd" type="search" placeholder="Dodaj składnik, którego brakuje" autocomplete="off"></label>'
            + '<div id="fd_aiaddres" class="fd-dres"></div></div>'
            + '<div class="fd-box" style="margin-top:18px"><span class="fd-eyebrow">Twój dzień po dodaniu</span>'
            + '<div id="fd_preview" style="display:flex;flex-direction:column;gap:10px;margin-top:10px"></div></div>'
            + '<p class="fd-mut" style="margin:14px 4px 0;font-size:12px;line-height:1.5">Każdy składnik trafi do dziennika jako osobny wpis – później zmienisz go jak każdy inny.</p>'
            + '</div><div class="fd-foot"><button type="button" class="fd-cta fd-press" data-commit></button></div>';
        bindHead(box, function () { setStep('aipaste', true); });
        bindMealChips(box, aiSum);
        productSearch(el('fd_aiadd'), el('fd_aiaddres'), function (p) {
            var d = p.default;
            var it = { ai: { name: p.name, grams: d.grams, count: null, unit: null, per100: { kcal: p.kcal, protein: p.protein, fat: p.fat, carbs: p.carbs }, confidence: 'high' },
                       sure: true, on: true, cands: [p], touched: true, fresh: true };
            aiUse(it, p);
            it.grams = d.grams;
            it.unit = d.label ? it.units.filter(function (u) { return u.label === d.label && Math.abs(u.grams - d.unit_g) < 0.05; })[0] || null : null;
            a.items.push(it);
            aiItems();
        });
        box.querySelector('[data-commit]').addEventListener('click', aiCommit);
        aiItems();
    }

    function aiItemHTML(it, i) {
        var u = it.per_ml ? 'ml' : 'g', warn = it.mode === 'ai' || !it.sure;
        var tag = it.mode === 'db' ? tagHTML(it.p) : '<span class="fd-tag ai">Szacunek AI</span>';
        var from = it.mode === 'db' && normLabel(it.ai.name) !== normLabel(it.name) ? '<span class="fd-ai-from">z „' + esc(it.ai.name) + '”</span>' : '';
        var low = it.ai.confidence === 'low' ? '<span class="fd-ai-low" title="Czat zgadywał – np. tłuszcz do smażenia albo sos">niepewne</span>' : '';
        var html = '<div class="fd-ai-item' + (it.on ? '' : ' is-off') + (warn ? ' is-warn' : '') + (it.fresh ? ' is-fresh' : '') + '">'
            + '<div class="fd-ai-top"><input type="checkbox" data-aion="' + i + '"' + (it.on ? ' checked' : '') + ' aria-label="Uwzględnij: ' + esc(it.name) + '">'
            + '<div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(it.name) + '</div>'
            + '<div class="fd-ai-meta">' + tag + low + from + '</div></div>'
            + '<div class="fd-ai-kcal"><b class="fd-num" data-aikcal="' + i + '">' + fmt(ingKcal(it)) + '</b><span>kcal</span></div></div>'
            + '<div class="fd-ing-ctl">'
            + '<button type="button" class="fd-ai-swap fd-press' + (it.open ? ' is-on' : '') + '" data-aiswap="' + i + '" aria-expanded="' + !!it.open + '">' + I.swap + (warn ? 'Sprawdź' : 'Zmień') + '</button>'
            + amountCtlHTML(it, i, 'a') + '</div>';
        if (it.open) {
            var cands = it.cands.filter(function (p) { return !it.p || p.id !== it.p.id; });
            html += '<div class="fd-ai-pick">'
                + (it.mode === 'ai' ? '<p class="fd-mut" style="margin:0 4px 8px;font-size:12px;line-height:1.5">Nie znalazłem pewnego odpowiednika w bazie. Wybierz produkt albo zostaw wartości z czatu: '
                    + fmt(it.ai.per100.kcal) + ' kcal / 100 g.</p>' : '')
                + (cands.length ? '<span class="fd-eyebrow" style="padding:2px 4px 4px">Podobne w HomeList</span>' + cands.map(function (p) {
                    var k = it.cands.indexOf(p);
                    return '<button type="button" class="fd-row fd-press" data-aialt="' + i + ':' + k + '"><div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(p.name) + '</div>'
                        + '<div class="text-xs fd-mut" style="display:flex;align-items:center;gap:6px;white-space:nowrap;overflow:hidden">' + tagHTML(p) + esc(p.brand || '') + '</div></div>'
                        + '<span class="fd-num fd-mut" style="font-size:12px;flex:none">' + fmt(p.kcal) + ' kcal/100 ' + (p.per_ml ? 'ml' : 'g') + '</span></button>';
                }).join('') : '')
                + (it.mode === 'db' ? '<button type="button" class="fd-row fd-press" data-aiself="' + i + '"><span class="fd-row-ico" style="width:30px;height:30px;background:rgba(245,158,11,.14);color:#d97706">' + I.sparkles + '</span>'
                    + '<div style="flex:1;min-width:0"><div class="fd-entry-name">Zostaw wartości z czatu</div><div class="text-xs fd-mut">' + esc(it.ai.name) + ' · ' + fmt(it.ai.per100.kcal) + ' kcal / 100 g</div></div></button>' : '')
                + '<div style="position:relative;margin-top:6px"><label class="fd-field">' + I.search + '<input type="search" data-aiq="' + i + '" placeholder="Szukaj innego produktu" autocomplete="off"></label>'
                + '<div class="fd-dres" data-aires="' + i + '"></div></div>'
                + '<div style="display:flex;gap:8px;margin-top:10px">'
                + (it.mode === 'ai' ? '<button type="button" class="fd-ghost-btn fd-press" data-aiok="' + i + '">' + I.check + 'Wartości z czatu są OK</button>' : '')
                + '<button type="button" class="fd-ghost-btn fd-press" data-airm="' + i + '" style="color:#e11d48">' + I.trash + 'Usuń</button></div>'
                + '</div>';
        }
        return html + '</div>';
    }

    function aiItems() {
        var a = S.sheet.ai, box = el('fd_aiitems');
        if (!box) return;
        box.innerHTML = a.items.length ? a.items.map(aiItemHTML).join('')
            : '<p class="fd-mut" style="margin:8px 4px;font-size:13px">Nie ma składników – dodaj je wyszukiwarką poniżej.</p>';
        a.items.forEach(function (it) { delete it.fresh; });
        var rerender = function () { aiItems(); };
        var at = function (attr) { return a.items[+attr]; };

        box.querySelectorAll('[data-aion]').forEach(function (c) {
            c.addEventListener('change', function () { at(c.dataset.aion).on = c.checked; c.closest('.fd-ai-item').classList.toggle('is-off', !c.checked); aiSum(); });
        });
        // ilość (wspólny licznik z edytora dania)
        box.querySelectorAll('[data-aq]').forEach(function (inp) {
            inp.addEventListener('focus', function () { inp.select(); });
            inp.addEventListener('input', function () {
                var it = at(inp.dataset.aq), v = parseFloat(inp.value.replace(',', '.'));
                if (isNaN(v) || v < 0) return;
                it.grams = it.unit ? Math.round(v * it.unit.grams * 10) / 10 : v;
                it.touched = true;
                box.querySelector('[data-aikcal="' + inp.dataset.aq + '"]').textContent = fmt(ingKcal(it));
                aiSum();
            });
            inp.addEventListener('blur', function () { var it = at(inp.dataset.aq); if (it) inp.value = it.unit ? qty(it.grams / it.unit.grams) : dec(it.grams); });
        });
        box.querySelectorAll('[data-as]').forEach(function (b) {
            b.addEventListener('click', function () { var it = at(b.dataset.i); stepAmount(it, +b.dataset.as > 0); it.touched = true; vib(); rerender(); });
        });
        box.querySelectorAll('[data-au]').forEach(function (s) {
            s.addEventListener('change', function () {
                var it = at(s.dataset.au), k = +s.value;
                it.unit = k < 0 ? null : it.units[k];
                if (it.unit) it.grams = Math.max(1, Math.round(it.grams / it.unit.grams)) * it.unit.grams;
                it.touched = true;
                rerender();
            });
        });
        box.querySelectorAll('[data-aiswap]').forEach(function (b) {
            b.addEventListener('click', function () {
                var it = at(b.dataset.aiswap);
                it.open = !it.open;
                rerender();
                if (it.open) { var q = box.querySelector('[data-aiq="' + b.dataset.aiswap + '"]'); if (q && window.matchMedia('(hover: hover)').matches) q.focus(); }
            });
        });
        box.querySelectorAll('[data-aialt]').forEach(function (b) {
            b.addEventListener('click', function () {
                var x = b.dataset.aialt.split(':'), it = at(x[0]);
                aiUse(it, it.cands[+x[1]]);
                it.sure = true;
                it.open = false;
                it.fresh = true;
                rerender();
            });
        });
        box.querySelectorAll('[data-aiself]').forEach(function (b) {
            b.addEventListener('click', function () { var it = at(b.dataset.aiself); aiUseAi(it); it.sure = true; it.open = false; it.fresh = true; rerender(); });
        });
        box.querySelectorAll('[data-aiok]').forEach(function (b) {
            b.addEventListener('click', function () { var it = at(b.dataset.aiok); it.sure = true; it.open = false; rerender(); });
        });
        box.querySelectorAll('[data-airm]').forEach(function (b) {
            b.addEventListener('click', function () { a.items.splice(+b.dataset.airm, 1); rerender(); });
        });
        box.querySelectorAll('[data-aiq]').forEach(function (inp) {
            var i = +inp.dataset.aiq;
            productSearch(inp, box.querySelector('[data-aires="' + i + '"]'), function (p) {
                var it = a.items[i];
                if (it.cands.every(function (c) { return c.id !== p.id; })) it.cands.push(p);
                aiUse(it, p);
                it.sure = true;
                it.open = false;
                it.fresh = true;
                rerender();
            });
        });
        aiSum();
    }

    function aiSum() {
        var sh = S.sheet, a = sh && sh.ai, box = el('fd_aisum');
        if (!a || !box) return;
        var t = aiTotals(a), on = a.items.filter(function (it) { return it.on; }), check = on.filter(function (it) { return it.mode === 'ai' || !it.sure; }).length;
        box.innerHTML = '<div style="display:flex;align-items:flex-start;gap:12px"><div style="flex:1;min-width:0">'
            + '<div class="fd-ai-title">' + esc(a.meal || 'Twój posiłek') + '</div>'
            + '<div class="text-xs fd-mut" style="margin-top:2px">' + (check ? 'Sprawdź ' + check + ' ' + plural(check, 'pozycję', 'pozycje', 'pozycji') + ' oznaczone na bursztynowo' : 'Wszystko dopasowane do bazy HomeList') + '</div></div>'
            + '<div style="text-align:right;flex:none"><b class="fd-ai-big fd-num">' + fmt(t.kcal) + '</b><span class="fd-mut" style="font-size:12px"> kcal</span></div></div>'
            + '<div class="fd-stack" style="margin-top:12px" aria-hidden="true">' + MACROS.map(function (m) {
                var kc = t[m[0]] * (m[0] === 'fat' ? 9 : 4);
                return kc > 0 ? '<span style="flex:' + kc.toFixed(1) + ';background:' + m[2] + '"></span>' : '';
            }).join('') + '</div>'
            + '<div class="fd-ai-macros">' + MACROS.map(function (m) {
                return '<span><i style="background:' + m[2] + '"></i>' + m[1] + ' <b class="fd-num">' + dec(t[m[0]]) + ' g</b></span>';
            }).join('') + '</div>';
        el('fd_aicount').textContent = 'Składniki · ' + on.length + (on.length !== a.items.length ? ' z ' + a.items.length : '');
        var pv = preview();
        el('fd_preview').innerHTML = barsHTML(pv.base, pv.ghost, S.data.day.goal, [['kcal', 'Kalorie', 'var(--fd-k)']].concat(MACROS), true);
        var cta = el('fd_step').querySelector('[data-commit]');
        cta.disabled = !on.length;
        cta.textContent = on.length ? 'Dodaj ' + MEAL_TO[sh.meal] + ' · ' + fmt(t.kcal) + ' kcal' : 'Zaznacz co najmniej jeden składnik';
    }

    function aiCommit(ev) {
        var sh = S.sheet, a = sh.ai, btn = ev.currentTarget;
        var on = a.items.filter(function (it) { return it.on && it.grams > 0; });
        if (!on.length) return;
        btn.disabled = true;
        post('scripts/food/ai_log.php', {
            date: S.date, meal: sh.meal,
            items: JSON.stringify(on.map(function (it) {
                return it.mode === 'db'
                    ? { product_id: it.p.id, grams: Math.round(it.grams * 10) / 10, unit_label: it.unit ? it.unit.label : '', unit_g: it.unit ? it.unit.grams : '' }
                    : { name: it.name, grams: Math.round(it.grams * 10) / 10, per100: it.p100 };
            }))
        }).then(function (res) {
            if (res.status !== 'success') { btn.disabled = false; return fail(res); }
            var meal = sh.meal, n = res.ids.length;
            closeSheet(true);
            afterChange(res.ids[0]);
            toast('Dodano ' + MEAL_TO[meal] + ': ' + n + ' ' + plural(n, 'składnik', 'składniki', 'składników') + ', ' + fmt(res.kcal) + ' kcal', function () {
                post('scripts/food/log_delete.php', { ids: res.ids.join(',') }).then(function () { afterChange(); });
            });
        }).catch(function () { btn.disabled = false; fail(); });
    }

    // podpowiedzi produktów pod polem (składnik dania, posiłek z AI); bez dań – składnik ma być produktem
    function productSearch(input, res, onPick) {
        input.addEventListener('input', function () {
            clearTimeout(input._t);
            var term = input.value.trim();
            if (!term) { res.innerHTML = ''; return; }
            input._t = setTimeout(function () {
                getJSON('scripts/food/search.php?q=' + encodeURIComponent(term)).then(function (r) {
                    if (input.value.trim() !== term) return;
                    var items = (r.groups || []).reduce(function (acc, g) { return acc.concat(g.items); }, []).filter(function (p) { return p.source !== 'dish'; }).slice(0, 8);
                    res.innerHTML = items.length ? items.map(function (p, i) {
                        var u = (p.units || []).filter(function (x) { return !x.base; })[0];
                        return '<button type="button" class="fd-row" data-pick="' + i + '"><div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(p.name) + '</div>'
                            + '<div class="text-xs fd-mut">' + esc([p.brand, fmt(p.kcal) + ' kcal / 100 ' + (p.per_ml ? 'ml' : 'g'), u ? '1 ' + u.label + ' = ' + dec(u.grams) + ' ' + (p.per_ml ? 'ml' : 'g') : ''].filter(Boolean).join(' · ')) + '</div></div>'
                            + I.plus.replace('<svg', '<svg width="18" height="18"') + '</button>';
                    }).join('') : '<p class="fd-mut" style="margin:12px 14px;font-size:13px">Brak w bazie HomeList.</p>';
                    res.querySelectorAll('[data-pick]').forEach(function (b) {
                        b.addEventListener('click', function () { input.value = ''; res.innerHTML = ''; onPick(items[+b.dataset.pick]); });
                    });
                });
            }, 220);
        });
    }

    // ---------- skaner kodów ----------
    // BarcodeDetector (Chrome / Android), w pozostałych przeglądarkach ZXing (EAN-13/8, UPC); obok zawsze ręczne wpisanie kodu
    function stopCamera() {
        if (!S.cam) return;
        S.cam.stopped = true;
        clearTimeout(S.cam.timer);
        if (S.cam.stream) S.cam.stream.getTracks().forEach(function (t) { t.stop(); });
        S.cam = null;
    }

    function makeDetector() {
        var formats = ['ean_13', 'ean_8', 'upc_a', 'upc_e'];
        var zxing = function () {
            return hlLoadScript('https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js').then(function () {
                var Z = window.ZXing, hints = new Map(), canvas = document.createElement('canvas'), ctx = canvas.getContext('2d', { willReadFrequently: true });
                hints.set(Z.DecodeHintType.POSSIBLE_FORMATS, [Z.BarcodeFormat.EAN_13, Z.BarcodeFormat.EAN_8, Z.BarcodeFormat.UPC_A, Z.BarcodeFormat.UPC_E]);
                hints.set(Z.DecodeHintType.TRY_HARDER, true);
                var reader = new Z.MultiFormatReader();
                reader.setHints(hints);
                return function (video) {
                    var w = video.videoWidth, h = video.videoHeight;
                    if (!w) return Promise.resolve('');
                    // środkowy pas kadru – szybciej i mniej fałszywych odczytów
                    canvas.width = Math.min(w, 960);
                    canvas.height = Math.round(canvas.width * 0.45);
                    var sh = canvas.height / canvas.width * w;
                    ctx.drawImage(video, 0, (h - sh) / 2, w, sh, 0, 0, canvas.width, canvas.height);
                    try {
                        var bmp = new Z.BinaryBitmap(new Z.HybridBinarizer(new Z.HTMLCanvasElementLuminanceSource(canvas)));
                        return Promise.resolve(reader.decode(bmp).getText());
                    } catch (e) { return Promise.resolve(''); }
                };
            });
        };
        if (!('BarcodeDetector' in window)) return zxing();
        return BarcodeDetector.getSupportedFormats().then(function (sup) {
            var use = formats.filter(function (f) { return sup.indexOf(f) >= 0; });
            if (!use.length) return zxing();
            var det = new BarcodeDetector({ formats: use });
            return function (video) { return det.detect(video).then(function (c) { return c.length ? c[0].rawValue : ''; }); };
        }).catch(zxing);
    }

    function renderScan(box) {
        box.innerHTML = headHTML(S.sheet.scanFor === 'dish' ? 'Skanuj składnik' : S.sheet.scanFor === 'info' ? 'Sprawdź produkt' : 'Skanuj kod kreskowy', S.sheet.scanFor !== 'info')
            + '<div class="fd-scroll" style="padding:8px 20px 12px">'
            + '<div class="fd-viewfinder"><video id="fd_video" playsinline muted autoplay></video>'
            + '<span class="fd-corner tl"></span><span class="fd-corner tr"></span><span class="fd-corner bl"></span><span class="fd-corner br"></span>'
            + '<span class="fd-scanline"></span><p id="fd_scan_msg" class="fd-scan-msg">Włączam aparat…</p></div>'
            + '<p class="fd-mut" style="margin:14px 4px 0;font-size:13px;line-height:1.5">Najpierw szukamy w bazie HomeList, potem w Open Food Facts. Gdy kodu nie ma nigdzie, otworzy się formularz z wpisanym już kodem.</p>'
            + '<label class="hl-label" for="fd_code" style="margin-top:16px">Albo wpisz kod spod paska</label>'
            + '<div style="display:flex;gap:8px"><div class="fd-field" style="flex:1"><input id="fd_code" type="text" inputmode="numeric" placeholder="np. 5901234123457" autocomplete="off"></div>'
            + '<button type="button" class="hl-btn hl-btn-primary" data-code style="height:46px">Szukaj</button></div></div>';
        bindHead(box, function () { var dish = S.sheet.scanFor === 'dish'; S.sheet.scanFor = null; setStep(dish ? 'dish' : 'search', true); });
        var codeInput = el('fd_code');
        var manual = function () { var c = codeInput.value.replace(/\D/g, ''); if (c.length >= 6) lookupCode(c); else codeInput.focus(); };
        box.querySelector('[data-code]').addEventListener('click', manual);
        codeInput.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') manual(); });

        var msg = el('fd_scan_msg'), video = el('fd_video');
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { msg.textContent = 'Ta przeglądarka nie daje dostępu do aparatu – wpisz kod ręcznie'; return; }
        var cam = S.cam = { stopped: false };
        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } }, audio: false }).then(function (stream) {
            if (cam.stopped) { stream.getTracks().forEach(function (t) { t.stop(); }); return; }
            cam.stream = stream;
            video.srcObject = stream;
            return video.play().then(makeDetector).then(function (detect) {
                msg.textContent = 'Nakieruj na kod kreskowy na opakowaniu';
                var last = '', hits = 0;
                var tick = function () {
                    if (cam.stopped) return;
                    detect(video).then(function (code) {
                        code = (code || '').replace(/\D/g, '');
                        if (cam.stopped) return;
                        // dwa takie same odczyty z rzędu – eliminuje przypadkowe cyfry przy ruchu
                        if (code.length >= 8 && code === last) hits++; else hits = 0;
                        last = code;
                        if (hits >= 1) { vib(); stopCamera(); lookupCode(code); return; }
                        cam.timer = setTimeout(tick, 120);
                    }).catch(function () { cam.timer = setTimeout(tick, 250); });
                };
                tick();
            });
        }).catch(function (err) {
            msg.textContent = err && err.name === 'NotAllowedError' ? 'Brak zgody na aparat – możesz wpisać kod ręcznie' : 'Nie udało się włączyć aparatu – wpisz kod ręcznie';
        });
    }

    function lookupCode(code) {
        var sh = S.sheet;
        setStep('loading');
        getJSON('scripts/food/barcode.php?code=' + code).then(function (res) {
            if (S.sheet !== sh) return;
            if (res.status === 'success' && res.found) {
                // skan składnika dania – wracamy do edytora z dodanym produktem
                if (sh.scanFor === 'dish' && sh.dish) { sh.scanFor = null; dishAdd(sh.dish, res.product); toast('Dodano: ' + res.product.name); return setStep('dish', true); }
                if (sh.scanFor === 'info') return openInfo(res.product.id, code);
                return openPortion(res.product, null, false, 'barcode');
            }
            if (res.status !== 'success' && res.status !== 'error') { fail(res); return setStep('scan', true); }
            sh.own = { code: code, name: res.name || '', notFound: true, offError: res.status === 'error', forDish: sh.scanFor === 'dish' };
            setStep('own');
        }).catch(function () { sh.own = { code: code, notFound: true, offError: true, forDish: sh.scanFor === 'dish' }; setStep('own'); });
    }

    // ---------- podgląd zeskanowanego produktu ----------
    // pełne dane z product.php (skład, jakość, składniki dania); „Dodaj do posiłku” otwiera porcję bez ponownego pobierania
    function openInfo(id, code) {
        var sh = S.sheet;
        getJSON('scripts/food/product.php?id=' + id).then(function (res) {
            if (S.sheet !== sh) return;
            if (res.status !== 'success') { fail(res); return setStep('scan', true); }
            sh.info = { product: res.product, quality: res.product.quality, code: code };
            setStep('info');
        }).catch(function () { if (S.sheet === sh) { toast('Nie udało się pobrać produktu'); setStep('scan', true); } });
    }

    function renderInfo(box) {
        var sh = S.sheet, pp = sh.info, p = pp.product, q = pp.quality || {}, u = p.per_ml ? 'ml' : 'g';
        var sub = [p.brand, p.quantity].filter(Boolean).join(' · ');
        var img = p.photo || q.image_url;
        var units = (p.units || []).filter(function (x) { return !x.base; });
        var macro = function (v, label, color) { return '<div><b class="fd-num"' + (color ? ' style="color:' + color + '"' : '') + '>' + (color ? dec(v) : fmt(v)) + '</b><span>' + label + '</span></div>'; };
        box.innerHTML = headHTML('Produkt', true)
            + '<div class="fd-scroll" style="padding:8px 20px 12px">'
            + '<div style="display:flex;align-items:center;gap:12px">'
            + (img ? '<img class="fd-thumb is-lg" src="' + esc(img) + '" alt="">' : '')
            + '<div style="flex:1;min-width:0"><div style="font-size:17px;font-weight:600;line-height:1.3">' + esc(p.name) + '</div>'
            + '<div class="text-xs fd-mut" style="display:flex;align-items:center;gap:6px;margin-top:4px;flex-wrap:wrap">' + tagHTML(p) + esc(sub) + '</div></div>'
            + '<span id="fd_ns_top" style="display:contents">' + nsMiniHTML(pp.quality) + '</span></div>'
            + '<span class="fd-eyebrow" style="margin-top:18px">Na 100 ' + u + '</span>'
            + '<div class="fd-mini" style="margin-top:8px">' + macro(p.kcal, 'kcal') + macro(p.protein, 'białko g', 'var(--fd-p)')
            + macro(p.fat, 'tłuszcz g', 'var(--fd-f)') + macro(p.carbs, 'węgle g', 'var(--fd-c)') + '</div>'
            + (units.length ? '<span class="fd-eyebrow" style="margin-top:16px">Porcje</span><table class="fd-table" style="margin-top:4px"><tbody>'
                + units.map(function (x) {
                    return '<tr><td>1 ' + esc(x.label) + ' <span class="fd-mut">· ' + dec(x.grams) + ' ' + u + '</span></td><td class="fd-num">' + fmt(p.kcal * x.grams / 100) + ' kcal</td></tr>';
                }).join('') + '</tbody></table>' : '')
            + '<div id="fd_quality">' + qualityHTML({ product: p, quality: pp.quality }) + '</div>'
            + '</div>'
            + '<div class="fd-foot"><button type="button" class="fd-round fd-press" data-rescan aria-label="Skanuj kolejny" title="Skanuj kolejny" style="width:54px;height:54px">' + I.scan + '</button>'
            + '<button type="button" class="fd-cta fd-press" data-commit>' + I.plus + 'Dodaj do posiłku</button></div>';
        bindHead(box, function () { sh.info = null; setStep('scan', true); });
        // w podglądzie skład i jakość od razu rozwinięte
        var qb = el('fd_q_box');
        if (qb) { qb.classList.add('is-open'); qb.querySelector('[data-qtoggle]').setAttribute('aria-expanded', 'true'); }
        bindQuality();
        bindNsMini();
        box.querySelector('[data-rescan]').addEventListener('click', function () { sh.info = null; setStep('scan', true); });
        box.querySelector('[data-commit]').addEventListener('click', function () { openPortion(p, null, true, 'barcode'); });
    }

    // ---------- własny produkt ----------
    // 14 alergenów UE – klucze jak w hl_food_allergen_names() (food_lib.php)
    var ALLERGENS = [['gluten', 'gluten'], ['milk', 'mleko'], ['eggs', 'jaja'], ['nuts', 'orzechy'], ['peanuts', 'orzeszki ziemne'], ['soybeans', 'soja'],
        ['sesame-seeds', 'sezam'], ['fish', 'ryby'], ['crustaceans', 'skorupiaki'], ['molluscs', 'mięczaki'], ['celery', 'seler'], ['mustard', 'gorczyca'],
        ['sulphur-dioxide-and-sulphites', 'siarczyny'], ['lupin', 'łubin']];
    var OWN_FIELDS = ['name', 'brand', 'code', 'kcal', 'protein', 'fat', 'carbs', 'sat_fat', 'sugars', 'fiber', 'salt', 'unit_label', 'unit_g', 'package_g', 'ingredients'];

    // edycja własnego produktu (oczekujący / odrzucony) albo z profilu („Twój wkład”); back: portion | close
    function editOwn(id, back) {
        var sh = S.sheet, prev = sh.p;
        setStep('loading');
        getJSON('scripts/food/product_edit.php?id=' + id).then(function (res) {
            if (S.sheet !== sh) return;
            if (res.status !== 'success') { fail(res); return back === 'portion' && prev ? (sh.p = prev, setStep('portion', true)) : closeSheet(true); }
            var o = res.product;
            // liczby jako tekst – pola formularza i walidacja pracują na tekście (0 nie może zniknąć)
            OWN_FIELDS.forEach(function (k) { if (typeof o[k] === 'number') o[k] = String(o[k]); else if (o[k] === null) delete o[k]; });
            o.back = back;
            o.prevP = prev;
            sh.own = o;
            setStep('own');
        });
    }

    function renderOwn(box) {
        var sh = S.sheet, o = sh.own || (sh.own = {});
        var u = o.per_ml ? 'ml' : 'g';
        o.allergens = o.allergens || [];
        o.traces = o.traces || [];
        var num = function (k, label, color, opt) {
            return '<label class="fd-field" style="padding-right:12px">' + (color ? '<span class="fd-dot" style="background:' + color + '"></span>' : '')
                + '<input type="text" inputmode="decimal" data-o="' + k + '" placeholder="' + (opt ? '–' : '0') + '" aria-label="' + label + '" value="' + esc(o[k] !== undefined ? o[k] : '') + '">'
                + '<span class="text-xs fd-mut" style="white-space:nowrap">' + label + '</span></label>';
        };
        // wybór z chipów: multi = lista (alergeny), pojedynczy = ponowne dotknięcie odznacza
        var chips = function (k, opts, multi) {
            return '<div class="fd-chips fd-wrap" data-ochips="' + k + '"' + (multi ? ' data-multi="1"' : '') + '>' + opts.map(function (x) {
                var on = multi ? o[k].indexOf(x[0]) >= 0 : String(o[k]) === String(x[0]);
                return '<button type="button" class="fd-chip fd-press' + (on ? ' is-on' : '') + '" data-v="' + esc(x[0]) + '" aria-pressed="' + on + '">' + x[1] + '</button>';
            }).join('') + '</div>';
        };
        var hasQuality = !!(o.ingredients || o.allergens.length || o.traces.length || o.nutriscore || o.nova || o.palm_oil != null || o.diet);
        var editing = !!o.id;
        box.innerHTML = headHTML(editing ? 'Popraw produkt' : 'Własny produkt', true)
            + '<div class="fd-scroll" style="padding:8px 20px 12px">'
            + (o.notFound ? '<p class="fd-pill" style="margin:0 0 12px;border-radius:14px;padding:8px 12px;display:flex">' + (o.offError ? 'Open Food Facts nie odpowiada – dodaj produkt ręcznie' : 'Nie znaleźliśmy kodu ' + esc(o.code) + (o.name ? ' („' + esc(o.name) + '” bez wartości odżywczych)' : '')) + '</p>' : '')
            + (editing && o.status === 'rejected' ? '<div class="fd-note is-warn"><b>Administrator odrzucił produkt</b>' + (o.review_note ? '<span>„' + esc(o.review_note) + '”</span>' : '') + '<span>Popraw dane i zapisz – produkt wróci do sprawdzenia.</span></div>' : '')
            + '<div id="fd_olabel" style="margin-bottom:16px"></div><input type="file" id="fd_ofile" accept="image/*" capture="environment" hidden>'
            + '<label class="hl-label" for="fd_oname">Nazwa</label>'
            + '<div class="fd-field"><input id="fd_oname" data-o="name" type="text" placeholder="np. Pasztet babci" value="' + esc(o.name || '') + '"></div>'
            + '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:10px">'
            + '<div class="fd-field"><input data-o="brand" type="text" placeholder="Marka (opcjonalnie)" aria-label="Marka" value="' + esc(o.brand || '') + '"></div>'
            + '<div class="fd-field"><input data-o="code" type="text" inputmode="numeric" placeholder="Kod (opcjonalnie)" aria-label="Kod kreskowy" value="' + esc(o.code || '') + '"></div></div>'
            + '<div style="display:flex;align-items:center;justify-content:space-between;margin:18px 0 8px"><span class="fd-eyebrow">Wartości na 100 ' + u + '</span>'
            + '<div class="fd-chips" style="margin:0;padding:0"><button type="button" class="fd-chip fd-press' + (o.per_ml ? '' : ' is-on') + '" data-ml="0">100 g</button><button type="button" class="fd-chip fd-press' + (o.per_ml ? ' is-on' : '') + '" data-ml="1">100 ml</button></div></div>'
            + '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px">'
            + num('kcal', 'kcal', 'var(--fd-k)') + num('protein', 'białko g', 'var(--fd-p)') + num('fat', 'tłuszcz g', 'var(--fd-f)') + num('carbs', 'węgle g', 'var(--fd-c)') + '</div>'
            + '<div id="fd_ocheck" class="fd-check" style="margin-top:10px"></div>'
            + '<details class="fd-more" style="margin-top:14px"' + (o.sugars || o.fiber || o.salt || o.sat_fat ? ' open' : '') + '><summary class="fd-eyebrow">Więcej z etykiety (opcjonalnie)</summary>'
            + '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:10px">'
            + num('sat_fat', 'nasycone g', '', 1) + num('sugars', 'cukry g', '', 1) + num('fiber', 'błonnik g', '', 1) + num('salt', 'sól g', '', 1) + '</div></details>'
            + '<span class="fd-eyebrow" style="margin:18px 0 8px">Opakowanie i porcja (opcjonalnie)</span>'
            + '<label class="fd-field" style="padding-right:12px"><span class="text-xs fd-mut" style="white-space:nowrap">Całe opakowanie</span><input data-o="package_g" type="text" inputmode="decimal" placeholder="np. ' + (u === 'ml' ? '500' : '250') + '" aria-label="Wielkość opakowania" value="' + esc(o.package_g || '') + '" style="text-align:right"><span class="text-xs fd-mut">' + u + '</span></label>'
            + '<div style="display:flex;gap:8px;margin-top:8px"><div class="fd-field" style="flex:1"><input data-o="unit_label" type="text" placeholder="Porcja, np. plaster, sztuka" aria-label="Nazwa porcji" value="' + esc(o.unit_label || '') + '"></div>'
            + '<label class="fd-field" style="width:110px;padding-right:12px"><input data-o="unit_g" type="text" inputmode="decimal" placeholder="20" aria-label="Waga porcji" value="' + esc(o.unit_g || '') + '"><span class="text-xs fd-mut">' + u + '</span></label></div>'
            + '<details class="fd-more" style="margin-top:18px"' + (hasQuality ? ' open' : '') + '><summary class="fd-eyebrow">Skład i jakość (opcjonalnie)</summary>'
            + '<label class="hl-label" for="fd_oingr" style="margin-top:10px">Skład</label>'
            + '<textarea id="fd_oingr" data-o="ingredients" class="fd-area" rows="4" maxlength="5000" placeholder="Przepisz skład z opakowania, np. mąka pszenna, cukier, olej rzepakowy, emulgator (E322)…">' + esc(o.ingredients || '') + '</textarea>'
            + '<p class="fd-mut" style="font-size:12px;margin:6px 2px 0">Dodatki E wpisane w składzie rozpoznamy sami.</p>'
            + '<span class="hl-label" style="margin-top:14px">Alergeny <span class="fd-mut" style="font-weight:400">– pogrubione w składzie</span></span>' + chips('allergens', ALLERGENS, true)
            + '<span class="hl-label" style="margin-top:14px">Może zawierać</span>' + chips('traces', ALLERGENS, true)
            + '<span class="hl-label" style="margin-top:14px">Nutri-Score <span class="fd-mut" style="font-weight:400">– jeśli jest na opakowaniu</span></span>'
            + chips('nutriscore', ['a', 'b', 'c', 'd', 'e'].map(function (x) { return [x, '<span class="fd-ns ' + x + '">' + x + '</span>']; }))
            + '<span class="hl-label" style="margin-top:14px">Przetworzenie (NOVA)</span>'
            + chips('nova', [[1, '1 · nieprzetworzone'], [2, '2 · składnik kulinarny'], [3, '3 · przetworzone'], [4, '4 · wysoko przetworzone']])
            + '<span class="hl-label" style="margin-top:14px">Olej palmowy</span>' + chips('palm_oil', [[1, 'zawiera'], [0, 'bez oleju palmowego']])
            + '<span class="hl-label" style="margin-top:14px">Dieta</span>' + chips('diet', [['vegan', 'wegańskie'], ['vegetarian', 'wegetariańskie'], ['none', 'z mięsem / rybą']])
            + '</details>'
            + '<p class="fd-pill" style="margin:16px 0 0;border-radius:14px;padding:10px 12px;display:flex;line-height:1.5">Od razu widzisz go Ty i domownicy. Dla pozostałych pojawi się po akceptacji administratora – wtedy dostanie znaczek HomeList.</p>'
            + '</div><div class="fd-foot"><button type="button" class="fd-cta fd-press" data-commit>' + (editing ? 'Zapisz poprawki' : o.forDish ? 'Zapisz i dodaj do dania' : 'Zapisz i wybierz porcję') + '</button></div>';
        var goBack = function () {
            if (o.back === 'close') return closeSheet(true);
            if (o.back === 'portion' && o.prevP) { sh.own = null; sh.p = o.prevP; return setStep('portion', true); }
            setStep(o.notFound ? 'scan' : o.forDish ? 'dish' : 'search', true);
        };
        bindHead(box, goBack);
        box.querySelectorAll('[data-ochips]').forEach(function (row) {
            var k = row.dataset.ochips, multi = !!row.dataset.multi;
            row.addEventListener('click', function (ev) {
                var b = ev.target.closest('[data-v]');
                if (!b) return;
                var v = b.dataset.v;
                if (multi) {
                    var i = o[k].indexOf(v);
                    if (i >= 0) o[k].splice(i, 1); else o[k].push(v);
                } else o[k] = String(o[k]) === v ? null : v;
                row.querySelectorAll('[data-v]').forEach(function (x) {
                    var on = multi ? o[k].indexOf(x.dataset.v) >= 0 : String(o[k]) === x.dataset.v;
                    x.classList.toggle('is-on', on);
                    x.setAttribute('aria-pressed', on);
                });
                vib();
            });
        });
        el('fd_ofile').addEventListener('change', function () {
            var file = this.files && this.files[0];
            this.value = '';
            if (!file) return;
            // etykieta musi być czytelna – większa rozdzielczość niż zdjęcie dania
            shrinkPhoto(file, 2200).then(function (blob) {
                if (o.labelPreview) URL.revokeObjectURL(o.labelPreview);
                o.labelBlob = blob;
                o.labelPreview = URL.createObjectURL(blob);
                o.labelRemove = false;
                renderOwnLabel();
            }).catch(function () { fail({ status: 'warning', message: 'Nie udało się wczytać zdjęcia – spróbuj innego pliku' }); });
        });
        renderOwnLabel();
        var check = function () {
            var v = function (k) { return parseFloat(String(o[k] || '').replace(',', '.')); };
            var filled = ['kcal', 'protein', 'fat', 'carbs'].every(function (k) { return !isNaN(v(k)); });
            var c = el('fd_ocheck'), btn = box.querySelector('[data-commit]');
            btn.disabled = !(String(o.name || '').trim() && filled);
            if (!filled) { c.className = 'fd-check'; c.textContent = 'Uzupełnij kalorie i makro – sprawdzimy, czy się zgadzają'; return; }
            var est = 4 * v('protein') + 4 * v('carbs') + 9 * v('fat') + 2 * (v('fiber') || 0);
            var ok = Math.abs(est - v('kcal')) <= Math.max(25, v('kcal') * 0.15);
            c.className = 'fd-check ' + (ok ? 'is-ok' : 'is-warn');
            c.textContent = ok ? 'Zgadza się: z makro wychodzi ~' + fmt(est) + ' kcal' : 'Sprawdź etykietę: z makro wychodzi ~' + fmt(est) + ' kcal, a wpisano ' + fmt(v('kcal'));
        };
        box.querySelectorAll('[data-o]').forEach(function (i) { i.addEventListener('input', function () { o[i.dataset.o] = i.value; check(); }); });
        box.querySelectorAll('[data-ml]').forEach(function (b) {
            b.addEventListener('click', function () {
                var top = box.querySelector('.fd-scroll').scrollTop;
                o.per_ml = b.dataset.ml === '1';
                renderOwn(box);
                box.querySelector('.fd-scroll').scrollTop = top;
            });
        });
        check();
        if (!editing && window.matchMedia('(hover: hover)').matches) setTimeout(function () { (o.name ? box.querySelector('[data-o=kcal]') : el('fd_oname')).focus(); }, 350);
        box.querySelector('[data-commit]').addEventListener('click', function (ev) {
            var btn = ev.currentTarget;
            btn.disabled = true;
            var data = {};
            OWN_FIELDS.forEach(function (k) { data[k] = String(o[k] || '').trim(); if (k !== 'ingredients' && k !== 'name' && k !== 'brand' && k !== 'unit_label') data[k] = data[k].replace(',', '.'); });
            data.per_ml = o.per_ml ? 1 : 0;
            data.allergens = o.allergens.join(',');
            data.traces = o.traces.join(',');
            ['nutriscore', 'nova', 'palm_oil', 'diet'].forEach(function (k) { data[k] = o[k] == null ? '' : o[k]; });
            if (o.id) data.id = o.id;
            if (o.labelBlob) data.label_photo = o.labelBlob;
            if (o.labelRemove) data.label_photo_remove = 1;
            post('scripts/food/product_save.php', data).then(function (res) {
                if (!S.sheet || S.sheet !== sh) return;
                var done = function (p) {
                    if (o.labelPreview) URL.revokeObjectURL(o.labelPreview);
                    sh.own = null;
                    // poprawka z profilu – wracamy tam; z arkusza porcji – porcja z nowymi wartościami
                    if (o.back === 'close') { closeSheet(true); return toast(res.message || 'Zapisano produkt'); }
                    if (o.back === 'portion') { toast(res.message || 'Zapisano produkt'); return openPortion(p, null, false); }
                    // składnik dania – zamiast porcji wracamy do edytora
                    if (o.forDish && sh.dish) { sh.scanFor = null; dishAdd(sh.dish, p); return setStep('dish', true); }
                    openPortion(p, null, false, o.notFound ? 'barcode' : undefined);
                };
                // ten kod już jest w bazie – zamiast duplikatu od razu ten produkt
                if (res.product && res.status === 'warning') { toast(res.message); return done(res.product); }
                if (res.status !== 'success') { btn.disabled = false; return fail(res); }
                done(res.product);
            });
        });
    }

    // zdjęcie etykiety – tylko do weryfikacji przez administratora, nie pokazujemy go innym
    function renderOwnLabel() {
        var o = S.sheet && S.sheet.own, box = el('fd_olabel');
        if (!o || !box) return;
        var src = o.labelPreview || (!o.labelRemove && o.label_photo);
        box.innerHTML = src
            ? '<figure class="fd-hero-photo fd-label-photo" style="margin:0"><img src="' + esc(src) + '" alt="Zdjęcie etykiety"><figcaption>Zdjęcie etykiety · widzi je tylko administrator</figcaption><div class="fd-photo-act">'
                + '<button type="button" class="fd-press" data-lpick>' + I.camera + 'Zmień</button>'
                + '<button type="button" class="fd-press" data-ldel aria-label="Usuń zdjęcie etykiety">' + I.trash + '</button></div></figure>'
            : '<button type="button" class="fd-photo-add fd-press" data-lpick>' + I.camera + '<span><b>Zrób zdjęcie etykiety</b>'
                + '<small>Tabela wartości i skład – administrator szybciej sprawdzi produkt. Nikt inny go nie zobaczy.</small></span></button>';
        box.querySelectorAll('[data-lpick]').forEach(function (b) { b.addEventListener('click', function () { el('fd_ofile').click(); }); });
        var del = box.querySelector('[data-ldel]');
        if (del) del.addEventListener('click', function () {
            if (o.labelPreview) URL.revokeObjectURL(o.labelPreview);
            o.labelBlob = o.labelPreview = null;
            o.labelRemove = !!o.label_photo;
            renderOwnLabel();
        });
    }

    // ---------- danie domowe ----------
    // składniki w jednostkach („3 × sztuka” jajka) albo gramach, skaner i własny produkt bez wychodzenia z edytora,
    // podział garnka na porcje (porcja = waga / liczba porcji), widoczność: dom albo wszyscy (po akceptacji)
    var DISH_UNITS = ['porcja', 'talerz', 'miska', 'kawałek', 'słoik'];

    function plural(n, one, few, many) {
        var t = n % 10, h = n % 100;
        return n === 1 ? one : t >= 2 && t <= 4 && (h < 12 || h > 14) ? few : many;
    }

    function newDish(name) {
        // domyślnie dla wszystkich – dom widzi od razu, reszta po akceptacji
        return { name: name || '', items: [], public: true, status: null, servings: 4, unit_label: 'porcja', cooked_g: '', photo: null };
    }

    // składnik z wyszukiwarki / skanera; ten sam produkt drugi raz = o jedną jednostkę (albo domyślną porcję) więcej
    function dishAdd(d, p) {
        var units = (p.units || []).filter(function (u) { return !u.base; });
        var same = d.items.filter(function (it) { return it.id === p.id; })[0];
        if (same) {
            same.grams += same.unit ? same.unit.grams : units[0] ? units[0].grams : 100;
            same.fresh = true;
            return same;
        }
        var it = { id: p.id, name: p.name, per_ml: !!p.per_ml, units: units, unit: units[0] || null, grams: units[0] ? units[0].grams : 100,
                   p100: { kcal: p.kcal, protein: p.protein, fat: p.fat, carbs: p.carbs }, fresh: true };
        d.items.push(it);
        return it;
    }

    function dishWeight(d) {
        var raw = d.items.reduce(function (a, it) { return a + it.grams; }, 0), cooked = parseFloat(String(d.cooked_g || '').replace(',', '.'));
        return { raw: raw, cooked: cooked > 0 ? cooked : 0, weight: cooked > 0 ? cooked : raw };
    }

    function bindEditDish() {
        var b = el('fd_step').querySelector('[data-editdish]');
        if (!b || b.dataset.bound) return;
        b.dataset.bound = '1';
        b.addEventListener('click', function () { editDish(S.sheet.p.product); });
    }

    // edytor z danych product.php (p.dish); from = 'dishes' – po zapisie i wstecz wracamy do listy „Twoje dania”
    function editDish(p, from) {
        var d = p.dish, unit = (p.units || []).filter(function (u) { return !u.base; })[0];
        var dish = Object.assign(newDish(p.name), { id: d.id, public: !!d.public, status: d.status, cooked_g: d.cooked_g || '', unit_label: unit ? unit.label : 'porcja',
                                                    photo: p.photo || null, from: from || null });
        dish.items = d.items.map(function (it) {
            var units = (it.units || []).filter(function (u) { return !u.base; });
            var unit = it.unit_label ? units.filter(function (u) { return u.label === it.unit_label && Math.abs(u.grams - it.unit_g) < 0.05; })[0] : null;
            // jednostka, której już nie ma przy produkcie (np. usunięta), zostaje do wyboru w tym daniu
            if (it.unit_label && !unit) units.push(unit = { label: it.unit_label, grams: it.unit_g });
            return { id: it.product_id, name: it.name, per_ml: it.per_ml, units: units, unit: unit, grams: it.grams, p100: it.p100 };
        });
        var w = dishWeight(dish).weight;
        dish.servings = d.servings || (unit && w ? Math.max(1, Math.min(20, Math.round(w / unit.grams))) : 1);
        S.sheet.dish = dish;
        setStep('dish');
    }

    // zdjęcie zmniejszamy w przeglądarce (telefon robi kilka MB, serwer nie ma GD) – JPEG, dłuższy bok 1600 px (albo max)
    function shrinkPhoto(file, max) {
        return new Promise(function (ok, no) {
            var img = new Image(), url = URL.createObjectURL(file);
            img.onload = function () {
                var s = Math.min(1, (max || 1600) / Math.max(img.naturalWidth, img.naturalHeight)), c = document.createElement('canvas');
                c.width = Math.round(img.naturalWidth * s);
                c.height = Math.round(img.naturalHeight * s);
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                URL.revokeObjectURL(url);
                c.toBlob(function (b) { if (b) ok(b); else no(); }, 'image/jpeg', 0.85);
            };
            img.onerror = function () { URL.revokeObjectURL(url); no(); };
            img.src = url;
        });
    }

    function renderDishPhoto() {
        var d = S.sheet && S.sheet.dish, box = el('fd_dphoto');
        if (!d || !box) return;
        var src = d.photoPreview || (!d.photoRemove && d.photo);
        box.innerHTML = src
            ? '<figure class="fd-hero-photo" style="margin:0"><img src="' + esc(src) + '" alt="Zdjęcie dania"><div class="fd-photo-act">'
                + '<button type="button" class="fd-press" data-phpick>' + I.camera + 'Zmień</button>'
                + '<button type="button" class="fd-press" data-phdel aria-label="Usuń zdjęcie">' + I.trash + '</button></div></figure>'
            : '<button type="button" class="fd-photo-add fd-press" data-phpick>' + I.camera + '<span><b>Dodaj zdjęcie dania</b>'
                + '<small>Zobaczą je wszyscy, którzy widzą to danie</small></span></button>';
        box.querySelectorAll('[data-phpick]').forEach(function (b) { b.addEventListener('click', function () { el('fd_dfile').click(); }); });
        var del = box.querySelector('[data-phdel]');
        if (del) del.addEventListener('click', function () {
            if (d.photoPreview) URL.revokeObjectURL(d.photoPreview);
            d.photoBlob = d.photoPreview = null;
            d.photoRemove = !!d.photo;
            renderDishPhoto();
        });
    }

    // Twoje dania: wszystkie przepisy autora – edycja, udostępnianie, szybkie dodanie do posiłku
    function renderDishes(box) {
        var sh = S.sheet;
        box.innerHTML = headHTML('Twoje dania', true)
            + '<div class="fd-scroll" id="fd_mydishes" style="padding:8px 14px 24px">' + '<div class="fd-skel"></div>'.repeat(3) + '</div>';
        bindHead(box, function () { setStep('search', true); });
        getJSON('scripts/food/dishes.php').then(function (r) {
            if (!S.sheet || S.sheet !== sh || sh.step !== 'dishes') return;
            var items = r.items || [], list = el('fd_mydishes');
            var state = function (d) {
                return !d.public ? ['Tylko dom', ''] : d.status === 'pending' ? ['Czeka na akceptację', 'is-wait'] : d.status === 'rejected' ? ['Nieudostępnione', 'is-no'] : ['Dla wszystkich', 'is-ok'];
            };
            list.innerHTML = '<button type="button" class="fd-row fd-press" data-dnew>'
                + '<span class="fd-row-ico" style="background:rgba(99,102,241,.14);color:#4f46e5">' + I.plus + '</span>'
                + '<div style="flex:1"><div style="font-size:14px;font-weight:500">Nowe danie</div><div class="text-xs fd-mut">Składniki, zdjęcie, podział na porcje</div></div></button>'
                + (items.length ? items.map(function (p, i) {
                    var c = state(p.dish), unit = (p.units || []).filter(function (x) { return !x.base; })[0];
                    return '<div class="fd-dcard"><button type="button" class="fd-row fd-press" style="flex:1;min-width:0" data-dedit="' + i + '">'
                        + (p.photo ? '<img class="fd-thumb is-lg" src="' + esc(p.photo) + '" alt="" loading="lazy">' : '<span class="fd-thumb is-lg is-empty">' + I.pot + '</span>')
                        + '<div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(p.name) + '</div>'
                        + '<div class="text-xs fd-mut" style="display:flex;flex-wrap:wrap;align-items:center;gap:4px 8px;margin-top:3px"><span class="fd-state ' + c[1] + '">' + c[0] + '</span>'
                        + (unit ? '1 ' + esc(unit.label) + ' · ' + fmt(p.kcal * unit.grams / 100) + ' kcal' : fmt(p.kcal) + ' kcal / 100 g')
                        + (p.dish.uses ? ' · w dzienniku ' + p.dish.uses + '×' : '') + '</div></div></button>'
                        + '<button type="button" class="fd-plus fd-press" data-dadd="' + i + '" aria-label="Dodaj do posiłku: ' + esc(p.name) + '">' + I.plus + '</button></div>';
                }).join('') : '<div class="hl-empty" style="padding:28px 0"><div class="hl-empty-ico">' + I.pot + '</div><h3>Nie masz jeszcze dań</h3><p>Zupa, sałatka, ciasto – zapisz przepis raz i dodawaj go jednym kliknięciem.</p></div>')
                + '<p class="fd-mut" style="margin:14px 8px 0;font-size:12px;line-height:1.5">Zmiany w przepisie działają od teraz. Wpisy w dzienniku mają własną kopię wartości, więc Twoja historia się nie zmieni.</p>';
            list.querySelector('[data-dnew]').addEventListener('click', function () { sh.dish = Object.assign(newDish(), { from: 'dishes' }); setStep('dish'); });
            list.querySelectorAll('[data-dadd]').forEach(function (b) { b.addEventListener('click', function () { openPortion(items[+b.dataset.dadd], null); }); });
            list.querySelectorAll('[data-dedit]').forEach(function (b) {
                b.addEventListener('click', function () {
                    setStep('loading');
                    getJSON('scripts/food/product.php?id=' + items[+b.dataset.dedit].id).then(function (res) {
                        if (S.sheet !== sh) return;
                        if (res.status !== 'success' || !res.product.dish) { fail(res); return setStep('dishes', true); }
                        editDish(res.product, 'dishes');
                    });
                });
            });
        });
    }

    function renderDish(box) {
        var sh = S.sheet, d = sh.dish;
        var opt = function (pub, title, sub) {
            return '<button type="button" role="radio" aria-checked="' + (d.public === pub) + '" class="fd-opt fd-press' + (d.public === pub ? ' is-on' : '') + '" data-dpub="' + (pub ? 1 : 0) + '">'
                + '<b>' + title + '</b><span>' + sub + '</span></button>';
        };
        box.innerHTML = headHTML(d.id ? 'Edytuj danie' : 'Nowe danie domowe', true)
            + '<div class="fd-scroll" id="fd_dish_scroll" style="padding:8px 20px 12px">'
            + '<div id="fd_dphoto" style="margin-bottom:16px"></div><input type="file" id="fd_dfile" accept="image/*" hidden>'
            + '<label class="hl-label" for="fd_dname">Nazwa</label>'
            + '<div class="fd-field"><input id="fd_dname" type="text" placeholder="np. Zupa pomidorowa" value="' + esc(d.name) + '"></div>'
            + '<span class="fd-eyebrow" style="margin:20px 0 6px">Składniki – cały garnek</span>'
            + '<div id="fd_ditems"></div>'
            + '<div style="position:relative;display:flex;gap:8px;margin-top:8px"><label class="fd-field" style="flex:1">' + I.search
            + '<input id="fd_dq" type="search" placeholder="Dodaj składnik: jajko, ryż…" autocomplete="off"></label>'
            + '<button type="button" class="fd-round fd-press" data-dscan style="width:46px;height:46px;border-radius:16px" aria-label="Skanuj kod składnika">' + I.scan + '</button>'
            + '<div id="fd_dres" class="fd-dres"></div></div>'
            + '<span class="fd-eyebrow" style="margin:22px 0 8px">Podział na porcje</span>'
            + '<div id="fd_dsplit" class="fd-box"></div>'
            + '<details class="fd-more" style="margin-top:12px"' + (d.cooked_g ? ' open' : '') + '><summary class="fd-eyebrow">Zważyłem gotowe danie</summary>'
            + '<label class="fd-field" style="padding-right:12px;margin-top:10px"><input id="fd_dcooked" type="text" inputmode="decimal" placeholder="waga bez garnka" value="' + esc(d.cooked_g || '') + '"><span class="text-xs fd-mut">g</span></label>'
            + '<p class="hl-hint">Woda odparowuje albo dochodzi, więc waga gotowego dania jest dokładniejsza niż suma składników. Porcje przeliczą się same.</p></details>'
            + '<span class="fd-eyebrow" style="margin:22px 0 8px">Kto zobaczy danie</span>'
            + '<div class="fd-opts" role="radiogroup" aria-label="Widoczność dania">' + opt(false, 'Tylko dom', 'Ty i domownicy – osoby ze wspólnych list')
            + opt(true, 'Dla wszystkich', 'Dom od razu, pozostali po akceptacji administratora') + '</div>'
            + '<p id="fd_dstatus" class="hl-hint"></p>'
            + '</div><div class="fd-foot">' + (d.id ? '<button type="button" class="fd-danger fd-press" data-ddel aria-label="Usuń danie">' + I.trash + '</button>' : '')
            + '<button type="button" class="fd-cta fd-press" data-commit>' + (d.id ? 'Zapisz danie' : 'Zapisz i dodaj porcję') + '</button></div>';
        bindHead(box, function () {
            if (d.from === 'dishes') { sh.dish = null; return setStep('dishes', true); }
            sh.dish = d.id ? null : sh.dish;
            setStep(d.id ? 'portion' : 'search', true);
        });
        el('fd_dfile').addEventListener('change', function () {
            var file = this.files && this.files[0];
            this.value = '';
            if (!file) return;
            shrinkPhoto(file).then(function (blob) {
                if (d.photoPreview) URL.revokeObjectURL(d.photoPreview);
                d.photoBlob = blob;
                d.photoPreview = URL.createObjectURL(blob);
                d.photoRemove = false;
                renderDishPhoto();
            }).catch(function () { fail({ status: 'warning', message: 'Nie udało się wczytać zdjęcia – spróbuj innego pliku' }); });
        });
        renderDishPhoto();
        el('fd_dname').addEventListener('input', function () { d.name = this.value; dishSum(); });
        el('fd_dcooked').addEventListener('input', function () { d.cooked_g = this.value; dishSum(); });
        box.querySelectorAll('[data-dpub]').forEach(function (b) {
            b.addEventListener('click', function () {
                d.public = b.dataset.dpub === '1';
                box.querySelectorAll('[data-dpub]').forEach(function (x) { var on = x === b; x.classList.toggle('is-on', on); x.setAttribute('aria-checked', on); });
                dishStatus();
            });
        });
        box.querySelector('[data-dscan]').addEventListener('click', function () { sh.scanFor = 'dish'; setStep('scan'); });

        var q = el('fd_dq'), res = el('fd_dres');
        var addOwn = function (name) { sh.own = { name: name, forDish: true }; setStep('own'); };
        q.addEventListener('input', function () {
            clearTimeout(S.dTimer);
            var term = q.value.trim();
            if (!term) { res.innerHTML = ''; return; }
            S.dTimer = setTimeout(function () {
                getJSON('scripts/food/search.php?q=' + encodeURIComponent(term)).then(function (r) {
                    if (q.value.trim() !== term) return;
                    var items = (r.groups || []).reduce(function (a, g) { return a.concat(g.items); }, []).filter(function (p) { return p.source !== 'dish'; }).slice(0, 8);
                    res.innerHTML = items.map(function (p, i) {
                        var u = (p.units || []).filter(function (x) { return !x.base; })[0];
                        return '<button type="button" class="fd-row" data-pick="' + i + '"><div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(p.name) + '</div>'
                            + '<div class="text-xs fd-mut">' + esc([p.brand, fmt(p.kcal) + ' kcal / 100 ' + (p.per_ml ? 'ml' : 'g'), u ? '1 ' + u.label + ' = ' + dec(u.grams) + ' ' + (p.per_ml ? 'ml' : 'g') : ''].filter(Boolean).join(' · ')) + '</div></div>'
                            + I.plus.replace('<svg', '<svg width="18" height="18"') + '</button>';
                    }).join('') + '<button type="button" class="fd-row" data-own><div style="flex:1;min-width:0"><div class="fd-entry-name">' + (items.length ? 'Nie ma tego?' : 'Brak w bazie')
                        + '</div><div class="text-xs fd-mut">Dodaj „' + esc(term) + '” jako własny produkt</div></div>' + I.plus.replace('<svg', '<svg width="18" height="18"') + '</button>';
                    res.querySelectorAll('[data-pick]').forEach(function (b) {
                        b.addEventListener('click', function () {
                            dishAdd(d, items[+b.dataset.pick]);
                            q.value = '';
                            res.innerHTML = '';
                            dishItems();
                            q.focus();
                        });
                    });
                    res.querySelector('[data-own]').addEventListener('click', function () { addOwn(term); });
                });
            }, 220);
        });

        box.querySelector('[data-commit]').addEventListener('click', function (ev) {
            var btn = ev.currentTarget;
            btn.disabled = true;
            post('scripts/food/dish_save.php', {
                id: d.id || '', name: d.name.trim(), cooked_g: String(d.cooked_g || '').replace(',', '.'), public: d.public ? 1 : 0,
                servings: d.servings, unit_label: d.unit_label,
                photo: d.photoBlob || null, photo_remove: d.photoRemove ? 1 : null,
                items: JSON.stringify(d.items.map(function (it) {
                    return { product_id: it.id, grams: it.grams, unit_label: it.unit ? it.unit.label : '', unit_g: it.unit ? it.unit.grams : '' };
                }))
            }).then(function (r) {
                if (!S.sheet || S.sheet !== sh) return;
                if (r.status !== 'success') { btn.disabled = false; return fail(r); }
                if (d.photoPreview) URL.revokeObjectURL(d.photoPreview);
                sh.dish = null;
                if (d.from === 'dishes') { toast(r.message); return setStep('dishes', true); }
                if (sh.p && sh.p.entry) { toast(r.message); return closeSheet(); }
                if (r.message !== 'Zapisano danie') toast(r.message);
                openPortion(r.product, null, false);
            });
        });
        var del = box.querySelector('[data-ddel]');
        if (del) del.addEventListener('click', function () {
            if (!confirm('Usunąć to danie? Wpisy w dziennikach zostaną.')) return;
            post('scripts/food/dish_delete.php', { id: d.id }).then(function (r) {
                if (r.status !== 'success') return fail(r);
                toast(r.message);
                sh.dish = null;
                sh.p = null;
                setStep(d.from === 'dishes' ? 'dishes' : 'search', true);
            });
        });
        dishItems();
        dishStatus();
    }

    function dishStatus() {
        var d = S.sheet && S.sheet.dish, p = el('fd_dstatus');
        if (!d || !p) return;
        p.textContent = !d.public ? ''
            : d.status === 'pending' ? 'Czeka na akceptację administratora – na razie widzi je Twój dom.'
            : d.status === 'rejected' ? 'Administrator nie udostępnił tego dania – widzi je tylko Twój dom. Zapisz ponownie, żeby wysłać do akceptacji.'
            : d.status === 'approved' && d.id ? 'Udostępnione wszystkim. Zmiany w przepisie wrócą do akceptacji.'
            : '';
    }

    function ingKcal(it) { return it.p100.kcal * it.grams / 100; }

    // −/+ składnika (danie, posiłek z AI): w jednostkach o całą sztukę (poniżej jednej – pół), w gramach o krok zależny od wagi
    function stepAmount(it, up) {
        var eps = 0.02;
        if (it.unit) {
            var n = it.grams / it.unit.grams;
            n = up ? Math.floor(n + eps) + 1 : n > 1 + eps ? Math.ceil(n - eps) - 1 : 0.5;
            it.grams = Math.round(n * it.unit.grams * 10) / 10;
        } else {
            var s = it.grams >= 100 ? 10 : it.grams >= 20 ? 5 : 1;
            it.grams = up ? Math.floor(it.grams / s) * s + s : Math.max(s, Math.ceil(it.grams / s) * s - s);
        }
    }

    // wybór jednostki + licznik (wspólny wygląd składnika dania i posiłku z AI); attr = prefiks atrybutów data-
    function amountCtlHTML(it, i, attr) {
        var u = it.per_ml ? 'ml' : 'g';
        var sel = it.units.length
            ? '<select class="fd-sel" data-' + attr + 'u="' + i + '" aria-label="Jednostka: ' + esc(it.name) + '">' + it.units.map(function (x, k) {
                return '<option value="' + k + '"' + (it.unit === x ? ' selected' : '') + '>' + esc(x.label) + ' · ' + dec(x.grams) + ' ' + u + '</option>';
            }).join('') + '<option value="-1"' + (it.unit ? '' : ' selected') + '>' + (it.per_ml ? 'mililitry' : 'gramy') + '</option></select>'
            : '';
        return sel + '<div class="fd-stepper"><button type="button" class="fd-cnt fd-press" data-' + attr + 's="-1" data-i="' + i + '" aria-label="Mniej">' + I.minus + '</button>'
            + '<input type="text" inputmode="decimal" data-' + attr + 'q="' + i + '" value="' + (it.unit ? qty(it.grams / it.unit.grams) : dec(it.grams)) + '" aria-label="Ilość: ' + esc(it.name) + '">'
            + '<span class="fd-mut">' + (it.unit ? '×' : u) + '</span>'
            + '<button type="button" class="fd-cnt fd-press" data-' + attr + 's="1" data-i="' + i + '" aria-label="Więcej">' + I.plus + '</button></div>';
    }

    function ingAmount(it) {
        var u = it.per_ml ? 'ml' : 'g';
        return (it.unit ? dec(it.grams) + ' ' + u + ' · ' : '') + fmt(ingKcal(it)) + ' kcal';
    }

    function dishItems() {
        var d = S.sheet.dish, box = el('fd_ditems');
        box.innerHTML = d.items.length ? d.items.map(function (it, i) {
            return '<div class="fd-ing' + (it.fresh ? ' is-fresh' : '') + '">'
                + '<div style="display:flex;align-items:center;gap:8px"><div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(it.name) + '</div>'
                + '<div class="text-xs fd-mut fd-num" data-ikcal="' + i + '">' + ingAmount(it) + '</div></div>'
                + '<button type="button" class="fd-icon-btn fd-press fd-mut" data-irm="' + i + '" aria-label="Usuń składnik">' + I.x.replace('<svg', '<svg width="18" height="18"') + '</button></div>'
                + '<div class="fd-ing-ctl">' + amountCtlHTML(it, i, 'i') + '</div></div>';
        }).join('') : '<p class="fd-mut" style="margin:4px;font-size:13px">Wyszukaj albo zeskanuj składniki. Jajka, kromki czy opakowania wpiszesz w sztukach.</p>';
        d.items.forEach(function (it) { delete it.fresh; });

        box.querySelectorAll('[data-iq]').forEach(function (inp) {
            inp.addEventListener('focus', function () { inp.select(); });
            inp.addEventListener('input', function () {
                var it = d.items[+inp.dataset.iq], v = parseFloat(inp.value.replace(',', '.'));
                if (isNaN(v) || v < 0) return;
                it.grams = it.unit ? Math.round(v * it.unit.grams * 10) / 10 : v;
                box.querySelector('[data-ikcal="' + inp.dataset.iq + '"]').textContent = ingAmount(it);
                dishSum();
            });
            inp.addEventListener('blur', function () { var it = d.items[+inp.dataset.iq]; if (it) inp.value = it.unit ? qty(it.grams / it.unit.grams) : dec(it.grams); });
        });
        box.querySelectorAll('[data-is]').forEach(function (b) {
            b.addEventListener('click', function () {
                stepAmount(d.items[+b.dataset.i], +b.dataset.is > 0);
                vib();
                dishItems();
            });
        });
        box.querySelectorAll('[data-iu]').forEach(function (s) {
            s.addEventListener('change', function () {
                var it = d.items[+s.dataset.iu], k = +s.value;
                // przełączenie na sztuki zaokrągla do całych (co najmniej jednej), na gramy – zostawia wagę
                it.unit = k < 0 ? null : it.units[k];
                if (it.unit) it.grams = Math.max(1, Math.round(it.grams / it.unit.grams)) * it.unit.grams;
                dishItems();
            });
        });
        box.querySelectorAll('[data-irm]').forEach(function (b) { b.addEventListener('click', function () { d.items.splice(+b.dataset.irm, 1); dishItems(); }); });
        dishSum();
    }

    function dishSum() {
        var d = S.sheet && S.sheet.dish, box = el('fd_dsplit');
        if (!d || !box) return;
        var w = dishWeight(d), tot = { kcal: 0, protein: 0, fat: 0, carbs: 0 };
        d.items.forEach(function (it) { Object.keys(tot).forEach(function (k) { tot[k] += (it.p100[k] || 0) * it.grams / 100; }); });
        var n = d.servings, portion = n ? w.weight / n : 0, f = w.weight ? portion / w.weight : 0;
        box.innerHTML = '<div style="display:flex;align-items:center;gap:12px">'
            + '<span style="flex:1;font-size:14px;font-weight:500">Na ile porcji?</span>'
            + '<div class="fd-stepper"><button type="button" class="fd-cnt fd-press" data-sv="-1" aria-label="Mniej porcji"' + (n <= 1 ? ' disabled' : '') + '>' + I.minus + '</button>'
            + '<b class="fd-num" style="min-width:2ch;text-align:center;font-size:18px">' + n + '</b>'
            + '<button type="button" class="fd-cnt fd-press" data-sv="1" aria-label="Więcej porcji"' + (n >= 20 ? ' disabled' : '') + '>' + I.plus + '</button></div></div>'
            + '<div class="fd-chips" style="margin:10px 0 0;padding:0" role="radiogroup" aria-label="Nazwa porcji">' + DISH_UNITS.map(function (l) {
                return '<button type="button" role="radio" aria-checked="' + (d.unit_label === l) + '" class="fd-chip fd-press' + (d.unit_label === l ? ' is-on' : '') + '" data-sl="' + l + '">' + l + '</button>';
            }).join('') + '</div>'
            + (w.weight ? '<p style="margin:12px 0 8px;font-size:13px"><b>1 ' + esc(d.unit_label) + '</b> ≈ ' + fmt(portion) + ' g'
                + ' <span class="fd-mut">· garnek ' + fmt(w.weight) + ' g' + (w.cooked && w.raw ? ' (składniki ' + fmt(w.raw) + ' g)' : '') + ' = ' + n + ' ' + plural(n, 'porcja', 'porcje', 'porcji') + '</span></p>'
                + '<div class="fd-mini"><div><b class="fd-num">' + fmt(tot.kcal * f) + '</b><span>kcal</span></div>'
                + '<div><b class="fd-num" style="color:var(--fd-p)">' + dec(tot.protein * f) + '</b><span>białko g</span></div>'
                + '<div><b class="fd-num" style="color:var(--fd-f)">' + dec(tot.fat * f) + '</b><span>tłuszcz g</span></div>'
                + '<div><b class="fd-num" style="color:var(--fd-c)">' + dec(tot.carbs * f) + '</b><span>węgle g</span></div></div>'
                + '<p class="fd-mut" style="margin:8px 0 0;font-size:12px;text-align:center">Cały garnek ' + fmt(tot.kcal) + ' kcal · ' + fmt(w.weight ? tot.kcal / w.weight * 100 : 0) + ' kcal / 100 g</p>'
              : '<p class="fd-mut" style="margin:12px 0 0;font-size:13px">Dodaj składniki – policzymy, ile ma jedna porcja.</p>');
        box.querySelectorAll('[data-sv]').forEach(function (b) {
            b.addEventListener('click', function () { d.servings = Math.max(1, Math.min(20, d.servings + +b.dataset.sv)); vib(); dishSum(); });
        });
        box.querySelectorAll('[data-sl]').forEach(function (b) { b.addEventListener('click', function () { d.unit_label = b.dataset.sl; dishSum(); }); });
        el('fd_step').querySelector('[data-commit]').disabled = !(d.name.trim() && d.items.length);
    }

    // ---------- cel ----------
    var ACTS = [[1.2, 'Głównie siedzę', 'praca przy biurku, mało ruchu'], [1.375, 'Lekka', 'spacery, 1–2 treningi w tygodniu'],
                [1.55, 'Umiarkowana', '3–4 treningi w tygodniu'], [1.725, 'Wysoka', 'praca fizyczna albo codzienny trening']];

    function goalCalc(c) {
        var bmr = 10 * c.weight + 6.25 * c.height - 5 * c.age + (c.sex === 'k' ? -161 : 5);
        var delta = c.goal === 'cut' ? -500 : c.goal === 'bulk' ? 300 : 0;
        var kcal = Math.round((bmr * c.activity + delta) / 10) * 10 + (c.adj || 0);
        kcal = Math.max(1200, kcal);
        var perKg = c.goal === 'keep' ? 1.6 : 1.8;
        var protein = Math.round(c.weight * perKg), fat = Math.round(kcal * 0.3 / 9), carbs = Math.max(0, Math.round((kcal - protein * 4 - fat * 9) / 4));
        return { bmr: bmr, delta: delta, kcal: kcal, protein: protein, fat: fat, carbs: carbs, perKg: perKg };
    }

    function renderGoal(box) {
        var sh = S.sheet, g = S.data.day.goal;
        if (!sh.calc) {
            sh.calc = Object.assign({ sex: 'm', age: 30, height: 175, weight: 75, activity: 1.375, goal: 'keep' }, (g && g.calc) || {}, { adj: 0 });
            // zapisany cel mógł mieć ręczną korektę – odtwarzamy ją względem wyliczenia
            if (g && g.calc) sh.calc.adj = g.kcal - goalCalc(sh.calc).kcal;
        }
        var c = sh.calc;
        var seg = function (key, opts) {
            return '<div class="hl-seg" style="display:grid;grid-template-columns:repeat(' + opts.length + ',minmax(0,1fr));gap:4px;padding:4px;border-radius:18px;background:var(--fd-field)">'
                + opts.map(function (o) {
                    var on = c[key] === o[0];
                    return '<button type="button" class="fd-press" data-set="' + key + '" data-v="' + o[0] + '" style="height:40px;border-radius:14px;font-size:13px;font-weight:500;'
                        + (on ? 'background:var(--hl-card);color:var(--hl-ink);box-shadow:0 1px 2px rgba(0,0,0,.08),0 6px 16px -10px rgba(0,0,0,.35)' : 'color:var(--hl-ink-2)') + '">' + o[1] + '</button>';
                }).join('') + '</div>';
        };
        var stepper = function (key, label, lo, hi) {
            return '<div><div style="display:flex;align-items:center;justify-content:space-between;padding:6px;border-radius:18px;background:var(--fd-field)">'
                + '<button type="button" class="fd-icon-btn fd-press" style="width:34px;height:34px" data-inc="' + key + '" data-d="-1" data-lo="' + lo + '" data-hi="' + hi + '" aria-label="' + label + ' mniej">' + I.minus.replace('<svg', '<svg width="16" height="16"') + '</button>'
                + '<span class="fd-num" style="font-size:16px;font-weight:600">' + c[key] + '</span>'
                + '<button type="button" class="fd-icon-btn fd-press" style="width:34px;height:34px" data-inc="' + key + '" data-d="1" data-lo="' + lo + '" data-hi="' + hi + '" aria-label="' + label + ' więcej">' + I.plus.replace('<svg', '<svg width="16" height="16"') + '</button></div>'
                + '<div class="fd-mut" style="margin-top:4px;text-align:center;font-size:11px">' + label + '</div></div>';
        };
        var r = goalCalc(c);
        box.innerHTML = headHTML('Twój dzienny cel', sh.from === 'stats')
            + '<div class="fd-scroll" style="padding:4px 20px 12px">'
            + '<p class="fd-mut" style="margin:0;font-size:13px">Policzymy go z Twoich danych. Każdą liczbę możesz potem zmienić.</p>'
            + '<span class="fd-eyebrow" style="margin:16px 0 8px">Chcę</span>' + seg('goal', [['cut', 'Schudnąć'], ['keep', 'Utrzymać'], ['bulk', 'Przytyć']])
            + '<span class="fd-eyebrow" style="margin:16px 0 8px">O mnie</span>' + seg('sex', [['m', 'Mężczyzna'], ['k', 'Kobieta']])
            + '<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:8px">'
            + stepper('age', 'wiek', 14, 99) + stepper('height', 'wzrost cm', 120, 230) + stepper('weight', 'waga kg', 35, 250) + '</div>'
            + '<span class="fd-eyebrow" style="margin:16px 0 8px">Aktywność w ciągu dnia</span><div style="display:flex;flex-direction:column;gap:6px">'
            + ACTS.map(function (a) {
                var on = c.activity === a[0];
                return '<button type="button" class="fd-row fd-press" data-act-v="' + a[0] + '" style="background:' + (on ? 'var(--hl-sel-bg);color:var(--hl-sel-ink)' : 'var(--fd-field)') + '">'
                    + '<span style="flex:1"><span style="display:block;font-size:14px;font-weight:500">' + a[1] + '</span><span style="display:block;font-size:12px;opacity:.7">' + a[2] + '</span></span></button>';
            }).join('') + '</div>'
            + '<div class="hl-card" style="margin-top:18px;padding:18px"><span class="fd-eyebrow">Dzienny cel</span>'
            + '<div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">'
            + '<button type="button" class="fd-round fd-press" style="width:40px;height:40px" data-adj="-50" aria-label="Mniej o 50 kcal">' + I.minus + '</button>'
            + '<div style="text-align:center"><span class="fd-num" style="font-size:44px;font-weight:600;letter-spacing:-.04em;line-height:1">' + fmt(r.kcal) + '</span><span class="fd-mut" style="font-size:16px"> kcal</span></div>'
            + '<button type="button" class="fd-round fd-press" style="width:40px;height:40px" data-adj="50" aria-label="Więcej o 50 kcal">' + I.plus + '</button></div>'
            + '<p class="fd-mut" style="margin:8px 0 0;text-align:center;font-size:12px">Przemiana materii ' + fmt(r.bmr) + ' × ' + String(c.activity).replace('.', ',')
            + (r.delta ? (r.delta < 0 ? ' − ' : ' + ') + Math.abs(r.delta) : '') + (c.adj ? ' · ręcznie ' + (c.adj > 0 ? '+' : '−') + Math.abs(c.adj) : '') + '</p>'
            + '<div style="display:flex;height:10px;border-radius:999px;overflow:hidden;gap:3px;margin-top:16px">'
            + '<span style="flex-grow:' + r.protein * 4 + ';background:var(--fd-p)"></span><span style="flex-grow:' + r.fat * 9 + ';background:var(--fd-f)"></span><span style="flex-grow:' + r.carbs * 4 + ';background:var(--fd-c)"></span></div>'
            + '<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:12px">'
            + '<div><div class="fd-num" style="font-size:17px;font-weight:600">' + r.protein + ' g</div><div class="fd-mut" style="font-size:11px">Białko · ' + String(r.perKg).replace('.', ',') + ' g/kg</div></div>'
            + '<div><div class="fd-num" style="font-size:17px;font-weight:600">' + r.fat + ' g</div><div class="fd-mut" style="font-size:11px">Tłuszcz · 30%</div></div>'
            + '<div><div class="fd-num" style="font-size:17px;font-weight:600">' + r.carbs + ' g</div><div class="fd-mut" style="font-size:11px">Węgle · reszta</div></div></div></div>'
            + '<p class="fd-mut" style="margin:12px 4px 0;font-size:11px;line-height:1.5">Wzór Mifflin-St Jeor. To szacunek, a nie zalecenie medyczne.</p></div>'
            + '<div class="fd-foot"><button type="button" class="fd-cta fd-press" data-commit>Zapisz cel</button></div>';
        bindHead(box, function () { sh.calc = null; setStep('stats', true); });
        var redraw = function () {
            var sc = box.querySelector('.fd-scroll'), top = sc ? sc.scrollTop : 0;
            renderGoal(box);
            box.querySelector('.fd-scroll').scrollTop = top;
        };
        box.querySelectorAll('[data-set]').forEach(function (b) { b.addEventListener('click', function () { c[b.dataset.set] = b.dataset.v; c.adj = 0; redraw(); }); });
        box.querySelectorAll('[data-inc]').forEach(function (b) {
            b.addEventListener('click', function () {
                var k = b.dataset.inc;
                c[k] = Math.max(+b.dataset.lo, Math.min(+b.dataset.hi, c[k] + +b.dataset.d));
                c.adj = 0;
                redraw();
            });
        });
        box.querySelectorAll('[data-act-v]').forEach(function (b) { b.addEventListener('click', function () { c.activity = +b.dataset.actV; c.adj = 0; redraw(); }); });
        box.querySelectorAll('[data-adj]').forEach(function (b) { b.addEventListener('click', function () { c.adj += +b.dataset.adj; redraw(); }); });
        box.querySelector('[data-commit]').addEventListener('click', function (ev) {
            var btn = ev.currentTarget;
            btn.disabled = true;
            post('scripts/food/goal_save.php', { kcal: r.kcal, protein: r.protein, fat: r.fat, carbs: r.carbs, calc: JSON.stringify(c) }).then(function (res) {
                if (res.status !== 'success') { btn.disabled = false; return fail(res); }
                closeSheet(true);
                afterChange();
                toast('Zapisano cel: ' + fmt(r.kcal) + ' kcal');
            });
        });
    }

    // ---------- cukry (podsumowanie dnia i statystyki) ----------
    // s: { grams, kcal, carbs, goalKcal, top: [{ name, sugars, share }], known, n, perDay }
    // punkt odniesienia: 10% energii celu (zalecenie WHO dla cukrów wolnych), bez celu 50 g
    function sugarHTML(s) {
        var ref = s.goalKcal ? Math.round(s.goalKcal * 0.1 / 4) : 50;
        var pct = s.grams / ref * 100, over = pct > 100;
        var color = over ? '#f59e0b' : 'var(--fd-c)';
        var side = [];
        if (s.carbs) side.push('<b class="fd-num">' + Math.round(s.grams / s.carbs * 100) + '%</b> węglowodanów');
        if (s.kcal) side.push('<b class="fd-num">' + Math.round(s.grams * 4 / s.kcal * 100) + '%</b> energii');
        var html = '<div class="fd-box">'
            + '<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px">'
            + '<div><span class="fd-num" style="font-size:28px;font-weight:600;letter-spacing:-.03em;line-height:1">' + dec(s.grams) + '</span>'
            + '<span class="fd-mut" style="font-size:14px"> g cukrów' + (s.perDay ? ' dziennie' : '') + '</span></div>'
            + '<div class="fd-mut" style="text-align:right;font-size:12px;line-height:1.5">' + side.join('<br>') + '</div></div>'
            + '<div class="fd-bar" style="margin-top:12px"><span style="left:0;width:' + Math.min(pct, 100).toFixed(1) + '%;background:' + color + '"></span></div>'
            + '<p class="fd-mut" style="margin:6px 0 0;font-size:12px">' + (over ? '<b style="color:#d97706">' + Math.round(pct) + '%</b>' : Math.round(pct) + '%')
            + ' orientacyjnego limitu ' + ref + ' g' + (s.goalKcal ? ' (10% energii celu)' : '') + '</p>';
        if (s.top.length) {
            html += '<div class="fd-list" style="margin-top:10px">' + s.top.map(function (t) {
                return '<div class="fd-li"><span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(t.name) + '</span>'
                    + '<span class="fd-num" style="white-space:nowrap;font-size:12px"><b>' + dec(t.sugars) + ' g</b><span class="fd-mut"> · ' + t.share + '%</span></span></div>';
            }).join('') + '</div>';
        }
        html += '</div><p class="fd-mut" style="margin:8px 2px 0;font-size:11px;line-height:1.5">'
            + (s.known < s.n ? 'Brak danych o cukrze dla ' + (s.n - s.known) + ' z ' + s.n + ' ' + (s.perDay ? 'wpisów' : 'produktów') + ' – nie są wliczone. ' : '')
            + 'Cukry ogółem, także naturalne z owoców i mleka – limit WHO dotyczy cukrów dodanych, więc traktuj go orientacyjnie.</p>';
        return html;
    }

    // ---------- podsumowanie dnia (przycisk pod posiłkami): liczby dnia + tabela wszystkich wpisów z makro ----------
    function renderDay(box) {
        var sh = S.sheet, day = S.data.day, t = day.total, goal = day.goal, d = s2d(day.date);
        var meals = day.meals.filter(function (m) { return m.entries.length; });
        var all = [];
        meals.forEach(function (m) { all = all.concat(m.entries); });
        var sec = function (title, html) { return '<span class="fd-eyebrow" style="margin:22px 0 10px">' + title + '</span>' + html; };
        var html = '<p class="fd-mut" style="margin:0;font-size:13px">' + DAY_LONG[d.getDay()] + ', ' + d.getDate() + ' ' + MONTHS[d.getMonth()] + '</p>';

        if (!all.length) {
            html += '<div class="hl-empty" style="margin-top:28px"><div class="hl-empty-ico">' + I.list.replace('<svg', '<svg width="32" height="32"') + '</div>'
                + '<h3>Ten dzień jest jeszcze pusty</h3><p>Dodaj coś do posiłków, a tutaj zobaczysz bilans dnia i tabelę z makro.</p></div>';
        } else {
            var kpi = function (label, big, sub, bar, color) {
                return '<div class="fd-kpi"><span class="fd-kpi-label">' + label + '</span><b class="fd-num">' + big + '</b>'
                    + (bar !== undefined ? '<span class="fd-kpi-bar"><span style="width:' + Math.min(bar, 100).toFixed(1) + '%' + (color ? ';background:' + color : '') + '"></span></span>' : '')
                    + '<span class="fd-kpi-sub">' + sub + '</span></div>';
            };
            var left = goal ? goal.kcal - t.kcal : 0, pct = goal ? Math.round(t.kcal / goal.kcal * 100) : 0;
            var mealWord = function (n) { return n === 1 ? 'posiłku' : 'posiłkach'; };
            html += '<div class="fd-kpis">'
                + kpi('Zjedzone', fmt(t.kcal) + '<small> kcal</small>', goal ? pct + '% celu ' + fmt(goal.kcal) + ' kcal' : 'bez ustawionego celu', goal ? pct : undefined, goal && pct > 110 ? '#f59e0b' : null)
                + (goal
                    ? kpi(left >= 0 ? 'Zostało' : 'Ponad cel', fmt(Math.abs(left)) + '<small> kcal</small>', left >= 0 ? (S.date === F.today() ? 'do końca dnia' : 'poniżej celu') : 'o ' + (pct - 100) + '% więcej niż cel')
                    : kpi('Średnio na posiłek', fmt(t.kcal / meals.length) + '<small> kcal</small>', 'w ' + meals.length + ' ' + mealWord(meals.length)))
                + kpi('Produkty', String(all.length), 'w ' + meals.length + ' ' + mealWord(meals.length))
                + kpi('Białko', dec(t.protein) + '<small> g</small>', goal && goal.protein ? Math.round(t.protein / goal.protein * 100) + '% celu ' + fmt(goal.protein) + ' g' : fmt(t.protein * 4) + ' kcal z białka',
                    goal && goal.protein ? t.protein / goal.protein * 100 : undefined, 'var(--fd-p)')
                + '</div>';

            // cukry wysoko – tuż pod kafelkami; źródła sumowane po nazwie (dwa razy pomidor = jedna pozycja)
            if (t.sugars_n) {
                var bySugar = {};
                all.forEach(function (e) { if (e.sugars > 0) bySugar[e.name] = (bySugar[e.name] || 0) + e.sugars; });
                var sugTop = Object.keys(bySugar).sort(function (a, b) { return bySugar[b] - bySugar[a]; }).slice(0, 3).map(function (n) {
                    return { name: n, sugars: bySugar[n], share: t.sugars ? Math.round(bySugar[n] / t.sugars * 100) : 0 };
                });
                html += sec('Cukry', sugarHTML({ grams: t.sugars, kcal: t.kcal, carbs: t.carbs, goalKcal: goal ? goal.kcal : 0, top: sugTop, known: t.sugars_n, n: all.length }));
            }

            html += sec('Makroskładniki', '<div class="fd-bars">' + barsHTML(t, null, goal, MACROS, false) + '</div>');

            // skąd kalorie: energia z makro (4 / 9 / 4 kcal na gram), obok proporcje z celu
            var en = { protein: t.protein * 4, fat: t.fat * 9, carbs: t.carbs * 4 }, enSum = en.protein + en.fat + en.carbs;
            if (enSum) {
                var gEn = goal && goal.protein ? { protein: goal.protein * 4, fat: goal.fat * 9, carbs: goal.carbs * 4 } : null;
                var gSum = gEn ? gEn.protein + gEn.fat + gEn.carbs : 0;
                html += sec('Skąd kalorie', '<div class="fd-stack">' + MACROS.map(function (m) { return '<span style="flex-grow:' + en[m[0]].toFixed(0) + ';background:' + m[2] + '"></span>'; }).join('') + '</div>'
                    + '<div class="fd-list" style="margin-top:8px">' + MACROS.map(function (m) {
                        return '<div class="fd-li"><i class="fd-dot" style="background:' + m[2] + '"></i><span style="flex:1">' + m[1] + '</span>'
                            + '<b class="fd-num">' + Math.round(en[m[0]] / enSum * 100) + '%</b>'
                            + (gSum ? '<span class="fd-mut fd-num" style="width:64px;text-align:right;font-size:12px">cel ' + Math.round(gEn[m[0]] / gSum * 100) + '%</span>' : '') + '</div>';
                    }).join('') + '</div>');
            }

            if (meals.length > 1) {
                html += sec('Posiłki', '<div class="fd-list">' + meals.map(function (m) {
                    var share = t.kcal ? m.kcal / t.kcal * 100 : 0;
                    return '<div class="fd-li" style="flex-wrap:wrap"><span style="flex:1">' + MEALS[m.meal] + '</span><b class="fd-num">' + fmt(m.kcal) + ' kcal</b>'
                        + '<span class="fd-mut fd-num" style="width:38px;text-align:right;font-size:12px">' + Math.round(share) + '%</span>'
                        + '<span class="fd-bar" style="flex-basis:100%;height:6px;margin-top:6px"><span style="left:0;width:' + share.toFixed(1) + '%;background:var(--fd-k)"></span></span></div>';
                }).join('') + '</div>');
            }

            // wyróżnione produkty – tylko gdy jest z czego wybierać
            if (all.length > 1) {
                var top = function (k) { return all.reduce(function (a, e) { return e[k] > a[k] ? e : a; }); };
                var notes = [['Najwięcej kalorii', 'kcal', ' kcal'], ['Najwięcej białka', 'protein', ' g'], ['Najwięcej tłuszczu', 'fat', ' g'], ['Najwięcej węglowodanów', 'carbs', ' g']]
                    .map(function (n) { return n.concat([top(n[1])]); })
                    .filter(function (n) { return n[3][n[1]] > 0; });
                html += sec('Wyróżnione', '<div class="fd-list">' + notes.map(function (n) {
                    var e = n[3], share = t[n[1]] ? Math.round(e[n[1]] / t[n[1]] * 100) : 0;
                    return '<div class="fd-li"><span class="fd-mut" style="width:40%;flex:none;font-size:12px">' + n[0] + '</span>'
                        + '<span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(e.name) + '</span>'
                        + '<span class="fd-num" style="white-space:nowrap;font-size:12px"><b>' + (n[1] === 'kcal' ? fmt(e.kcal) : dec(e[n[1]])) + n[2] + '</b><span class="fd-mut"> · ' + share + '%</span></span></div>';
                }).join('') + '</div>');
            }

            var cells = function (o) {
                return ['kcal', 'protein', 'fat', 'carbs'].map(function (k) { return '<td>' + (k === 'kcal' ? fmt(o[k]) : dec(o[k])) + '</td>'; }).join('');
            };
            var rows = meals.map(function (m) {
                return '<tr class="fd-tb-meal"><th scope="rowgroup">' + MEALS[m.meal] + '</th>' + cells(m) + '</tr>'
                    + m.entries.map(function (e) {
                        var amt = amountText(e);
                        return '<tr><th scope="row"><span class="fd-tb-name">' + esc(e.name) + '</span>' + (amt ? '<span class="fd-tb-amt">' + esc(amt) + '</span>' : '') + '</th>' + cells(e) + '</tr>';
                    }).join('');
            }).join('');
            html += sec('Wszystko, co zjedzone', '<div class="fd-tb-wrap"><table class="fd-tb"><thead><tr><th scope="col">Produkt</th><th scope="col">kcal</th>'
                + MACROS.map(function (m) { return '<th scope="col" title="' + m[1] + ' (g)"><i style="background:' + m[2] + '"></i>' + m[1].charAt(0) + '</th>'; }).join('') + '</tr></thead>'
                + '<tbody>' + rows + '</tbody><tfoot><tr><th scope="row">Razem</th>' + cells(t) + '</tr>'
                + (goal ? '<tr class="fd-tb-goal"><th scope="row">Cel</th>' + cells({ kcal: goal.kcal, protein: goal.protein || 0, fat: goal.fat || 0, carbs: goal.carbs || 0 }) + '</tr>' : '')
                + '</tfoot></table></div>'
                + '<p class="fd-mut" style="margin:8px 2px 0;font-size:11px">B – białko, T – tłuszcz, W – węglowodany, w gramach.</p>');
        }

        box.innerHTML = headHTML('Podsumowanie dnia', false)
            + '<div class="fd-scroll" style="padding:4px 20px 16px">' + html + '</div>'
            + '<div class="fd-foot"><button type="button" class="fd-cta fd-press" data-stats>Statystyki z 7 / 30 / 90 dni'
            + I.chev.replace('<svg', '<svg width="16" height="16" style="transform:rotate(-90deg)"') + '</button></div>';
        bindHead(box);
        box.querySelector('[data-stats]').addEventListener('click', function () { sh.parent = 'day'; setStep('stats'); });
    }

    // ---------- statystyki (karta dnia → statystyki z 7 / 30 / 90 dni, scripts/food/stats.php) ----------
    var RANGES = [[7, '7 dni'], [30, '30 dni'], [90, '90 dni']];
    var WEEK = ['pn', 'wt', 'śr', 'cz', 'pt', 'sb', 'nd'];
    S.range = 30;

    function shortDate(s) { var d = s2d(s); return d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3); }
    function daysWord(n) { return n === 1 ? 'dzień' : 'dni'; }

    function renderStats(box) {
        var sh = S.sheet, st = sh.stats && sh.stats.range === S.range ? sh.stats : null;
        if (!st) {
            var range = S.range;
            getJSON('scripts/food/stats.php?range=' + range).then(function (res) {
                if (S.sheet !== sh || sh.step !== 'stats' || range !== S.range) return;
                if (res.status !== 'success') return fail(res);
                sh.stats = res;
                renderStats(box);
            }).catch(function () { fail({ status: 'error', message: 'Nie udało się wczytać statystyk' }); });
        }
        var goal = S.data.day.goal;
        box.innerHTML = headHTML('Statystyki', sh.parent === 'day')
            + '<div class="fd-scroll" style="padding:4px 20px 16px">'
            + '<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:4px;padding:4px;border-radius:18px;background:var(--fd-field)">'
            + RANGES.map(function (r) {
                var on = r[0] === S.range;
                return '<button type="button" class="fd-press" data-range="' + r[0] + '" aria-pressed="' + on + '" style="height:38px;border-radius:14px;font-size:13px;font-weight:500;'
                    + (on ? 'background:var(--hl-card);color:var(--hl-ink);box-shadow:0 1px 2px rgba(0,0,0,.08),0 6px 16px -10px rgba(0,0,0,.35)' : 'color:var(--hl-ink-2)') + '">' + r[1] + '</button>';
            }).join('') + '</div>'
            + (st ? statsBody(st) : '<div style="padding:12px 0">' + '<div class="fd-skel"></div>'.repeat(5) + '</div>')
            + '</div>'
            + '<div class="fd-foot"><button type="button" class="fd-cta fd-press" data-goal>' + I.target.replace('<svg', '<svg width="18" height="18"')
            + (goal ? 'Zmień cel · ' + fmt(goal.kcal) + ' kcal' : 'Ustaw dzienny cel') + '</button></div>';
        bindHead(box, function () { sh.parent = null; setStep('day', true); });
        box.querySelectorAll('[data-range]').forEach(function (b) {
            b.addEventListener('click', function () { S.range = +b.dataset.range; renderStats(box); });
        });
        box.querySelector('[data-goal]').addEventListener('click', function () { sh.from = 'stats'; setStep('goal'); });
        if (st) bindStatsChart(box, st);
    }

    function statsBody(st) {
        var sec = function (title, html) { return '<span class="fd-eyebrow" style="margin:22px 0 10px">' + title + '</span>' + html; };
        var since = st.start > st.from ? '<p class="fd-mut" style="margin:10px 2px 0;font-size:12px">Liczone od pierwszego wpisu: ' + shortDate(st.start) + '</p>' : '';
        if (!st.logged) {
            return since + '<div class="hl-empty" style="margin-top:28px"><div class="hl-empty-ico">' + I.target.replace('<svg', '<svg width="32" height="32"') + '</div>'
                + '<h3>Brak wpisów w tym okresie</h3><p>Dodaj posiłki, a tutaj zobaczysz średnie, serie i trafienie w cel.</p></div>';
        }
        var pct = st.tracked ? Math.round(st.logged / st.tracked * 100) : 0;
        var kpi = function (label, big, sub, bar) {
            return '<div class="fd-kpi"><span class="fd-kpi-label">' + label + '</span><b class="fd-num">' + big + '</b>'
                + (bar !== undefined ? '<span class="fd-kpi-bar"><span style="width:' + bar + '%"></span></span>' : '')
                + '<span class="fd-kpi-sub">' + sub + '</span></div>';
        };
        var html = since + '<div class="fd-kpis">'
            + kpi('Uzupełnione dni', pct + '%', st.logged + ' z ' + st.tracked + ' ' + daysWord(st.tracked), pct)
            + kpi('Średnio dziennie', fmt(st.avg.kcal) + '<small> kcal</small>', st.avg_goal ? Math.round(st.avg.kcal / st.avg_goal.kcal * 100) + '% celu' : 'w dni z wpisami')
            + kpi('W celu', st.hit.ok + '<small> ' + daysWord(st.hit.ok) + '</small>', st.with_goal > st.hit.pending ? 'z ' + (st.with_goal - st.hit.pending) + ' · ±10% celu' : (st.with_goal ? 'dziś jeszcze trwa' : 'ustaw cel, by liczyć'))
            + kpi('Seria', st.streak + '<small> ' + daysWord(st.streak) + '</small>', 'najdłuższa: ' + st.best + ' ' + daysWord(st.best))
            + '</div>';

        var sg = st.sugars;
        if (sg && sg.n_known) {
            html += sec('Cukry – średnio dziennie', sugarHTML({ grams: sg.avg, kcal: st.avg.kcal, carbs: st.avg.carbs, goalKcal: st.avg_goal ? st.avg_goal.kcal : 0,
                top: sg.top, known: sg.n_known, n: sg.n, perDay: true }));
        }

        // wykres dni: słupek = kcal, kreska = cel obowiązujący tego dnia
        var max = Math.max.apply(null, st.days.map(function (d) { return Math.max(d.kcal, d.goal || 0); })) * 1.08 || 1;
        var cols = st.days.map(function (d, i) {
            var over = d.goal && d.kcal > d.goal * 1.1, under = d.goal && d.kcal < d.goal * 0.9;
            return '<div class="fd-col' + (d.date === st.today ? ' is-today' : '') + (d.date < st.start ? ' is-before' : '') + '" data-i="' + i + '">'
                + (d.n ? '<span class="fd-col-bar" style="height:' + (d.kcal / max * 100).toFixed(1) + '%;background:' + (over ? '#f59e0b' : 'var(--fd-k)') + (under ? ';opacity:.5' : '') + '"></span>' : '<span class="fd-col-empty"></span>')
                + (d.goal ? '<span class="fd-col-goal" style="bottom:' + (d.goal / max * 100).toFixed(1) + '%"></span>' : '') + '</div>';
        }).join('');
        var mid = st.days[Math.floor(st.days.length / 2)].date;
        html += sec('Kalorie dzień po dniu', '<div class="fd-chart-read" data-read></div>'
            + '<div class="fd-chart' + (st.days.length > 40 ? ' is-dense' : '') + '" data-chart>' + cols + '</div>'
            + '<div class="fd-chart-axis fd-mut"><span>' + shortDate(st.from) + '</span><span>' + shortDate(mid) + '</span><span>dziś</span></div>'
            + '<div class="fd-legend fd-mut"><span><i style="background:var(--fd-k)"></i>w celu</span><span><i style="background:var(--fd-k);opacity:.5"></i>poniżej</span>'
            + '<span><i style="background:#f59e0b"></i>powyżej</span><span><i class="is-line"></i>cel</span></div>');

        if (st.with_goal) {
            var gaps = Math.max(0, st.tracked - st.logged);
            var parts = [['W celu (90–110%)', 'var(--fd-k)', st.hit.ok], ['Poniżej celu', 'color-mix(in srgb, var(--fd-k) 50%, transparent)', st.hit.under],
                ['Powyżej celu', '#f59e0b', st.hit.over], ['Dziś – dzień jeszcze trwa', 'color-mix(in srgb, var(--fd-k) 22%, transparent)', st.hit.pending], ['Bez wpisów', 'var(--fd-track)', gaps]];
            if (st.logged > st.with_goal) parts.push(['Bez ustawionego celu', 'var(--fd-mut)', st.logged - st.with_goal]);
            parts = parts.filter(function (p) { return p[2]; });
            var bal = '';
            if (st.balance_days) {
                var b = st.balance;
                bal = '<div class="fd-box" style="margin-top:12px;display:flex;gap:12px;align-items:center">'
                    + '<span class="fd-num" style="font-size:22px;font-weight:600;letter-spacing:-.02em;white-space:nowrap;color:' + (b > 0 ? '#d97706' : 'var(--hl-ink)') + '">' + (b > 0 ? '+' : b < 0 ? '−' : '') + fmt(Math.abs(b)) + '</span>'
                    + '<span class="fd-mut" style="font-size:12px;line-height:1.45">kcal ' + (b > 0 ? 'ponad cel' : 'poniżej celu') + ' łącznie w ' + st.balance_days + ' ' + daysWord(st.balance_days)
                    + ' z wpisami (bez dziś)' + (Math.abs(b) >= 770 ? ' · to ok. ' + dec(Math.abs(b) / 7700) + ' kg' : '') + '</span></div>';
            }
            html += sec('Trafienie w cel', '<div class="fd-stack">' + parts.map(function (p) {
                return '<span style="flex-grow:' + p[2] + ';background:' + p[1] + '" title="' + p[0] + ': ' + p[2] + '"></span>';
            }).join('') + '</div><div class="fd-list" style="margin-top:10px">' + parts.map(function (p) {
                return '<div class="fd-li"><i class="fd-dot" style="background:' + p[1] + '"></i><span style="flex:1">' + p[0] + '</span><b class="fd-num">' + p[2] + ' ' + daysWord(p[2]) + '</b></div>';
            }).join('') + '</div>' + bal);
        }

        var en = { protein: st.avg.protein * 4, fat: st.avg.fat * 9, carbs: st.avg.carbs * 4 }, enSum = en.protein + en.fat + en.carbs;
        html += sec('Średnio makro', '<div class="fd-bars">' + barsHTML(st.avg, null, st.avg_goal, MACROS, false) + '</div>'
            + (enSum ? '<div class="fd-stack" style="margin-top:16px">' + MACROS.map(function (m) { return '<span style="flex-grow:' + en[m[0]].toFixed(0) + ';background:' + m[2] + '"></span>'; }).join('') + '</div>'
                + '<p class="fd-mut" style="margin:8px 0 0;font-size:12px">Kalorie z: ' + MACROS.map(function (m) { return m[1].toLowerCase() + ' ' + Math.round(en[m[0]] / enSum * 100) + '%'; }).join(' · ') + '</p>' : ''));

        if (st.meals.length) {
            var mTot = st.meals.reduce(function (a, m) { return a + m.kcal; }, 0) || 1;
            html += sec('Posiłki – średnio dziennie', '<div class="fd-list">' + st.meals.map(function (m) {
                var share = m.kcal / mTot * 100;
                return '<div class="fd-li" style="flex-wrap:wrap"><span style="flex:1">' + MEALS[m.meal] + '</span><b class="fd-num">' + fmt(m.kcal) + ' kcal</b>'
                    + '<span class="fd-mut fd-num" style="width:38px;text-align:right;font-size:12px">' + Math.round(share) + '%</span>'
                    + '<span class="fd-bar" style="flex-basis:100%;height:6px;margin-top:6px"><span style="left:0;width:' + share.toFixed(1) + '%;background:var(--fd-k)"></span></span></div>';
            }).join('') + '</div>');
        }

        var wMax = Math.max.apply(null, st.week.map(function (v) { return v || 0; })) || 1;
        html += sec('Dni tygodnia – średnio', '<div class="fd-week">' + st.week.map(function (v, i) {
            return '<div title="' + WEEK[i] + (v === null ? ': brak wpisów' : ': ' + fmt(v) + ' kcal') + '"><span class="fd-week-col"><span style="height:' + ((v || 0) / wMax * 100).toFixed(1) + '%' + (v === wMax ? ';background:var(--fd-k)' : '') + '"></span></span>'
                + '<span class="fd-week-val fd-num">' + (v === null ? '–' : fmt(v)) + '</span><span class="fd-week-name' + (i === 6 ? ' is-sun' : '') + '">' + WEEK[i] + '</span></div>';
        }).join('') + '</div>');

        if (st.top.length) {
            html += sec('Najczęściej jedzone', '<div class="fd-list">' + st.top.map(function (p, i) {
                return '<div class="fd-li"><span class="fd-top-n fd-num">' + (i + 1) + '</span><span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(p.name) + '</span>'
                    + '<span class="fd-mut fd-num" style="font-size:12px;white-space:nowrap">' + p.n + '× · ' + fmt(p.kcal) + ' kcal</span></div>';
            }).join('') + '</div>');
        }
        return html + '<p class="fd-mut" style="margin:18px 2px 0;font-size:11px">' + fmt(st.entries) + ' wpisów w ' + st.logged + ' ' + daysWord(st.logged) + '. Średnie liczone z dni z wpisami.</p>';
    }

    // najechanie / dotknięcie słupka pokazuje dzień; „Otwórz” przenosi do niego dziennik
    function bindStatsChart(box, st) {
        var chart = box.querySelector('[data-chart]'), read = box.querySelector('[data-read]');
        if (!chart) return;
        var sel = null;
        var show = function (i) {
            chart.querySelectorAll('.is-hot').forEach(function (c) { c.classList.remove('is-hot'); });
            if (i === null) {
                read.innerHTML = '<span>Średnio <b class="fd-num">' + fmt(st.avg.kcal) + ' kcal</b> w dni z wpisami</span>';
                return;
            }
            var d = st.days[i], dt = s2d(d.date);
            chart.children[i].classList.add('is-hot');
            read.innerHTML = '<span>' + DAY_NAMES[dt.getDay()] + ', ' + shortDate(d.date) + ' · '
                + (d.n ? '<b class="fd-num">' + fmt(d.kcal) + ' kcal</b>' + (d.goal ? ' · ' + Math.round(d.kcal / d.goal * 100) + '% celu' : '') : 'brak wpisów') + '</span>'
                + '<button type="button" class="fd-chart-open fd-press" data-open-day="' + d.date + '">Otwórz' + I.chev.replace('<svg', '<svg width="12" height="12" style="transform:rotate(-90deg)"') + '</button>';
        };
        show(null);
        chart.addEventListener('pointerover', function (ev) {
            var c = ev.target.closest('[data-i]');
            if (c && ev.pointerType === 'mouse') show(+c.dataset.i);
        });
        chart.addEventListener('pointerleave', function (ev) { if (ev.pointerType === 'mouse') show(sel); });
        chart.addEventListener('click', function (ev) {
            var c = ev.target.closest('[data-i]');
            if (!c) return;
            sel = sel === +c.dataset.i ? null : +c.dataset.i;
            show(sel);
        });
        read.addEventListener('click', function (ev) {
            var b = ev.target.closest('[data-open-day]');
            if (!b) return;
            closeSheet(true);
            F.go(b.dataset.openDay);
        });
    }
})();
