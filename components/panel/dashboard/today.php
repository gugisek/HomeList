<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Feed dnia: zaplanowane zadania + rutyny, podzielone na pory dnia
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
include __DIR__.'/../../../scripts/conn_db.php';
include __DIR__.'/../../../scripts/planner/planner_lib.php';
include __DIR__.'/feed_parts.php';

$user_id = (int)$_SESSION['login_id'];
$today = date('Y-m-d');
$date = hl_valid_date($_GET['date'] ?? '') ? $_GET['date'] : $today;

$feed = hl_feed($conn, $user_id, $date, $today);
$parts = hl_parts();
$strip_from = hl_add_days($date, -7);
$strip_to = hl_add_days($date, 21);
$counts = hl_day_counts($conn, $user_id, $strip_from, $strip_to, $today);

$title = hl_relative_day($date, $today);
$subtitle = in_array($title, ['Dziś', 'Jutro', 'Wczoraj']) ? hl_long_date($date) : (int)date('j', strtotime($date)).' '.hl_month_name(date('n', strtotime($date))).(date('Y', strtotime($date)) !== date('Y') ? ' '.date('Y', strtotime($date)) : '');
$pct = $feed['total'] ? round($feed['done'] / $feed['total'] * 100) : 0;
$all_done = $feed['total'] > 0 && $feed['done'] === $feed['total'];
$is_empty = $feed['total'] === 0 && !$feed['overdue'] && !$feed['late'];
$pill = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-300 hover:bg-[#1c1c1e] hover:text-white dark:hover:bg-white dark:hover:text-black active:scale-95 duration-150 cursor-pointer';
?>
<section id="sticky_nav_body" data-aos="fade-up" data-aos-delay="100" class="sticky top-0 z-[1] pt-6 bg-gradient-to-b -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 from-[#fdfdfd] via-[#fdfdfd]/70 via-70% to-white/0 dark:from-[#3d3d3d] dark:via-[#3d3d3d]/70 dark:to-[#3d3d3d]/0">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <span class="font-medium text-2xl text-black dark:text-white font-[poppins]"><?=$title?></span>
            <p class="text-sm text-gray-400 first-letter:uppercase truncate"><?=$subtitle?></p>
        </div>
        <div class="flex items-center gap-1 flex-none">
            <?php if ($date !== $today): ?>
            <button onclick="feedGoTo('<?=$today?>')" class="text-xs font-medium px-3 py-1.5 rounded-full text-green-500 ring-1 ring-green-400/60 hover:bg-green-400 hover:text-white active:scale-95 duration-150">Dziś</button>
            <?php endif; ?>
            <div id="feed-progress" class="relative h-11 w-11 <?=$feed['total'] ? '' : 'hidden'?>" title="Postęp dnia">
                <svg viewBox="0 0 36 36" class="h-11 w-11 -rotate-90">
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="3" class="stroke-gray-200 dark:stroke-white/10" />
                    <circle id="feed-ring" cx="18" cy="18" r="15.9155" fill="none" stroke-width="3" stroke-linecap="round" class="stroke-green-400 transition-all duration-500" stroke-dasharray="<?=$pct?> 100" />
                </svg>
                <span id="feed-ring-text" class="absolute inset-0 flex items-center justify-center text-[10px] font-semibold text-gray-700 dark:text-gray-200"><?=$feed['done']?>/<?=$feed['total']?></span>
            </div>
            <div onclick="openPopupTodayAdd('<?=$date?>')" title="Dodaj zadanie do planu" class="hl-icon-btn">
                <?=hl_icon('plus', 'size-6', '2')?>
            </div>
        </div>
    </div>
</section>

<section class="mt-2" data-aos="fade-up" data-aos-delay="120">
    <div id="day-strip" class="no-scrollbar flex gap-1.5 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 pt-1 pb-5 -mb-4">
        <?php
        foreach ($counts as $d => $c) {
            $sel = $d === $date;
            $is_today = $d === $today;
            $cls = $sel
                ? 'bg-[#1c1c1e] dark:bg-white text-white dark:text-black shadow-lg shadow-black/20'
                : ($is_today ? 'ring-1 ring-inset ring-green-400/70 text-green-500 hover:bg-green-50 dark:hover:bg-green-400/10' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5');
            if ($d < $today && !$sel) $cls .= ' opacity-60';
            $is_sunday = (int)date('N', strtotime($d)) === 7;
            $name_cls = $is_sunday ? ($sel ? 'text-red-400' : 'text-red-500') : ($sel ? 'opacity-60' : 'text-gray-400');
            echo '<button type="button" onclick="feedGoTo(\''.$d.'\')" data-date="'.$d.'" '.($sel ? 'data-selected="1"' : '').' class="flex-none w-12 flex flex-col items-center gap-0.5 py-2 rounded-2xl duration-150 active:scale-95 '.$cls.'">';
            echo '<span class="text-[10px] uppercase tracking-wide '.$name_cls.'">'.hl_day_name($d, true).'</span>';
            echo '<span class="text-base font-semibold leading-none">'.(int)date('j', strtotime($d)).'</span>';
            echo '<span class="flex gap-0.5 h-1 mt-1" '.($sel ? 'id="strip-selected-dots"' : '').'>';
            $shown = min($c['total'], 4);
            $done_shown = $c['total'] ? (int)round($c['done'] / $c['total'] * $shown) : 0;
            for ($i = 0; $i < $shown; $i++) {
                $dot = $i < $done_shown ? 'bg-green-400' : ($sel ? 'bg-white/40 dark:bg-black/30' : 'bg-gray-300 dark:bg-gray-500');
                echo '<span class="h-1 w-1 rounded-full '.$dot.'"></span>';
            }
            echo '</span></button>';
        }
        ?>
    </div>
    <div class="mt-3 flex items-center gap-2">
        <a onclick="openPopupBacklog('<?=$date?>')" class="<?=$pill?>"><?=hl_icon('queue', 'size-4')?>Zaplanuj z list</a>
        <a onclick="openPopupMonth('<?=substr($date, 0, 7)?>')" class="<?=$pill?>"><?=hl_icon('calendar', 'size-4')?>Miesiąc</a>
    </div>
</section>

<div id="push-banner" class="hidden mt-3 rounded-2xl bg-gray-50 dark:bg-[#2c2c2c] p-3 flex items-center gap-3">
    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-2xl bg-green-400/15 text-green-500">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Włącz przypomnienia</p>
        <p id="push-banner-text" class="text-xs text-gray-500 dark:text-gray-400">Plan na początku pory dnia i przypomnienie, gdy o czymś zapomnisz. Bez spamu.</p>
    </div>
    <button id="push-banner-btn" onclick="pushBannerEnable()" class="flex-none text-xs font-medium px-3 py-1.5 rounded-full bg-green-400 text-white hover:shadow-lg active:scale-95 duration-150">Włącz</button>
    <button onclick="pushBannerDismiss()" title="Nie teraz" class="flex-none text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 active:scale-95 duration-150">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
    </button>
</div>

<div id="feed-clip" class="-mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
<section id="feed" class="mt-2" data-date="<?=$date?>" data-aos="fade-right" data-aos-delay="150">

    <div id="feed-celebrate" class="feed-celebrate overflow-hidden" style="max-height:<?=$all_done ? '120px' : '0px'?>;opacity:<?=$all_done ? '1' : '0'?>;margin-top:<?=$all_done ? '0.75rem' : '0px'?>">
        <div class="flex items-center gap-3 rounded-2xl px-4 py-3 bg-green-400/10 dark:bg-green-400/10 ring-1 ring-green-400/30">
            <div class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-green-400 text-white shadow-lg shadow-green-400/40"><?=hl_icon('check', 'size-5', '2.5')?></div>
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Dzień zaliczony!</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Wszystko z planu odhaczone. Dobra robota.</p>
            </div>
        </div>
    </div>

    <?php if ($is_empty): ?>
    <div class="flex items-center justify-center py-10">
        <div class="text-center max-w-xs">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-green-400/10 text-green-400"><?=hl_icon('sun', 'size-8')?></div>
            <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-200">Nic tu jeszcze nie ma</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Wybierz zadania z list albo dodaj nowe prosto do planu. Rutyny pojawią się tu same.</p>
            <div class="mt-6 flex items-center justify-center gap-2">
                <button onclick="openPopupBacklog('<?=$date?>')" type="button" class="hl-btn hl-btn-primary">
                    <?=hl_icon('queue', 'size-5')?>Zaplanuj z list
                </button>
                <button onclick="openPopupTodayAdd('<?=$date?>')" type="button" class="hl-btn hl-btn-ghost">
                    <?=hl_icon('plus', 'size-5')?>Nowe
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>

        <?php if ($feed['overdue']): ?>
        <div data-feed-section="overdue" data-hide-empty="1" class="mt-5">
            <div class="flex items-center gap-2 px-1 text-amber-500">
                <?=hl_icon('warning', 'size-5')?>
                <h3 class="text-sm font-semibold">Zaległe</h3>
                <span class="ml-auto text-xs text-gray-400">przeciągnij do pory albo „Na dziś”</span>
            </div>
            <ul class="feed-list mt-1" data-source="overdue">
                <?php foreach ($feed['overdue'] as $it) hl_render_feed_item($it, $date, $today, 'overdue'); ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($feed['deadline']): ?>
        <div data-feed-section="deadline" data-hide-empty="1" class="mt-5">
            <div class="flex items-center gap-2 px-1 text-rose-400">
                <?=hl_icon('flag', 'size-5')?>
                <h3 class="text-sm font-semibold">Termin <?=$date === $today ? 'dziś' : 'tego dnia'?></h3>
                <span class="feed-count ml-auto text-xs text-gray-400"></span>
            </div>
            <ul class="feed-list mt-1" data-source="deadline">
                <?php foreach ($feed['deadline'] as $it) hl_render_feed_item($it, $date, $today, 'deadline'); ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($feed['late']): ?>
        <div data-feed-section="late" data-hide-empty="1" class="mt-3">
            <button type="button" onclick="feedToggleLate(this.parentElement)" class="w-full flex items-center gap-2 px-3 py-2 rounded-2xl text-left bg-red-400/5 hover:bg-red-400/10 ring-1 ring-red-400/20 duration-150">
                <span class="text-red-400"><?=hl_icon('warning', 'size-4')?></span>
                <span class="text-xs font-medium text-red-400">Po terminie z wcześniejszych dni</span>
                <span class="feed-late-count text-xs text-gray-400"><?=count($feed['late'])?></span>
                <span class="feed-late-chevron ml-auto text-gray-400 duration-200"><?=hl_icon('right', 'size-4', '2')?></span>
            </button>
            <ul class="feed-list mt-1 hidden" data-source="late">
                <?php foreach ($feed['late'] as $it) hl_render_feed_item($it, $date, $today, 'late'); ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php foreach ($parts as $p => $info): ?>
        <div data-feed-section="part-<?=$p?>" class="mt-5">
            <div class="flex items-center gap-2 px-1">
                <span class="<?=$info['color']?>"><?=hl_icon($info['icon'], 'size-5')?></span>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200"><?=$info['label']?></h3>
                <span class="feed-count text-xs text-gray-400"></span>
                <button type="button" onclick="openPopupTodayAdd('<?=$date?>&part=<?=$p?>')" title="Dodaj do: <?=$info['label']?>" class="ml-auto p-1 rounded-lg text-gray-400 hover:text-white hover:bg-green-400 active:scale-90 duration-150">
                    <?=hl_icon('plus', 'size-4', '2')?>
                </button>
            </div>
            <ul class="feed-list mt-1 min-h-[2.75rem]" data-part="<?=$p?>" data-date="<?=$date?>">
                <?php foreach ($feed['parts'][$p] as $it) hl_render_feed_item($it, $date, $today, 'part'); ?>
                <li class="feed-empty my-1.5 flex items-center justify-center h-11 rounded-2xl border border-dashed border-gray-300 dark:border-white/10 text-xs text-gray-400 duration-150">Pusto – przeciągnij tu lub dodaj +</li>
            </ul>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>

    <div class="text-xs text-gray-400 text-center w-full flex items-center justify-center mt-10 md:pb-12 pb-8">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-green-400 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
        </svg>
        HomeList - version <?php include '../../../version.php'?>, powered by <a href="https://rgbpc.pl/" target="_blank" class="ml-1 duration-300"><span class="text-red-600">R</span><span class="text-green-600">G</span><span class="text-blue-600">B</span>pc.pl</a>
        <br/><br/><br/><br/><br/>
    </div>
</section>
</div>

<?php hl_feed_assets(); ?>
<script>
hlEnsureSortable(feedInitSortable);
feedUpdateProgress();
pushBannerInit();

// baner zgody na push: pokazywany, dopóki użytkownik nie zdecyduje (odrzucenie "Nie teraz" ukrywa na 14 dni)
function pushBannerInit() {
    const banner = document.getElementById('push-banner');
    if (!banner || typeof hlPushState !== 'function') return;
    const state = hlPushState();
    hlPushSync();
    let snoozed = 0;
    try { snoozed = +localStorage.getItem('hlPushBannerSnooze') || 0; } catch (e) {}
    if (snoozed > Date.now() || !['default', 'ios-install'].includes(state)) return;
    if (state === 'ios-install') {
        document.getElementById('push-banner-text').textContent = 'Na iPhonie: Udostępnij → „Do ekranu początkowego”, otwórz HomeList z ikonki i włącz tu powiadomienia.';
        document.getElementById('push-banner-btn').classList.add('hidden');
    }
    banner.classList.remove('hidden');
}

function pushBannerEnable() {
    hlPushEnable().then(ok => { if (ok || Notification.permission === 'denied') document.getElementById('push-banner')?.classList.add('hidden'); });
}

function pushBannerDismiss() {
    try { localStorage.setItem('hlPushBannerSnooze', Date.now() + 14 * 864e5); } catch (e) {}
    document.getElementById('push-banner').classList.add('hidden');
}
(function () {
    const strip = document.getElementById('day-strip');
    const sel = strip && strip.querySelector('[data-selected]');
    if (!sel) return;
    const anchor = window.feedStripAnchor;
    window.feedStripAnchor = null;
    // global.css ma scroll-behavior: smooth, które gubi się przy animacjach AOS – przewijamy natychmiast
    const center = (behavior) => {
        const s = strip.getBoundingClientRect(), r = sel.getBoundingClientRect();
        strip.scrollTo({ left: strip.scrollLeft + r.left - s.left - s.width / 2 + r.width / 2, behavior: behavior });
    };
    if (anchor && anchor.date === sel.dataset.date) {
        // zmiana dnia: pasek ma nowy zakres dat, więc ustawiamy wybrany dzień tam, gdzie był przed podmianą, i płynnie centrujemy
        const s = strip.getBoundingClientRect(), r = sel.getBoundingClientRect();
        strip.scrollTo({ left: strip.scrollLeft + r.left - s.left - anchor.x, behavior: 'instant' });
        requestAnimationFrame(() => center('smooth'));
    } else {
        center('instant');
        requestAnimationFrame(() => center('instant'));
    }
})();
feedEnter();
feedBindSwipe();

// przełączenie dnia bez pełnego przeładowania zakładki (bez spinnera i animacji AOS)
function feedGoTo(date, dir) {
    const strip = document.getElementById('day-strip');
    const feed = document.getElementById('feed');
    if (!strip || !feed) {
        openDetailTab('today', 'list=today&date=' + date);
        return;
    }
    const current = window.feedNavTarget || feed.dataset.date;
    if (date === current) return;
    if (!dir) dir = date > current ? 1 : -1;
    window.feedNavTarget = date;
    window.feedNavDir = dir;
    if (window.feedNavBusy) return; // po bieżącym ładowaniu pójdziemy od razu do feedNavTarget

    const target = strip.querySelector('[data-date="' + date + '"]');
    window.feedStripAnchor = target ? { date: date, x: target.getBoundingClientRect().left - strip.getBoundingClientRect().left } : null;
    window.feedNavBusy = true;
    feedClipX(true);
    feed.style.transition = 'opacity .15s ease, transform .15s ease';
    feed.style.opacity = '0';
    feed.style.transform = 'translateX(' + (-dir * 24) + 'px)';

    const prevLink = localStorage.getItem('current_todo');
    localStorage.setItem('current_todo', 'list=today&date=' + date);
    window.feedEnterDir = dir;
    reloadTabSilently()
        .catch(() => {
            localStorage.setItem('current_todo', prevLink);
            window.feedEnterDir = null;
            window.feedNavTarget = null;
            feed.style.opacity = '';
            feed.style.transform = '';
            feedClipX();
            showAlert('error', 'Wystąpił problem połączenia z serwerem');
        })
        .finally(() => {
            window.feedNavBusy = false;
            const next = window.feedNavTarget;
            const shown = document.getElementById('feed');
            window.feedNavTarget = null;
            if (next && shown && next !== shown.dataset.date) feedGoTo(next, window.feedNavDir);
        });
}

function feedShiftDate(date, days) {
    const p = date.split('-').map(Number);
    const d = new Date(p[0], p[1] - 1, p[2] + days);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

// wjazd nowego dnia z kierunku, w którym przełączono
function feedEnter() {
    const dir = window.feedEnterDir;
    window.feedEnterDir = null;
    const feed = document.getElementById('feed');
    if (!dir || !feed) return;
    feedClipX();
    feed.style.transition = 'none';
    feed.style.opacity = '0';
    feed.style.transform = 'translateX(' + (dir * 24) + 'px)';
    requestAnimationFrame(() => requestAnimationFrame(() => {
        feed.style.transition = 'opacity .2s ease, transform .2s ease';
        feed.style.opacity = '';
        feed.style.transform = '';
    }));
}

// przesunięty w prawo feed wystaje poza stronę – pojawia się poziomy pasek, za nim pionowy scroll i skacze dolna nawigacja.
// Przycinamy na czas animacji tylko wrapper feedu (na stałe ucinałby cień przeciąganych kart).
// Wrapper jest poszerzony o padding kontenera, bo karty na mobile mają -mx-4 i inaczej traciłyby boki.
function feedClipX(hold) {
    const wrap = document.getElementById('feed-clip');
    if (!wrap) return;
    wrap.style.overflowX = 'clip';
    clearTimeout(window.feedClipTimer);
    if (!hold) window.feedClipTimer = setTimeout(() => { wrap.style.overflowX = ''; }, 400);
}

// swipe w lewo = następny dzień, w prawo = poprzedni
function feedBindSwipe() {
    const feed = document.getElementById('feed');
    const header = document.getElementById('sticky_nav_body');
    let start = null;
    const onStart = (e) => {
        start = e.touches.length === 1 ? { x: e.touches[0].clientX, y: e.touches[0].clientY, t: Date.now() } : null;
    };
    const onEnd = (e) => {
        if (!start || window.feedDragging) { start = null; return; }
        const dx = e.changedTouches[0].clientX - start.x;
        const dy = e.changedTouches[0].clientY - start.y;
        const quick = Date.now() - start.t < 700;
        start = null;
        if (!quick || Math.abs(dx) < 60 || Math.abs(dx) < Math.abs(dy) * 1.5) return;
        const from = window.feedNavTarget || feed.dataset.date;
        const step = dx < 0 ? 1 : -1;
        feedGoTo(feedShiftDate(from, step), step);
    };
    [feed, header].forEach(el => {
        if (!el) return;
        el.addEventListener('touchstart', onStart, { passive: true });
        el.addEventListener('touchend', onEnd, { passive: true });
        el.addEventListener('touchcancel', () => { start = null; }, { passive: true });
    });
}

function feedToggleLate(section) {
    const ul = section.querySelector('.feed-list');
    const open = ul.classList.toggle('hidden') === false;
    section.querySelector('.feed-late-chevron').classList.toggle('rotate-90', open);
}
</script>

<?php
$name_in_scripts = 'DetailsElement';
$delete_path = '';
$delete_v2 = '';
$path = 'components/panel/dashboard/element_details.php';
$close = "true";
include "../../popup_center.php";
?>
