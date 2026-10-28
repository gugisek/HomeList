<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['dashboard', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Jedzenie: dziennik kalorii. Serwer daje szkielet + dane dnia (JSON), resztę rysuje i obsługuje js/food.js
// (pasek dni, podsumowanie, posiłki, arkusz wyszukiwarki / porcji / celu). Moduł włączany per użytkownik.
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
include __DIR__.'/../../../scripts/conn_db.php';
require_once __DIR__.'/../../../scripts/food/food_lib.php';
include_once __DIR__.'/../../../scripts/sync/sync_lib.php';

$user_id = (int)$_SESSION['login_id'];
$settings = hl_food_settings($conn, $user_id);
$sync_tag = hl_sync_tag($conn, $user_id, 'food');
$date = hl_food_valid_date($_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
$payload = $settings['enabled'] ? hl_food_payload($conn, $user_id, $date) : null;
$fork = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3v6a2 2 0 0 0 4 0V3M8 3v18M17 21V3c-2 1.4-3 4-3 7.5V13h3" /></svg>';
?>
<?=$sync_tag?>
<?php if (!$settings['enabled']): ?>
<div class="hl-page-head" data-aos="fade-up" data-aos-delay="100">
    <div class="min-w-0">
        <h1 class="hl-title">Jedzenie</h1>
        <p class="hl-sub">Licznik kalorii i makro</p>
    </div>
</div>
<div class="hl-card hl-card-pad mt-4 max-w-xl" data-aos="fade-up" data-aos-delay="150">
    <div class="fd-intro-art"><?=$fork?></div>
    <h2 class="hl-h2 mt-4">Licz kalorie bez reklam i bez zgadywania</h2>
    <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
        Dziennik posiłków z bazą ponad 11 tysięcy polskich produktów, skanerem kodów i daniami domowymi, które gotujesz razem z domownikami.
        Zanim coś dodasz, zobaczysz, jak zmieni Twój dzień.
    </p>
    <ul class="mt-4 flex flex-col gap-2 text-sm text-gray-600 dark:text-gray-300">
        <li class="flex gap-2"><span class="fd-dot low mt-1.5"></span>Twój dziennik widzisz tylko Ty</li>
        <li class="flex gap-2"><span class="fd-dot low mt-1.5"></span>Skład, alergeny i Nutri-Score z Open Food Facts</li>
        <li class="flex gap-2"><span class="fd-dot low mt-1.5"></span>Możesz to w każdej chwili wyłączyć w ustawieniach konta</li>
    </ul>
    <button type="button" onclick="hlFoodEnable(this)" class="hl-btn hl-btn-primary mt-6">Włącz licznik kalorii</button>
</div>
<script>
function hlFoodEnable(btn) {
    btn.disabled = true;
    var fd = new FormData();
    fd.append('enabled', '1');
    fetch('scripts/food/settings_save.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') { showAlert(res.status, res.message); btn.disabled = false; return; }
            if (typeof refreshLists === 'function') refreshLists();
            if (typeof hlRailRefresh === 'function') hlRailRefresh();
            openDetailTab('food', 'list=food');
        })
        .catch(() => { btn.disabled = false; showAlert('error', 'Nie udało się włączyć licznika'); });
}
</script>
<?php else: ?>
<div id="fd_root">
    <div id="sticky_nav_body" class="hl-page-head -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 bg-gradient-to-b from-[#fdfdfd] via-[#fdfdfd]/80 via-70% to-white/0 dark:from-[#3d3d3d] dark:via-[#3d3d3d]/80 dark:to-[#3d3d3d]/0" data-aos="fade-up" data-aos-delay="100">
        <div class="min-w-0">
            <h1 class="hl-title" id="fd_title" data-vt="title">Jedzenie</h1>
            <p class="hl-sub truncate" id="fd_subtitle" data-vt="sub"></p>
        </div>
        <div class="flex items-center gap-1 flex-none" data-vt="actions">
            <button type="button" id="fd_today_btn" onclick="hlFood.go(hlFood.today())" class="hidden text-xs font-medium px-3 py-1.5 rounded-full text-green-500 ring-1 ring-green-400/60 hover:bg-green-400 hover:text-white active:scale-95 duration-150">Dziś</button>
            <button type="button" onclick="hlFood.openScan()" title="Sprawdź produkt po kodzie" aria-label="Sprawdź produkt po kodzie" class="hl-icon-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5V5.25a1.5 1.5 0 0 1 1.5-1.5H7.5M16.5 3.75h2.25a1.5 1.5 0 0 1 1.5 1.5V7.5M20.25 16.5v2.25a1.5 1.5 0 0 1-1.5 1.5H16.5M7.5 20.25H5.25a1.5 1.5 0 0 1-1.5-1.5V16.5M8 8.5v7M11 8.5v7M13.5 8.5v7M16 8.5v7" /></svg>
            </button>
            <button type="button" onclick="hlFood.openAdd()" title="Dodaj jedzenie" aria-label="Dodaj jedzenie" class="hl-icon-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            </button>
        </div>
    </div>
    <?php /* ta sama geometria co #day-strip w today.php – przy przejściu Dziś ↔ Jedzenie zmieniają się tylko kropki / paski pod dniami */ ?>
    <div class="mt-2" data-aos="fade-up" data-aos-delay="120">
        <div id="fd_strip" class="fd-strip" data-vt="strip" role="tablist" aria-label="Dni"></div>
    </div>
    <?php /* jak #feed-clip w today.php: przycinany tylko na czas zmiany dnia, poszerzony o padding kontenera */ ?>
    <div id="fd_clip" class="-mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div id="fd_body" class="mt-4 md:grid md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] md:gap-5 md:items-start" data-aos="fade-up" data-aos-delay="150">
        <div class="md:sticky md:top-28">
            <div id="fd_hero" class="hl-card"></div>
            <p class="hidden md:block mt-3 px-2 text-[11px] fd-mut">Część danych: <a href="https://world.openfoodfacts.org" target="_blank" rel="noopener" class="underline">Open Food Facts</a> (ODbL)</p>
        </div>
        <div id="fd_meals" class="mt-4 md:mt-0 flex flex-col gap-3"></div>
    </div>
    </div>
    <p class="md:hidden mt-5 mb-24 text-center text-[11px] fd-mut">Część danych: <a href="https://world.openfoodfacts.org" target="_blank" rel="noopener" class="underline">Open Food Facts</a> (ODbL)</p>
    <?php /* dane w atrybucie, nie w <script> – loader zakładek wykonuje każdy skrypt z widoku */ ?>
    <div id="fd_data" hidden data-json="<?=htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')?>"></div>
</div>
<script>
// hlTabReady: przejście między zakładkami (hlTabSwap) czeka, aż widok się narysuje
window.hlTabReady = hlLoadScript('js/food.js?v=<?=filemtime(__DIR__.'/../../../js/food.js')?>').then(function () {
    var el = document.getElementById('fd_data');
    if (el) hlFood.init(JSON.parse(el.dataset.json));
});
</script>
<?php endif; ?>
