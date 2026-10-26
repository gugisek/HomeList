// Toasty (alerty) – showAlert(type, message) dla panelu i stron logowania. Style: css/toast.css
// Stos powiadomień: najnowszy z przodu, starsze schowane za nim. Najechanie (albo dotknięcie) rozwija stos i wstrzymuje odliczanie,
// przesunięcie w bok zamyka toast. Na dole ekranu stos omija pływający pasek list ([data-toast-dock]) i klawiaturę.
(function () {
    const DURATION = 5000;
    const VISIBLE = 3;   // ile toastów widać w stosie
    const MAX = 6;       // ile trzymamy naraz, starsze są zamykane
    const GAP = 10;      // odstęp w rozwiniętym stosie
    const PEEK = 14;     // o ile starsze toasty wystają spod najnowszego
    const SWIPE = 70;    // próg przesunięcia, które zamyka toast
    const MIN_LEFT = 1200;
    const TITLES = { success: 'Gotowe', error: 'Coś poszło nie tak', warning: 'Uwaga', info: 'Informacja' };
    const svg = (d) => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path pathLength="1" stroke-linecap="round" stroke-linejoin="round" d="' + d + '"/></svg>';
    const ICONS = {
        success: svg('M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'),
        error: svg('M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z'),
        warning: svg('M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'),
        info: svg('m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z'),
    };
    const CLOSE = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';

    const toasts = [];   // najnowszy pierwszy: { el, h, left, started, timer }
    let box = null, expanded = false, paused = false;

    function container() {
        if (box && box.isConnected) return box;
        box = document.getElementById('alertsContainer');
        if (!box) {
            box = document.createElement('div');
            box.id = 'alertsContainer';
            document.body.appendChild(box);
        }
        box.classList.add('hl-toasts');
        box.setAttribute('aria-live', 'polite');
        box.addEventListener('mouseenter', () => setExpanded(true));
        box.addEventListener('mouseleave', () => setExpanded(false));
        return box;
    }

    // podniesienie stosu nad klawiaturę albo pasek list, jeśli zachodzą na siebie
    function lift() {
        let px = 0;
        const vv = window.visualViewport;
        if (vv) px = Math.max(0, Math.round(innerHeight - vv.height - vv.offsetTop));
        const dock = px ? null : document.querySelector('[data-toast-dock]');
        if (dock) {
            const d = dock.getBoundingClientRect(), b = box.getBoundingClientRect();
            if (d.height && d.left < b.right && d.right > b.left) {
                const base = parseFloat(getComputedStyle(box).bottom) || 0;
                px = Math.max(0, Math.round(innerHeight - d.top + 12 - base));
            }
        }
        box.style.setProperty('--hl-lift', px + 'px');
    }

    function layout() {
        if (!box) return;
        lift();
        const frontH = toasts.length ? toasts[0].h : 0;
        let offset = 0;
        toasts.forEach((t, i) => {
            const behind = !expanded && i > 0;
            t.el.style.setProperty('--y', -(expanded ? offset : i * PEEK) + 'px');
            t.el.style.setProperty('--s', expanded ? 1 : Math.max(.8, 1 - i * .05));
            t.el.style.height = (behind ? frontH : t.h) + 'px';
            t.el.style.zIndex = 100 - i;
            t.el.classList.toggle('is-behind', behind);
            t.el.classList.toggle('is-hidden', i >= VISIBLE);
            if (i < VISIBLE) offset += t.h + GAP;
        });
        box.style.height = (expanded ? Math.max(0, offset - GAP) : frontH) + 'px';
    }

    function run(t) {
        clearTimeout(t.timer);
        t.started = Date.now();
        t.timer = setTimeout(() => dismiss(t), t.left);
    }

    function hold(t) {
        clearTimeout(t.timer);
        t.left = Math.max(MIN_LEFT, t.left - (Date.now() - t.started));
    }

    // odliczanie stoi, gdy stos jest rozwinięty albo karta przeglądarki jest w tle
    function syncTimers() {
        const p = expanded || document.hidden;
        if (p === paused) return;
        paused = p;
        if (box) box.classList.toggle('is-paused', p);
        toasts.forEach(p ? hold : run);
    }

    function setExpanded(v) {
        v = v && toasts.length > 0;
        if (v === expanded) return;
        expanded = v;
        box.classList.toggle('is-expanded', v);
        syncTimers();
        layout();
    }

    function dismiss(t) {
        const i = toasts.indexOf(t);
        if (i < 0) return;
        toasts.splice(i, 1);
        clearTimeout(t.timer);
        t.el.classList.add('is-out');
        setTimeout(() => t.el.remove(), 450);
        if (!toasts.length) setExpanded(false);
        layout();
    }

    function bindSwipe(t) {
        const el = t.el;
        let x0 = null, dx = 0, moved = false;
        el.addEventListener('pointerdown', (e) => {
            if (e.button > 0 || e.target.closest('button')) return;
            x0 = e.clientX; dx = 0; moved = false;
        });
        el.addEventListener('pointermove', (e) => {
            if (x0 === null) return;
            dx = e.clientX - x0;
            if (!moved && Math.abs(dx) < 5) return;
            if (!moved) { moved = true; el.classList.add('is-dragging'); el.setPointerCapture(e.pointerId); }
            el.style.setProperty('--x', dx + 'px');
            el.style.opacity = String(Math.max(.15, 1 - Math.abs(dx) / 260));
        });
        const release = (e) => {
            if (x0 === null) return;
            x0 = null;
            el.classList.remove('is-dragging');
            el.style.opacity = '';
            if (moved && Math.abs(dx) > SWIPE) {
                el.style.setProperty('--x', (dx > 0 ? 120 : -120) + '%');
                dismiss(t);
            } else {
                el.style.setProperty('--x', '0px');
                // na dotyku stuknięcie rozwija / zwija stos
                if (!moved && e.type === 'pointerup' && e.pointerType !== 'mouse') setExpanded(!expanded);
            }
        };
        el.addEventListener('pointerup', release);
        el.addEventListener('pointercancel', release);
    }

    function showAlert(type, message) {
        if (!TITLES[type]) type = 'info';
        container();
        const el = document.createElement('div');
        el.className = 'hl-toast is-' + type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.style.setProperty('--dur', DURATION + 'ms');
        el.innerHTML = '<div class="hl-toast-card">'
            + '<span class="hl-toast-ico">' + ICONS[type] + '</span>'
            + '<div class="hl-toast-body"><b></b><span></span></div>'
            + '<button type="button" class="hl-toast-x" aria-label="Zamknij">' + CLOSE + '</button>'
            + '<i class="hl-toast-bar"></i>'
            + '</div>';
        el.querySelector('.hl-toast-body b').textContent = TITLES[type];
        el.querySelector('.hl-toast-body span').textContent = message == null ? '' : String(message);

        const t = { el, h: 0, left: DURATION, started: 0, timer: null };
        el._toast = t;
        el.querySelector('.hl-toast-x').addEventListener('click', () => dismiss(t));
        bindSwipe(t);

        box.appendChild(el);
        t.h = el.offsetHeight;
        toasts.unshift(t);
        toasts.slice(MAX).forEach(dismiss);
        layout();
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-in')));
        if (!paused) run(t);
        return el;
    }

    function closeAlertWithAnimation(el) {
        const t = el && el.closest && el.closest('.hl-toast') ? el.closest('.hl-toast')._toast : null;
        if (t) dismiss(t);
        else if (el) el.remove();
    }

    function remeasure() {
        toasts.forEach(t => {
            const h = t.el.style.height;
            t.el.style.height = '';
            t.h = t.el.offsetHeight;
            t.el.style.height = h;
        });
        layout();
    }

    window.addEventListener('resize', remeasure);
    if (window.visualViewport) {
        visualViewport.addEventListener('resize', layout);
        visualViewport.addEventListener('scroll', layout);
    }
    document.addEventListener('visibilitychange', syncTimers);
    // stuknięcie poza stosem zwija go (na dotyku nie ma mouseleave)
    document.addEventListener('pointerdown', (e) => {
        if (expanded && box && !box.contains(e.target)) setExpanded(false);
    });

    window.showAlert = showAlert;
    window.closeAlertWithAnimation = closeAlertWithAnimation;
})();
