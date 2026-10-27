<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['food', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Baza produktów, trzy sekcje:
//  - Produkty: dodane przez użytkowników, czekające na akceptację (widzą je już autor i domownicy)
//  - Jednostki: zgłoszone jednostki porcji (np. opakowanie, którego nie zna OFF – autor widzi je od razu)
//  - Historia: wszystko, co dodali użytkownicy i moderatorzy – kto, kiedy, decyzja (kto i kiedy); usunięte są w archiwum zmian
// Akceptacja = widoczne dla wszystkich; odrzucenie = zostaje tylko u autora; usunięcie nie rusza dzienników (kopie wartości).
include "../../../scripts/conn_db.php";
require_once __DIR__ . '/../../../scripts/planner/planner_lib.php';
require_once __DIR__ . '/../../../scripts/food/food_lib.php';

$can_edit = hl_can('food', 'edit');
$can_delete = hl_can('food', 'full');
$me = (int)$_SESSION['login_id'];

$items = [];
$res = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM food_log l WHERE l.product_id = p.id) AS uses
                     FROM food_products p WHERE (p.source = 'user' OR (p.source = 'dish' AND p.public = 1)) AND p.status = 'pending' ORDER BY p.id LIMIT 200");
while ($row = $res->fetch_assoc()) $items[] = $row;
// składniki udostępnianych dań – moderator ocenia przepis, nie etykietę
$dish_items = [];
$dish_ids = array_map(fn($p) => (int)$p['id'], array_filter($items, fn($p) => $p['source'] === 'dish'));
if ($dish_ids) {
    $res = $conn->query('SELECT d.product_id, d.cooked_g, d.servings, i.grams, i.unit_label, i.unit_g, p.name, p.kcal FROM food_dishes d
                         JOIN food_dish_items i ON i.dish_id = d.id JOIN food_products p ON p.id = i.product_id
                         WHERE d.product_id IN (' . implode(',', $dish_ids) . ') ORDER BY i.sort, i.id');
    while ($row = $res->fetch_assoc()) $dish_items[(int)$row['product_id']][] = $row;
}

$units = [];
$res = $conn->query("SELECT un.id, un.label, un.grams, un.created_at, un.owner_id, p.name, p.brand, p.code, p.quantity, p.package_g, p.serving_g, p.per_ml
                     FROM food_product_units un JOIN food_products p ON p.id = un.product_id
                     WHERE un.status = 'pending' ORDER BY un.id LIMIT 200");
while ($row = $res->fetch_assoc()) $units[] = $row;

// historia: produkty użytkowników + zgłoszone jednostki, najnowsze na górze
$history = [];
$res = $conn->query("(SELECT IF(p.source = 'dish', 'dish', 'product') AS kind, p.id, p.name, p.brand, NULL AS label, NULL AS grams, p.per_ml, p.kcal, p.status,
                             p.created_at, p.owner_id, p.reviewed_by, p.reviewed_at
                      FROM food_products p WHERE p.source = 'user' OR (p.source = 'dish' AND p.public = 1))
                     UNION ALL
                     (SELECT 'unit', un.id, p.name, p.brand, un.label, un.grams, p.per_ml, NULL, un.status,
                             un.created_at, un.owner_id, un.reviewed_by, un.reviewed_at
                      FROM food_product_units un JOIN food_products p ON p.id = un.product_id WHERE un.owner_id IS NOT NULL)
                     ORDER BY created_at DESC, id DESC LIMIT 200");
while ($row = $res->fetch_assoc()) $history[] = $row;

// autorzy i moderatorzy jednym zapytaniem
$uids = array_unique(array_filter(array_map('intval', array_merge(array_column($items, 'owner_id'), array_column($units, 'owner_id'),
                                                                  array_column($history, 'owner_id'), array_column($history, 'reviewed_by')))));
$people = [];
if ($uids) {
    $res = $conn->query('SELECT id, name, sur_name, profile_picture FROM users WHERE id IN (' . implode(',', $uids) . ')');
    while ($u = $res->fetch_assoc()) $people[(int)$u['id']] = $u;
}
$who = fn($id) => isset($people[(int)$id]) ? trim($people[(int)$id]['name'] . ' ' . $people[(int)$id]['sur_name']) : 'Usunięte konto';
$pic = fn($id) => 'img/users_images/' . hl_e(($people[(int)$id]['profile_picture'] ?? '') ?: 'default.png');
$when = fn($d) => hl_e(date('j.m.Y H:i', strtotime($d)));

$stats = $conn->query("SELECT source, COUNT(*) FROM food_products WHERE status = 'approved' GROUP BY source")->fetch_all(MYSQLI_NUM);
$stats = array_column($stats, 1, 0);
$fmt = fn($v) => rtrim(rtrim(number_format((float)$v, 1, ',', ''), '0'), ',');
$icon = fn($d, $cls = 'size-4') => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="' . $cls . ' flex-none"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '" /></svg>';
$check = 'm4.5 12.75 6 6 9-13.5';

$tab = in_array($_GET['tab'] ?? '', ['products', 'units', 'history'], true) ? $_GET['tab'] : ($items ? 'products' : ($units ? 'units' : 'history'));
$tabs = ['products' => ['Produkty', count($items)], 'units' => ['Jednostki', count($units)], 'history' => ['Historia', null]];

// przyciski decyzji; w historii widoczne zależnie od statusu (data-show), żeby po kliknięciu nie przeładowywać strony
$actions = function (string $kind, int $id, bool $inHistory) use ($can_edit, $can_delete, $icon, $check) {
    if (!$can_edit) return '';
    $t = $kind === 'product' ? '' : ", '$kind'";
    $show = fn($s) => $inHistory ? ' data-show="' . $s . '"' : '';
    return '<div class="mt-3 flex flex-wrap gap-2">'
        . '<button type="button"' . $show('pending rejected') . ' onclick="foodModerate(' . $id . ", 'approve'$t)\" class=\"hl-btn hl-btn-green hl-btn-sm\">" . $icon($check) . 'Akceptuj</button>'
        . '<button type="button"' . $show('pending approved') . ' onclick="foodModerate(' . $id . ", 'reject'$t)\" class=\"hl-btn hl-btn-ghost hl-btn-sm\">" . ($inHistory ? 'Odrzuć / cofnij' : 'Odrzuć') . '</button>'
        . ($can_delete && $kind !== 'dish' ? '<button type="button" onclick="foodModerate(' . $id . ", 'delete'$t)\" class=\"hl-btn hl-btn-danger hl-btn-sm\">Usuń</button>" : '')
        . '</div>';
};
$empty = fn($title, $text) => '<div class="hl-empty"><div class="hl-empty-ico">' . $icon('M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'size-8') . '</div><h3>' . $title . '</h3><p>' . $text . '</p></div>';
?>
<div id="food_admin_root" class="max-w-4xl mx-auto mb-28 md:mb-10">
    <div data-aos="fade-up" data-aos-delay="100" class="pt-2">
        <h1 class="hl-title">Baza produktów</h1>
        <p class="hl-sub">
            <?=number_format((int)($stats['off'] ?? 0), 0, ',', ' ')?> z Open Food Facts · <?=(int)($stats['base'] ?? 0)?> podstawowych ·
            od użytkowników: <?=(int)($stats['user'] ?? 0)?> · dania domowe: <?=(int)($stats['dish'] ?? 0)?>
        </p>
    </div>

    <div data-aos="fade-up" data-aos-delay="120" role="tablist" class="mt-6 inline-flex max-w-full overflow-x-auto rounded-full bg-gray-100 dark:bg-[#2c2c2e] p-1 gap-1">
        <?php foreach ($tabs as $k => [$label, $count]): ?>
        <button type="button" role="tab" data-ftab="<?=$k?>" aria-selected="<?=$k === $tab ? 'true' : 'false'?>"
                class="fa-tab flex-none inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium duration-150">
            <?=$label?>
            <?php if ($count !== null): ?><span data-fcount="<?=$k?>" class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?=$count ? 'bg-amber-400 text-white' : 'bg-black/5 dark:bg-white/10 text-gray-500 dark:text-gray-400'?>"><?=$count?></span><?php endif; ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- produkty do akceptacji -->
    <section data-fpanel="products" class="mt-4 flex flex-col gap-3"<?=$tab !== 'products' ? ' hidden' : ''?>>
        <?php if (!$items) echo $empty('Wszystko sprawdzone', 'Nowe produkty od użytkowników pojawią się tutaj.'); ?>
        <?php foreach ($items as $p):
            $match = hl_food_macros_match((float)$p['kcal'], (float)$p['protein'], (float)$p['fat'], (float)$p['carbs'], 0.15, 25, 2 * (float)$p['fiber']);
            $est = round(hl_food_kcal_from_macros((float)$p['protein'], (float)$p['fat'], (float)$p['carbs']));
            $u = $p['per_ml'] ? 'ml' : 'g';
        ?>
        <div id="fp_<?=(int)$p['id']?>" class="hl-card hl-card-pad duration-300">
            <div class="flex items-start gap-3">
                <img class="size-10 flex-none rounded-full object-cover border border-black/10" src="<?=$pic($p['owner_id'])?>" alt="">
                <div class="min-w-0 flex-1">
                    <p class="text-base font-medium text-gray-900 dark:text-gray-100"><?=hl_e($p['name'])?><?php if ($p['brand']): ?> <span class="text-gray-500 dark:text-gray-400 font-normal">· <?=hl_e($p['brand'])?></span><?php endif; ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <?=$p['source'] === 'dish' ? '<span class="font-semibold text-indigo-500">Danie domowe do udostępnienia</span> · ' : ''?>
                        <?=hl_e($who($p['owner_id']))?> · <?=$when($p['created_at'])?>
                        <?=$p['code'] ? ' · kod ' . hl_e($p['code']) : ''?> · w dziennikach: <?=(int)$p['uses']?> · wartości na 100 <?=$u?>
                    </p>
                    <?php if (!empty($p['photo'])): ?>
                    <img src="<?=hl_e(hl_food_photo_url($p))?>" alt="Zdjęcie dania" loading="lazy" class="mt-3 w-full max-w-sm aspect-[16/10] object-cover rounded-2xl ring-1 ring-black/5 dark:ring-white/10">
                    <?php endif; ?>
                    <?php if ($p['source'] === 'dish' && !empty($dish_items[(int)$p['id']])): $di = $dish_items[(int)$p['id']]; ?>
                    <ul class="mt-3 divide-y divide-black/5 dark:divide-white/10 text-sm">
                        <?php foreach ($di as $it): ?>
                        <li class="flex justify-between gap-3 py-1.5">
                            <span class="min-w-0 text-gray-800 dark:text-gray-200"><?=hl_e($it['name'])?>
                                <span class="text-gray-500 dark:text-gray-400"><?=$it['unit_label'] && $it['unit_g'] > 0 ? $fmt($it['grams'] / $it['unit_g']) . ' × ' . hl_e($it['unit_label']) . ' · ' : ''?><?=$fmt($it['grams'])?> g</span></span>
                            <span class="flex-none text-gray-500 dark:text-gray-400"><?=round($it['kcal'] * $it['grams'] / 100)?> kcal</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <?=$di[0]['cooked_g'] ? 'Waga gotowego dania: ' . $fmt($di[0]['cooked_g']) . ' g' : 'Waga = suma składników'?><?=$di[0]['servings'] ? ' · garnek na ' . (int)$di[0]['servings'] . ' ' . ((int)$di[0]['servings'] === 1 ? 'porcję' : (in_array((int)$di[0]['servings'] % 10, [2, 3, 4]) && !in_array((int)$di[0]['servings'] % 100, [12, 13, 14]) ? 'porcje' : 'porcji')) : ''?>
                    </p>
                    <?php endif; ?>
                    <div class="mt-3 grid grid-cols-4 gap-2 text-center">
                        <?php foreach ([['kcal', 'kcal', 'var(--fd-k)'], ['protein', 'białko', 'var(--fd-p)'], ['fat', 'tłuszcz', 'var(--fd-f)'], ['carbs', 'węgle', 'var(--fd-c)']] as [$k, $label, $c]): ?>
                        <div class="rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 py-2">
                            <p class="text-base font-semibold" style="color: <?=$k === 'kcal' ? 'inherit' : $c?>"><?=$fmt($p[$k])?></p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400"><?=$label?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($p['source'] !== 'dish'): ?>
                    <p class="mt-2 inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium <?=$match ? 'bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300'?>">
                        <?=$match ? 'Kalorie zgadzają się z makro (~' . $est . ' kcal)' : 'Sprawdź: z makro wychodzi ~' . $est . ' kcal, a podano ' . $fmt($p['kcal'])?>
                    </p>
                    <?php endif; ?>
                    <?=$actions($p['source'] === 'dish' ? 'dish' : 'product', (int)$p['id'], false)?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- jednostki porcji do akceptacji -->
    <section data-fpanel="units" class="mt-4 flex flex-col gap-3"<?=$tab !== 'units' ? ' hidden' : ''?>>
        <?php if (!$units) echo $empty('Brak nowych jednostek', 'Gdy ktoś doda np. wagę opakowania, której nie zna Open Food Facts, pojawi się tutaj.'); ?>
        <?php foreach ($units as $un):
            $u = $un['per_ml'] ? 'ml' : 'g';
            $known = array_filter([$un['quantity'] ? 'opis: ' . $un['quantity'] : null, $un['package_g'] !== null ? 'opakowanie ' . $fmt($un['package_g']) . " $u" : null,
                                   $un['serving_g'] !== null ? 'porcja ' . $fmt($un['serving_g']) . " $u" : null]);
        ?>
        <div id="fu_<?=(int)$un['id']?>" class="hl-card hl-card-pad duration-300">
            <div class="flex items-start gap-3">
                <img class="size-10 flex-none rounded-full object-cover border border-black/10" src="<?=$pic($un['owner_id'])?>" alt="">
                <div class="min-w-0 flex-1">
                    <p class="text-base font-medium text-gray-900 dark:text-gray-100"><?=hl_e($un['label'])?> = <?=$fmt($un['grams'])?> <?=$u?></p>
                    <p class="text-sm text-gray-700 dark:text-gray-300"><?=hl_e($un['name'])?><?php if ($un['brand']): ?> <span class="text-gray-500 dark:text-gray-400">· <?=hl_e($un['brand'])?></span><?php endif; ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <?=hl_e($who($un['owner_id']))?> · <?=$when($un['created_at'])?>
                        <?=$un['code'] ? ' · kod ' . hl_e($un['code']) : ''?> · OFF: <?=$known ? hl_e(implode(', ', $known)) : 'brak danych o opakowaniu'?>
                    </p>
                    <?=$actions('unit', (int)$un['id'], false)?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- historia dodań i decyzji -->
    <section data-fpanel="history" class="mt-4"<?=$tab !== 'history' ? ' hidden' : ''?>>
        <div class="flex flex-wrap gap-2">
            <?php foreach (['all' => 'Wszystko', 'product' => 'Produkty', 'unit' => 'Jednostki', 'dish' => 'Dania', 'pending' => 'Czeka', 'approved' => 'Zaakceptowane', 'rejected' => 'Odrzucone'] as $k => $label): ?>
            <button type="button" data-fhist="<?=$k?>" class="fa-filter rounded-full px-3 py-1.5 text-xs font-medium duration-150"><?=$label?></button>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 flex flex-col gap-2">
            <?php if (!$history) echo $empty('Pusto', 'Tu pojawi się wszystko, co użytkownicy i administratorzy dodali do bazy.'); ?>
            <?php foreach ($history as $h):
                $u = $h['per_ml'] ? 'ml' : 'g';
                $self = $h['reviewed_by'] !== null && (int)$h['reviewed_by'] === (int)$h['owner_id'];
            ?>
            <div id="fh_<?=$h['kind']?>_<?=(int)$h['id']?>" data-kind="<?=$h['kind']?>" data-status="<?=$h['status']?>" class="hl-card px-4 py-3 duration-300">
                <div class="flex items-start gap-3">
                    <img class="size-9 flex-none rounded-full object-cover border border-black/10" src="<?=$pic($h['owner_id'])?>" alt="">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="rounded-full bg-black/5 dark:bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><?=['unit' => 'Jednostka', 'dish' => 'Danie', 'product' => 'Produkt'][$h['kind']]?></span>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 min-w-0">
                                <?php if ($h['kind'] === 'unit'): ?><?=hl_e($h['label'])?> = <?=$fmt($h['grams'])?> <?=$u?> <span class="font-normal text-gray-500 dark:text-gray-400">· <?=hl_e($h['name'])?></span>
                                <?php else: ?><?=hl_e($h['name'])?><?=$h['brand'] ? ' <span class="font-normal text-gray-500 dark:text-gray-400">· ' . hl_e($h['brand']) . '</span>' : ''?> <span class="font-normal text-gray-500 dark:text-gray-400">· <?=$fmt($h['kcal'])?> kcal/100 <?=$u?></span><?php endif; ?>
                            </p>
                            <span data-badge class="fa-badge ml-auto"></span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Dodał(a): <?=hl_e($who($h['owner_id']))?> · <?=$when($h['created_at'])?></p>
                        <p data-decision class="text-xs text-gray-500 dark:text-gray-400">
                            <?php if ($h['status'] === 'pending'): ?>Czeka na decyzję
                            <?php elseif ($self): ?>Dodane przez moderatora – zatwierdzone od razu<?=$h['reviewed_at'] ? ' · ' . $when($h['reviewed_at']) : ''?>
                            <?php else: ?><?=$h['status'] === 'approved' ? 'Zaakceptował(a)' : 'Odrzucił(a)'?>: <?=$h['reviewed_by'] !== null ? hl_e($who($h['reviewed_by'])) : 'nieznane (przed zapisem historii)'?><?=$h['reviewed_at'] ? ' · ' . $when($h['reviewed_at']) : ''?>
                            <?php endif; ?>
                        </p>
                        <?=$actions($h['kind'], (int)$h['id'], true)?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="mt-4 ml-1 text-xs text-gray-500 dark:text-gray-400">Ostatnie 200 pozycji. Usunięte znikają z tej listy – ślad zostaje w Archiwum zmian.</p>
    </section>

    <p class="mt-6 ml-1 text-xs text-gray-500 dark:text-gray-400">Odrzucone zostaje widoczne tylko dla autora. Usunięcie nie zmienia wpisów w dziennikach – mają własną kopię wartości.</p>
</div>

<style>
#food_admin_root .fa-tab { color: var(--hl-ink-2); }
#food_admin_root .fa-tab[aria-selected="true"] { background: var(--hl-card); color: var(--hl-ink); box-shadow: 0 4px 12px -6px rgba(0, 0, 0, .3); }
#food_admin_root .fa-filter { background: var(--hl-chip); color: var(--hl-ink-2); }
#food_admin_root .fa-filter.is-on { background: var(--hl-sel-bg); color: var(--hl-sel-ink); }
#food_admin_root .fa-badge { flex: none; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
#food_admin_root [data-status="pending"] .fa-badge { background: rgba(245, 158, 11, .16); color: #b45309; }
#food_admin_root [data-status="approved"] .fa-badge { background: rgba(74, 222, 128, .16); color: #15803d; }
#food_admin_root [data-status="rejected"] .fa-badge { background: rgba(244, 63, 94, .12); color: #be123c; }
#food_admin_root [data-status="pending"] .fa-badge::before { content: "Czeka"; }
#food_admin_root [data-status="approved"] .fa-badge::before { content: "Zaakceptowane"; }
#food_admin_root [data-status="rejected"] .fa-badge::before { content: "Odrzucone"; }
@media (prefers-color-scheme: dark) {
    #food_admin_root [data-status="pending"] .fa-badge { color: #fcd34d; }
    #food_admin_root [data-status="approved"] .fa-badge { color: #4ade80; }
    #food_admin_root [data-status="rejected"] .fa-badge { color: #fb7185; }
}
</style>

<script>
(function () {
    var root = document.getElementById('food_admin_root');
    var histFilter = 'all';

    function showTab(name) {
        root.querySelectorAll('[data-ftab]').forEach(function (b) { b.setAttribute('aria-selected', b.dataset.ftab === name ? 'true' : 'false'); });
        root.querySelectorAll('[data-fpanel]').forEach(function (p) { p.hidden = p.dataset.fpanel !== name; });
    }
    function applyHist() {
        root.querySelectorAll('[data-fhist]').forEach(function (b) { b.classList.toggle('is-on', b.dataset.fhist === histFilter); });
        root.querySelectorAll('[id^="fh_"]').forEach(function (r) {
            r.hidden = !(histFilter === 'all' || r.dataset.kind === histFilter || r.dataset.status === histFilter);
            // przyciski zależne od statusu (akceptacja tylko gdy nie jest zaakceptowane itd.)
            r.querySelectorAll('[data-show]').forEach(function (b) { b.hidden = b.dataset.show.split(' ').indexOf(r.dataset.status) < 0; });
        });
    }
    root.querySelectorAll('[data-ftab]').forEach(function (b) { b.addEventListener('click', function () { showTab(b.dataset.ftab); }); });
    root.querySelectorAll('[data-fhist]').forEach(function (b) { b.addEventListener('click', function () { histFilter = b.dataset.fhist; applyHist(); }); });
    applyHist();

    function fade(el) {
        if (!el) return;
        el.style.opacity = '0';
        el.style.transform = 'scale(.97)';
        setTimeout(function () { el.remove(); }, 300);
    }
    function pad(n) { return String(n).padStart(2, '0'); }

    window.foodModerate = function (id, action, type) {
        var kind = type === 'unit' || type === 'dish' ? type : 'product';
        if (action === 'delete' && !confirm(kind === 'unit' ? 'Usunąć tę jednostkę?' : 'Usunąć ten produkt z bazy?')) return;
        var fd = new FormData();
        fd.append('id', id);
        fd.append('action', action);
        if (type === 'unit') fd.append('type', type);
        hlJson('scripts/food/moderate.php', fd).then(function (r) {
            showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
            if (r.status !== 'success') return;
            // karta w kolejce znika po każdej decyzji
            var card = document.getElementById((kind === 'unit' ? 'fu_' : 'fp_') + id);
            if (card && !card.dataset.gone) {
                card.dataset.gone = '1';
                fade(card);
                var c = root.querySelector('[data-fcount="' + (kind === 'unit' ? 'units' : 'products') + '"]');
                if (c) {
                    c.textContent = Math.max(0, +c.textContent - 1);
                    if (c.textContent === '0') c.className = 'rounded-full px-2 py-0.5 text-[11px] font-semibold bg-black/5 dark:bg-white/10 text-gray-500 dark:text-gray-400';
                }
            }
            // wiersz historii: nowy status i decyzja bez przeładowania
            var row = document.getElementById('fh_' + kind + '_' + id);
            if (row) {
                if (action === 'delete') fade(row);
                else {
                    var d = new Date();
                    row.dataset.status = action === 'approve' ? 'approved' : 'rejected';
                    row.querySelector('[data-decision]').textContent = (action === 'approve' ? 'Zaakceptował(a)' : 'Odrzucił(a)') + ': Ty · '
                        + d.getDate() + '.' + pad(d.getMonth() + 1) + '.' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
                    applyHist();
                }
            }
            var dot = document.getElementById('food_nav_dot');
            if (dot && !root.querySelector('[id^="fp_"]:not([data-gone]), [id^="fu_"]:not([data-gone])')) dot.remove();
        });
    };
})();
</script>
