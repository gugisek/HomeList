<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['reports', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Zgłoszenia błędów od użytkowników (Konto i ustawienia > Zgłoś błąd). Tylko do wglądu – bez statusów i odpowiedzi.
include "../../../scripts/conn_db.php";
require_once __DIR__ . '/../../../scripts/planner/planner_lib.php';
require_once __DIR__ . '/../../../scripts/reports/report_lib.php';

$per_page = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;
$total = (int)$conn->query('SELECT COUNT(*) AS c FROM bug_reports')->fetch_assoc()['c'];
$pages = max(1, (int)ceil($total / $per_page));
$can_delete = hl_can('reports', 'full');

$reports = [];
$stmt = $conn->prepare('SELECT bug_reports.*, users.name, users.sur_name, users.mail, users.profile_picture
                        FROM bug_reports LEFT JOIN users ON users.id = bug_reports.user_id
                        ORDER BY bug_reports.id DESC LIMIT ? OFFSET ?');
$stmt->bind_param('ii', $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $reports[] = $row;

// wyświetlone = przeczytane (kropka w sidebarze znika, "Nowe" zostaje do kolejnego wejścia)
$unseen = array_map(fn($r) => (int)$r['id'], array_filter($reports, fn($r) => $r['seen_at'] === null));
if ($unseen) $conn->query('UPDATE bug_reports SET seen_at = NOW() WHERE id IN (' . implode(',', $unseen) . ')');
$unseen_left = (int)$conn->query('SELECT COUNT(*) AS c FROM bug_reports WHERE seen_at IS NULL')->fetch_assoc()['c'];

$card = 'hl-card hl-card-pad';
$chip = 'inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-[#3d3d3d] px-2.5 py-1 text-[11px] font-medium text-gray-600 dark:text-gray-300';
$icon = fn($d, $cls = 'size-3.5') => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="' . $cls . ' flex-none"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '" /></svg>';
?>
<div id="reports_root" class="max-w-4xl mx-auto mb-28 md:mb-10">
    <div data-aos="fade-up" data-aos-delay="100" class="pt-2">
        <h1 class="hl-title">Zgłoszenia błędów</h1>
        <p class="hl-sub">
            Wiadomości wysłane przez użytkowników z Konto i ustawienia &rsaquo; Zgłoś błąd<?=$total ? ' · ' . $total . ' łącznie' : ''?>
        </p>
    </div>

    <?php if (!$reports): ?>
    <div data-aos="fade-up" data-aos-delay="150" class="mt-[20vh] flex flex-col items-center text-center">
        <span class="rounded-full bg-green-50 dark:bg-green-400/10 p-4 text-green-500"><?=$icon('M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'size-8')?></span>
        <p class="mt-4 font-medium text-lg text-black dark:text-gray-200 font-[poppins]">Cisza i spokój</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nikt jeszcze nie zgłosił żadnego błędu.</p>
    </div>
    <?php endif; ?>

    <div class="mt-6 flex flex-col gap-3">
        <?php foreach ($reports as $i => $r):
            $shots = hl_report_shots($r['screenshots']);
            $author = $r['name'] !== null ? trim($r['name'] . ' ' . $r['sur_name']) : 'Usunięte konto';
        ?>
        <div id="report_<?=(int)$r['id']?>" data-aos="fade-up" data-aos-delay="<?=min(150 + $i * 50, 400)?>" class="<?=$card?> duration-200">
            <div class="flex items-start gap-3">
                <img class="size-10 flex-none rounded-full object-cover border border-black/10" src="img/users_images/<?=hl_e($r['profile_picture'] ?: 'default.png')?>" alt="">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-200 truncate"><?=hl_e($author)?></p>
                        <?php if ($r['seen_at'] === null): ?>
                        <span class="rounded-full bg-green-400 px-2 py-0.5 text-[10px] font-semibold text-white">Nowe</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?=hl_e($r['mail'] ?? '')?><?=$r['mail'] ? ' · ' : ''?><?=hl_e(hl_short_date($r['created_at'], true))?></p>
                </div>
                <?php if ($can_delete): ?>
                <button type="button" onclick="reportDelete(<?=(int)$r['id']?>, this)" title="Usuń zgłoszenie" class="flex-none rounded-full p-2 text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-400/10 active:scale-90 duration-150">
                    <?=$icon('m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0', 'size-5')?>
                </button>
                <?php endif; ?>
            </div>

            <p class="mt-4 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line break-words"><?=hl_e($r['description'])?></p>

            <?php if ($shots): ?>
            <div class="mt-4 flex flex-wrap gap-2">
                <?php foreach ($shots as $n => $file):
                    $src = 'scripts/reports/screenshot.php?id=' . (int)$r['id'] . '&n=' . $n;
                ?>
                <button type="button" onclick="reportPreview('<?=$src?>')" class="size-24 sm:size-28 rounded-2xl overflow-hidden ring-1 ring-black/5 dark:ring-white/10 bg-gray-100 dark:bg-[#3d3d3d] hover:scale-105 hover:shadow-xl active:scale-95 duration-150">
                    <img class="size-full object-cover" loading="lazy" src="<?=$src?>" alt="Zrzut ekranu <?=$n + 1?>">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="mt-4 flex flex-wrap gap-1.5">
                <?php if ($r['app_version']): ?>
                <span class="<?=$chip?>" title="Wersja aplikacji"><?=$icon('M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z')?>v<?=hl_e($r['app_version'])?></span>
                <?php endif; ?>
                <span class="<?=$chip?>" title="<?=hl_e($r['user_agent'])?>"><?=$icon('M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3')?><?=hl_e(hl_report_device($r['user_agent']))?></span>
                <?php if ($r['viewport']): ?>
                <span class="<?=$chip?>" title="Rozmiar okna"><?=$icon('M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15')?><?=hl_e($r['viewport'])?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="mt-6 flex items-center justify-center gap-2 text-sm">
        <button type="button" <?=$page <= 1 ? 'disabled' : ''?> onclick="openPanelSite('reports', 'page=<?=$page - 1?>')" class="hl-btn hl-btn-ghost hl-btn-sm">Nowsze</button>
        <span class="px-2 text-gray-500 dark:text-gray-400"><?=$page?> / <?=$pages?></span>
        <button type="button" <?=$page >= $pages ? 'disabled' : ''?> onclick="openPanelSite('reports', 'page=<?=$page + 1?>')" class="hl-btn hl-btn-ghost hl-btn-sm">Starsze</button>
    </div>
    <?php endif; ?>
</div>

<!-- podgląd zrzutu na pełnym ekranie -->
<div id="report_preview" onclick="reportPreview(null)" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4 cursor-zoom-out opacity-0 duration-200">
    <img id="report_preview_img" class="max-h-full max-w-full rounded-2xl shadow-2xl" alt="">
</div>

<script>
// funkcje (nie const/let) – skrypt może zostać wykonany ponownie przy przeładowaniu podstrony
(function () {
    var dot = document.getElementById('reports_nav_dot');
    if (dot && <?=$unseen_left?> === 0) dot.remove();
})();

function reportPreview(src) {
    var box = document.getElementById('report_preview');
    if (!box) return;
    if (src) {
        document.getElementById('report_preview_img').src = src;
        box.classList.remove('hidden');
        box.classList.add('flex');
        requestAnimationFrame(function () { box.classList.remove('opacity-0'); });
    } else {
        box.classList.add('opacity-0');
        setTimeout(function () { box.classList.add('hidden'); box.classList.remove('flex'); }, 200);
    }
}

function reportDelete(id, btn) {
    if (!confirm('Usunąć to zgłoszenie razem ze zrzutami ekranu?')) return;
    var data = new FormData();
    data.append('id', id);
    btn.disabled = true;
    hlJson('scripts/reports/delete.php', data).then(function (r) {
        btn.disabled = false;
        showAlert(r.status, r.message);
        if (r.status !== 'success') return;
        var card = document.getElementById('report_' + id);
        card.classList.add('opacity-0', 'scale-95');
        setTimeout(function () { card.remove(); }, 200);
    });
}
</script>
