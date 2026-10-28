// HomeList – Opłaty: ekran (podsumowanie, „Wymaga uwagi”, nadchodzące / wszystkie) + arkusz (wybór typu, formularz,
// szczegóły opłaty ze statystykami pozycji, płatność / doładowanie z dodatkami, statystyki ogólne).
// Ładowany raz (hlLoadScript) przez components/panel/dashboard/bills.php, który przy każdym otwarciu woła hlBills.init(dane).
// Endpointy: scripts/bills/*.php (JSON). Arkusz i toast żyją w <body> i korzystają z klas fd-* (food.css).
(function () {
    if (window.hlBills) return;

    var P = 'stroke-linecap="round" stroke-linejoin="round"';
    var svg = function (d, w) { return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="' + (w || 2) + '" stroke="currentColor" aria-hidden="true"><path ' + P + ' d="' + d + '"/></svg>'; };
    var I = {
        plus: svg('M12 4.5v15m7.5-7.5h-15', 2.2),
        minus: svg('M5 12h14', 2.2),
        x: svg('M6 18 18 6M6 6l12 12'),
        back: svg('M15.75 19.5 8.25 12l7.5-7.5'),
        chev: svg('m8.25 4.5 7.5 7.5-7.5 7.5'),
        check: svg('m4.5 12.75 6 6 9-13.5', 3),
        repeat: svg('M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99', 2.4),
        clock: svg('M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'),
        cal: svg('M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'),
        warn: svg('M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'),
        edit: svg('m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125', 1.8),
        chart: svg('M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z', 1.8),
        up: svg('M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941'),
        down: svg('M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181'),
        card: svg('M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z', 1.8)
    };
    var TYPE_ICON = {
        sub: 'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z',
        plan: 'M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z',
        prepaid: 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        loan: 'M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm4.5 4.5h.008v.008h-.008V13.5Z',
        pass: 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z',
        ins: 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
        bill: 'm3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z',
        other: 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z'
    };
    var TYPE_DESC = {
        sub: 'Netflix, Spotify, chmura, aplikacje', plan: 'Internet, telefon, telewizja', prepaid: 'Stały pakiet + dokupione dodatki', loan: 'Telefon na raty, kredyt, leasing',
        pass: 'Siłownia, basen, zajęcia', ins: 'OC/AC, mieszkanie, na życie', bill: 'Prąd, gaz, woda, czynsz', other: 'Wszystko, co się powtarza'
    };
    var NAME_HINT = { sub: 'np. Netflix', plan: 'np. Internet światłowód', prepaid: 'np. Telefon mamy', loan: 'np. Rata za telefon', pass: 'np. Siłownia', ins: 'np. OC/AC – auto', bill: 'np. Prąd', other: 'Nazwa' };
    var AMOUNT_LABEL = { sub: 'Kwota', plan: 'Abonament', prepaid: 'Pakiet', loan: 'Rata', pass: 'Cena', ins: 'Składka', bill: 'Kwota', other: 'Kwota' };
    var MONTHS = ['stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia'];
    var MONTHS_NOM = ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
    var MONTHS_LOC = ['w styczniu', 'w lutym', 'w marcu', 'w kwietniu', 'w maju', 'w czerwcu', 'w lipcu', 'w sierpniu', 'we wrześniu', 'w październiku', 'w listopadzie', 'w grudniu'];
    var MONTHS_SHORT = ['sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'paź', 'lis', 'gru'];
    var DAYS = ['niedziela', 'poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota'];
    var DAYS_SHORT = ['nd.', 'pon.', 'wt.', 'śr.', 'czw.', 'pt.', 'sob.'];

    var S = { data: null, tab: 'up', yearly: false, sheet: null, dirty: false, busy: {} };
    var B = window.hlBills = {};

    // ---------- pomocnicze ----------
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function el(id) { return document.getElementById(id); }
    function num(n, d) { return (+n || 0).toLocaleString('pl-PL', { minimumFractionDigits: d, maximumFractionDigits: d }); }
    function money(n) { return num(n, 2) + ' zł'; }
    function money0(n) { return num(Math.round(n), 0) + ' zł'; }
    function s2d(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function d2s(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
    function addDays(s, n) { var d = s2d(s); d.setDate(d.getDate() + n); return d2s(d); }
    function addMonths(s, n) {
        var d = s2d(s), day = d.getDate();
        d.setDate(1); d.setMonth(d.getMonth() + n);
        d.setDate(Math.min(day, new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate()));
        return d2s(d);
    }
    function diffDays(a, b) { return Math.round((s2d(b) - s2d(a)) / 86400000); }
    function today() { return S.data ? S.data.today : d2s(new Date()); }
    function longDate(s) { var d = s2d(s); return d.getDate() + ' ' + MONTHS[d.getMonth()] + (d.getFullYear() !== s2d(today()).getFullYear() ? ' ' + d.getFullYear() : ''); }
    function shortDate(s) { var d = s2d(s); return d.getDate() + '.' + String(d.getMonth() + 1).padStart(2, '0') + (d.getFullYear() !== s2d(today()).getFullYear() ? '.' + d.getFullYear() : ''); }
    function monthYearGen(s) { var d = s2d(s); return MONTHS[d.getMonth()] + ' ' + d.getFullYear(); }
    function monthYear(s) { var d = s2d(s); return MONTHS_NOM[d.getMonth()].toLowerCase() + ' ' + d.getFullYear(); }
    function inDays(n) { return n === 0 ? 'dziś' : n === 1 ? 'jutro' : n === -1 ? 'wczoraj' : n > 1 ? 'za ' + n + ' dni' : (-n) + ' dni temu'; }
    function whenLabel(due) {
        var n = diffDays(today(), due);
        if (n >= -1 && n <= 1) return inDays(n);
        if (n > 1 && n < 7) return DAYS_SHORT[s2d(due).getDay()] + ', ' + shortDate(due);
        return shortDate(due);
    }
    function plural(n, one, few, many) { var a = Math.abs(n); return a === 1 ? one : (a % 10 >= 2 && a % 10 <= 4 && (a % 100 < 12 || a % 100 > 14)) ? few : many; }
    function getJSON(url) { return fetch(url, { cache: 'no-store' }).then(function (r) { return r.json(); }); }
    function post(url, obj) {
        var fd = new FormData();
        Object.keys(obj).forEach(function (k) { if (obj[k] !== null && obj[k] !== undefined) fd.append(k, obj[k]); });
        return fetch(url, { method: 'POST', body: fd }).then(function (r) { return r.json(); });
    }
    function fail(res) { if (typeof showAlert === 'function') showAlert(res && res.status === 'warning' ? 'warning' : 'error', (res && res.message) || 'Coś poszło nie tak – spróbuj ponownie'); }
    function vib() { try { if (navigator.vibrate) navigator.vibrate(4); } catch (e) {} }
    function typeOf(k) { return (S.data.types || []).filter(function (t) { return t.key === k; })[0] || { key: k, label: k, group: k, short: k, color: '#64748b' }; }
    function billOf(id) { return (S.data.bills || []).filter(function (b) { return b.id === id; })[0]; }
    function tile(type, size) { return '<span class="bl-tile' + (size ? ' is-' + size : '') + '" style="--c:' + typeOf(type).color + '">' + svg(TYPE_ICON[type] || TYPE_ICON.other, 1.8) + '</span>'; }
    function small(icon, px) { return icon.replace('<svg', '<svg width="' + (px || 14) + '" height="' + (px || 14) + '"'); }

    // ---------- start / odświeżanie ----------
    B.init = function (data) {
        if (S.sheet) closeSheet(true);
        S.data = data;
        ensureShell();
        render();
        var pending = null;
        try { pending = sessionStorage.getItem('hl_bills_open'); sessionStorage.removeItem('hl_bills_open'); } catch (e) {}
        // 'stats' | 'type' | 'bill:ID' | 'pay:ID:DUE' (z Dziś, powiadomienia)
        if (pending) { var p = pending.split(':'); setTimeout(function () { B.open(p[0], p[1] ? { id: +p[1], due: p[2] } : {}); }, 300); }
    };

    // synchronizacja (hlSyncApply) i powrót po zapisie – bez spinnera; przy otwartym arkuszu dopiero po zamknięciu
    B.refresh = function () {
        if (!el('bl_root')) return Promise.resolve();
        if (S.sheet) { S.dirty = true; return Promise.resolve(); }
        return load();
    };
    function load() {
        return getJSON('scripts/bills/overview.php').then(function (res) {
            if (res.status !== 'success') return;
            S.data = res;
            badge(res.due_count);
            if (el('bl_root')) render();
        }).catch(function () {});
    }

    // licznik w menu „więcej” i kropki w nawigacji (lists.php, nav_rail.php)
    function badge(n) {
        document.querySelectorAll('[data-bills-badge]').forEach(function (b) { b.textContent = n; b.hidden = !n; });
        document.querySelectorAll('[data-bills-dot]').forEach(function (d) { d.hidden = !n; });
    }

    function render() {
        var d = s2d(today());
        if (el('bl_subtitle')) el('bl_subtitle').textContent = DAYS[d.getDay()].charAt(0).toUpperCase() + DAYS[d.getDay()].slice(1) + ', ' + d.getDate() + ' ' + MONTHS[d.getMonth()];
        renderHero();
        renderAlerts();
        renderSeg();
        renderList();
        loadCal();
    }

    // ---------- podsumowanie ----------
    function renderHero() {
        var box = el('bl_hero'), sum = S.data.summary;
        if (!box) return;
        if (!S.data.bills.length) {
            box.innerHTML = '<div class="bl-empty"><div class="bl-empty-art">' + I.card + '</div>'
                + '<h2 class="hl-h2 mt-4">Wszystkie stałe koszty w jednym miejscu</h2>'
                + '<p class="mt-2 text-sm leading-relaxed bl-mut">Subskrypcje, abonamenty, numery na kartę, raty i karnety. Przypomnimy o terminach i końcach umów, a w statystykach zobaczysz, ile naprawdę na nie wydajesz.</p>'
                + '<button type="button" class="hl-btn hl-btn-primary mt-5" data-act="add">Dodaj pierwszą opłatę</button></div>';
            box.querySelector('[data-act=add]').onclick = function () { B.open('type'); };
            return;
        }
        var types = S.data.types.filter(function (t) { return sum.by_type[t.key]; });
        var k = S.yearly ? 12 : 1;
        box.innerHTML = '<div class="bl-hero">'
            + '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">'
            + '<div><span class="bl-eyebrow">Stałe koszty · ' + (S.yearly ? 'rocznie' : 'miesięcznie') + '</span>'
            + '<div class="bl-big bl-num" style="margin-top:6px">' + num(S.yearly ? Math.round(sum.yearly) : sum.monthly, S.yearly ? 0 : 2) + '<small> zł</small></div></div>'
            + '<button type="button" class="bl-mini-btn fd-press" data-act="period">' + (S.yearly ? 'Miesięcznie' : 'Rocznie') + '</button></div>'
            + '<p class="text-xs bl-mut" style="margin:8px 0 0">' + (sum.left_this_month > 0 ? 'Do końca ' + MONTHS[s2d(today()).getMonth()] + ' jeszcze <b class="bl-num" style="color:var(--hl-ink);font-weight:600">' + money(sum.left_this_month) + '</b>' : 'W tym miesiącu wszystko opłacone') + '</p>'
            + '<div class="bl-stack" style="margin-top:14px" role="img" aria-label="Podział kosztów na rodzaje">' + types.map(function (t) { return '<span style="flex-grow:' + Math.round(sum.by_type[t.key] * 100) + ';background:' + t.color + '"></span>'; }).join('') + '</div>'
            + '<div class="bl-legend">' + types.map(function (t) { return '<span><i style="background:' + t.color + '"></i>' + esc(t.short) + ' <b class="bl-num">' + money0(sum.by_type[t.key] * k) + '</b></span>'; }).join('') + '</div></div>'
            + '<div class="bl-hero-foot"><button type="button" class="bl-link" data-act="stats">Statystyki' + I.chev + '</button>'
            + '<span class="text-xs bl-mut">' + sum.count + ' ' + plural(sum.count, 'opłata', 'opłaty', 'opłat') + '</span></div>';
        box.querySelector('[data-act=period]').onclick = function () { S.yearly = !S.yearly; renderHero(); };
        box.querySelector('[data-act=stats]').onclick = function () { B.open('stats'); };
    }

    // ---------- kalendarz miesiąca (tylko szeroki ekran – na telefonie kontener jest ukryty i nic się nie pobiera) ----------
    function loadCal(ym) {
        var box = el('bl_cal');
        if (!box || !window.matchMedia('(min-width: 768px)').matches || !S.data.bills.length) { if (box) box.innerHTML = ''; return; }
        ym = ym || (S.cal && S.cal.ym) || today().slice(0, 7);
        var seq = S.calSeq = (S.calSeq || 0) + 1;
        if (!S.cal || S.cal.ym !== ym) renderCal(box, { ym: ym, days: null, sel: null });
        getJSON('scripts/bills/month.php?ym=' + ym).then(function (res) {
            if (seq !== S.calSeq || res.status !== 'success') return;
            var keep = S.cal && S.cal.ym === ym ? S.cal.sel : null;
            var first = Object.keys(res.days)[0] || null;
            renderCal(box, { ym: ym, days: res.days, sel: keep || (today().slice(0, 7) === ym ? today() : first) });
        }).catch(function () {});
    }
    function renderCal(box, cal) {
        S.cal = cal;
        var y = +cal.ym.slice(0, 4), m = +cal.ym.slice(5) - 1, first = new Date(y, m, 1), len = new Date(y, m + 1, 0).getDate();
        var lead = (first.getDay() + 6) % 7, cells = '', t = today();
        for (var i = 0; i < lead; i++) cells += '<span></span>';
        for (var d = 1; d <= len; d++) {
            var ds = cal.ym + '-' + String(d).padStart(2, '0'), items = (cal.days && cal.days[ds]) || [];
            var dots = items.slice(0, 3).map(function (it) { return '<i style="background:' + typeOf(it.type).color + '"></i>'; }).join('');
            cells += '<button type="button" class="bl-cd' + (ds === cal.sel ? ' is-sel' : '') + (ds === t ? ' is-today' : '') + (ds < t ? ' is-past' : '') + '" data-day="' + ds + '"'
                + ' aria-label="' + d + ' ' + MONTHS[m] + (items.length ? ': ' + items.length + ' ' + plural(items.length, 'płatność', 'płatności', 'płatności') : '') + '">'
                + '<span>' + d + '</span><span class="bl-cd-dots">' + dots + '</span></button>';
        }
        var sel = cal.sel && cal.days ? (cal.days[cal.sel] || []) : [];
        var monthSum = cal.days ? Object.keys(cal.days).reduce(function (a, k) { return a + cal.days[k].reduce(function (b, it) { return b + it.amount; }, 0); }, 0) : 0;
        box.innerHTML = '<span class="bl-lbl"><span>Kalendarz</span><span class="bl-num">' + (cal.days ? money0(monthSum) + ' w miesiącu' : '') + '</span></span>'
            + '<div class="hl-card bl-cal"><div class="bl-cal-head"><button type="button" class="fd-icon-btn fd-press" data-cal="-1" aria-label="Poprzedni miesiąc">' + I.back + '</button>'
            + '<b>' + MONTHS_NOM[m] + ' ' + y + '</b><button type="button" class="fd-icon-btn fd-press" data-cal="1" aria-label="Następny miesiąc" style="transform:scaleX(-1)">' + I.back + '</button></div>'
            + '<div class="bl-cal-grid bl-cal-wd">' + ['pn', 'wt', 'śr', 'cz', 'pt', 'so', 'nd'].map(function (w) { return '<span>' + w + '</span>'; }).join('') + '</div>'
            + '<div class="bl-cal-grid' + (cal.days ? '' : ' is-loading') + '">' + cells + '</div>'
            + (cal.sel ? '<div class="bl-cal-day"><b>' + longDate(cal.sel) + '</b>' + (sel.length ? sel.map(function (it) {
                return '<button type="button" class="bl-cal-it" data-bill="' + it.bill_id + '"><i style="background:' + typeOf(it.type).color + '"></i><span class="bl-row-name" style="flex:1;font-size:13px' + (it.paid ? ';opacity:.6' : '') + '">' + esc(it.name) + '</span>'
                    + '<span class="bl-num" style="font-size:13px;font-weight:600">' + (it.estimate ? '~' : '') + money(it.amount) + '</span>' + (it.paid ? '<span class="bl-src">' + (cal.sel < t ? 'zapłacona' : 'opłacona') + '</span>' : '') + '</button>';
            }).join('') : '<p class="text-xs bl-mut" style="margin:6px 0 0">Tego dnia nic nie płacisz.</p>') + '</div>' : '')
            + '</div>';
        box.querySelectorAll('[data-cal]').forEach(function (b) {
            b.onclick = function () {
                var dt = new Date(y, m + +b.dataset.cal, 1);
                loadCal(dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0'));
            };
        });
        box.querySelectorAll('[data-day]').forEach(function (b) { b.onclick = function () { renderCal(box, Object.assign({}, S.cal, { sel: b.dataset.day })); }; });
        box.querySelectorAll('[data-bill]').forEach(function (b) { b.onclick = function () { B.open('bill', { id: +b.dataset.bill }); }; });
    }

    // ---------- „Wymaga uwagi” ----------
    function alertHTML(a, i) {
        var b = billOf(a.bill_id);
        if (!b) return '';
        var when = inDays(a.days), tag, text, acts, good = false;
        var btn = function (act, label, dark) { return '<button type="button" class="bl-mini-btn fd-press' + (dark ? ' is-dark' : '') + '" data-alert="' + i + '" data-do="' + act + '">' + label + '</button>'; };
        switch (a.kind) {
            case 'trial':
                tag = small(I.clock) + (a.days ? 'Koniec okresu próbnego ' + when : 'Dziś koniec okresu próbnego');
                text = 'Potem ' + money(b.amount) + ' ' + b.cycle_label; acts = btn('trial_keep', 'Zostaję', true) + btn('trial_cancel', 'Rezygnuję'); break;
            case 'notice':
                tag = small(I.cal) + 'Wypowiedzenie do ' + shortDate(a.date);
                text = 'Umowa kończy się ' + longDate(b.contract_until) + '. Zdecyduj, co dalej – ' + when + ' mija termin.'; acts = btn('open', 'Co dalej?', true); break;
            case 'contract':
                tag = small(I.cal) + 'Umowa kończy się ' + when; text = longDate(a.date) + ' – sprawdź nowe warunki.'; acts = btn('open', 'Szczegóły', true); break;
            case 'insurance':
                tag = small(I.cal) + 'Polisa kończy się ' + when; text = 'Dobry moment, żeby porównać oferty.'; acts = btn('open', 'Szczegóły', true); break;
            case 'promo':
                tag = small(I.up) + 'Koniec promocji ' + when; text = b.price_after ? 'Potem ' + money(b.price_after) + ' zamiast ' + money(b.amount) : 'Sprawdź, ile zapłacisz po promocji.'; acts = btn('open', 'Szczegóły', true); break;
            case 'last_installment':
                good = true; tag = small(I.down) + 'Ostatnia rata ' + when; text = 'Potem miesięcznie o ' + money(b.monthly) + ' mniej.'; acts = btn('open', 'Szczegóły'); break;
            case 'overdue':
                tag = small(I.warn) + 'Zaległa od ' + (-a.days) + ' ' + plural(-a.days, 'dnia', 'dni', 'dni'); text = 'Termin minął ' + longDate(a.date) + '.'; acts = btn('pay', 'Opłacone', true) + btn('open', 'Szczegóły'); break;
            case 'prepaid':
                tag = small(I.clock) + 'Pakiet ważny ' + (a.days <= 0 ? 'do dziś' : 'jeszcze ' + a.days + ' ' + plural(a.days, 'dzień', 'dni', 'dni'));
                text = 'Doładuj ' + money(b.amount) + ' do ' + longDate(a.date) + ', żeby nie stracić pakietu.'; acts = btn('pay', 'Doładowałem', true); break;
            default: return '';
        }
        return '<div class="bl-alert" style="animation-delay:' + (i * 40) + 'ms"><span class="bl-alert-tag' + (good ? ' is-good' : '') + '">' + tag + '</span>'
            + '<button type="button" class="bl-alert-name" style="text-align:left" data-alert="' + i + '" data-do="open">' + esc(b.name) + '</button>'
            + '<span class="bl-alert-text">' + text + '</span><div class="bl-alert-acts">' + acts + '</div></div>';
    }
    function renderAlerts() {
        var box = el('bl_alerts'), list = S.data.alerts || [];
        if (!box) return;
        if (!list.length) { box.innerHTML = ''; return; }
        box.innerHTML = '<span class="bl-lbl"><span>Wymaga uwagi</span><span>' + list.length + '</span></span><div class="bl-alerts">' + list.map(alertHTML).join('') + '</div>';
        box.querySelectorAll('[data-do]').forEach(function (btn) {
            btn.onclick = function () {
                var a = list[+btn.dataset.alert], b = billOf(a.bill_id);
                if (btn.dataset.do === 'open') return B.open('bill', { id: a.bill_id });
                if (btn.dataset.do === 'pay') {
                    var u = (S.data.upcoming || []).filter(function (x) { return x.bill_id === a.bill_id && x.due === a.date; })[0] || { bill_id: a.bill_id, due: a.date, amount: b.amount };
                    return payTap(u);
                }
                btn.disabled = true;
                post('scripts/bills/decide.php', { id: a.bill_id, action: btn.dataset.do }).then(function (res) {
                    if (res.status !== 'success') { btn.disabled = false; return fail(res); }
                    toast(res.message);
                    load();
                }).catch(function () { btn.disabled = false; fail(); });
            };
        });
    }

    // ---------- lista ----------
    function renderSeg() {
        var box = el('bl_seg');
        if (!box) return;
        if (!S.data.bills.length) { box.innerHTML = ''; return; }
        box.innerHTML = segHTML(LIST_TABS, S.tab, 'tab');
        box.querySelectorAll('[data-tab]').forEach(function (b) {
            b.onclick = function () {
                var from = segIndex(LIST_TABS, S.tab), to = segIndex(LIST_TABS, b.dataset.tab);
                if (from === to) return;
                S.tab = b.dataset.tab;
                moveSeg(box.querySelector('.bl-seg'), to);
                renderList();
                slideIn(el('bl_list'), to > from);
            };
        });
    }
    var LIST_TABS = [['up', 'Nadchodzące'], ['all', 'Wszystkie']];
    // segment z przesuwanym wskaźnikiem – przełączanie przez moveSeg(), bez przebudowy HTML
    function segIndex(opts, v) { for (var i = 0; i < opts.length; i++) if (opts[i][0] === v) return i; return 0; }
    function segHTML(opts, on, key, style) {
        return '<div class="bl-seg has-ind" role="tablist" style="--n:' + opts.length + ';--i:' + segIndex(opts, on) + (style ? ';' + style : '') + '"><span class="bl-seg-ind" aria-hidden="true"></span>'
            + opts.map(function (o) { return '<button type="button" role="tab" aria-selected="' + (o[0] === on) + '" class="' + (o[0] === on ? 'is-on' : '') + '" data-' + key + '="' + o[0] + '">' + o[1] + '</button>'; }).join('') + '</div>';
    }
    function moveSeg(seg, i) {
        seg.style.setProperty('--i', i);
        seg.querySelectorAll('button').forEach(function (b, j) { b.classList.toggle('is-on', j === i); b.setAttribute('aria-selected', j === i); });
    }
    // wjazd nowej zawartości z kierunku przełączenia (.fd-res z food.css)
    function slideIn(node, next) {
        if (!node) return;
        node.classList.remove('fd-res', 'is-next', 'is-prev');
        void node.offsetWidth;
        node.classList.add('fd-res', next ? 'is-next' : 'is-prev');
    }

    // grupy: zaległe, dziś, jutro, do końca tygodnia, reszta miesiąca, kolejne miesiące
    function groupOf(u) {
        var t = today(), n = diffDays(t, u.due), td = s2d(t), dd = s2d(u.due);
        if (u.overdue) return ['0', 'Zaległe'];
        if (n === 0) return ['1', 'Dziś'];
        if (n === 1) return ['2', 'Jutro'];
        var toSunday = (7 - td.getDay()) % 7;
        if (n <= toSunday) return ['3', 'W tym tygodniu'];
        if (dd.getMonth() === td.getMonth() && dd.getFullYear() === td.getFullYear()) return ['4', 'Później ' + MONTHS_LOC[td.getMonth()]];
        return ['5' + u.due.slice(0, 7), MONTHS_NOM[dd.getMonth()] + (dd.getFullYear() !== td.getFullYear() ? ' ' + dd.getFullYear() : '')];
    }
    function rowMeta(u, b) {
        if (u.overdue) return ['Zaległa od ' + (-diffDays(today(), u.due)) + ' ' + plural(diffDays(today(), u.due), 'dnia', 'dni', 'dni'), true];
        if (u.paid && u.source === 'auto') return ['Pobrane automatycznie', false, true];
        if (u.paid) {
            var on = u.paid_on || u.due, ago = diffDays(on, today());
            return ['Opłacone ' + (ago === 0 ? 'dziś' : ago === 1 ? 'wczoraj' : shortDate(on)) + ' · ręcznie', false, true];
        }
        if (u.skipped) return ['Pominięte', false];
        if (u.installment && b.installments) return ['Rata ' + u.installment + ' z ' + b.installments, false];
        if (b.type === 'prepaid') return ['Pakiet ważny do ' + shortDate(u.due), false];
        if (b.amount_variable) return ['Kwota zmienna · szacunek', false];
        if (b.trial_until && u.due >= b.trial_until && diffDays(b.trial_until, u.due) < 40) return ['Pierwsza płatność po okresie próbnym', false];
        return [b.cycle_label.charAt(0).toUpperCase() + b.cycle_label.slice(1) + (b.pay_mode === 'auto' ? ' · automatycznie' : ''), false];
    }
    function rowHTML(u, i) {
        var b = billOf(u.bill_id);
        if (!b) return '';
        var m = rowMeta(u, b), key = u.bill_id + '|' + u.due;
        // pobrane automatycznie – sam znacznik (cofnięcie i tak dopisałby je z powrotem)
        var right = u.paid && u.source === 'auto'
            ? '<span class="bl-chk is-done is-static" title="Pobrane automatycznie">' + I.check + '</span>'
            : b.pay_mode === 'auto' && !u.overdue && !u.paid
            ? '<span class="bl-auto" title="Płatność automatyczna">' + I.repeat + 'Auto</span>'
            : '<button type="button" class="bl-chk' + (u.paid ? ' is-done' : '') + '" data-pay="' + key + '" aria-label="' + (u.paid ? 'Cofnij opłacenie: ' : 'Oznacz jako opłacone: ') + esc(b.name) + '">' + I.check + '</button>';
        return '<div class="bl-row' + (u.paid || u.skipped ? ' is-paid' : '') + (u.overdue ? ' is-overdue' : '') + '" style="animation-delay:' + Math.min(i * 25, 300) + 'ms">'
            + '<button type="button" class="bl-row-main" data-open="' + b.id + '">' + tile(b.type)
            + '<span style="flex:1;min-width:0"><span class="bl-row-name">' + esc(b.name) + '</span><span class="bl-row-meta' + (m[1] ? ' is-warn' : '') + (m[2] ? ' is-ok' : '') + '">' + esc(m[0]) + '</span></span>'
            + '<span class="bl-row-amt"><b>' + (u.estimate ? '~' : '') + money(u.amount) + '</b><span>' + whenLabel(u.due) + '</span></span></button>' + right + '</div>';
    }
    function renderList() {
        var box = el('bl_list');
        if (!box) return;
        if (!S.data.bills.length) {
            box.innerHTML = '<span class="bl-lbl"><span>Co chcesz dodać?</span></span><div class="bl-types">' + typesHTML() + '</div>';
            bindTypes(box);
            return;
        }
        if (S.tab === 'all') return renderAll(box);
        var groups = {}, order = [];
        (S.data.upcoming || []).forEach(function (u) {
            if (!billOf(u.bill_id)) return;
            var g = groupOf(u);
            if (!groups[g[0]]) { groups[g[0]] = { label: g[1], items: [] }; order.push(g[0]); }
            groups[g[0]].items.push(u);
        });
        order.sort();
        if (!order.length) {
            box.innerHTML = '<div class="hl-card hl-card-pad mt-4 text-sm bl-mut">W najbliższych tygodniach nic do zapłaty.</div>';
            return;
        }
        var i = 0;
        box.innerHTML = order.map(function (k) {
            var g = groups[k], left = g.items.reduce(function (a, u) { return a + (u.paid || u.skipped ? 0 : u.amount); }, 0);
            return '<span class="bl-lbl"><span>' + g.label + '</span><span class="bl-num">' + (left ? money(left) : 'opłacone') + '</span></span>'
                + '<div class="bl-rows">' + g.items.map(function (u) { return rowHTML(u, i++); }).join('') + '</div>';
        }).join('');
        bindRows(box);
    }
    function renderAll(box) {
        var sum = S.data.summary, html = '';
        S.data.types.forEach(function (t) {
            var bills = S.data.bills.filter(function (b) { return b.type === t.key; });
            if (!bills.length) return;
            var monthly = bills.reduce(function (a, b) { return a + (b.status === 'active' ? b.monthly : 0); }, 0);
            html += '<span class="bl-lbl"><span style="display:inline-flex;align-items:center;gap:6px"><i style="width:8px;height:8px;border-radius:3px;background:' + t.color + '"></i>' + esc(t.group) + '</span>'
                + '<span class="bl-num">' + money(monthly) + ' / mies.</span></span><div class="hl-card bl-group">'
                + bills.map(function (b) {
                    var sub = b.status === 'paused' ? 'Wstrzymana' : b.cycle_label.charAt(0).toUpperCase() + b.cycle_label.slice(1) + (b.cycle_unit !== 'month' || b.cycle_every !== 1 ? ' · ' + money(b.amount) : '');
                    return '<button type="button" class="bl-li" data-open="' + b.id + '">' + tile(b.type, 'sm')
                        + '<span style="flex:1;min-width:0"><span class="bl-row-name">' + esc(b.name) + '</span><span class="bl-row-meta">' + esc(sub) + '</span></span>'
                        + '<span class="bl-num" style="font-size:13px;font-weight:600">' + (b.status === 'paused' ? '–' : (b.amount_variable ? '~' : '') + money(b.monthly)) + '</span></button>';
                }).join('') + '</div>';
        });
        box.innerHTML = html + '<p class="bl-note">Kwoty przeliczone na miesiąc. Rachunki o zmiennej kwocie i numery na kartę liczone ze średniej z ostatnich płatności. Razem ' + money(sum.monthly) + ' miesięcznie.</p>';
        bindRows(box);
    }
    function bindRows(box) {
        box.querySelectorAll('[data-open]').forEach(function (b) { b.onclick = function () { B.open('bill', { id: +b.dataset.open }); }; });
        box.querySelectorAll('[data-pay]').forEach(function (b) {
            b.onclick = function () {
                var k = b.dataset.pay.split('|');
                var u = S.data.upcoming.filter(function (x) { return x.bill_id === +k[0] && x.due === k[1]; })[0];
                if (u) payTap(u, b);
            };
        });
    }

    // ---------- odhaczanie ----------
    function payTap(u, btn) {
        var b = billOf(u.bill_id), key = u.bill_id + '|' + u.due;
        if (!b || S.busy[key]) return;
        if (u.paid) return undoPay(u, b);
        // kwota zmienna / doładowanie – najpierw arkusz z kwotą i dodatkami
        if (b.amount_variable || b.type === 'prepaid') return B.open('pay', { id: b.id, due: u.due });
        vib();
        S.busy[key] = true;
        u.paid = true;
        if (btn) { btn.classList.add('is-done'); btn.closest('.bl-row').classList.add('is-paid'); }
        post('scripts/bills/pay.php', { bill_id: b.id, due: u.due }).then(function (res) {
            delete S.busy[key];
            if (res.status !== 'success') { u.paid = false; renderList(); return fail(res); }
            toast(b.name + ' · opłacone', function () { undoPay(u, b, true); });
            load();
        }).catch(function () { delete S.busy[key]; u.paid = false; renderList(); fail(); });
    }
    function undoPay(u, b, quiet) {
        post('scripts/bills/pay.php', { bill_id: b.id, due: u.due, action: 'undo' }).then(function (res) {
            if (res.status !== 'success') return fail(res);
            if (!quiet) toast('Cofnięto: ' + b.name, function () {
                post('scripts/bills/pay.php', { bill_id: b.id, due: u.due, action: 'paid', amount: res.restore.amount, extras: JSON.stringify(res.restore.extras), paid_on: res.restore.paid_on, source: res.restore.source }).then(refreshAll);
            });
            refreshAll();
        }).catch(function () { fail(); });
    }
    function refreshAll() {
        if (S.sheet && S.sheet.step === 'bill') renderStep();
        return load();
    }

    // ---------- toast ----------
    function toast(text, undo) {
        var t = el('bl_toast');
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
        if (el('bl_sheet')) return;
        document.body.insertAdjacentHTML('beforeend',
            '<div id="bl_scrim" class="fd-scrim"></div>'
            + '<div id="bl_sheet" class="fd-sheet" role="dialog" aria-modal="true" aria-label="Opłaty"><div class="fd-handle"><span></span></div><div id="bl_step" class="fd-step"></div></div>'
            + '<div id="bl_toast" class="fd-toast liquid-glass" role="status">' + I.check.replace('<svg', '<svg width="18" height="18" style="color:#4ade80;flex:none"') + '<span style="flex:1;min-width:0"></span><button type="button">Cofnij</button></div>');
        el('bl_scrim').addEventListener('click', function () { closeSheet(); });
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && S.sheet) closeSheet(); });
        var sh = el('bl_sheet'), h = sh.querySelector('.fd-handle'), drag = null;
        h.addEventListener('pointerdown', function (ev) { drag = { y: ev.clientY, dy: 0 }; h.setPointerCapture(ev.pointerId); sh.style.transition = 'none'; });
        h.addEventListener('pointermove', function (ev) { if (!drag) return; drag.dy = Math.max(0, ev.clientY - drag.y); sh.style.transform = 'translateY(' + drag.dy + 'px)'; });
        var up = function () { if (!drag) return; var dy = drag.dy; drag = null; sh.style.transition = ''; sh.style.transform = ''; if (dy > 120) closeSheet(); };
        h.addEventListener('pointerup', up);
        h.addEventListener('pointercancel', up);
    }

    // open('type' | 'form' | 'bill' | 'pay' | 'stats', params) – także z innych widoków (Dziś, profil)
    B.open = function (step, params) {
        ensureShell();
        S.sheet = Object.assign({ step: step, hist: [] }, params || {});
        el('bl_toast').classList.remove('is-show');
        renderStep();
        el('bl_scrim').classList.add('is-open');
        el('bl_sheet').classList.add('is-open');
        document.documentElement.style.overflow = 'hidden';
    };
    function closeSheet(skipRefresh) {
        if (!S.sheet) return;
        S.sheet = null;
        el('bl_scrim').classList.remove('is-open');
        el('bl_sheet').classList.remove('is-open');
        document.documentElement.style.overflow = '';
        if (S.dirty && !skipRefresh) { S.dirty = false; load(); }
        S.dirty = false;
    }
    // przejście do kolejnego kroku z zapamiętaniem poprzedniego (strzałka „wstecz”)
    function go(step, params) {
        S.sheet.hist.push(Object.assign({}, S.sheet, { hist: null }));
        Object.assign(S.sheet, { step: step }, params || {});
        renderStep();
    }
    function back() {
        var prev = S.sheet.hist.pop();
        if (!prev) return closeSheet();
        var hist = S.sheet.hist;
        S.sheet = Object.assign(prev, { hist: hist });
        renderStep(true);
    }
    function renderStep(isBack) {
        var box = el('bl_step');
        box.className = 'fd-step' + (isBack ? ' is-back' : '');
        el('bl_sheet').classList.toggle('is-wide', S.sheet.step === 'stats');
        void box.offsetWidth;
        var st = S.sheet.step;
        if (st === 'type') renderType(box);
        else if (st === 'form') renderForm(box);
        else if (st === 'bill') renderBill(box);
        else if (st === 'pay') renderPay(box);
        else if (st === 'stats') renderStats(box);
    }
    // ponowne narysowanie kroku bez animacji wejścia, z zachowaniem przewinięcia
    function redraw(fn) {
        var box = el('bl_step'), sc = box.querySelector('.fd-scroll'), top = sc ? sc.scrollTop : 0;
        fn(box);
        sc = box.querySelector('.fd-scroll');
        // global.css ma * { scroll-behavior: smooth } – bez tego przewijanie leci od góry z animacją
        if (sc) { sc.style.scrollBehavior = 'auto'; sc.scrollTop = top; }
    }
    function headHTML(title, opts) {
        opts = opts || {};
        var canBack = S.sheet.hist.length > 0;
        return '<div style="display:flex;align-items:center;gap:4px;padding:4px 12px 0 ' + (canBack ? '12px' : '20px') + '">'
            + (canBack ? '<button type="button" class="fd-icon-btn fd-press" data-back aria-label="Wróć">' + I.back + '</button>' : '')
            + '<h2 class="hl-popup-title" style="flex:1;min-width:0;padding:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + title + '</h2>'
            + (opts.extra || '')
            + '<button type="button" class="fd-icon-btn fd-press bl-mut" data-close aria-label="Zamknij">' + I.x + '</button></div>';
    }
    function bindHead(box) {
        var c = box.querySelector('[data-close]');
        if (c) c.onclick = function () { closeSheet(); };
        var b = box.querySelector('[data-back]');
        if (b) b.onclick = back;
    }
    function loadingHTML(title) {
        return headHTML(title) + '<div style="padding:16px 20px">' + '<div class="fd-skel"></div>'.repeat(4) + '</div>';
    }

    // ---------- wybór typu ----------
    function typesHTML() {
        return S.data.types.map(function (t, i) {
            return '<button type="button" class="bl-type" style="animation-delay:' + (i * 30) + 'ms" data-type="' + t.key + '">' + tile(t.key)
                + '<span><b>' + esc(t.label) + '</b><small>' + esc(TYPE_DESC[t.key] || '') + '</small></span></button>';
        }).join('');
    }
    function bindTypes(box) {
        box.querySelectorAll('[data-type]').forEach(function (b) {
            b.onclick = function () {
                if (!S.sheet) B.open('type');
                go('form', { f: newForm(b.dataset.type), edit: null });
            };
        });
    }
    function renderType(box) {
        box.innerHTML = headHTML('Co dodajesz?') + '<div class="fd-scroll" style="padding:4px 20px 24px">'
            + '<p class="text-sm bl-mut" style="margin:0 0 14px">Od rodzaju zależy, o co zapytamy i o czym przypomnimy.</p>'
            + '<div class="bl-types">' + typesHTML() + '</div></div>';
        bindHead(box);
        bindTypes(box);
    }

    // ---------- formularz ----------
    function newForm(type) {
        var t = typeOf(type);
        return {
            type: type, name: '', amount: '', amount_variable: type === 'bill', cycle_unit: t.cycle_unit, cycle_every: t.cycle_every,
            next_due: type === 'prepaid' ? addDays(today(), 30) : today(), paid_from: '', paid_count: 0, pay_mode: type === 'sub' || type === 'plan' ? 'auto' : 'manual',
            reminders: type === 'ins' ? [7, 30] : [1], ref: '', trial: false, trial_until: addDays(today(), 7), contract_until: '', notice_months: 1, promo: false, promo_until: '', price_after: '',
            auto_renew: true, installments: 24, loan_rate: '', pass_kind: 'open', pass_entries: 8, single_price: '', end_date: ''
        };
    }
    function formFromBill(b, next) {
        var f = newForm(b.type);
        Object.keys(f).forEach(function (k) { if (b[k] !== undefined && b[k] !== null) f[k] = b[k]; });
        f.amount = num(b.amount, 2).replace(/ /g, '');
        f.next_due = next || '';
        f.orig_next = next || '';
        f.trial = !!b.trial_until;
        f.promo = !!b.promo_until;
        f.pass_kind = b.pass_entries ? 'entries' : 'open';
        f.pass_entries = b.pass_entries || 8;
        f.notice_months = b.notice_months || 0;
        f.price_after = b.price_after != null ? num(b.price_after, 2).replace(/ /g, '') : '';
        f.single_price = b.single_price != null ? num(b.single_price, 2).replace(/ /g, '') : '';
        f.loan_rate = b.loan_rate != null ? String(b.loan_rate).replace('.', ',') : '';
        f.auto_renew = b.auto_renew !== false;
        return f;
    }
    function parseAmount(v) { var n = parseFloat(String(v).replace(/\s/g, '').replace(',', '.')); return isNaN(n) ? null : n; }
    function cyclePreset(f) {
        if (f.cycle_unit === 'month' && f.cycle_every === 1) return 'm';
        if (f.cycle_unit === 'month' && f.cycle_every === 3) return 'q';
        if (f.cycle_unit === 'year' && f.cycle_every === 1) return 'y';
        return 'x';
    }
    function stepper(key, val, label) {
        return '<span class="bl-step"><button type="button" data-step="' + key + '" data-d="-1" aria-label="' + label + ' mniej">' + I.minus + '</button><b>' + val + '</b>'
            + '<button type="button" data-step="' + key + '" data-d="1" aria-label="' + label + ' więcej">' + I.plus + '</button></span>';
    }
    function sw(key, on, label) { return '<button type="button" class="bl-sw fd-press' + (on ? ' is-on' : '') + '" data-sw="' + key + '" role="switch" aria-checked="' + on + '" aria-label="' + label + '"></button>'; }
    function field(label, key, value, attrs) {
        return '<label class="bl-f"><span>' + label + '</span><input data-f="' + key + '" value="' + esc(value) + '" ' + (attrs || '') + '></label>';
    }
    function chips(key, opts, multi) {
        var f = S.sheet.f;
        return '<div class="fd-chips" style="flex-wrap:wrap;margin:0;padding:0">' + opts.map(function (o) {
            var on = multi ? f[key].indexOf(o[0]) >= 0 : f[key] === o[0];
            return '<button type="button" class="fd-chip fd-press' + (on ? ' is-on' : '') + '" data-chip="' + key + '" data-v="' + o[0] + '"' + (multi ? ' data-multi="1"' : '') + ' aria-pressed="' + on + '">' + o[1] + '</button>';
        }).join('') + '</div>';
    }
    function loanCalc(f) {
        var amount = parseAmount(f.amount) || 0, left = Math.max(0, f.installments - f.paid_count);
        if (!f.next_due || !left) return '';
        var last = f.cycle_unit === 'year' ? addMonths(f.next_due, 12 * (left - 1)) : addMonths(f.next_due, left - 1);
        return '<div class="bl-calc">Ostatnia rata <b>' + monthYear(last) + '</b>' + (amount ? ' · zostało <b class="bl-num">' + money(left * amount) + '</b> w ' + left + ' ' + plural(left, 'racie', 'ratach', 'ratach') : ' · ' + left + ' ' + plural(left, 'rata', 'raty', 'rat') + ' do zapłaty') + '</div>';
    }
    function renderForm(box) {
        var f = S.sheet.f, t = typeOf(f.type), edit = !!S.sheet.edit, html = '';
        var lbl = function (s) { return '<span class="bl-lbl"><span>' + s + '</span></span>'; };
        html += '<div style="display:flex;align-items:center;gap:12px;margin:4px 0 16px">' + tile(f.type) + '<b style="font-size:16px;font-weight:600">' + esc(t.label) + '</b></div>';
        html += '<div class="bl-form-group">' + field('Nazwa', 'name', f.name, 'placeholder="' + esc(NAME_HINT[f.type]) + '" maxlength="120" autocomplete="off"')
            + field(f.type === 'bill' && f.amount_variable ? 'Kwota (szacunek)' : AMOUNT_LABEL[f.type], 'amount', f.amount, 'inputmode="decimal" placeholder="0,00 zł" class="bl-num"') + '</div>';

        if (f.type === 'prepaid') {
            html += lbl('Pakiet') + '<div class="bl-form-group"><div class="bl-f"><span>Ważny przez</span>' + stepper('cycle_every', f.cycle_every + ' dni', 'Dni') + '</div>'
                + '<label class="bl-f"><span>Ważny do</span><input type="date" data-f="next_due" value="' + esc(f.next_due) + '"></label>'
                + field('Numer telefonu', 'ref', f.ref, 'inputmode="tel" placeholder="opcjonalnie" maxlength="60"') + '</div>'
                + '<p class="bl-hint">Przy każdym doładowaniu dopiszesz dodatki, np. internet – policzymy z nich realny koszt numeru.</p>';
        } else if (f.type !== 'loan') {
            var preset = cyclePreset(f);
            html += lbl('Jak często') + '<div class="bl-seg is-4">' + [['m', 'Miesiąc'], ['q', 'Kwartał'], ['y', 'Rok'], ['x', 'Inny']].map(function (o) {
                return '<button type="button" class="' + (preset === o[0] ? 'is-on' : '') + '" data-cycle="' + o[0] + '">' + o[1] + '</button>';
            }).join('') + '</div>';
            if (preset === 'x') {
                html += '<div class="bl-form-group" style="margin-top:8px"><div class="bl-f"><span>Co</span>' + stepper('cycle_every', f.cycle_every, 'Co ile')
                    + '<select data-f="cycle_unit" style="flex:none;text-align:right">' + [['day', 'dni'], ['week', 'tygodni'], ['month', 'miesięcy'], ['year', 'lat']].map(function (o) {
                        return '<option value="' + o[0] + '"' + (f.cycle_unit === o[0] ? ' selected' : '') + '>' + o[1] + '</option>';
                    }).join('') + '</select></div></div>';
            }
        }

        if (f.type === 'loan') {
            html += lbl('Raty') + '<div class="bl-form-group"><div class="bl-f"><span>Liczba rat</span>' + stepper('installments', f.installments, 'Rat') + '</div>'
                + (edit ? '' : '<div class="bl-f"><span>Już zapłacone</span>' + stepper('paid_count', f.paid_count, 'Zapłaconych') + '</div>')
                + field('RRSO %', 'loan_rate', f.loan_rate, 'inputmode="decimal" placeholder="opcjonalnie"') + '</div>';
        }
        if (f.type === 'sub') {
            html += lbl('Subskrypcja') + '<div class="bl-form-group"><div class="bl-f"><span>Okres próbny</span>' + sw('trial', f.trial, 'Okres próbny') + '</div>'
                + (f.trial ? '<label class="bl-f"><span>Kończy się</span><input type="date" data-f="trial_until" value="' + esc(f.trial_until) + '"></label>' : '') + '</div>'
                + (f.trial ? '<p class="bl-hint">Dzień przed końcem zapytamy: zostajesz czy rezygnujesz?</p>' : '');
        }
        if (f.type === 'plan') {
            html += lbl('Umowa') + '<div class="bl-form-group"><label class="bl-f"><span>Umowa do</span><input type="date" data-f="contract_until" value="' + esc(f.contract_until) + '"></label>'
                + '<div class="bl-f"><span>Wypowiedzenie</span>' + stepper('notice_months', f.notice_months ? f.notice_months + ' mies.' : 'brak', 'Miesięcy wypowiedzenia') + '</div>'
                + '<div class="bl-f"><span>Cena promocyjna</span>' + sw('promo', f.promo, 'Cena promocyjna') + '</div>'
                + (f.promo ? '<label class="bl-f"><span>Promocja do</span><input type="date" data-f="promo_until" value="' + esc(f.promo_until) + '"></label>' + field('Potem', 'price_after', f.price_after, 'inputmode="decimal" placeholder="0,00 zł"') : '')
                + field('Numer umowy', 'ref', f.ref, 'placeholder="opcjonalnie" maxlength="60"') + '</div>'
                + '<p class="bl-hint">Przypomnimy o ostatnim dniu na wypowiedzenie' + (f.promo ? ' i o końcu promocji' : '') + '.</p>';
        }
        if (f.type === 'ins') {
            html += lbl('Polisa') + '<div class="bl-form-group"><label class="bl-f"><span>Ochrona do</span><input type="date" data-f="contract_until" value="' + esc(f.contract_until) + '"></label>'
                + '<div class="bl-f"><span>Odnawia się sama</span>' + sw('auto_renew', f.auto_renew, 'Odnawia się sama') + '</div>'
                + field('Numer polisy', 'ref', f.ref, 'placeholder="opcjonalnie" maxlength="60"') + '</div>'
                + '<p class="bl-hint">30 dni przed końcem przypomnimy, że to dobry moment na porównanie ofert.</p>';
        }
        if (f.type === 'pass') {
            html += lbl('Karnet') + chips('pass_kind', [['open', 'Open'], ['entries', 'Na wejścia']])
                + '<div class="bl-form-group" style="margin-top:8px">' + (f.pass_kind === 'entries' ? '<div class="bl-f"><span>Wejść w okresie</span>' + stepper('pass_entries', f.pass_entries, 'Wejść') + '</div>' : '')
                + field('Pojedyncze wejście', 'single_price', f.single_price, 'inputmode="decimal" placeholder="opcjonalnie"') + '</div>'
                + '<p class="bl-hint">Odhaczasz wejścia – pokażemy koszt jednego treningu i porównamy z ceną pojedynczego wejścia.</p>';
        }
        if (f.type === 'bill') {
            html += lbl('Rachunek') + '<div class="bl-form-group"><div class="bl-f"><span>Kwota się zmienia</span>' + sw('amount_variable', f.amount_variable, 'Kwota się zmienia') + '</div></div>'
                + '<p class="bl-hint">' + (f.amount_variable ? 'Kwotę wpiszesz przy opłaceniu. W podsumowaniu liczymy średnią z ostatnich płatności.' : 'Stała kwota, jak przy abonamencie.') + '</p>';
        }
        if (f.type === 'other') {
            html += lbl('Koniec') + '<div class="bl-form-group"><label class="bl-f"><span>Do kiedy</span><input type="date" data-f="contract_until" value="' + esc(f.contract_until) + '"></label></div>';
        }

        if (f.type !== 'prepaid') {
            html += lbl('Najbliższa płatność') + '<div class="bl-form-group"><label class="bl-f"><span>Kiedy</span><input type="date" data-f="next_due" value="' + esc(f.next_due) + '"></label>'
                + (!edit && f.type !== 'loan' ? '<label class="bl-f"><span>Płacę od</span><input type="date" data-f="paid_from" value="' + esc(f.paid_from) + '" max="' + esc(f.next_due) + '"></label>' : '') + '</div>'
                + (!edit && f.type !== 'loan' ? '<p class="bl-hint">„Płacę od” jest opcjonalne – uzupełnimy wcześniejsze płatności, więc statystyki od razu pokażą, ile już wydałeś.</p>' : '');
            if (f.type === 'loan') html += loanCalc(f);
        } else if (!edit) {
            html += '<div class="bl-form-group" style="margin-top:8px"><label class="bl-f"><span>Doładowuję od</span><input type="date" data-f="paid_from" value="' + esc(f.paid_from) + '" max="' + esc(f.next_due) + '"></label></div>';
        }

        html += lbl('Płacę') + chips('pay_mode', [['auto', 'Automatycznie'], ['manual', 'Sam']])
            + '<p class="bl-hint">' + (f.pay_mode === 'auto' ? 'Pobiera się sama (karta, polecenie zapłaty) – tylko informujemy, nic nie trzeba odhaczać.' : 'W dniu płatności pojawi się w „Do zapłaty”, a po zapłaceniu odhaczasz ją jak zadanie.') + '</p>';
        html += lbl('Przypomnij') + chips('reminders', [[0, 'W dniu płatności'], [1, 'Dzień przed'], [3, '3 dni przed'], [7, 'Tydzień przed']].concat(f.type === 'ins' ? [[30, 'Miesiąc przed']] : []), true);

        box.innerHTML = headHTML(edit ? 'Edytuj opłatę' : 'Nowa opłata') + '<div class="fd-scroll" style="padding:4px 20px 24px">' + html + '</div>'
            + '<div class="fd-foot"><button type="button" class="fd-cta fd-press" data-save>' + (edit ? 'Zapisz' : 'Dodaj') + '</button></div>';
        bindHead(box);
        bindForm(box);
    }
    function bindForm(box) {
        var f = S.sheet.f, again = function () { redraw(renderForm); };
        box.querySelectorAll('[data-f]').forEach(function (inp) {
            inp.addEventListener(inp.tagName === 'SELECT' ? 'change' : 'input', function () {
                f[inp.dataset.f] = inp.value;
                if (inp.dataset.f === 'amount' && f.type === 'loan') { var c = box.querySelector('.bl-calc'); if (c) c.outerHTML = loanCalc(f); }
                if (inp.dataset.f === 'next_due' && f.type === 'loan') again();
            });
        });
        box.querySelectorAll('[data-sw]').forEach(function (b) { b.onclick = function () { f[b.dataset.sw] = !f[b.dataset.sw]; again(); }; });
        box.querySelectorAll('[data-cycle]').forEach(function (b) {
            b.onclick = function () {
                var c = { m: ['month', 1], q: ['month', 3], y: ['year', 1], x: [f.cycle_unit === 'day' ? 'day' : 'week', f.cycle_unit === 'day' ? f.cycle_every : 2] }[b.dataset.cycle];
                f.cycle_unit = c[0]; f.cycle_every = c[1];
                again();
            };
        });
        box.querySelectorAll('[data-step]').forEach(function (b) {
            b.onclick = function () {
                var k = b.dataset.step, d = +b.dataset.d, lim = { cycle_every: [1, 400], installments: [2, 600], paid_count: [0, f.installments - 1], notice_months: [0, 12], pass_entries: [1, 100] }[k];
                f[k] = Math.max(lim[0], Math.min(lim[1], (+f[k] || 0) + d));
                if (k === 'installments') f.paid_count = Math.min(f.paid_count, f.installments - 1);
                if (k === 'cycle_every' && f.type === 'prepaid' && !S.sheet.edit) f.next_due = addDays(today(), f.cycle_every);
                again();
            };
        });
        box.querySelectorAll('[data-chip]').forEach(function (b) {
            b.onclick = function () {
                var k = b.dataset.chip, v = k === 'reminders' ? +b.dataset.v : b.dataset.v;
                if (b.dataset.multi) { var i = f[k].indexOf(v); if (i >= 0) f[k].splice(i, 1); else f[k].push(v); }
                else f[k] = v;
                again();
            };
        });
        box.querySelector('[data-save]').onclick = function () { saveForm(this); };
    }
    function saveForm(btn) {
        var f = S.sheet.f, edit = S.sheet.edit;
        if (!f.name.trim()) return fail({ status: 'warning', message: 'Podaj nazwę' });
        var data = {
            type: f.type, name: f.name, amount: f.amount, amount_variable: f.amount_variable ? 1 : '', cycle_unit: f.cycle_unit, cycle_every: f.cycle_every,
            pay_mode: f.pay_mode, reminders: f.reminders.join(','), ref: f.ref,
            trial_until: f.type === 'sub' && f.trial ? f.trial_until : '', contract_until: f.contract_until, notice_months: f.notice_months || '',
            promo_until: f.promo ? f.promo_until : '', price_after: f.promo ? f.price_after : '', auto_renew: f.type === 'ins' ? (f.auto_renew ? 1 : 0) : '',
            installments: f.installments, loan_rate: f.loan_rate, pass_entries: f.pass_kind === 'entries' ? f.pass_entries : '', single_price: f.single_price
        };
        if (edit) {
            data.id = edit;
            if (f.next_due && f.next_due !== f.orig_next) data.next_due = f.next_due;
        } else {
            data.next_due = f.next_due;
            data.paid_from = f.paid_from;
            if (f.type === 'loan') data.paid_count = f.paid_count;
        }
        btn.disabled = true;
        post('scripts/bills/save.php', data).then(function (res) {
            btn.disabled = false;
            if (res.status !== 'success') return fail(res);
            vib();
            toast(res.message);
            S.dirty = false;
            if (edit && S.sheet.hist.length) { S.sheet.hist[S.sheet.hist.length - 1].reload = true; back(); }
            else closeSheet(true);
            load();
        }).catch(function () { btn.disabled = false; fail(); });
    }

    // ---------- szczegóły opłaty ----------
    function renderBill(box) {
        var id = S.sheet.id;
        if (!S.sheet.detail || S.sheet.detail.bill.id !== id || S.sheet.reload) {
            S.sheet.reload = false;
            box.innerHTML = loadingHTML(esc((billOf(id) || {}).name || 'Opłata'));
            bindHead(box);
            getJSON('scripts/bills/bill.php?id=' + id).then(function (res) {
                if (!S.sheet || S.sheet.step !== 'bill' || S.sheet.id !== id) return;
                if (res.status !== 'success') { fail(res); return closeSheet(); }
                S.sheet.detail = res;
                renderBill(el('bl_step'));
            }).catch(function () { fail(); });
            return;
        }
        var d = S.sheet.detail, b = d.bill, st = d.stats, t = typeOf(b.type), html = '';
        var sub = money(b.amount) + ' · ' + b.cycle_label + ' · ' + (b.pay_mode === 'auto' ? 'automatycznie' : 'płacę sam') + (b.ref ? ' · ' + b.ref : '');
        html += '<div style="display:flex;align-items:center;gap:12px;margin:4px 0 14px">' + tile(b.type, 'lg')
            + '<div style="min-width:0"><span class="bl-eyebrow">' + esc(t.label) + (b.status !== 'active' ? ' · ' + (b.status === 'paused' ? 'wstrzymana' : 'zakończona') : '') + '</span>'
            + '<p class="text-sm bl-mut bl-num" style="margin:2px 0 0">' + esc(sub) + '</p></div></div>';
        html += typeHero(d);
        html += nextHTML(d);
        html += itemStatsHTML(d);
        html += historyHTML(d);
        html += '<span class="bl-lbl"><span>Opłata</span></span><div class="bl-acts">'
            + '<button type="button" class="hl-btn hl-btn-soft" data-act="edit">' + small(I.edit, 16) + 'Edytuj</button>'
            + (b.status === 'active' ? '<button type="button" class="hl-btn hl-btn-ghost" data-act="pause">Wstrzymaj</button><button type="button" class="hl-btn hl-btn-ghost" data-act="end">Zakończ</button>'
                : '<button type="button" class="hl-btn hl-btn-ghost" data-act="resume">Wznów</button>')
            + '<button type="button" class="hl-btn hl-btn-danger" data-act="delete">Usuń</button></div>'
            + '<p class="bl-hint">Zakończona opłata znika z listy, ale zostaje w statystykach. Usunięcie kasuje też całą historię płatności.</p>';
        box.innerHTML = headHTML(esc(b.name)) + '<div class="fd-scroll" style="padding:4px 20px 28px">' + html + '</div>' + footFor(d);
        bindHead(box);
        bindBill(box);
    }
    function footFor(d) {
        var b = d.bill, n = d.next[0];
        if (b.status !== 'active') return '';
        if (b.type === 'prepaid' && n) return '<div class="fd-foot"><button type="button" class="fd-cta fd-press" data-pay-next>' + small(I.repeat, 18) + 'Doładowałem</button></div>';
        if (b.type === 'pass') return '<div class="fd-foot"><button type="button" class="fd-cta fd-press" data-visit>' + small(I.check, 18) + 'Odhacz wejście</button></div>';
        return '';
    }
    function typeHero(d) {
        var b = d.bill, st = d.stats, t = today(), html = '';
        if (b.type === 'loan' && st.remaining) {
            var paid = b.installments - st.remaining.count, n = b.installments;
            html += '<div class="bl-card"><div style="display:flex;justify-content:space-between;align-items:flex-end;gap:8px"><div><span class="bl-eyebrow">Zostało do spłaty</span>'
                + '<div class="bl-big bl-num" style="font-size:30px;margin-top:4px">' + num(st.remaining.amount, 2) + '<small> zł</small></div></div>'
                + '<div style="text-align:right"><b class="bl-num" style="font-size:20px;font-weight:600">' + paid + '<span class="bl-mut" style="font-weight:500">/' + n + '</span></b><span class="text-xs bl-mut" style="display:block">' + plural(paid, 'rata zapłacona', 'raty zapłacone', 'rat zapłaconych') + '</span></div></div>'
                + (n <= 72 ? '<div class="bl-dots" style="margin-top:16px;grid-template-columns:repeat(' + Math.min(12, n) + ',minmax(0,1fr))">' + Array.from({ length: n }, function (_, i) { return '<span class="' + (i < paid ? 'is-paid' : i === paid ? 'is-next' : '') + '"></span>'; }).join('') + '</div>'
                    : '<div class="bl-bar" style="margin-top:16px"><span style="width:' + (paid / n * 100) + '%;background:#f59e0b"></span></div>')
                + '<div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px" class="bl-mut"><span>' + shortDate(b.start_date) + '</span><span>Ostatnia: ' + (st.remaining.last_due ? monthYear(st.remaining.last_due) : '–') + '</span></div></div>';
        }
        if (b.type === 'prepaid' && d.next[0]) {
            var due = d.next[0].due, left = Math.max(0, diffDays(t, due)), len = b.cycle_unit === 'day' ? b.cycle_every : 30, warn = left <= 7;
            html += '<div class="bl-card" style="display:flex;align-items:center;gap:16px"><div class="bl-ring"><svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.9155" style="stroke:var(--bl-track)"/>'
                + '<circle cx="18" cy="18" r="15.9155" stroke-linecap="round" stroke-dasharray="' + Math.round(Math.min(1, left / len) * 100) + ' 100" style="stroke:' + (warn ? '#f59e0b' : '#22c55e') + '"/></svg>'
                + '<span><span><b class="bl-num" style="display:block;font-size:22px;font-weight:600">' + left + '</b><span class="bl-mut" style="font-size:10px">' + plural(left, 'dzień', 'dni', 'dni') + '</span></span></span></div>'
                + '<div><span class="bl-eyebrow">Pakiet ważny do</span><b style="display:block;margin-top:2px;font-size:18px;font-weight:600">' + longDate(due) + '</b>'
                + '<span class="text-xs" style="color:' + (warn ? 'var(--bl-warn)' : 'var(--bl-mut)') + '">' + (left === 0 ? 'Kończy się dziś' : 'Kończy się ' + inDays(left)) + '</span></div></div>'
                + '<div class="bl-tiles" style="margin-top:10px"><div><small>Pakiet</small><b>' + money(b.amount) + '</b><small>' + b.cycle_label + '</small></div>'
                + '<div><small>Realnie miesięcznie</small><b>' + money(b.monthly) + '</b><small>średnia z dodatkami</small></div></div>';
        }
        if ((b.type === 'plan' || b.type === 'ins' || b.type === 'other') && b.contract_until) {
            var start = st.first_due || b.start_date, total = Math.max(1, diffDays(start, b.contract_until)), gone = Math.max(0, Math.min(total, diffDays(start, t))), toEnd = diffDays(t, b.contract_until);
            html += '<div class="bl-card"><span class="bl-eyebrow">' + (b.type === 'ins' ? 'Ochrona' : 'Umowa') + '</span>'
                + '<div style="display:flex;align-items:baseline;gap:8px;margin-top:4px"><b class="bl-num" style="font-size:30px;font-weight:600;letter-spacing:-.04em">' + Math.max(0, toEnd) + '</b>'
                + '<span class="text-sm bl-mut">' + (toEnd >= 0 ? plural(toEnd, 'dzień', 'dni', 'dni') + ' do końca · ' + longDate(b.contract_until) : 'zakończona ' + longDate(b.contract_until)) + '</span></div>'
                + '<div class="bl-tl" style="margin-top:16px"><i style="width:' + (gone / total * 100) + '%;background:' + typeOf(b.type).color + '"></i>'
                + (b.notice_deadline ? '<b style="left:' + Math.max(0, Math.min(100, diffDays(start, b.notice_deadline) / total * 100)) + '%" title="Ostatni dzień na wypowiedzenie"></b>' : '') + '</div>'
                + '<div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px" class="bl-mut"><span>' + shortDate(start) + '</span><span>' + shortDate(b.contract_until) + '</span></div>'
                + (b.notice_deadline && b.notice_deadline >= t ? '<div class="bl-warnbox" style="margin-top:12px">' + I.warn + '<span><b style="font-weight:600">Do ' + longDate(b.notice_deadline) + '</b> wypowiedz albo negocjuj – okres wypowiedzenia to ' + b.notice_months + ' ' + plural(b.notice_months, 'miesiąc', 'miesiące', 'miesięcy') + '.</span></div>' : '')
                + '</div>';
            if (b.type !== 'other' && toEnd >= -30) {
                var opts = b.type === 'ins'
                    ? [['renew', 'Odnawiam', 'Wpiszę nową składkę po odnowieniu'], ['quit', 'Rezygnuję', 'Ostatnia płatność przed końcem ochrony'], ['open', 'Porównam oferty', 'Przypomnimy przed końcem ochrony']]
                    : [['renew', 'Przedłużam', 'Wpiszę nową cenę i datę końca'], ['quit', 'Wypowiadam', 'Ostatnia płatność przed końcem umowy'], ['open', 'Zostawiam bez umowy', 'Przejdzie na czas nieokreślony']];
                html += '<span class="bl-lbl"><span>Co dalej?</span></span><div style="display:flex;flex-direction:column;gap:6px">' + opts.map(function (p) {
                    return '<button type="button" class="bl-opt fd-press' + (b.contract_plan === p[0] ? ' is-on' : '') + '" data-plan="' + p[0] + '"><span style="flex:1"><b>' + p[1] + '</b><small>' + p[2] + '</small></span></button>';
                }).join('') + '</div>';
            }
            if (b.promo_until) html += '<div class="bl-warnbox" style="margin-top:10px">' + I.up + '<span>Promocja do <b style="font-weight:600">' + longDate(b.promo_until) + '</b>' + (b.price_after ? ', potem ' + money(b.price_after) : '') + '.</span></div>';
        }
        if (b.type === 'sub') {
            if (b.trial_until && b.trial_until >= t) {
                html += '<div class="bl-warnbox">' + I.clock + '<span>Okres próbny do <b style="font-weight:600">' + longDate(b.trial_until) + '</b>. Potem ' + money(b.amount) + ' ' + b.cycle_label + '.</span></div>'
                    + '<div class="bl-acts" style="margin-top:8px"><button type="button" class="hl-btn hl-btn-primary" data-decide="trial_keep">Zostaję</button><button type="button" class="hl-btn hl-btn-ghost" data-decide="trial_cancel">Rezygnuję</button></div>';
            } else {
                html += '<div class="bl-tiles"><div><small>Miesięcznie</small><b>' + money(b.monthly) + '</b></div><div><small>Rocznie</small><b>' + money(b.monthly * 12) + '</b></div></div>'
                    + '<button type="button" class="bl-opt fd-press" style="margin-top:8px" data-decide="review"><span style="flex:1"><b>Nadal z tego korzystam</b><small>' + (b.review_at ? 'Ostatnio sprawdzone ' + longDate(b.review_at) : 'Raz na kwartał warto sprawdzić, czy subskrypcja jest potrzebna') + '</small></span></button>';
            }
        }
        if (b.type === 'pass') html += visitsHTML(d);
        if (b.type === 'bill' && b.amount_variable && st.count) {
            var last = d.history.filter(function (h) { return h.status === 'paid'; })[0];
            html += '<div class="bl-tiles"><div><small>Średnio</small><b>' + money(b.monthly) + '</b><small>z ostatnich płatności</small></div><div><small>Ostatnio</small><b>' + (last ? money(last.amount) : '–') + '</b><small>' + (last ? longDate(last.due) : '') + '</small></div></div>';
        }
        return html;
    }
    function visitsHTML(d) {
        var b = d.bill, v = d.stats.visits || [], cur = v[v.length - 1] || { count: 0 }, max = Math.max.apply(null, v.map(function (x) { return x.count; }).concat([1]));
        var html = '<div class="bl-tiles"><div><small>Wejść w tym miesiącu</small><b>' + cur.count + '</b><small>' + (b.pass_entries ? 'z ' + b.pass_entries + ' w karnecie' : 'karnet open') + '</small></div>'
            + '<div><small>Koszt jednego</small><b>' + (cur.per_visit ? money(cur.per_visit) : '–') + '</b><small>' + (b.single_price ? 'pojedyncze ' + money(b.single_price) : 'w tym miesiącu') + '</small></div></div>';
        if (v.some(function (x) { return x.count; })) {
            html += '<span class="bl-lbl"><span>Czy się opłaca?</span></span><div class="bl-card" style="padding:6px 16px">' + v.map(function (x) {
                var bad = b.single_price && x.per_visit && x.per_visit > b.single_price;
                return '<div class="bl-kv"><span style="width:40px;flex:none">' + MONTHS_SHORT[+x.ym.slice(5) - 1] + '</span><span class="bl-bar" style="flex:1;height:8px;margin:0 10px"><span style="width:' + (x.count / max * 100) + '%;background:#ec4899"></span></span>'
                    + '<span class="bl-num" style="width:60px;color:var(--hl-ink-2);font-weight:400">' + x.count + ' ' + plural(x.count, 'wejście', 'wejścia', 'wejść') + '</span>'
                    + '<span class="bl-num" style="width:76px;' + (bad ? 'color:var(--bl-warn);font-weight:600' : '') + '">' + (x.per_visit ? money(x.per_visit) : '–') + '</span></div>';
            }).join('') + '</div>' + (b.single_price ? '<p class="bl-hint">Na pomarańczowo miesiące, w których jedno wejście wyszło drożej niż pojedynczy bilet (' + money(b.single_price) + ').</p>' : '');
        }
        return html;
    }
    function nextHTML(d) {
        var b = d.bill, t = today();
        if (!d.next.length || b.status !== 'active') return '';
        return '<span class="bl-lbl"><span>' + (b.type === 'prepaid' ? 'Następne doładowanie' : 'Najbliższe płatności') + '</span></span><div class="bl-card" style="padding:4px 16px">'
            + d.next.slice(0, b.type === 'prepaid' ? 1 : 4).map(function (u, i) {
                var isNow = u.due <= t, manual = b.pay_mode === 'manual' || u.overdue;
                var acts = (manual && i === 0 && b.type !== 'prepaid' ? '<button type="button" class="bl-mini-btn fd-press" data-skip="' + u.due + '" title="Pomiń tę płatność">Pomiń</button>'
                    + '<button type="button" class="bl-mini-btn is-dark fd-press" data-pay-due="' + u.due + '">' + small(I.check, 13) + 'Zapłacone</button>' : '')
                    + (!manual && i === 0 && b.type !== 'prepaid' ? '<button type="button" class="bl-mini-btn fd-press" data-skip="' + u.due + '" title="Pomiń tę płatność">Pomiń tę płatność</button>' : '');
                return '<div class="bl-next' + (isNow ? ' is-today' : '') + '"><span class="bl-next-n bl-num">' + (u.installment || MONTHS_SHORT[s2d(u.due).getMonth()]) + '</span>'
                    + '<span style="flex:1;min-width:0"><b style="display:block;font-size:14px;font-weight:500">' + (u.overdue ? 'Zaległa · ' : '') + whenLabel(u.due).replace(/^./, function (c) { return c.toUpperCase(); }) + '</b>'
                    + '<span class="text-xs bl-mut">' + longDate(u.due) + (u.installment ? ' · rata ' + u.installment : '') + '</span></span>'
                    + '<span class="bl-num" style="font-size:14px;font-weight:600">' + (u.estimate ? '~' : '') + money(u.amount) + '</span>'
                    + (acts ? '<div class="bl-next-acts">' + acts + '</div>' : '') + '</div>';
            }).join('') + '</div>';
    }
    // statystyki pozycji: suma narastająca, średnia, prognozy, zmiany ceny
    function itemStatsHTML(d) {
        var st = d.stats, b = d.bill;
        if (!st.count) return '';
        var html = '<span class="bl-lbl"><span>Wydane łącznie</span><span>od ' + shortDate(st.first_due) + '</span></span><div class="bl-card">'
            + '<div class="bl-big bl-num" style="font-size:32px">' + num(Math.round(st.total), 0) + '<small> zł</small></div>'
            + '<p class="text-xs bl-mut" style="margin:6px 0 0">' + st.count + ' ' + plural(st.count, 'płatność', 'płatności', 'płatności') + (st.avg_monthly ? ' · średnio <b class="bl-num" style="color:var(--hl-ink);font-weight:600">' + money0(st.avg_monthly) + '</b> miesięcznie' : '') + '</p>';
        if (st.series.length >= 2) html += cumChart(st.series, typeOf(b.type).color);
        html += '</div>';
        if (b.status === 'active') {
            html += '<div class="bl-tiles" style="margin-top:10px"><div><small>Kolejne 12 miesięcy</small><b>' + money0(st.next_12) + '</b><small>przy obecnej kwocie</small></div>'
                + '<div><small>' + (st.remaining ? 'Do końca spłaty' : 'Za 5 lat') + '</small><b>' + money0(st.remaining ? st.remaining.amount : st.next_60) + '</b><small>' + (st.remaining ? st.remaining.count + ' ' + plural(st.remaining.count, 'rata', 'raty', 'rat') : 'jeśli nic się nie zmieni') + '</small></div></div>';
        }
        var pr = st.prices || [];
        if (pr.length > 1) {
            var a = pr[pr.length - 2], z = pr[pr.length - 1], pct = a.amount ? Math.round((z.amount - a.amount) / a.amount * 1000) / 10 : 0;
            html += '<div class="bl-warnbox" style="margin-top:10px">' + (z.amount >= a.amount ? I.up : I.down) + '<span>Cena ' + money(a.amount) + ' → <b style="font-weight:600">' + money(z.amount) + '</b> od ' + longDate(z.valid_from)
                + ' (' + (pct > 0 ? '+' : '') + String(pct).replace('.', ',') + '%)' + (pr.length > 2 ? ' · ' + (pr.length - 1) + ' zmiany ceny' : '') + '</span></div>';
        }
        if (st.estimated) html += '<p class="bl-hint">' + st.estimated + ' ' + plural(st.estimated, 'płatność uzupełniona', 'płatności uzupełnione', 'płatności uzupełnionych') + ' wstecz według ceny – to szacunek.</p>';
        return html;
    }
    function cumChart(series, color) {
        var n = series.length, max = series[n - 1].cum, step = max > 20000 ? 10000 : max > 5000 ? 2000 : max > 2000 ? 1000 : max > 500 ? 250 : 100;
        var top = Math.ceil(max / step) * step || 1;
        var x = function (i) { return n === 1 ? 50 : i / (n - 1) * 100; }, y = function (v) { return 100 - v / top * 100; };
        var line = series.map(function (p, i) { return (i ? 'L' : 'M') + x(i).toFixed(2) + ' ' + y(p.cum).toFixed(2); }).join(' ');
        var grid = [0, top / 2, top].map(function (v) { return '<div class="bl-gl is-in" style="bottom:' + (v / top * 100) + '%"><span class="bl-num">' + (v ? num(v, 0) : '') + '</span></div>'; }).join('');
        return '<div class="bl-plot" style="margin-top:28px" data-cum data-top="' + top + '" role="img" aria-label="Suma wydatków rośnie do ' + money0(max) + '">' + grid
            + '<svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><path d="' + line + ' L100 100 L0 100 Z" style="fill:' + color + ';opacity:.12"/>'
            + '<path d="' + line + '" fill="none" stroke="' + color + '" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"/></svg>'
            + '<span class="bl-xh" hidden></span><span class="bl-pt" hidden style="background:' + color + '"></span><span class="bl-tip bl-num" hidden></span>'
            + '<div class="bl-hit">' + series.map(function (p, i) { return '<button type="button" data-i="' + i + '" aria-label="' + shortDate(p.due) + ': ' + money(p.amount) + ', razem ' + money(p.cum) + '"></button>'; }).join('') + '</div></div>'
            + '<div class="bl-axis"><span>' + shortDate(series[0].due) + '</span><span>' + shortDate(series[n - 1].due) + '</span></div>';
    }
    function bindCum(box, series) {
        var plot = box.querySelector('[data-cum]');
        if (!plot) return;
        var n = series.length, top = +plot.dataset.top || series[n - 1].cum;
        var show = function (i) {
            var p = series[i], left = n === 1 ? 50 : i / (n - 1) * 100;
            plot.querySelector('.bl-xh').hidden = plot.querySelector('.bl-pt').hidden = plot.querySelector('.bl-tip').hidden = false;
            plot.querySelector('.bl-xh').style.left = left + '%';
            var pt = plot.querySelector('.bl-pt');
            pt.style.left = left + '%';
            pt.style.top = (100 - p.cum / top * 100) + '%';
            var tip = plot.querySelector('.bl-tip');
            tip.textContent = shortDate(p.due) + ' · ' + money(p.amount) + ' · razem ' + money0(p.cum);
            tip.style.left = Math.max(0, Math.min(100 - tip.offsetWidth / plot.offsetWidth * 100, left - tip.offsetWidth / plot.offsetWidth * 50)) + '%';
        };
        plot.querySelectorAll('.bl-hit button').forEach(function (b) {
            b.addEventListener('mouseenter', function () { show(+b.dataset.i); });
            b.addEventListener('click', function () { show(+b.dataset.i); });
            b.addEventListener('focus', function () { show(+b.dataset.i); });
        });
        show(n - 1);
    }
    function historyHTML(d) {
        var h = d.history.slice(0, 12);
        if (!h.length) return '';
        return '<span class="bl-lbl"><span>Historia</span><span>' + (d.history.length > 12 ? 'ostatnie 12' : '') + '</span></span><div class="bl-card" style="padding:2px 16px">' + h.map(function (p) {
            var src = p.status === 'skipped' ? 'pominięta' : p.source === 'estimate' ? 'szacunek' : p.source === 'auto' ? 'auto' : '';
            return '<div class="bl-hist"><div style="flex:1;min-width:0"><b style="display:block;font-size:14px;font-weight:500">' + longDate(p.due) + (src ? '<span class="bl-src">' + src + '</span>' : '') + '</b>'
                + (p.installment ? '<span class="text-xs bl-mut">Rata ' + p.installment + '</span>' : (p.paid_on && p.paid_on !== p.due ? '<span class="text-xs bl-mut">zapłacono ' + shortDate(p.paid_on) + '</span>' : ''))
                + (p.extras.length ? '<div>' + p.extras.map(function (e) { return '<span class="bl-ex">+ ' + esc(e.label) + ' · ' + money(e.amount) + '</span>'; }).join('') + '</div>' : '') + '</div>'
                + '<span class="bl-num" style="font-size:14px;font-weight:600;' + (p.status === 'skipped' ? 'opacity:.4' : '') + '">' + money(p.amount) + '</span></div>';
        }).join('') + '</div>';
    }
    function bindBill(box) {
        var d = S.sheet.detail, b = d.bill, id = b.id;
        var after = function (res, closeAfter) {
            if (res.status !== 'success') return fail(res);
            vib();
            toast(res.message);
            if (closeAfter) { closeSheet(true); return load(); }
            S.sheet.reload = true;
            renderBill(el('bl_step'));
            load();
        };
        bindCum(box, d.stats.series);
        box.querySelectorAll('[data-act]').forEach(function (btn) {
            btn.onclick = function () {
                var a = btn.dataset.act;
                if (a === 'edit') return go('form', { f: formFromBill(b, d.next[0] ? d.next[0].due : ''), edit: id });
                if (a === 'delete' && !btn.classList.contains('bl-confirm')) {
                    btn.classList.add('bl-confirm'); btn.textContent = 'Na pewno usunąć?';
                    setTimeout(function () { if (btn.isConnected) { btn.classList.remove('bl-confirm'); btn.textContent = 'Usuń'; } }, 4000);
                    return;
                }
                btn.disabled = true;
                post('scripts/bills/status.php', { id: id, action: a }).then(function (res) { after(res, a === 'delete' || a === 'end'); }).catch(function () { btn.disabled = false; fail(); });
            };
        });
        box.querySelectorAll('[data-plan]').forEach(function (btn) {
            btn.onclick = function () {
                var v = b.contract_plan === btn.dataset.plan ? '' : btn.dataset.plan;
                post('scripts/bills/decide.php', { id: id, action: 'plan', value: v }).then(function (res) { after(res); });
            };
        });
        box.querySelectorAll('[data-decide]').forEach(function (btn) {
            btn.onclick = function () { post('scripts/bills/decide.php', { id: id, action: btn.dataset.decide }).then(function (res) { after(res, btn.dataset.decide === 'trial_cancel'); }); };
        });
        box.querySelectorAll('[data-pay-due]').forEach(function (btn) {
            btn.onclick = function () {
                if (b.amount_variable) return go('pay', { due: btn.dataset.payDue });
                btn.disabled = true;
                post('scripts/bills/pay.php', { bill_id: id, due: btn.dataset.payDue }).then(function (res) {
                    if (res.status !== 'success') { btn.disabled = false; return fail(res); }
                    vib();
                    toast(res.message, function () { undoPay({ bill_id: id, due: btn.dataset.payDue }, b, true); });
                    S.sheet.reload = true; renderBill(el('bl_step')); load();
                });
            };
        });
        box.querySelectorAll('[data-skip]').forEach(function (btn) {
            btn.onclick = function () {
                post('scripts/bills/pay.php', { bill_id: id, due: btn.dataset.skip, action: 'skip' }).then(function (res) {
                    if (res.status !== 'success') return fail(res);
                    toast(res.message, function () { undoPay({ bill_id: id, due: btn.dataset.skip }, b, true); });
                    S.sheet.reload = true; renderBill(el('bl_step')); load();
                });
            };
        });
        var pn = box.querySelector('[data-pay-next]');
        if (pn) pn.onclick = function () { go('pay', { due: d.next[0].due }); };
        var vb = box.querySelector('[data-visit]');
        if (vb) vb.onclick = function () {
            vb.disabled = true;
            post('scripts/bills/visit.php', { bill_id: id }).then(function (res) {
                vb.disabled = false;
                if (res.status !== 'success') return fail(res);
                vib();
                toast('Wejście ' + res.count + ' w tym miesiącu' + (res.per_visit ? ' · ' + money(res.per_visit) + ' za trening' : ''), function () {
                    post('scripts/bills/visit.php', { bill_id: id, action: 'remove' }).then(function () { S.sheet && (S.sheet.reload = true) && renderBill(el('bl_step')); });
                });
                S.sheet.reload = true; renderBill(el('bl_step'));
            }).catch(function () { vb.disabled = false; fail(); });
        };
    }

    // ---------- płatność / doładowanie ----------
    function renderPay(box) {
        var id = S.sheet.id;
        if (!S.sheet.detail || S.sheet.detail.bill.id !== id) {
            box.innerHTML = loadingHTML('Płatność');
            bindHead(box);
            getJSON('scripts/bills/bill.php?id=' + id).then(function (res) {
                if (!S.sheet || S.sheet.step !== 'pay') return;
                if (res.status !== 'success') { fail(res); return closeSheet(); }
                S.sheet.detail = res;
                renderPay(el('bl_step'));
            });
            return;
        }
        var d = S.sheet.detail, b = d.bill, due = S.sheet.due, prepaid = b.type === 'prepaid';
        if (!S.sheet.p) {
            var u = d.next.filter(function (x) { return x.due === due; })[0];
            S.sheet.p = { amount: b.amount_variable ? '' : num(u ? u.amount : b.amount, 2).replace(/ /g, ''), extras: [], paid_on: today(), customLabel: '', customAmount: '' };
        }
        var p = S.sheet.p, sugg = d.extra_suggestions || [];
        var total = (parseAmount(p.amount) || 0) + p.extras.reduce(function (a, e) { return a + e[1]; }, 0);
        var html = '<p class="text-sm bl-mut" style="margin:0 0 14px">' + esc(b.name) + ' · ' + (prepaid ? 'pakiet ważny do ' : 'termin ') + longDate(due) + '</p>'
            + '<div class="bl-form-group">' + field(prepaid ? 'Pakiet' : 'Kwota', 'amount', p.amount, 'inputmode="decimal" placeholder="' + (b.amount_variable ? 'kwota z rachunku' : '0,00 zł') + '" class="bl-num"') + '</div>'
            + (b.amount_variable && b.monthly ? '<p class="bl-hint">Średnio ' + money(b.monthly) + ' – wpisz kwotę z rachunku.</p>' : '');
        if (prepaid || sugg.length) {
            var all = sugg.map(function (s) { return [s.label, s.amount]; });
            p.extras.forEach(function (e) { if (!all.some(function (a) { return a[0] === e[0] && a[1] === e[1]; })) all.push(e); });
            html += '<span class="bl-lbl"><span>Dodatkowo dokupiłem</span></span><div style="display:flex;flex-direction:column;gap:6px">' + all.map(function (e, i) {
                var on = p.extras.some(function (x) { return x[0] === e[0] && x[1] === e[1]; });
                return '<button type="button" class="bl-opt fd-press' + (on ? ' is-on' : '') + '" data-ex="' + i + '"><span style="flex:1"><b>' + esc(e[0]) + '</b></span><span class="bl-num" style="font-weight:500">+' + money(e[1]) + '</span></button>';
            }).join('')
                + '<div class="bl-form-group"><label class="bl-f"><span>Inny dodatek</span><input data-c="customLabel" value="' + esc(p.customLabel) + '" placeholder="np. Internet 10 GB" maxlength="80" style="flex:1.5"></label>'
                + '<div class="bl-f"><span>Kwota</span><input data-c="customAmount" value="' + esc(p.customAmount) + '" inputmode="decimal" placeholder="0,00 zł"><button type="button" class="bl-mini-btn fd-press" data-addex>Dodaj</button></div></div></div>'
                + (sugg.length ? '<p class="bl-hint">Podpowiadamy dodatki z Twoich poprzednich doładowań.</p>' : '');
            S.sheet.allExtras = all;
        }
        var y = addDays(today(), -1);
        html += '<span class="bl-lbl"><span>Zapłacono</span></span><div class="fd-chips" style="flex-wrap:wrap;margin:0;padding:0">'
            + [[today(), 'Dziś'], [y, 'Wczoraj']].map(function (o) { return '<button type="button" class="fd-chip fd-press' + (p.paid_on === o[0] ? ' is-on' : '') + '" data-on="' + o[0] + '">' + o[1] + '</button>'; }).join('')
            + '<label class="fd-chip' + (p.paid_on !== today() && p.paid_on !== y ? ' is-on' : '') + '" style="display:inline-flex;align-items:center;gap:6px">Inny dzień<input type="date" data-pdate value="' + esc(p.paid_on) + '" max="' + today() + '" style="width:118px;border:0;outline:0;background:transparent;color:inherit;font:inherit"></label></div>'
            + (prepaid ? '<p class="bl-hint">Kolejny termin policzymy od dnia doładowania.</p>' : '');
        html += '<div style="display:flex;align-items:baseline;justify-content:space-between;margin:18px 4px 4px"><span class="text-sm" style="color:var(--hl-ink-2)">Razem</span>'
            + '<span class="bl-big bl-num" style="font-size:28px" data-total>' + num(total, 2) + '<small> zł</small></span></div>';
        box.innerHTML = headHTML(prepaid ? 'Doładowanie' : 'Płatność') + '<div class="fd-scroll" style="padding:4px 20px 24px">' + html + '</div>'
            + '<div class="fd-foot"><button type="button" class="fd-cta fd-press" data-savepay>' + (prepaid ? 'Zapisz doładowanie' : 'Zapisz płatność') + '</button></div>';
        bindHead(box);
        var again = function () { redraw(renderPay); };
        var upd = function () {
            var t = (parseAmount(p.amount) || 0) + p.extras.reduce(function (a, e) { return a + e[1]; }, 0);
            box.querySelector('[data-total]').innerHTML = num(t, 2) + '<small> zł</small>';
        };
        var a = box.querySelector('[data-f=amount]');
        a.addEventListener('input', function () { p.amount = a.value; upd(); });
        if (b.amount_variable) setTimeout(function () { a.focus(); }, 350);
        box.querySelectorAll('[data-c]').forEach(function (inp) { inp.addEventListener('input', function () { p[inp.dataset.c] = inp.value; }); });
        box.querySelectorAll('[data-ex]').forEach(function (btn) {
            btn.onclick = function () {
                var e = S.sheet.allExtras[+btn.dataset.ex], i = -1;
                p.extras.forEach(function (x, j) { if (x[0] === e[0] && x[1] === e[1]) i = j; });
                if (i >= 0) p.extras.splice(i, 1); else p.extras.push(e);
                again();
            };
        });
        var add = box.querySelector('[data-addex]');
        if (add) add.onclick = function () {
            var v = parseAmount(p.customAmount);
            if (!p.customLabel.trim() || !v || v <= 0) return fail({ status: 'warning', message: 'Podaj nazwę i kwotę dodatku' });
            p.extras.push([p.customLabel.trim(), Math.round(v * 100) / 100]);
            p.customLabel = p.customAmount = '';
            again();
        };
        box.querySelectorAll('[data-on]').forEach(function (btn) { btn.onclick = function () { p.paid_on = btn.dataset.on; again(); }; });
        var pd = box.querySelector('[data-pdate]');
        pd.addEventListener('change', function () { if (pd.value) { p.paid_on = pd.value; again(); } });
        box.querySelector('[data-savepay]').onclick = function () {
            var btn = this;
            if (b.amount_variable && !parseAmount(p.amount)) return fail({ status: 'warning', message: 'Podaj kwotę z rachunku' });
            btn.disabled = true;
            post('scripts/bills/pay.php', { bill_id: b.id, due: due, action: 'paid', amount: p.amount, extras: JSON.stringify(p.extras), paid_on: p.paid_on }).then(function (res) {
                btn.disabled = false;
                if (res.status !== 'success') return fail(res);
                vib();
                toast(res.message, function () { undoPay({ bill_id: b.id, due: due }, b, true); });
                if (S.sheet.hist.length) { S.sheet.p = null; var prev = S.sheet.hist[S.sheet.hist.length - 1]; prev.reload = true; back(); }
                else closeSheet(true);
                load();
            }).catch(function () { btn.disabled = false; fail(); });
        };
    }

    // ---------- statystyki ----------
    var PERIODS = [['12', '12 miesięcy'], ['year', 'Ten rok'], ['all', 'Od początku']];
    function renderStats(box) {
        var period = S.sheet.period || '12', r = (S.sheet.stc || {})[period];
        if (!r) {
            box.innerHTML = loadingHTML('Statystyki');
            bindHead(box);
            loadStats(period, function () { redraw(renderStats); });
            return;
        }
        box.innerHTML = headHTML('Statystyki') + '<div class="fd-scroll" style="padding:4px 20px 28px">' + segHTML(PERIODS, period, 'period', 'margin-bottom:12px')
            + '<div class="bl-st-body" id="bl_st_body">' + statsHTML(r, period) + '</div></div>';
        bindHead(box);
        box.querySelectorAll('[data-period]').forEach(function (b) { b.onclick = function () { setPeriod(b.dataset.period); }; });
        bindStats(box);
    }
    function loadStats(period, done) {
        getJSON('scripts/bills/stats.php?period=' + period).then(function (res) {
            if (!S.sheet || S.sheet.step !== 'stats') return;
            if (res.status !== 'success') { var body = el('bl_st_body'); if (body) body.classList.remove('is-loading'); return fail(res); }
            (S.sheet.stc = S.sheet.stc || {})[period] = res;
            if ((S.sheet.period || '12') === period) done();
        });
    }
    // zmiana okresu: wskaźnik jedzie od razu, treść wjeżdża z boku (z cache albo po pobraniu)
    function setPeriod(p) {
        var from = segIndex(PERIODS, S.sheet.period || '12'), to = segIndex(PERIODS, p);
        if (from === to) return;
        S.sheet.period = p;
        S.sheet.sel = null;
        moveSeg(el('bl_step').querySelector('.bl-seg'), to);
        var show = function () {
            var body = el('bl_st_body');
            if (!body) return;
            body.classList.remove('is-loading');
            body.innerHTML = statsHTML(S.sheet.stc[p], p);
            bindStats(body);
            slideIn(body, to > from);
        };
        if (S.sheet.stc && S.sheet.stc[p]) show();
        else { el('bl_st_body').classList.add('is-loading'); loadStats(p, show); }
    }
    function statsHTML(r, period) {
        var st = r.stats, types = r.types, keys = Object.keys(st.months), html = '';
        var totals = keys.map(function (k) { return Object.keys(st.months[k]).reduce(function (a, t) { return a + st.months[k][t]; }, 0); });
        if (S.sheet.sel == null || S.sheet.sel >= keys.length) S.sheet.sel = keys.length - 1;
        var sel = S.sheet.sel, max = Math.max.apply(null, totals.concat([1]));
        var step = max > 20000 ? 10000 : max > 6000 ? 2000 : max > 3000 ? 1000 : max > 1200 ? 500 : 250, top = Math.ceil(max / (2 * step)) * 2 * step;
        var label = { '12': 'Wydane przez ostatnie 12 miesięcy', year: 'Wydane w tym roku', all: 'Wydane od ' + monthYearGen(st.from) }[period];
        var diff = st.total - st.prev_total;
        html += '<div class="bl-card"><span class="bl-eyebrow">' + label + '</span><div class="bl-big bl-num" style="font-size:38px;margin-top:6px">' + num(Math.round(st.total), 0) + '<small> zł</small></div>'
            + '<p class="text-sm" style="margin:6px 0 0;color:var(--hl-ink-2)">To <b class="bl-num" style="color:var(--hl-ink);font-weight:600">' + money(st.per_day) + '</b> każdego dnia'
            + (period !== 'all' && st.prev_total ? ' · <span style="color:' + (diff > 0 ? 'var(--bl-warn)' : '#16a34a') + ';font-weight:500">' + (diff > 0 ? '+' : '−') + money0(Math.abs(diff)) + ' niż ' + (period === 'year' ? 'w tym samym okresie rok wcześniej' : 'rok wcześniej') + '</span>' : '') + '</p>';
        if (keys.length) {
            var dense = keys.length > 18;
            html += '<div class="bl-cols" style="margin-top:22px" role="img" aria-label="Płatności w kolejnych miesiącach, od ' + money0(Math.min.apply(null, totals)) + ' do ' + money0(max) + '">'
                + [0, top / 2, top].map(function (v) { return '<div class="bl-gl" style="bottom:' + (v / top * 100) + '%"><span class="bl-num">' + (v ? (v >= 1000 ? num(v / 1000, v % 1000 ? 1 : 0) + ' tys.' : num(v, 0)) : '') + '</span></div>'; }).join('')
                + '<div class="bl-cols-in" style="gap:' + (dense ? 2 : 6) + 'px">' + keys.map(function (k, i) {
                    var m = st.months[k];
                    return '<button type="button" class="bl-col' + (i === sel ? '' : ' is-dim') + '" data-col="' + i + '" aria-label="' + MONTHS_NOM[+k.slice(5) - 1] + ' ' + k.slice(0, 4) + ': ' + money(totals[i]) + '">'
                        + '<span class="bl-col-stack" style="height:' + (totals[i] / top * 100) + '%">' + Object.keys(m).map(function (t) { return '<i style="flex-grow:' + Math.round(m[t]) + ';flex-basis:0;background:' + types[t].color + '"></i>'; }).join('') + '</span></button>';
                }).join('') + '</div></div>'
                + '<div class="bl-xl" style="gap:' + (dense ? 2 : 6) + 'px">' + keys.map(function (k, i) {
                    var show = !dense || i % Math.ceil(keys.length / 8) === 0 || i === keys.length - 1;
                    return '<span class="' + (i === sel ? 'is-on' : '') + '">' + (show ? MONTHS_SHORT[+k.slice(5) - 1] + (k.slice(5) === '01' && keys.length > 12 ? ' ' + k.slice(2, 4) : '') : '') + '</span>';
                }).join('') + '</div>';
            var sm = st.months[keys[sel]];
            html += '<div class="bl-readout"><div style="display:flex;justify-content:space-between;align-items:baseline"><b style="font-size:13px;font-weight:600">' + MONTHS_NOM[+keys[sel].slice(5) - 1] + ' ' + keys[sel].slice(0, 4) + '</b>'
                + '<b class="bl-num" style="font-size:15px;font-weight:600">' + money(totals[sel]) + '</b></div>'
                + '<div class="bl-legend" style="margin-top:6px">' + Object.keys(sm).map(function (t) { return '<span><i style="background:' + types[t].color + '"></i>' + esc(types[t].label) + ' <b class="bl-num">' + money0(sm[t]) + '</b></span>'; }).join('') + '</div></div>';
        }
        html += '</div>';
        var bt = Object.keys(st.by_type), first = bt.length ? st.by_type[bt[0]] : 1;
        if (bt.length) {
            html += '<span class="bl-lbl"><span>Gdzie idą pieniądze</span></span><div class="bl-card bl-rank">' + bt.map(function (t) {
                var v = st.by_type[t];
                return '<div><div style="display:flex;justify-content:space-between;align-items:baseline;font-size:13px"><span class="bl-legend" style="margin:0;font-size:13px;color:var(--hl-ink)"><span><i style="background:' + types[t].color + '"></i>' + esc(types[t].label) + '</span></span>'
                    + '<span><b class="bl-num" style="font-weight:600">' + money0(v) + '</b> <span class="bl-num bl-mut" style="font-size:12px">' + Math.round(v / st.total * 100) + '%</span></span></div>'
                    + '<div class="bl-bar" style="margin-top:6px"><span style="width:' + (v / first * 100) + '%;background:' + types[t].color + '"></span></div></div>';
            }).join('') + '</div>';
        }
        if (r.top.length) {
            html += '<span class="bl-lbl"><span>Najdroższe pozycje</span><span>od początku</span></span><div class="hl-card bl-group">' + r.top.map(function (t, i) {
                return '<button type="button" class="bl-li" data-bill="' + t.bill_id + '"><span class="bl-num bl-mut" style="width:18px;font-size:13px;font-weight:600">' + (i + 1) + '</span>'
                    + '<span style="flex:1;min-width:0"><span class="bl-row-name">' + esc(t.name) + '</span><span class="bl-row-meta">od ' + monthYearGen(t.first_due) + ' · ' + t.count + ' ' + plural(t.count, 'płatność', 'płatności', 'płatności') + (t.status === 'ended' ? ' · zakończona' : '') + '</span></span>'
                    + '<span class="bl-num" style="font-size:14px;font-weight:600">' + money0(t.total) + '</span>' + small(I.chev, 16).replace('<svg', '<svg style="color:var(--bl-mut)"') + '</button>';
            }).join('') + '</div>';
        }
        var ins = [];
        if (r.price_changes.length) {
            var names = r.price_changes.map(function (c) { return c.name + ' (' + (c.to > c.from ? '+' : '−') + money0(Math.abs(c.to - c.from)) + ')'; });
            var up = r.price_changes_yearly > 0;
            ins.push([up ? I.up : I.down, up ? '#f59e0b' : '#16a34a', r.price_changes.length + ' ' + plural(r.price_changes.length, 'opłata zmieniła', 'opłaty zmieniły', 'opłat zmieniło') + ' cenę w ostatnim roku',
                names.join(', ') + ' – razem <b class="bl-num">' + (up ? '+' : '−') + money0(Math.abs(r.price_changes_yearly)) + '</b> rocznie.']);
        }
        if (r.subscriptions_5y > 0) ins.push([svg(TYPE_ICON.sub, 2), '#6366f1', 'Subskrypcje przez 5 lat: ' + money0(r.subscriptions_5y), 'Przy dzisiejszych cenach. Warto co jakiś czas sprawdzić, z których naprawdę korzystasz.']);
        if (r.loan_end) {
            var le = r.loan_end, months = Math.max(0, Math.round(diffDays(today(), le.last_due) / 30.44));
            ins.push([I.down, '#16a34a', (months ? 'Za ' + months + ' ' + plural(months, 'miesiąc', 'miesiące', 'miesięcy') + ' ' : 'Wkrótce ') + '−' + money0(le.monthly) + ' miesięcznie',
                monthYear(le.last_due).replace(/^./, function (c) { return c.toUpperCase(); }) + ' spłacisz: ' + esc(le.name) + '. Do tego czasu jeszcze ' + money0(le.left_amount) + '.']);
        }
        if (ins.length) {
            html += '<span class="bl-lbl"><span>Do przemyślenia</span></span><div style="display:flex;flex-direction:column;gap:8px">' + ins.map(function (x) {
                return '<div class="bl-ins"><span class="bl-ins-ic" style="color:' + x[1] + '">' + x[0] + '</span><div><b>' + x[2] + '</b><span>' + x[3] + '</span></div></div>';
            }).join('') + '</div>';
        }
        html += '<p class="bl-note">Wykres pokazuje faktyczne płatności w danym miesiącu (np. roczna polisa w całości w miesiącu zapłaty). Płatności uzupełnione wstecz są szacunkiem.</p>';
        return html;
    }
    function bindStats(box) {
        box.querySelectorAll('[data-col]').forEach(function (b) {
            var pick = function () { if (S.sheet.sel !== +b.dataset.col) { S.sheet.sel = +b.dataset.col; redraw(renderStats); } };
            b.addEventListener('click', pick);
            b.addEventListener('mouseenter', pick);
        });
        box.querySelectorAll('[data-bill]').forEach(function (b) { b.onclick = function () { go('bill', { id: +b.dataset.bill, detail: null }); }; });
    }
})();
