// HomeList – Jedzenie: ekran dnia + arkusz (wyszukiwarka, porcja, szybkie kcal, cel).
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
        target: svg('M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 1.8)
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
        if (pending === 'goal') setTimeout(function () { openSheet({ step: 'goal' }); }, 300);
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
        // cała karta otwiera podsumowanie; przycisk celu ma własną akcję (closest() łapie go pierwszy)
        box.innerHTML = '<div class="fd-hero-wrap" data-act="stats" role="button" tabindex="0" aria-label="Podsumowanie i statystyki"><div class="fd-hero">'
            + '<div class="fd-ring"><svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" style="stroke:var(--fd-track)"/>' + ring + '</svg>'
            + '<div class="fd-ring-center">' + center + '</div></div>'
            + '<div class="fd-bars">' + barsHTML(pv.base, pv.ghost, goal, MACROS, false)
            + (goal ? '<p class="text-xs fd-mut" style="margin:2px 0 0">Zjedzone <b class="fd-num" style="color:var(--hl-ink);font-weight:600">' + fmt(eaten) + '</b> z ' + fmt(goal.kcal) + ' kcal</p>' : '')
            + '</div></div>'
            + '<div class="fd-hero-foot"><span class="fd-hero-more">Podsumowanie' + I.chev.replace('<svg', '<svg width="14" height="14" style="transform:rotate(-90deg)"') + '</span>'
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
                    + '<span class="fd-num" style="font-size:14px;font-weight:600">' + fmt(e.kcal) + '</span></div>';
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
        }).join('');
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
        void box.offsetWidth; // restart animacji wejścia
        var step = S.sheet.step;
        if (step === 'search') renderSearch(box);
        else if (step === 'portion') renderPortion(box);
        else if (step === 'quick') renderQuick(box);
        else if (step === 'goal') renderGoal(box);
        else if (step === 'stats') renderStats(box);
        else if (step === 'scan') renderScan(box);
        else if (step === 'own') renderOwn(box);
        else if (step === 'dish') renderDish(box);
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
    function renderSearch(box) {
        var sh = S.sheet;
        box.innerHTML = headHTML('Dodaj ' + MEAL_TO[sh.meal], false)
            + '<div style="padding:8px 20px 0;flex:none">' + mealChips()
            + '<div style="display:flex;gap:8px;margin-top:12px"><label class="fd-field" style="flex:1">' + I.search + '<input type="search" id="fd_q" placeholder="Szukaj: skyr, jajko, ryż…" aria-label="Szukaj produktu" autocomplete="off" enterkeyhint="search" value="' + esc(sh.q || '') + '"></label>'
            + '<button type="button" class="fd-round fd-press" data-scanbtn style="width:46px;height:46px;border-radius:16px" aria-label="Skanuj kod kreskowy">' + I.scan + '</button></div></div>'
            + '<div class="fd-scroll" id="fd_results" style="padding:12px 14px 24px"></div>';
        bindHead(box);
        bindMealChips(box, function () { box.querySelector('h2').textContent = 'Dodaj ' + MEAL_TO[sh.meal]; if (!sh.q) runSearch(); });
        box.querySelector('[data-scanbtn]').addEventListener('click', function () { setStep('scan'); });
        var input = el('fd_q');
        input.addEventListener('input', function () {
            sh.q = input.value;
            sh.off = null;
            clearTimeout(S.qTimer);
            S.qTimer = setTimeout(runSearch, 220);
        });
        if (sh.results) drawResults(); else runSearch();
        if (window.matchMedia('(hover: hover)').matches) setTimeout(function () { input.focus(); }, 350);
    }

    function runSearch() {
        var sh = S.sheet;
        if (!sh || sh.step !== 'search') return;
        var q = (sh.q || '').trim(), seq = sh.seq = (sh.seq || 0) + 1;
        if (!sh.results) el('fd_results').innerHTML = '<div class="fd-skel"></div>'.repeat(3);
        getJSON('scripts/food/search.php?meal=' + sh.meal + '&q=' + encodeURIComponent(q)).then(function (res) {
            if (!S.sheet || sh.seq !== seq || sh.step !== 'search') return;
            sh.results = res.groups || [];
            sh.rq = q;
            drawResults();
        });
    }

    function rowHTML(p, gi, ii) {
        var sub = [p.brand, p.quantity].filter(Boolean).join(' · ');
        var d = p.default, du = d.label ? qty(d.grams / d.unit_g) + ' × ' + d.label : dec(d.grams) + ' ' + (p.per_ml ? 'ml' : 'g');
        return '<div style="display:flex;align-items:center;gap:4px">'
            + '<button type="button" class="fd-row" style="flex:1;min-width:0" data-open="' + gi + ':' + ii + '">'
            + '<div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(p.name) + '</div>'
            + '<div class="text-xs fd-mut" style="display:flex;align-items:center;gap:6px;margin-top:2px;white-space:nowrap;overflow:hidden">'
            + '<span class="fd-tag ' + p.source + '">' + (p.status === 'pending' ? 'Czeka na akceptację' : TAGS[p.source]) + '</span>' + esc(sub) + '</div></div>'
            + '<div style="text-align:right;flex:none"><div class="fd-num" style="font-size:14px;font-weight:600">' + fmt(p.kcal) + '</div><div class="fd-mut" style="font-size:11px">kcal / 100 ' + (p.per_ml ? 'ml' : 'g') + '</div></div></button>'
            + '<button type="button" class="fd-plus fd-press" data-quick="' + gi + ':' + ii + '" title="Dodaj od razu: ' + esc(du) + '" aria-label="Dodaj od razu: ' + esc(p.name + ', ' + du) + '">' + I.plus + '</button></div>';
    }

    function drawResults() {
        var sh = S.sheet, box = el('fd_results');
        if (!box) return;
        // produkty z OFF zapisane przy wcześniejszych wyszukiwaniach są już w wynikach lokalnych – bez duplikatów
        var seen = {};
        (sh.results || []).forEach(function (g) { g.items.forEach(function (p) { seen[p.id] = 1; }); });
        var offGroups = (sh.off && sh.off.groups ? sh.off.groups : []).map(function (g) {
            return Object.assign({}, g, { items: g.items.filter(function (p) { return !seen[p.id]; }) });
        }).filter(function (g) { return g.items.length; });
        var groups = (sh.results || []).concat(offGroups);
        var q = (sh.rq || '').trim();
        var html = groups.map(function (g, gi) {
            return '<div style="margin-bottom:10px"><span class="fd-eyebrow" style="padding:6px 8px">' + esc(g.title) + '</span>'
                + g.items.map(function (p, ii) { return rowHTML(p, gi, ii); }).join('') + '</div>';
        }).join('');
        if (!groups.length && !q) {
            html = '<div class="hl-empty" style="padding:28px 0"><div class="hl-empty-ico">' + I.search + '</div><h3>Czego szukasz?</h3><p>Tu pojawią się produkty, które jadasz najczęściej.</p></div>';
        } else if (!groups.length) {
            html = '<p class="fd-mut" style="margin:8px;font-size:13px">W bazie HomeList nie ma „' + esc(q) + '”.</p>';
        }
        if (q && !sh.off) {
            html += '<button type="button" class="fd-row fd-press" data-off style="margin-top:2px;box-shadow:inset 0 0 0 1px var(--fd-track)">'
                + '<span class="fd-row-ico" style="background:rgba(245,158,11,.14);color:#d97706">' + I.search + '</span>'
                + '<div style="flex:1"><div style="font-size:14px;font-weight:500">Szukaj dalej w Open Food Facts</div><div class="text-xs fd-mut">Otwarta baza produktów z kodami kreskowymi</div></div></button>';
        } else if (sh.off === 'loading') {
            html += '<div class="fd-skel"></div><div class="fd-skel"></div>';
        } else if (sh.off && sh.off.message) {
            html += '<p class="fd-mut" style="margin:8px;font-size:13px">' + esc(sh.off.message) + '</p>';
        } else if (sh.off && !(sh.off.groups || []).length) {
            html += '<p class="fd-mut" style="margin:8px;font-size:13px">Open Food Facts też tego nie zna.</p>';
        }
        if (q) {
            html += '<button type="button" class="fd-row fd-press" data-own style="margin-top:6px">'
                + '<span class="fd-row-ico" style="background:rgba(56,189,248,.14);color:#0284c7">' + I.plus + '</span>'
                + '<div style="flex:1"><div style="font-size:14px;font-weight:500">Nie ma? Dodaj własny produkt</div><div class="text-xs fd-mut">Przepisz wartości z etykiety – zajmie to chwilę</div></div></button>';
        }
        html += '<button type="button" class="fd-row fd-press" data-scan style="margin-top:6px">'
            + '<span class="fd-row-ico" style="background:var(--hl-chip);color:var(--hl-ink)">' + I.scan + '</span>'
            + '<div style="flex:1"><div style="font-size:14px;font-weight:500">Skanuj kod kreskowy</div><div class="text-xs fd-mut">Najszybciej dla produktów w opakowaniu</div></div></button>';
        html += '<button type="button" class="fd-row fd-press" data-dish style="margin-top:6px">'
            + '<span class="fd-row-ico" style="background:rgba(99,102,241,.14);color:#4f46e5">' + I.pot + '</span>'
            + '<div style="flex:1"><div style="font-size:14px;font-weight:500">Nowe danie domowe</div><div class="text-xs fd-mut">Z kilku składników – zjedzą je też domownicy</div></div></button>';
        html += '<button type="button" class="fd-row fd-press" data-quickkcal style="margin-top:6px">'
            + '<span class="fd-row-ico" style="background:rgba(74,222,128,.14);color:#16a34a">' + I.bolt + '</span>'
            + '<div style="flex:1"><div style="font-size:14px;font-weight:500">Dodaj same kalorie</div><div class="text-xs fd-mut">Gdy znasz tylko wartość, np. z menu restauracji</div></div></button>';
        box.innerHTML = html;

        var pick = function (attr) {
            var a = attr.split(':');
            return groups[+a[0]].items[+a[1]];
        };
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
        var off = box.querySelector('[data-off]');
        if (off) off.addEventListener('click', function () {
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
        var own = box.querySelector('[data-own]');
        if (own) own.addEventListener('click', function () { S.sheet.own = { name: q.charAt(0).toUpperCase() + q.slice(1) }; setStep('own'); });
        box.querySelector('[data-scan]').addEventListener('click', function () { setStep('scan'); });
        box.querySelector('[data-dish]').addEventListener('click', function () { S.sheet.dish = { name: '', items: [], unit_label: 'talerz', unit_g: 350 }; setStep('dish'); });
        box.querySelector('[data-quickkcal]').addEventListener('click', function () {
            S.sheet.quick = { name: q ? q.charAt(0).toUpperCase() + q.slice(1) : '' };
            setStep('quick');
        });
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

    function renderPortion(box) {
        var sh = S.sheet, pp = sh.p, p = pp.product, u = p.per_ml ? 'ml' : 'g';
        var sub = [p.brand, p.quantity].filter(Boolean).join(' · ');
        box.innerHTML = '<div class="fd-scroll" style="padding:4px 20px 12px">'
            + '<div style="display:flex;align-items:center;gap:4px;margin-left:-8px">'
            + '<button type="button" class="fd-icon-btn fd-press" data-back aria-label="' + (pp.entry ? 'Zamknij' : 'Wróć') + '">' + (pp.entry ? I.x : I.back) + '</button>'
            + '<div style="flex:1;min-width:0"><h2 class="hl-popup-title" style="padding:0;font-size:17px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + esc(p.name) + '</h2>'
            + '<div class="text-xs fd-mut" style="display:flex;align-items:center;gap:6px;white-space:nowrap;overflow:hidden"><span class="fd-tag ' + p.source + '">' + (p.status === 'pending' ? 'Czeka na akceptację' : TAGS[p.source] || '') + '</span>' + esc(sub) + '</div></div></div>'
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
            setStep('search', true);
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
        bindEditDish();
        updatePortion();
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
                    return '<tr><td>' + esc(it.name) + ' <span class="fd-mut">' + dec(it.grams) + ' g</span></td><td class="fd-num">' + fmt(it.kcal) + ' kcal</td></tr>';
                }).join('') + '</tbody></table>'
                + (p.dish.cooked_g ? '<p class="fd-mut" style="margin:6px 0 0;font-size:12px">Waga po ugotowaniu: ' + fmt(p.dish.cooked_g) + ' g</p>' : '');
        }
        if (p.source === 'off') body += '<p class="fd-mut" style="margin:14px 0 0;font-size:11px">Dane: Open Food Facts (ODbL)' + (p.code ? ' · kod ' + esc(p.code) : '') + '</p>';
        if (p.source === 'user' && p.owner) body += '<p class="fd-mut" style="margin:14px 0 0;font-size:11px">Dodał(a): ' + esc(p.owner) + (p.status === 'pending' ? ' · czeka na akceptację' : '') + '</p>';

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
        box.innerHTML = headHTML('Skanuj kod kreskowy', true)
            + '<div class="fd-scroll" style="padding:8px 20px 12px">'
            + '<div class="fd-viewfinder"><video id="fd_video" playsinline muted autoplay></video>'
            + '<span class="fd-corner tl"></span><span class="fd-corner tr"></span><span class="fd-corner bl"></span><span class="fd-corner br"></span>'
            + '<span class="fd-scanline"></span><p id="fd_scan_msg" class="fd-scan-msg">Włączam aparat…</p></div>'
            + '<p class="fd-mut" style="margin:14px 4px 0;font-size:13px;line-height:1.5">Najpierw szukamy w bazie HomeList, potem w Open Food Facts. Gdy kodu nie ma nigdzie, otworzy się formularz z wpisanym już kodem.</p>'
            + '<label class="hl-label" for="fd_code" style="margin-top:16px">Albo wpisz kod spod paska</label>'
            + '<div style="display:flex;gap:8px"><div class="fd-field" style="flex:1"><input id="fd_code" type="text" inputmode="numeric" placeholder="np. 5901234123457" autocomplete="off"></div>'
            + '<button type="button" class="hl-btn hl-btn-primary" data-code style="height:46px">Szukaj</button></div></div>';
        bindHead(box, function () { setStep('search', true); });
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
            if (res.status === 'success' && res.found) return openPortion(res.product, null, false, 'barcode');
            if (res.status !== 'success' && res.status !== 'error') { fail(res); return setStep('scan', true); }
            sh.own = { code: code, name: res.name || '', notFound: true, offError: res.status === 'error' };
            setStep('own');
        }).catch(function () { sh.own = { code: code, notFound: true, offError: true }; setStep('own'); });
    }

    // ---------- własny produkt ----------
    function renderOwn(box) {
        var sh = S.sheet, o = sh.own || (sh.own = {});
        var u = o.per_ml ? 'ml' : 'g';
        var num = function (k, label, color, opt) {
            return '<label class="fd-field" style="padding-right:12px">' + (color ? '<span class="fd-dot" style="background:' + color + '"></span>' : '')
                + '<input type="text" inputmode="decimal" data-o="' + k + '" placeholder="' + (opt ? '–' : '0') + '" aria-label="' + label + '" value="' + esc(o[k] !== undefined ? o[k] : '') + '">'
                + '<span class="text-xs fd-mut" style="white-space:nowrap">' + label + '</span></label>';
        };
        box.innerHTML = headHTML('Własny produkt', true)
            + '<div class="fd-scroll" style="padding:8px 20px 12px">'
            + (o.notFound ? '<p class="fd-pill" style="margin:0 0 12px;border-radius:14px;padding:8px 12px;display:flex">' + (o.offError ? 'Open Food Facts nie odpowiada – dodaj produkt ręcznie' : 'Nie znaleźliśmy kodu ' + esc(o.code) + (o.name ? ' („' + esc(o.name) + '” bez wartości odżywczych)' : '')) + '</p>' : '')
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
            + '<span class="fd-eyebrow" style="margin:18px 0 8px">Porcja (opcjonalnie)</span>'
            + '<div style="display:flex;gap:8px"><div class="fd-field" style="flex:1"><input data-o="unit_label" type="text" placeholder="np. plaster, sztuka" aria-label="Nazwa porcji" value="' + esc(o.unit_label || '') + '"></div>'
            + '<label class="fd-field" style="width:110px;padding-right:12px"><input data-o="unit_g" type="text" inputmode="decimal" placeholder="20" aria-label="Waga porcji" value="' + esc(o.unit_g || '') + '"><span class="text-xs fd-mut">' + u + '</span></label></div>'
            + '<p class="fd-pill" style="margin:16px 0 0;border-radius:14px;padding:10px 12px;display:flex;line-height:1.5">Od razu widzisz go Ty i domownicy. Dla pozostałych pojawi się po akceptacji administratora.</p>'
            + '</div><div class="fd-foot"><button type="button" class="fd-cta fd-press" data-commit>Zapisz i wybierz porcję</button></div>';
        bindHead(box, function () { setStep(o.notFound ? 'scan' : 'search', true); });
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
        box.querySelectorAll('[data-ml]').forEach(function (b) { b.addEventListener('click', function () { o.per_ml = b.dataset.ml === '1'; renderOwn(box); }); });
        check();
        if (window.matchMedia('(hover: hover)').matches) setTimeout(function () { (o.name ? box.querySelector('[data-o=kcal]') : el('fd_oname')).focus(); }, 350);
        box.querySelector('[data-commit]').addEventListener('click', function (ev) {
            var btn = ev.currentTarget;
            btn.disabled = true;
            var data = {};
            ['name', 'brand', 'code', 'kcal', 'protein', 'fat', 'carbs', 'sat_fat', 'sugars', 'fiber', 'salt', 'unit_label', 'unit_g'].forEach(function (k) { data[k] = String(o[k] || '').trim().replace(',', '.'); });
            data.per_ml = o.per_ml ? 1 : 0;
            post('scripts/food/product_save.php', data).then(function (res) {
                if (!S.sheet || S.sheet !== sh) return;
                // ten kod już jest w bazie – zamiast duplikatu od razu ten produkt
                if (res.product && res.status === 'warning') { toast(res.message); return openPortion(res.product, null, false, o.notFound ? 'barcode' : undefined); }
                if (res.status !== 'success') { btn.disabled = false; return fail(res); }
                sh.own = null;
                openPortion(res.product, null, false, o.notFound ? 'barcode' : undefined);
            });
        });
    }

    // ---------- danie domowe ----------
    function bindEditDish() {
        var b = el('fd_step').querySelector('[data-editdish]');
        if (!b || b.dataset.bound) return;
        b.dataset.bound = '1';
        b.addEventListener('click', function () {
            var p = S.sheet.p.product, d = p.dish, unit = (p.units || []).filter(function (u) { return !u.base; })[0];
            S.sheet.dish = {
                id: d.id, name: p.name, cooked_g: d.cooked_g || '', unit_label: unit ? unit.label : '', unit_g: unit ? unit.grams : '',
                items: d.items.map(function (it) { return { id: it.product_id, name: it.name, grams: it.grams, kcal100: it.grams ? it.kcal / it.grams * 100 : 0 }; })
            };
            setStep('dish');
        });
    }

    function renderDish(box) {
        var sh = S.sheet, d = sh.dish;
        box.innerHTML = headHTML(d.id ? 'Edytuj danie' : 'Nowe danie domowe', true)
            + '<div class="fd-scroll" id="fd_dish_scroll" style="padding:8px 20px 12px">'
            + '<label class="hl-label" for="fd_dname">Nazwa</label>'
            + '<div class="fd-field"><input id="fd_dname" type="text" placeholder="np. Zupa pomidorowa" value="' + esc(d.name) + '"></div>'
            + '<span class="fd-eyebrow" style="margin:18px 0 6px">Składniki (na cały garnek)</span>'
            + '<div id="fd_ditems"></div>'
            + '<div style="position:relative;margin-top:8px"><label class="fd-field">' + I.search + '<input id="fd_dq" type="search" placeholder="Dodaj składnik: ryż, marchew…" autocomplete="off"></label>'
            + '<div id="fd_dres" class="fd-dres"></div></div>'
            + '<label class="hl-label" for="fd_dcooked" style="margin-top:18px">Waga gotowego dania (opcjonalnie)</label>'
            + '<label class="fd-field" style="padding-right:12px"><input id="fd_dcooked" type="text" inputmode="decimal" placeholder="suma składników" value="' + esc(d.cooked_g || '') + '"><span class="text-xs fd-mut">g</span></label>'
            + '<p class="hl-hint">Zważ gotowe danie bez garnka – woda odparowuje albo dochodzi, więc 100 g zupy to nie 100 g składników.</p>'
            + '<span class="hl-label" style="margin-top:14px">Porcja</span>'
            + '<div style="display:flex;gap:8px"><div class="fd-field" style="flex:1"><input id="fd_dul" type="text" placeholder="talerz" value="' + esc(d.unit_label || '') + '" aria-label="Nazwa porcji"></div>'
            + '<label class="fd-field" style="width:110px;padding-right:12px"><input id="fd_dug" type="text" inputmode="decimal" placeholder="350" value="' + esc(d.unit_g || '') + '" aria-label="Waga porcji"><span class="text-xs fd-mut">g</span></label></div>'
            + '<div id="fd_dsum" class="fd-box" style="margin-top:14px"></div>'
            + '<p class="fd-pill" style="margin:14px 0 0;border-radius:14px;padding:10px 12px;display:flex;line-height:1.5">Danie zobaczą i dodadzą do swojego dziennika domownicy (osoby ze wspólnych list).</p>'
            + '</div><div class="fd-foot">' + (d.id ? '<button type="button" class="fd-danger fd-press" data-ddel aria-label="Usuń danie">' + I.trash + '</button>' : '')
            + '<button type="button" class="fd-cta fd-press" data-commit>' + (d.id ? 'Zapisz danie' : 'Zapisz i dodaj porcję') + '</button></div>';
        bindHead(box, function () { if (d.id) setStep('portion', true); else setStep('search', true); });
        el('fd_dname').addEventListener('input', function () { d.name = this.value; dishSum(); });
        el('fd_dcooked').addEventListener('input', function () { d.cooked_g = this.value; dishSum(); });
        el('fd_dul').addEventListener('input', function () { d.unit_label = this.value; });
        el('fd_dug').addEventListener('input', function () { d.unit_g = this.value; dishSum(); });
        var q = el('fd_dq'), res = el('fd_dres');
        q.addEventListener('input', function () {
            clearTimeout(S.dTimer);
            var term = q.value.trim();
            if (!term) { res.innerHTML = ''; return; }
            S.dTimer = setTimeout(function () {
                getJSON('scripts/food/search.php?q=' + encodeURIComponent(term)).then(function (r) {
                    if (q.value.trim() !== term) return;
                    var items = (r.groups || []).reduce(function (a, g) { return a.concat(g.items); }, []).filter(function (p) { return p.source !== 'dish'; }).slice(0, 8);
                    res.innerHTML = items.length ? items.map(function (p, i) {
                        return '<button type="button" class="fd-row" data-pick="' + i + '"><div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(p.name) + '</div>'
                            + '<div class="text-xs fd-mut">' + esc([p.brand, fmt(p.kcal) + ' kcal / 100 g'].filter(Boolean).join(' · ')) + '</div></div>' + I.plus.replace('<svg', '<svg width="18" height="18"') + '</button>';
                    }).join('') : '<p class="fd-mut" style="margin:10px;font-size:13px">Brak w bazie – dodaj go najpierw jako własny produkt.</p>';
                    res.querySelectorAll('[data-pick]').forEach(function (b) {
                        b.addEventListener('click', function () {
                            var p = items[+b.dataset.pick];
                            d.items.push({ id: p.id, name: p.name, grams: p.default && !p.default.last ? p.default.grams : 100, kcal100: p.kcal, fresh: true });
                            q.value = '';
                            res.innerHTML = '';
                            dishItems();
                            q.focus();
                        });
                    });
                });
            }, 220);
        });
        box.querySelector('[data-commit]').addEventListener('click', function (ev) {
            var btn = ev.currentTarget;
            btn.disabled = true;
            post('scripts/food/dish_save.php', {
                id: d.id || '', name: d.name.trim(), cooked_g: String(d.cooked_g || '').replace(',', '.'),
                items: JSON.stringify(d.items.map(function (it) { return { product_id: it.id, grams: it.grams }; })),
                units: JSON.stringify(d.unit_label && d.unit_g ? [{ label: d.unit_label, grams: String(d.unit_g).replace(',', '.') }] : [])
            }).then(function (r) {
                if (!S.sheet || S.sheet !== sh) return;
                if (r.status !== 'success') { btn.disabled = false; return fail(r); }
                sh.dish = null;
                if (sh.p && sh.p.entry) { toast(r.message); return closeSheet(); }
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
                setStep('search', true);
            });
        });
        dishItems();
    }

    function dishItems() {
        var d = S.sheet.dish, box = el('fd_ditems');
        box.innerHTML = d.items.length ? d.items.map(function (it, i) {
            return '<div class="fd-ditem' + (it.fresh ? ' is-fresh' : '') + '"><div style="flex:1;min-width:0"><div class="fd-entry-name">' + esc(it.name) + '</div>'
                + '<div class="text-xs fd-mut fd-num" data-ikcal="' + i + '">' + fmt(it.kcal100 * it.grams / 100) + ' kcal</div></div>'
                + '<label class="fd-field" style="width:96px;min-height:40px;padding:0 10px"><input type="text" inputmode="decimal" data-ig="' + i + '" value="' + dec(it.grams) + '" aria-label="Gramy: ' + esc(it.name) + '"><span class="text-xs fd-mut">g</span></label>'
                + '<button type="button" class="fd-icon-btn fd-press fd-mut" data-irm="' + i + '" aria-label="Usuń składnik">' + I.x.replace('<svg', '<svg width="18" height="18"') + '</button></div>';
        }).join('') : '<p class="fd-mut" style="margin:4px;font-size:13px">Wyszukaj składniki poniżej i podaj, ile ich poszło do garnka.</p>';
        d.items.forEach(function (it) { delete it.fresh; });
        box.querySelectorAll('[data-ig]').forEach(function (i) {
            i.addEventListener('input', function () {
                var it = d.items[+i.dataset.ig], v = parseFloat(i.value.replace(',', '.'));
                it.grams = isNaN(v) ? 0 : v;
                box.querySelector('[data-ikcal="' + i.dataset.ig + '"]').textContent = fmt(it.kcal100 * it.grams / 100) + ' kcal';
                dishSum();
            });
        });
        box.querySelectorAll('[data-irm]').forEach(function (b) { b.addEventListener('click', function () { d.items.splice(+b.dataset.irm, 1); dishItems(); }); });
        dishSum();
    }

    function dishSum() {
        var d = S.sheet && S.sheet.dish, box = el('fd_dsum');
        if (!d || !box) return;
        var raw = 0, kcal = 0;
        d.items.forEach(function (it) { raw += it.grams; kcal += it.kcal100 * it.grams / 100; });
        var cooked = parseFloat(String(d.cooked_g || '').replace(',', '.')), weight = cooked > 0 ? cooked : raw;
        var per = weight ? kcal / weight * 100 : 0, ug = parseFloat(String(d.unit_g || '').replace(',', '.'));
        box.innerHTML = '<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;text-align:center">'
            + '<div><b class="fd-num" style="font-size:18px">' + fmt(kcal) + '</b><div class="fd-mut" style="font-size:11px">kcal w garnku</div></div>'
            + '<div><b class="fd-num" style="font-size:18px">' + fmt(per) + '</b><div class="fd-mut" style="font-size:11px">kcal / 100 g</div></div>'
            + '<div><b class="fd-num" style="font-size:18px">' + (ug > 0 ? fmt(per * ug / 100) : '–') + '</b><div class="fd-mut" style="font-size:11px">kcal / ' + esc(d.unit_label || 'porcja') + '</div></div></div>'
            + (cooked > 0 && raw ? '<p class="fd-mut" style="margin:8px 0 0;text-align:center;font-size:12px">Składniki ' + fmt(raw) + ' g → gotowe ' + fmt(cooked) + ' g</p>' : '');
        box.parentElement.parentElement.querySelector('[data-commit]').disabled = !(d.name.trim() && d.items.length);
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

    // ---------- podsumowanie (karta dnia → statystyki z 7 / 30 / 90 dni, scripts/food/stats.php) ----------
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
            }).catch(function () { fail({ status: 'error', message: 'Nie udało się wczytać podsumowania' }); });
        }
        var goal = S.data.day.goal;
        box.innerHTML = headHTML('Podsumowanie', false)
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
        bindHead(box);
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
