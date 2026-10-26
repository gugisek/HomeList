// HomeList – strona główna: interaktywne makiety aplikacji i animacje przewijania
(function () {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const $ = (s, root) => (root || document).querySelector(s);
    const $$ = (s, root) => [...(root || document).querySelectorAll(s)];
    const clamp = (v, a, b) => Math.min(b, Math.max(a, v));

    // jednorazowa obserwacja wejścia w ekran
    function onView(el, cb, threshold) {
        if (!el) return;
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (!e.isIntersecting) return;
                io.unobserve(e.target);
                cb(e.target);
            });
        }, { threshold: threshold ?? .25 });
        io.observe(el);
    }

    // konfetti z odhaczenia – jak feedBurst() w feed_parts.php
    function burst(el, scale) {
        if (reduced) return;
        const colors = ['#4ade80', '#facc15', '#38bdf8', '#fb7185', '#a78bfa', '#fb923c'];
        const size = el.offsetWidth;
        for (let i = 0; i < 12; i++) {
            const s = document.createElement('span');
            const a = (Math.PI * 2 * i) / 12 + Math.random() * .4;
            const dist = (size * .62 + Math.random() * size * .55) * (scale || 1);
            s.className = 'lp-particle';
            s.style.setProperty('--dx', (Math.cos(a) * dist) + 'px');
            s.style.setProperty('--dy', (Math.sin(a) * dist) + 'px');
            s.style.background = colors[i % colors.length];
            if (scale) s.style.width = s.style.height = Math.max(5, size / 9) + 'px';
            el.appendChild(s);
            setTimeout(() => s.remove(), 650);
        }
    }

    /* ---------- hero: ptaszek w nagłówku ---------- */
    const heroCheck = $('[data-hero-check]');
    if (heroCheck) {
        setTimeout(() => {
            heroCheck.classList.add('is-done');
            burst(heroCheck, 1.1);
        }, reduced ? 0 : 1100);
        heroCheck.addEventListener('click', () => {
            const done = heroCheck.classList.toggle('is-done');
            if (done) burst(heroCheck, 1.1);
        });
    }

    /* ---------- odhaczanie w makietach ---------- */
    function feedProgress(feed) {
        const items = $$('.lp-item:not(.is-overdue)', feed);
        const total = items.length;
        const done = items.filter(li => li.classList.contains('is-done')).length;
        const ring = $('[data-ring]', feed);
        if (ring) {
            $('.lp-ring-fg', ring).setAttribute('stroke-dasharray', (total ? Math.round(done / total * 100) : 0) + ' 100');
            $('span', ring).textContent = done + '/' + total;
        }
        const dots = $('[data-strip-dots]', feed);
        if (dots) {
            const shown = Math.min(total, 4);
            const on = total ? Math.round(done / total * shown) : 0;
            dots.innerHTML = Array.from({ length: shown }, (_, i) => '<i' + (i < on ? ' class="is-on"' : '') + '></i>').join('');
        }
        $$('.lp-part-head', feed).forEach(head => {
            const count = $('.lp-part-count', head);
            const list = head.nextElementSibling;
            if (!count || !list) return;
            const lis = $$('.lp-item', list);
            count.textContent = lis.length ? lis.filter(li => li.classList.contains('is-done')).length + '/' + lis.length : '';
        });
        const cel = $('[data-celebrate]', feed);
        if (cel) {
            const all = total > 0 && done === total;
            if (all && !cel.classList.contains('is-on')) setTimeout(() => burst($('.lp-celebrate-ico', cel), 1.4), 250);
            cel.classList.toggle('is-on', all);
        }
    }

    function toggleItem(li) {
        const done = !li.classList.contains('is-done');
        li.classList.toggle('is-done', done);
        const streak = $('.lp-streak', li);
        if (streak) {
            const n = $('b', streak);
            n.textContent = Math.max(0, +n.textContent + (done ? 1 : -1));
            streak.classList.toggle('is-zero', n.textContent === '0');
            streak.classList.remove('is-bump');
            void streak.offsetWidth;
            streak.classList.add('is-bump');
        }
        const today = $('.lp-week .is-today', li);
        if (today) today.className = 'is-today ' + (done ? 'is-done' : 'is-missed');
        if (done) {
            li.classList.add('just-done');
            setTimeout(() => li.classList.remove('just-done'), 450);
            burst($('.lp-check', li));
            if (navigator.vibrate) navigator.vibrate(12);
        }
        feedProgress(li.closest('[data-feed]'));
    }

    $$('[data-feed]').forEach(feed => {
        feed.addEventListener('click', (e) => {
            if (e.target.closest('[data-to-today]')) return;
            const li = e.target.closest('.lp-item');
            if (li && !li.classList.contains('is-flying')) toggleItem(li);
        });
        feedProgress(feed);
    });

    /* ---------- hero: przechył 3D zależny od przewinięcia ---------- */
    const tilt = $('[data-hero-tilt]');
    let mouseX = 0, mouseY = 0;
    function heroFrame() {
        if (!tilt) return;
        const vh = window.innerHeight;
        const top = tilt.getBoundingClientRect().top;
        const p = clamp((vh * .95 - top) / (vh * .7), 0, 1);
        const e = 1 - Math.pow(1 - p, 2);
        const rx = 26 * (1 - e) + mouseY * -2.5;
        const ry = mouseX * 4 * (1 - e * .6);
        tilt.style.transform = 'rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg) scale(' + (.9 + .1 * e).toFixed(4) + ')';
    }
    if (tilt && !reduced) {
        // pozycja makiety zmienia się w trakcie animacji wejścia – przeliczamy po jej końcu
        $('.lp-rise').addEventListener('animationend', () => requestScroll());
        if (finePointer) {
            window.addEventListener('pointermove', (ev) => {
                mouseX = ev.clientX / window.innerWidth * 2 - 1;
                mouseY = ev.clientY / window.innerHeight * 2 - 1;
                requestScroll();
            }, { passive: true });
        }
    }

    /* ---------- reveal 3D + przechył pod kursorem ---------- */
    $$('[data-reveal]').forEach(el => onView(el, () => {
        el.classList.add('is-in');
        setTimeout(() => el.classList.add('is-settled'), 1300);
    }, .15));

    if (finePointer && !reduced) {
        $$('[data-tilt-hover]').forEach(el => {
            el.addEventListener('pointermove', (ev) => {
                if (!el.classList.contains('is-settled')) return;
                const r = el.getBoundingClientRect();
                const x = (ev.clientX - r.left) / r.width - .5;
                const y = (ev.clientY - r.top) / r.height - .5;
                el.style.setProperty('--ry', (x * 7).toFixed(2) + 'deg');
                el.style.setProperty('--rx', (y * -6).toFixed(2) + 'deg');
            });
            el.addEventListener('pointerleave', () => {
                el.style.setProperty('--ry', '0deg');
                el.style.setProperty('--rx', '0deg');
            });
        });
    }

    /* ---------- poświata pory dnia, dolny pasek, aktywna sekcja ---------- */
    const skySections = $$('[data-sky]');
    const skyLayers = {};
    $$('[data-sky-layer]').forEach(l => { skyLayers[l.dataset.skyLayer] = l; });
    const dock = $('.lp-dock');
    const navLinks = $$('[data-nav]');
    let currentSky = null, currentNav = null;

    function sectionFrame() {
        const mid = window.innerHeight * .5;
        let active = skySections[0];
        skySections.forEach(s => { if (s.getBoundingClientRect().top < mid) active = s; });
        if (active && active.dataset.sky !== currentSky) {
            currentSky = active.dataset.sky;
            Object.entries(skyLayers).forEach(([k, l]) => l.classList.toggle('is-on', k === currentSky));
        }
        const id = active ? active.id : '';
        if (id !== currentNav) {
            currentNav = id;
            navLinks.forEach(a => a.classList.toggle('is-on', a.dataset.nav === id));
        }
        if (dock) dock.classList.toggle('is-shown', window.scrollY > window.innerHeight * .35);
    }

    let ticking = false;
    function requestScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            ticking = false;
            if (!reduced) heroFrame();
            sectionFrame();
        });
    }
    window.addEventListener('scroll', requestScroll, { passive: true });
    window.addEventListener('resize', requestScroll);
    requestScroll();

    /* ---------- Rano: kroki opowieści o porach dnia ---------- */
    const phone = $('[data-story-phone]');
    // FLIP: przeniesienie karty do innej listy z płynnym przelotem
    function moveItem(li, target, prepend) {
        if (!li || !target || li.parentElement === target) return;
        const before = li.getBoundingClientRect();
        if (prepend) target.prepend(li); else target.appendChild(li);
        const after = li.getBoundingClientRect();
        if (reduced) return;
        li.classList.add('is-flying');
        li.style.transition = 'none';
        li.style.transform = 'translate(' + (before.left - after.left) + 'px,' + (before.top - after.top) + 'px) scale(1.04)';
        requestAnimationFrame(() => requestAnimationFrame(() => {
            li.style.transition = 'transform .7s cubic-bezier(.25, 1, .5, 1), box-shadow .3s';
            li.style.transform = '';
            setTimeout(() => { li.classList.remove('is-flying'); li.style.transition = ''; }, 720);
        }));
    }

    if (phone) {
        const parcel = $('[data-key="parcel"]', phone);
        const overdue = $('[data-overdue]', phone);
        let rescued = false;

        function setStep(n) {
            if (+phone.dataset.stepNow === n) return;
            phone.dataset.stepNow = n;
            moveItem(parcel, $('[data-list="' + (n >= 2 ? 2 : 0) + '"]', phone), n >= 2);
            overdue.classList.toggle('is-open', n >= 3 && !rescued);
            feedProgress(phone);
        }

        phone.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-to-today]');
            if (!btn) return;
            const li = btn.closest('.lp-item');
            rescued = true;
            btn.remove();
            li.classList.remove('is-overdue');
            // znika dopisek „Wczoraj” razem z kropką-separatorem przed nim
            const warn = $('.lp-item-meta .lp-tone-amber', li);
            if (warn) {
                if (warn.previousElementSibling) warn.previousElementSibling.remove();
                warn.remove();
            }
            moveItem(li, $('[data-list="1"]', phone));
            setTimeout(() => { overdue.classList.remove('is-open'); feedProgress(phone); }, 650);
        });

        const steps = $$('.lp-step');
        if (steps[0]) steps[0].classList.add('is-active');
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (!e.isIntersecting) return;
                steps.forEach(s => s.classList.toggle('is-active', s === e.target));
                setStep(+e.target.dataset.step);
            });
        }, { rootMargin: '-45% 0px -45% 0px' });
        steps.forEach(s => io.observe(s));
    }

    /* ---------- Popołudnie: kod listy, podgląd linku, nowe zadania od domowników ---------- */
    const flap = $('[data-flap]');
    onView(flap, () => {
        const code = flap.dataset.flap;
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $$('b', flap).forEach((b, i) => {
            let left = reduced ? 0 : 8 + i * 3;
            const step = () => {
                b.classList.remove('is-flip');
                void b.offsetWidth;
                b.classList.add('is-flip');
                b.textContent = left > 0 ? chars[Math.floor(Math.random() * chars.length)] : code[i];
                if (left-- > 0) setTimeout(step, 70);
            };
            setTimeout(step, i * 60);
        });
    }, .6);

    const og = $('[data-og] .lp-og');
    onView(og, () => setTimeout(() => og.classList.add('is-loaded'), reduced ? 0 : 1500), .6);

    const live = $('[data-live-list]');
    onView(live, () => {
        const pool = [['Baterie AA do pilota', 'O', 'Ola', 'rose'], ['Karma dla Burka', 'K', 'Kuba', 'sky'], ['Pomidory malinowe', 'M', 'Mama', 'amber']];
        const template = $('.lp-row.is-wait', live);
        let i = 0;
        const add = () => {
            if (i >= pool.length || !template) return;
            const [title, letter, who, tone] = pool[i++];
            const row = template.cloneNode(true);
            $('.lp-row-body p', row).textContent = title;
            const ava = $('.lp-ava', row);
            ava.textContent = letter;
            ava.className = 'lp-ava lp-ava-' + tone;
            const spans = $$('.lp-item-meta > span:not(.lp-ava)', row);
            spans[0].textContent = 'Dodane przez ' + who;
            spans[1].textContent = 'przed chwilą';
            row.classList.add('is-new');
            live.prepend(row);
            const rows = $$('.lp-row', live);
            if (rows.length > 6) rows[rows.length - 1].remove();
            setTimeout(add, 4200);
        };
        setTimeout(add, reduced ? 0 : 1800);
    }, .4);

    /* ---------- Wieczór: licznik serii i powiadomienia ---------- */
    const big = $('[data-count]');
    onView(big, () => {
        const target = +big.dataset.count;
        const out = $('b', big);
        if (reduced) { out.textContent = target; return; }
        const t0 = performance.now();
        const tick = (t) => {
            const p = Math.min(1, (t - t0) / 1600);
            out.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }, .6);

    const lock = $('[data-notify]');
    if (lock) {
        let visible = false, timers = [];
        const run = () => {
            timers.forEach(clearTimeout);
            lock.classList.remove('is-merged', 'is-reset');
            requestAnimationFrame(() => lock.classList.add('is-in'));
            if (reduced) { lock.classList.add('is-merged'); return; }
            timers = [
                setTimeout(() => lock.classList.add('is-merged'), 2900),
                setTimeout(() => lock.classList.add('is-reset'), 6900),
                setTimeout(() => { lock.classList.remove('is-in', 'is-merged'); }, 7600),
                setTimeout(() => { lock.classList.remove('is-reset'); if (visible) run(); }, 8100),
            ];
        };
        new IntersectionObserver((entries) => {
            entries.forEach(e => {
                const was = visible;
                visible = e.isIntersecting;
                if (visible && !was && !timers.length) run();
                if (!visible) { timers.forEach(clearTimeout); timers = []; lock.classList.remove('is-in', 'is-merged', 'is-reset'); }
            });
        }, { threshold: .35 }).observe(lock);
    }

    /* ---------- Kiedykolwiek: suwak jasny / ciemny ---------- */
    const cmp = $('[data-compare]');
    if (cmp) {
        let dragging = false;
        const handle = $('[data-compare-handle]', cmp);
        const setPos = (y) => {
            const r = cmp.getBoundingClientRect();
            cmp.style.setProperty('--pos', clamp((y - r.top) / r.height * 100, 6, 94) + '%');
        };
        // przeciąganie za linię (na dotyku tylko za nią, żeby nie blokować przewijania), kliknięcie gdziekolwiek przestawia
        handle.addEventListener('pointerdown', (e) => { dragging = true; handle.setPointerCapture(e.pointerId); e.preventDefault(); });
        handle.addEventListener('pointermove', (e) => { if (dragging) setPos(e.clientY); });
        ['pointerup', 'pointercancel'].forEach(t => handle.addEventListener(t, () => { dragging = false; }));
        cmp.addEventListener('click', (e) => { if (!e.target.closest('[data-compare-handle]')) setPos(e.clientY); });
        if (finePointer) cmp.addEventListener('pointermove', (e) => { if (!dragging && e.buttons === 1) setPos(e.clientY); });

        // podpowiedź: suwak sam przejeżdża raz w obie strony
        onView(cmp, () => {
            if (reduced) return;
            const frames = [[0, 50], [700, 78], [1500, 24], [2300, 50]];
            const t0 = performance.now();
            const tick = (t) => {
                if (dragging) return;
                const dt = t - t0;
                let k = frames.length - 1;
                for (let i = 1; i < frames.length; i++) if (dt < frames[i][0]) { k = i; break; }
                const [ta, va] = frames[k - 1], [tb, vb] = frames[k];
                const p = clamp((dt - ta) / (tb - ta), 0, 1);
                const ease = p < .5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;
                cmp.style.setProperty('--pos', (va + (vb - va) * ease) + '%');
                if (dt < frames[frames.length - 1][0]) requestAnimationFrame(tick);
            };
            setTimeout(() => requestAnimationFrame(tick), 900);
        }, .5);
    }
})();
