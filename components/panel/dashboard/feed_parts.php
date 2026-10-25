<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Wspólne elementy widoku feedu dnia i list rutyn: karta elementu + style + skrypty odhaczania/przeciągania.
// Wymaga wcześniejszego includowania scripts/planner/planner_lib.php

if (defined('HL_FEED_PARTS')) return;
define('HL_FEED_PARTS', true);

/**
 * $variant: part | overdue | deadline | routine_list
 * $extra (routine_list): ['summary' => ..., 'week' => [['date'=>..,'state'=>done|missed|off], ...]]
 */
function hl_render_feed_item(array $it, $date, $today, $variant = 'part', array $extra = []) {
    $parts = hl_parts();
    $is_routine = $it['type'] === 'routine';

    $border = 'border-black/10 dark:border-white/5';
    if ($variant === 'overdue') $border = 'border-amber-400/40';
    elseif ($variant === 'deadline' || $variant === 'late') $border = 'border-rose-400/30';

    $dot = '<svg viewBox="0 0 2 2" class="h-0.5 w-0.5 flex-none fill-gray-300 dark:fill-gray-500"><circle cx="1" cy="1" r="1" /></svg>';
    $meta = [];

    if ($variant === 'routine_list') {
        $meta[] = '<span class="flex items-center gap-1 min-w-0">'.hl_icon('repeat', 'size-3.5 flex-none').'<span class="truncate">'.hl_e($extra['summary'] ?? '').'</span></span>';
        $meta[] = '<span class="flex items-center gap-1 whitespace-nowrap '.$parts[$it['part']]['color'].'">'.hl_icon($parts[$it['part']]['icon'], 'size-3.5').'<span class="text-gray-400">'.$parts[$it['part']]['label'].'</span></span>';
    } else {
        $meta[] = '<span class="flex items-center gap-1 min-w-0">'.($is_routine ? hl_icon('repeat', 'size-3.5 flex-none') : '').'<span class="truncate">'.hl_e($it['list_name']).'</span></span>';
    }
    if ($it['time']) {
        $meta[] = '<span class="flex items-center gap-1 whitespace-nowrap">'.hl_icon('clock', 'size-3.5').$it['time'].'</span>';
    }
    if ($it['status_id'] === 3 && !$it['done']) {
        $meta[] = '<span class="whitespace-nowrap text-sky-400">W trakcie</span>';
    }
    if ($it['deadline'] && $variant !== 'routine_list') {
        $late = $it['deadline'] < date('Y-m-d H:i:s') && !$it['done'];
        $dl_date = substr($it['deadline'], 0, 10);
        $label = $dl_date === $date ? date('H:i', strtotime($it['deadline'])) : date('H:i d.m', strtotime($it['deadline']));
        $meta[] = '<span class="whitespace-nowrap '.($late ? 'text-red-400' : 'text-rose-400').'">Deadline '.$label.'</span>';
    }
    if ($variant === 'overdue' && $it['plan_date']) {
        $was = $it['plan_date'] === hl_add_days($today, -1) ? 'Wczoraj' : hl_day_name($it['plan_date'], true).' '.date('d.m', strtotime($it['plan_date']));
        $meta[] = '<span class="flex items-center gap-1 whitespace-nowrap text-amber-500">'.hl_icon('warning', 'size-3.5').$was.'</span>';
    }
    if ($it['shared']) {
        $meta[] = '<span class="flex items-center" title="Odhaczenie wspólne dla wszystkich">'.hl_icon('users', 'size-3.5').'</span>';
    }
    if ($it['link']) {
        $meta[] = '<span class="hidden sm:flex items-center text-sky-400" title="Ma dołączony link">'.hl_icon('link', 'size-3.5', '1.8').'</span>';
    }

    $check_title = $it['checkable'] ? 'Odhacz' : ($variant === 'routine_list' ? 'Dziś wolne' : 'Rutynę odhaczysz w jej dniu');

    echo '<li class="feed-item group relative flex items-center gap-3 my-1.5 py-3 pl-3 pr-3 sm:pr-4 sm:mx-0 -mx-4 rounded-2xl bg-white dark:bg-[#2f2f2f] border '.$border.' shadow-sm hover:shadow-md transition-[box-shadow,opacity,transform] duration-200 md:hover:scale-[1.01] cursor-pointer select-none'.($it['done'] ? ' is-done' : '').'"
            data-key="'.$it['key'].'" data-type="'.$it['type'].'" data-id="'.$it['id'].'" data-done="'.($it['done'] ? '1' : '0').'"
            data-date="'.$date.'" data-part="'.$it['part'].'" data-variant="'.$variant.'" onclick="feedOpen(this)">';

    echo '<button type="button" aria-label="'.$check_title.'" title="'.$check_title.'" class="feed-check'.($is_routine ? ' is-routine' : '').'" onclick="event.stopPropagation();feedToggle(this)"'.($it['checkable'] ? '' : ' disabled').'>
            <svg viewBox="0 0 24 24" class="size-4"><path class="feed-check-path" d="m5 12.5 4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>';

    echo '<div class="min-w-0 flex-1">
            <h2 class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate"><span class="feed-title">'.hl_e($it['title']).'</span></h2>
            <div class="flex items-center gap-1.5 text-xs text-gray-400 mt-1 min-w-0">'.implode($dot, $meta).'</div>
          </div>';

    echo '<div class="flex items-center gap-2 flex-none">';
    if ($is_routine) {
        echo '<span class="feed-streak flex items-center gap-0.5 text-xs font-semibold text-orange-400'.($it['streak'] ? '' : ' hidden').'" title="Seria">'.hl_icon('fire', 'size-4').'<span class="feed-streak-n">'.(int)$it['streak'].'</span></span>';
    }
    if ($variant === 'routine_list' && !empty($extra['week'])) {
        echo '<span class="flex items-center gap-1" title="Ostatnie 7 dni">';
        foreach ($extra['week'] as $w) {
            $c = $w['state'] === 'done' ? 'bg-green-400' : ($w['state'] === 'missed' ? 'bg-gray-300 dark:bg-gray-600' : 'bg-gray-200/50 dark:bg-white/5');
            echo '<span class="h-1.5 w-1.5 rounded-full '.$c.($w['date'] === $today ? ' feed-today-dot' : '').'" data-state="'.$w['state'].'"></span>';
        }
        echo '</span>';
    }
    if ($variant === 'overdue') {
        echo '<button type="button" onclick="event.stopPropagation();feedOverdue(this, \'today\')" class="feed-extra text-xs font-medium px-2.5 py-1 rounded-full text-green-500 bg-green-100 dark:bg-green-400/20 hover:bg-green-400 hover:text-white active:scale-95 duration-150">Na dziś</button>
              <button type="button" onclick="event.stopPropagation();feedOverdue(this, \'unplan\')" title="Usuń z planu" class="feed-extra p-1 rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 hover:text-gray-600 active:scale-95 duration-150">'.hl_icon('x', 'size-4', '2').'</button>';
    }
    echo '</div></li>';
}

function hl_feed_assets() {
?>
<style>
.feed-check { position: relative; width: 1.6rem; height: 1.6rem; flex: none; border-radius: 9999px; display: grid; place-items: center; color: transparent; box-shadow: inset 0 0 0 2px rgb(209 213 219); transition: background-color .2s, box-shadow .2s, transform .15s; }
.feed-check.is-routine { border-radius: .6rem; }
.feed-check:not(:disabled):hover { box-shadow: inset 0 0 0 2px #4ade80; }
.feed-check:not(:disabled):active { transform: scale(.85); }
.feed-check:disabled { opacity: .35; cursor: not-allowed; }
.feed-check .feed-check-path { stroke-dasharray: 24; stroke-dashoffset: 24; transition: stroke-dashoffset .3s ease .05s; }
.is-done .feed-check { background: #4ade80; box-shadow: inset 0 0 0 2px #4ade80; color: #fff; }
.is-done .feed-check .feed-check-path { stroke-dashoffset: 0; }
.just-done .feed-check { animation: feed-pop .4s cubic-bezier(.34,1.56,.64,1); }
.feed-title { background-image: linear-gradient(currentColor, currentColor); background-repeat: no-repeat; background-position: 0 55%; background-size: 0% 1.5px; transition: background-size .35s ease, color .25s; }
.is-done .feed-title { background-size: 100% 1.5px; color: rgb(156 163 175); }
.feed-item.is-done { opacity: .75; }
.feed-particle { position: absolute; left: 50%; top: 50%; width: 5px; height: 5px; border-radius: 9999px; pointer-events: none; animation: feed-burst .6s ease-out forwards; }
.feed-list:has(.feed-item) > .feed-empty { display: none; }
.is-dragging .feed-empty { border-color: #4ade80; color: #4ade80; }
.feed-ghost { opacity: .35; background: rgba(74, 222, 128, .12) !important; }
.feed-chosen { box-shadow: 0 8px 30px rgba(0, 0, 0, .18); }
.feed-removing { transition: opacity .3s, transform .3s, max-height .35s, margin .35s, padding .35s; opacity: 0; transform: translateX(24px); max-height: 0 !important; margin: 0 !important; padding-top: 0 !important; padding-bottom: 0 !important; overflow: hidden; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { scrollbar-width: none; }
.feed-celebrate { transition: max-height .45s ease, opacity .45s ease, margin .45s ease; }
@keyframes feed-pop { 0% { transform: scale(1); } 40% { transform: scale(1.3); } 100% { transform: scale(1); } }
@keyframes feed-burst { from { transform: translate(-50%, -50%) scale(1); opacity: 1; } to { transform: translate(calc(-50% + var(--dx)), calc(-50% + var(--dy))) scale(.3); opacity: 0; } }
@media (prefers-color-scheme: dark) {
  .feed-check { box-shadow: inset 0 0 0 2px rgb(107 114 128); }
  .is-done .feed-title { color: rgb(107 114 128); }
}
</style>
<script>
function feedOpen(li) {
    if (window.feedDragging) return;
    openPopupDetailsElement(li.dataset.id);
}

function feedSetDone(li, done) {
    li.dataset.done = done ? '1' : '0';
    li.classList.toggle('is-done', done);
    if (done) {
        li.classList.add('just-done');
        setTimeout(() => li.classList.remove('just-done'), 450);
    }
    const todayDot = li.querySelector('.feed-today-dot');
    if (todayDot) {
        todayDot.classList.remove('bg-green-400', 'bg-gray-300', 'dark:bg-gray-600');
        todayDot.classList.add(...(done ? ['bg-green-400'] : ['bg-gray-300', 'dark:bg-gray-600']));
    }
}

function feedBurst(btn) {
    const colors = ['#4ade80', '#facc15', '#38bdf8', '#fb7185', '#a78bfa', '#fb923c'];
    for (let i = 0; i < 12; i++) {
        const s = document.createElement('span');
        const a = (Math.PI * 2 * i) / 12 + Math.random() * 0.4;
        const dist = 16 + Math.random() * 14;
        s.className = 'feed-particle';
        s.style.setProperty('--dx', (Math.cos(a) * dist) + 'px');
        s.style.setProperty('--dy', (Math.sin(a) * dist) + 'px');
        s.style.background = colors[i % colors.length];
        btn.appendChild(s);
        setTimeout(() => s.remove(), 650);
    }
}

function feedSetStreak(li, streak) {
    const el = li.querySelector('.feed-streak');
    if (!el) return;
    el.querySelector('.feed-streak-n').textContent = streak;
    el.classList.toggle('hidden', !streak);
}

function feedToggle(btn) {
    const li = btn.closest('.feed-item');
    if (!li || btn.disabled) return;
    const done = li.dataset.done !== '1';
    feedSetDone(li, done);
    if (done) {
        feedBurst(btn);
        if (navigator.vibrate) navigator.vibrate(12);
    }
    feedUpdateProgress();

    const fd = new FormData();
    fd.append('type', li.dataset.type);
    fd.append('id', li.dataset.id);
    fd.append('date', li.dataset.date);
    fd.append('done', done ? '1' : '0');
    hlPost('scripts/planner/toggle.php', fd)
        .then(list => {
            const res = list[0] || {};
            if (res.status !== 'success') {
                feedSetDone(li, !done);
                feedUpdateProgress();
                showAlert(res.status === 'warning' ? 'warning' : 'error', res.message || 'Nieznany status odpowiedzi');
                return;
            }
            if (res.streak !== undefined) feedSetStreak(li, res.streak);
            if (done && (li.dataset.variant === 'overdue' || li.dataset.variant === 'late')) setTimeout(() => feedRemove(li), 500);
        })
        .catch(error => {
            feedSetDone(li, !done);
            feedUpdateProgress();
            showAlert('error', 'Wystąpił problem połączenia z serwerem');
            console.error('Błąd:', error);
        });
}

function feedRemove(li) {
    li.style.maxHeight = li.offsetHeight + 'px';
    requestAnimationFrame(() => li.classList.add('feed-removing'));
    setTimeout(() => {
        const section = li.closest('[data-feed-section]');
        li.remove();
        if (section && section.dataset.hideEmpty === '1' && !section.querySelector('.feed-item')) section.remove();
        feedUpdateProgress();
    }, 380);
}

function feedOverdue(btn, action) {
    const li = btn.closest('.feed-item');
    const fd = new FormData();
    fd.append('element_id', li.dataset.id);
    if (action === 'today') {
        fd.append('action', 'plan');
        fd.append('date', li.dataset.date);
        fd.append('day_part', li.dataset.part);
    } else {
        fd.append('action', 'unplan');
    }
    hlPost('scripts/planner/plan.php', fd)
        .then(list => {
            const res = list[0] || {};
            if (res.status !== 'success') {
                showAlert(res.status === 'warning' ? 'warning' : 'error', res.message || 'Nieznany status odpowiedzi');
                return;
            }
            if (action === 'today') reloadTabSilently();
            else feedRemove(li);
        })
        .catch(() => showAlert('error', 'Wystąpił problem połączenia z serwerem'));
}

// postęp dnia: pierścień, liczniki sekcji, kropki w pasku dni, baner "dzień zaliczony"
function feedUpdateProgress() {
    const feed = document.getElementById('feed');
    if (!feed) return;
    const items = feed.querySelectorAll('.feed-item:not([data-variant="overdue"]):not([data-variant="late"])');
    const total = items.length;
    const done = [...items].filter(li => li.dataset.done === '1').length;

    const ring = document.getElementById('feed-ring');
    if (ring) {
        ring.setAttribute('stroke-dasharray', (total ? Math.round(done / total * 100) : 0) + ' 100');
        document.getElementById('feed-ring-text').textContent = done + '/' + total;
        document.getElementById('feed-progress').classList.toggle('hidden', total === 0);
    }

    feed.querySelectorAll('[data-feed-section]').forEach(section => {
        const counter = section.querySelector('.feed-count');
        if (!counter) return;
        const lis = section.querySelectorAll('.feed-item');
        const d = [...lis].filter(li => li.dataset.done === '1').length;
        counter.textContent = lis.length ? (d + '/' + lis.length) : '';
    });

    const lateCount = feed.querySelector('.feed-late-count');
    if (lateCount) lateCount.textContent = feed.querySelectorAll('.feed-item[data-variant="late"]').length;

    const dots = document.getElementById('strip-selected-dots');
    if (dots) {
        let html = '';
        const shown = Math.min(total, 4);
        const doneShown = total ? Math.round(done / total * shown) : 0;
        for (let i = 0; i < shown; i++) html += '<span class="h-1 w-1 rounded-full ' + (i < doneShown ? 'bg-green-400' : 'bg-white/40 dark:bg-black/30') + '"></span>';
        dots.innerHTML = html;
    }

    const celebrate = document.getElementById('feed-celebrate');
    if (celebrate) {
        const show = total > 0 && done === total;
        celebrate.style.maxHeight = show ? '120px' : '0px';
        celebrate.style.opacity = show ? '1' : '0';
        celebrate.style.marginTop = show ? '0.75rem' : '0px';
    }
}

function feedInitSortable() {
    const base = {
        group: 'feed',
        animation: 180,
        easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
        draggable: '.feed-item',
        filter: '.feed-check, .feed-extra',
        preventOnFilter: false,
        delay: 220,
        delayOnTouchOnly: true,
        touchStartThreshold: 6,
        ghostClass: 'feed-ghost',
        chosenClass: 'feed-chosen',
        onStart: () => {
            window.feedDragging = true;
            document.getElementById('feed').classList.add('is-dragging');
        },
        onEnd: feedDragEnd,
    };
    document.querySelectorAll('#feed .feed-list[data-part]').forEach(ul => new Sortable(ul, base));
    document.querySelectorAll('#feed .feed-list[data-source]').forEach(ul => new Sortable(ul, Object.assign({}, base, {
        group: { name: 'feed', pull: true, put: false },
        sort: false,
    })));
}

function feedDragEnd(evt) {
    setTimeout(() => { window.feedDragging = false; }, 60);
    document.getElementById('feed').classList.remove('is-dragging');
    const to = evt.to, from = evt.from;
    if (!to.dataset.part || (to === from && evt.oldIndex === evt.newIndex)) return;
    if (from !== to && from.dataset.source) {
        // z sekcji Zaległe / Termin do konkretnej pory = zaplanowanie na ten dzień
        evt.item.dataset.variant = 'part';
        evt.item.querySelectorAll('.feed-extra').forEach(e => e.remove());
        evt.item.classList.remove('border-amber-400/40', 'border-rose-400/30');
        evt.item.classList.add('border-black/10', 'dark:border-white/5');
        const section = from.closest('[data-feed-section]');
        if (section && section.dataset.hideEmpty === '1' && !section.querySelector('.feed-item')) section.remove();
    }
    evt.item.dataset.part = to.dataset.part;
    feedUpdateProgress();

    const fd = new FormData();
    fd.append('date', to.dataset.date);
    fd.append('day_part', to.dataset.part);
    fd.append('items', JSON.stringify([...to.querySelectorAll('.feed-item')].map(li => li.dataset.key)));
    hlPost('scripts/planner/reorder.php', fd)
        .then(list => {
            const res = list[0] || {};
            if (res.status !== 'success') {
                showAlert(res.status === 'warning' ? 'warning' : 'error', res.message || 'Nieznany status odpowiedzi');
                reloadTabSilently();
            }
        })
        .catch(() => {
            showAlert('error', 'Wystąpił problem połączenia z serwerem');
            reloadTabSilently();
        });
}
</script>
<?php
}
