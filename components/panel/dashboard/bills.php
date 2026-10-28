<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Opłaty: stałe koszty (subskrypcje, umowy, na kartę, raty, karnety…). Serwer daje szkielet + dane (JSON),
// resztę rysuje i obsługuje js/bills.js (podsumowanie, „Wymaga uwagi”, lista, arkusz dodawania / szczegółów / statystyk).
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
include __DIR__.'/../../../scripts/conn_db.php';
require_once __DIR__.'/../../../scripts/bills/bills_lib.php';
include_once __DIR__.'/../../../scripts/sync/sync_lib.php';

$user_id = (int)$_SESSION['login_id'];
$sync_tag = hl_sync_tag($conn, $user_id, 'bills');
$payload = hl_bill_overview($conn, $user_id);
?>
<?=$sync_tag?>
<div id="bl_root">
    <div id="sticky_nav_body" class="hl-page-head -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 bg-gradient-to-b from-[#fdfdfd] via-[#fdfdfd]/80 via-70% to-white/0 dark:from-[#3d3d3d] dark:via-[#3d3d3d]/80 dark:to-[#3d3d3d]/0" data-aos="fade-up" data-aos-delay="100">
        <div class="min-w-0">
            <h1 class="hl-title">Opłaty</h1>
            <p class="hl-sub truncate" id="bl_subtitle"></p>
        </div>
        <div class="flex items-center gap-1 flex-none">
            <button type="button" onclick="hlBills.open('stats')" title="Statystyki" aria-label="Statystyki" class="hl-icon-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
            </button>
            <button type="button" onclick="hlBills.open('type')" title="Dodaj opłatę" aria-label="Dodaj opłatę" class="hl-icon-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            </button>
        </div>
    </div>
    <div id="bl_body" class="mt-3 pb-28 md:pb-10 md:grid md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] md:gap-5 md:items-start" data-aos="fade-up" data-aos-delay="150">
        <div class="min-w-0">
            <div id="bl_hero" class="hl-card"></div>
            <div id="bl_alerts"></div>
            <div id="bl_cal" class="hidden md:block"></div>
        </div>
        <div class="min-w-0">
            <div id="bl_seg" class="mt-4 md:mt-0"></div>
            <div id="bl_list"></div>
        </div>
    </div>
    <?php /* dane w atrybucie, nie w <script> – loader zakładek wykonuje każdy skrypt z widoku */ ?>
    <div id="bl_data" hidden data-json="<?=htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')?>"></div>
</div>
<script>
window.hlTabReady = hlLoadScript('js/bills.js?v=<?=filemtime(__DIR__.'/../../../js/bills.js')?>').then(function () {
    var el = document.getElementById('bl_data');
    if (el) hlBills.init(JSON.parse(el.dataset.json));
});
</script>
