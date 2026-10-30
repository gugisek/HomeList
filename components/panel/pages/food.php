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
$res = $conn->query("SELECT p.*, d.ingredients, d.allergens, d.traces, d.additives, d.nutriscore, d.nova, d.palm_oil, d.vegan, d.vegetarian,
                            (SELECT COUNT(*) FROM food_log l WHERE l.product_id = p.id) AS uses
                     FROM food_products p LEFT JOIN food_product_details d ON d.product_id = p.id WHERE (p.source = 'user' OR (p.source = 'dish' AND p.public = 1)) AND p.status = 'pending' ORDER BY p.id LIMIT 200");
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
$res = $conn->query("SELECT un.id, un.product_id, un.label, un.grams, un.created_at, un.owner_id, p.name, p.brand, p.code, p.quantity, p.package_g, p.serving_g, p.per_ml
                     FROM food_product_units un JOIN food_products p ON p.id = un.product_id
                     WHERE un.status = 'pending' ORDER BY un.id LIMIT 200");
while ($row = $res->fetch_assoc()) $units[] = $row;

// propozycje zmian dowolnych produktów: porównanie „teraz → propozycja” z aktualnym stanem produktu
$edits = [];
$res = $conn->query("SELECT e.*, p.name, p.brand, p.code, p.source, p.per_ml, p.owner_id AS product_owner,
                            (SELECT COUNT(*) FROM food_log l WHERE l.product_id = e.product_id) AS uses
                     FROM food_product_edits e JOIN food_products p ON p.id = e.product_id WHERE e.status = 'pending' ORDER BY e.id LIMIT 200");
while ($row = $res->fetch_assoc()) {
    $prod = $conn->query('SELECT * FROM food_products WHERE id = ' . (int)$row['product_id'])->fetch_assoc();
    $row['cur'] = hl_food_edit_values(hl_food_edit_data($conn, $prod));
    $row['ch'] = json_decode($row['data'], true) ?: [];
    $edits[] = $row;
}

// historia: produkty użytkowników + zgłoszone jednostki + propozycje zmian, najnowsze na górze
$history = [];
$res = $conn->query("(SELECT IF(p.source = 'dish', 'dish', 'product') AS kind, p.id, p.id AS product_id, p.name, p.brand, NULL AS label, NULL AS grams, p.per_ml, p.kcal, p.status,
                             p.created_at, p.owner_id, p.reviewed_by, p.reviewed_at, p.code, p.review_note, p.label_photo,
                             (SELECT COUNT(*) FROM food_log l WHERE l.product_id = p.id) AS uses, NULL AS edit_data
                      FROM food_products p WHERE p.source = 'user' OR (p.source = 'dish' AND p.public = 1))
                     UNION ALL
                     (SELECT 'unit', un.id, un.product_id, p.name, p.brand, un.label, un.grams, p.per_ml, NULL, un.status,
                             un.created_at, un.owner_id, un.reviewed_by, un.reviewed_at, p.code, NULL, NULL, NULL, NULL
                      FROM food_product_units un JOIN food_products p ON p.id = un.product_id WHERE un.owner_id IS NOT NULL)
                     UNION ALL
                     (SELECT 'edit', e.id, e.product_id, p.name, p.brand, NULL, NULL, p.per_ml, p.kcal, e.status,
                             e.created_at, e.user_id, e.reviewed_by, e.reviewed_at, p.code, e.review_note, NULL, NULL, e.data
                      FROM food_product_edits e JOIN food_products p ON p.id = e.product_id)
                     ORDER BY created_at DESC, id DESC LIMIT 200");
while ($row = $res->fetch_assoc()) $history[] = $row;

// autorzy i moderatorzy jednym zapytaniem
$uids = array_unique(array_filter(array_map('intval', array_merge(array_column($items, 'owner_id'), array_column($units, 'owner_id'), array_column($edits, 'user_id'),
                                                                  array_column($edits, 'product_owner'), array_column($history, 'owner_id'), array_column($history, 'reviewed_by')))));
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

$tab = in_array($_GET['tab'] ?? '', ['products', 'edits', 'units', 'history'], true) ? $_GET['tab'] : ($items ? 'products' : ($edits ? 'edits' : ($units ? 'units' : 'history')));
$tabs = ['products' => ['Produkty', count($items)], 'edits' => ['Poprawki', count($edits)], 'units' => ['Jednostki', count($units)], 'history' => ['Historia', null]];

// przyciski decyzji; w historii widoczne zależnie od statusu (data-show), żeby po kliknięciu nie przeładowywać strony
$actions = function (string $kind, int $id, bool $inHistory, int $productId = 0) use ($can_edit, $can_delete, $icon, $check) {
    if (!$can_edit) return '';
    $t = $kind === 'product' ? '' : ", '$kind'";
    $show = fn($s) => $inHistory ? ' data-show="' . $s . '"' : '';
    // odrzucenie produktu / dania / poprawki pyta o powód (autor zobaczy go w „Twój wkład”), jednostki – od razu
    $reject = $kind === 'unit' ? 'foodModerate(' . $id . ", 'reject'$t)" : 'foodReject(' . $id . ", '$kind')";
    // poprawka: akceptacja zaznaczonych pól; zaakceptowanej nie da się cofnąć (produkt już zmieniony)
    $approve = $kind === 'edit' ? 'foodEditApprove(' . $id . ')' : 'foodModerate(' . $id . ", 'approve'$t)";
    return '<div class="mt-3 flex flex-wrap gap-2">'
        . '<button type="button"' . $show('pending rejected') . ' onclick="' . $approve . '" class="hl-btn hl-btn-green hl-btn-sm">' . $icon($check) . ($kind === 'edit' && !$inHistory ? 'Akceptuj zaznaczone' : 'Akceptuj') . '</button>'
        . ($kind === 'product' ? '<button type="button" onclick="foodEdit(' . $id . ')" class="hl-btn hl-btn-soft hl-btn-sm">' . $icon('m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125') . 'Popraw</button>' : '')
        . '<button type="button"' . $show($kind === 'edit' ? 'pending' : 'pending approved') . ' onclick="' . $reject . '" class="hl-btn hl-btn-ghost hl-btn-sm">' . ($inHistory && $kind !== 'edit' ? 'Odrzuć / cofnij' : 'Odrzuć') . '</button>'
        // wszystkie jednostki produktu: poprawki, nowe, decyzje – w jednym oknie
        . ($kind !== 'dish' && $productId ? '<button type="button" onclick="foodUnits(' . $productId . ')" class="hl-btn hl-btn-ghost hl-btn-sm">' . $icon('M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z') . 'Jednostki produktu</button>' : '')
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
                    <?php if (!empty($p['label_photo'])): ?>
                    <a href="<?=hl_e(hl_food_label_photo_url($p))?>" target="_blank" rel="noopener" class="group relative mt-3 block w-full max-w-sm overflow-hidden rounded-2xl ring-1 ring-black/5 dark:ring-white/10 bg-black">
                        <img src="<?=hl_e(hl_food_label_photo_url($p))?>" alt="Zdjęcie etykiety" loading="lazy" class="w-full max-h-72 object-contain group-hover:opacity-90 duration-150">
                        <span class="absolute left-2 top-2 rounded-full bg-black/60 px-2.5 py-1 text-[11px] font-medium text-white">Zdjęcie etykiety · otwórz w pełnym rozmiarze</span>
                    </a>
                    <?php elseif ($p['source'] === 'user'): ?>
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Bez zdjęcia etykiety</p>
                    <?php endif; ?>
                    <?php if (!empty($p['review_note'])): ?>
                    <p class="mt-2 rounded-xl bg-gray-50 dark:bg-white/5 px-3 py-2 text-xs text-gray-600 dark:text-gray-300">Poprawka po odrzuceniu. Wcześniejsza uwaga: „<?=hl_e($p['review_note'])?>”</p>
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
                    <?php if ($p['source'] !== 'dish'):
                        $extra = array_filter(['nasycone' => $p['sat_fat'], 'cukry' => $p['sugars'], 'błonnik' => $p['fiber'], 'sól' => $p['salt']], fn($v) => $v !== null);
                        $an = hl_food_allergen_names();
                        $tagList = fn($s) => implode(', ', array_map(fn($t) => $an[$t] ?? $t, array_filter(explode(',', (string)$s))));
                        $diet = $p['vegan'] === null && $p['vegetarian'] === null ? null : ((int)$p['vegan'] === 1 ? 'wegańskie' : ((int)$p['vegetarian'] === 1 ? 'wegetariańskie' : 'z mięsem / rybą'));
                        $facts = array_filter([
                            $extra ? implode(' · ', array_map(fn($k, $v) => $k . ' ' . $fmt($v) . ' g', array_keys($extra), $extra)) : null,
                            $p['quantity'] ? 'opakowanie ' . hl_e($p['quantity']) : null,
                            $p['nutriscore'] ? 'Nutri-Score <span class="fd-ns ' . hl_e($p['nutriscore']) . '" style="width:18px;height:18px;font-size:10px;border-radius:5px">' . hl_e($p['nutriscore']) . '</span>' : null,
                            $p['nova'] ? 'NOVA ' . (int)$p['nova'] : null,
                            $p['palm_oil'] !== null ? ((int)$p['palm_oil'] ? 'z olejem palmowym' : 'bez oleju palmowego') : null,
                            $diet,
                        ]);
                    ?>
                    <?php if ($facts): ?><p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-600 dark:text-gray-300"><?=implode('<span class="text-gray-300 dark:text-gray-600">·</span>', $facts)?></p><?php endif; ?>
                    <?php if ($p['ingredients']): ?>
                    <details class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                        <summary class="cursor-pointer font-medium text-gray-700 dark:text-gray-200">Skład<?=$p['additives'] ? ' · dodatki: ' . hl_e(strtoupper(str_replace(',', ', ', $p['additives']))) : ''?></summary>
                        <p class="mt-1 leading-relaxed"><?=hl_e($p['ingredients'])?></p>
                    </details>
                    <?php endif; ?>
                    <?php if ($p['allergens'] || $p['traces']): ?>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300"><?=$p['allergens'] ? '<b class="font-medium">Alergeny:</b> ' . hl_e($tagList($p['allergens'])) : ''?><?=$p['allergens'] && $p['traces'] ? ' · ' : ''?><?=$p['traces'] ? '<b class="font-medium">Może zawierać:</b> ' . hl_e($tagList($p['traces'])) : ''?></p>
                    <?php endif; ?>
                    <p class="mt-2 inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium <?=$match ? 'bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300'?>">
                        <?=$match ? 'Kalorie zgadzają się z makro (~' . $est . ' kcal)' : 'Sprawdź: z makro wychodzi ~' . $est . ' kcal, a podano ' . $fmt($p['kcal'])?>
                    </p>
                    <?php endif; ?>
                    <?=$actions($p['source'] === 'dish' ? 'dish' : 'product', (int)$p['id'], false, (int)$p['id'])?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- propozycje zmian produktów: porównanie z aktualnym produktem, akceptacja wybranych pól -->
    <section data-fpanel="edits" class="mt-4 flex flex-col gap-3"<?=$tab !== 'edits' ? ' hidden' : ''?>>
        <?php if (!$edits) echo $empty('Brak poprawek', 'Gdy ktoś zaproponuje zmianę danych produktu (np. inne wartości z etykiety), pojawi się tutaj.'); ?>
        <?php
        $src_label = ['off' => 'Open Food Facts', 'base' => 'produkt podstawowy', 'user' => 'od użytkownika'];
        $field_labels = hl_food_edit_fields();
        foreach ($edits as $e):
            $cur = $e['cur'];
            $after = array_merge($cur, $e['ch']);
            $ml = (bool)$cur['per_ml'];
            $ok = $after['kcal'] !== null && hl_food_macros_match((float)$after['kcal'], (float)$after['protein'], (float)$after['fat'], (float)$after['carbs'], 0.15, 25, 2 * (float)$after['fiber']);
            $est = round(hl_food_kcal_from_macros((float)$after['protein'], (float)$after['fat'], (float)$after['carbs']));
            $macro_changed = (bool)array_intersect_key($e['ch'], array_flip(['kcal', 'protein', 'fat', 'carbs', 'fiber']));
        ?>
        <div id="fe_<?=(int)$e['id']?>" class="hl-card hl-card-pad duration-300">
            <div class="flex items-start gap-3">
                <img class="size-10 flex-none rounded-full object-cover border border-black/10" src="<?=$pic($e['user_id'])?>" alt="">
                <div class="min-w-0 flex-1">
                    <p class="text-base font-medium text-gray-900 dark:text-gray-100"><?=hl_e($e['name'])?><?php if ($e['brand']): ?> <span class="text-gray-500 dark:text-gray-400 font-normal">· <?=hl_e($e['brand'])?></span><?php endif; ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Proponuje: <?=hl_e($who($e['user_id']))?> · <?=$when($e['created_at'])?> ·
                        <?=$src_label[$e['source']] ?? hl_e($e['source'])?><?=$e['source'] === 'user' && $e['product_owner'] ? ' (' . hl_e($who($e['product_owner'])) . ')' : ''?>
                        <?=$e['code'] ? ' · kod ' . hl_e($e['code']) : ''?> · w dziennikach: <?=(int)$e['uses']?>
                    </p>
                    <?php if ($e['note']): ?>
                    <p class="mt-2 rounded-xl bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-700 dark:text-gray-300">„<?=hl_e($e['note'])?>”</p>
                    <?php endif; ?>
                    <?php if ($e['label_photo']): $lp = hl_e(hl_food_edit_photo_url($e)); ?>
                    <a href="<?=$lp?>" target="_blank" rel="noopener" class="group relative mt-3 block w-full max-w-sm overflow-hidden rounded-2xl ring-1 ring-black/5 dark:ring-white/10 bg-black">
                        <img src="<?=$lp?>" alt="Zdjęcie etykiety" loading="lazy" class="w-full max-h-72 object-contain group-hover:opacity-90 duration-150">
                        <span class="absolute left-2 top-2 rounded-full bg-black/60 px-2.5 py-1 text-[11px] font-medium text-white">Zdjęcie etykiety · otwórz w pełnym rozmiarze</span>
                    </a>
                    <?php else: ?>
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Bez zdjęcia etykiety</p>
                    <?php endif; ?>
                    <div class="mt-3 divide-y divide-black/5 dark:divide-white/10 rounded-2xl ring-1 ring-black/5 dark:ring-white/10 px-3">
                        <?php foreach ($e['ch'] as $k => $v):
                            if (!isset($field_labels[$k])) continue;
                            $same = !hl_food_edit_diff([$k => $cur[$k]], [$k => $v]);
                            $long = $k === 'ingredients';
                        ?>
                        <label class="flex items-start gap-3 py-2.5 cursor-pointer<?=$same ? ' opacity-60' : ''?>">
                            <input type="checkbox" data-efield="<?=$k?>" <?=$same ? 'disabled' : 'checked'?> class="mt-0.5 size-5 flex-none">
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs text-gray-500 dark:text-gray-400"><?=$field_labels[$k]?><?=$same ? ' · produkt ma już tę wartość' : ''?></span>
                                <?php if ($long): ?>
                                <span class="mt-1 block text-xs leading-relaxed text-gray-400 dark:text-gray-500 line-through break-words"><?=hl_e(hl_food_edit_text($k, $cur[$k], $ml))?></span>
                                <span class="mt-1 block text-sm leading-relaxed text-gray-900 dark:text-gray-100 break-words"><?=hl_e(hl_food_edit_text($k, $v, $ml))?></span>
                                <?php else: ?>
                                <span class="block text-sm break-words">
                                    <span class="text-gray-400 dark:text-gray-500 line-through"><?=hl_e(hl_food_edit_text($k, $cur[$k], $ml))?></span>
                                    <span class="text-gray-400 px-1">→</span>
                                    <b class="font-semibold text-gray-900 dark:text-gray-100"><?=hl_e(hl_food_edit_text($k, $v, (bool)$after['per_ml']))?></b>
                                </span>
                                <?php endif; ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($macro_changed): ?>
                    <p class="mt-2 inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium <?=$ok ? 'bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300'?>">
                        <?=$ok ? 'Po zmianie kalorie zgadzają się z makro (~' . $est . ' kcal)' : 'Sprawdź: po zmianie z makro wychodzi ~' . $est . ' kcal, a kalorie to ' . $fmt($after['kcal'])?>
                    </p>
                    <?php endif; ?>
                    <?=$actions('edit', (int)$e['id'], false, (int)$e['product_id'])?>
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
                    <?=$actions('unit', (int)$un['id'], false, (int)$un['product_id'])?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- historia dodań i decyzji -->
    <section data-fpanel="history" class="mt-4"<?=$tab !== 'history' ? ' hidden' : ''?>>
        <input type="search" id="fa_hq" class="hl-input mb-3" placeholder="Szukaj: nazwa, marka, kod, autor…" autocomplete="off" aria-label="Szukaj w historii">
        <div class="flex flex-wrap gap-2">
            <?php foreach (['all' => 'Wszystko', 'product' => 'Produkty', 'edit' => 'Poprawki', 'unit' => 'Jednostki', 'dish' => 'Dania', 'pending' => 'Czeka', 'approved' => 'Zaakceptowane', 'rejected' => 'Odrzucone'] as $k => $label): ?>
            <button type="button" data-fhist="<?=$k?>" class="fa-filter rounded-full px-3 py-1.5 text-xs font-medium duration-150"><?=$label?></button>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 flex flex-col gap-2">
            <?php if (!$history) echo $empty('Pusto', 'Tu pojawi się wszystko, co użytkownicy i administratorzy dodali do bazy.'); ?>
            <?php foreach ($history as $h):
                $u = $h['per_ml'] ? 'ml' : 'g';
                $self = $h['reviewed_by'] !== null && (int)$h['reviewed_by'] === (int)$h['owner_id'];
            ?>
            <div id="fh_<?=$h['kind']?>_<?=(int)$h['id']?>" data-kind="<?=$h['kind']?>" data-status="<?=$h['status']?>" data-q="<?=hl_e(hl_food_norm($h['name'] . ' ' . $h['brand'] . ' ' . $h['label'] . ' ' . $h['code'] . ' ' . $who($h['owner_id'])))?>" class="hl-card px-4 py-3 duration-300">
                <div class="flex items-start gap-3">
                    <img class="size-9 flex-none rounded-full object-cover border border-black/10" src="<?=$pic($h['owner_id'])?>" alt="">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="rounded-full bg-black/5 dark:bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><?=['unit' => 'Jednostka', 'dish' => 'Danie', 'product' => 'Produkt', 'edit' => 'Poprawka'][$h['kind']]?></span>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 min-w-0">
                                <?php if ($h['kind'] === 'unit'): ?><?=hl_e($h['label'])?> = <?=$fmt($h['grams'])?> <?=$u?> <span class="font-normal text-gray-500 dark:text-gray-400">· <?=hl_e($h['name'])?></span>
                                <?php else: ?><?=hl_e($h['name'])?><?=$h['brand'] ? ' <span class="font-normal text-gray-500 dark:text-gray-400">· ' . hl_e($h['brand']) . '</span>' : ''?> <span class="font-normal text-gray-500 dark:text-gray-400">· <?=$fmt($h['kcal'])?> kcal/100 <?=$u?></span><?php endif; ?>
                            </p>
                            <span data-badge class="fa-badge ml-auto"></span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"><?=$h['kind'] === 'edit' ? 'Proponuje' : 'Dodał(a)'?>: <?=hl_e($who($h['owner_id']))?> · <?=$when($h['created_at'])?><?=!in_array($h['kind'], ['unit', 'edit'], true) ? ' · w dziennikach: ' . (int)$h['uses'] : ''?><?=$h['kind'] !== 'dish' && $h['code'] ? ' · kod ' . hl_e($h['code']) : ''?><?=$h['label_photo'] ? ' · <a class="underline" target="_blank" rel="noopener" href="' . hl_e(hl_food_label_photo_url($h)) . '">etykieta</a>' : ''?></p>
                        <?php if ($h['kind'] === 'edit'): $hch = json_decode((string)$h['edit_data'], true) ?: []; $hl = hl_food_edit_fields(); ?>
                        <p class="text-xs text-gray-600 dark:text-gray-300 break-words">Zmiany: <?=hl_e(implode(' · ', array_map(fn($k, $v) => ($hl[$k] ?? $k) . ' → ' . mb_strimwidth(hl_food_edit_text($k, $v, (bool)$h['per_ml']), 0, 60, '…'), array_keys($hch), $hch)))?></p>
                        <?php endif; ?>
                        <?php if ($h['review_note']): ?><p class="text-xs text-gray-500 dark:text-gray-400">Uwaga: „<?=hl_e($h['review_note'])?>”</p><?php endif; ?>
                        <p data-decision class="text-xs text-gray-500 dark:text-gray-400">
                            <?php if ($h['status'] === 'pending'): ?>Czeka na decyzję
                            <?php elseif ($self): ?><?=$h['kind'] === 'edit' ? 'Zmiana moderatora – od razu w produkcie' : 'Dodane przez moderatora – zatwierdzone od razu'?><?=$h['reviewed_at'] ? ' · ' . $when($h['reviewed_at']) : ''?>
                            <?php else: ?><?=$h['status'] === 'approved' ? 'Zaakceptował(a)' : 'Odrzucił(a)'?>: <?=$h['reviewed_by'] !== null ? hl_e($who($h['reviewed_by'])) : 'nieznane (przed zapisem historii)'?><?=$h['reviewed_at'] ? ' · ' . $when($h['reviewed_at']) : ''?>
                            <?php endif; ?>
                        </p>
                        <?=$actions($h['kind'], (int)$h['id'], true, (int)$h['product_id'])?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="mt-4 ml-1 text-xs text-gray-500 dark:text-gray-400">Ostatnie 200 pozycji. Usunięte znikają z tej listy – ślad zostaje w Archiwum zmian.</p>
    </section>

    <p class="mt-6 ml-1 text-xs text-gray-500 dark:text-gray-400">Odrzucone zostaje widoczne tylko dla autora. Usunięcie nie zmienia wpisów w dziennikach – mają własną kopię wartości.</p>

    <?php if ($can_edit):
        $fa_chip = 'inline-flex items-center gap-1.5 h-9 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-white/[.06] dark:text-gray-300 duration-150 peer-checked:bg-gray-900 peer-checked:text-white dark:peer-checked:bg-white dark:peer-checked:text-black peer-focus-visible:ring-2 peer-focus-visible:ring-green-400';
        $fa_radio = function (string $name, array $opts) use ($fa_chip) {
            $out = '<div class="mt-1.5 flex flex-wrap gap-1.5">';
            foreach ($opts as $v => $label) $out .= '<label class="relative cursor-pointer"><input type="radio" name="' . $name . '" value="' . hl_e((string)$v) . '" class="peer sr-only"><span class="' . $fa_chip . '">' . $label . '</span></label>';
            return $out . '</div>';
        };
        $fa_checks = function (string $name) use ($fa_chip) {
            $out = '<div class="mt-1.5 flex flex-wrap gap-1.5">';
            foreach (hl_food_allergen_names() as $k => $label) $out .= '<label class="relative cursor-pointer"><input type="checkbox" name="' . $name . '" value="' . $k . '" class="peer sr-only"><span class="' . $fa_chip . '">' . $label . '</span></label>';
            return $out . '</div>';
        };
        $fa_num = fn($name, $label) => '<label class="block"><span class="hl-label">' . $label . '</span><input name="' . $name . '" type="text" inputmode="decimal" class="hl-input" autocomplete="off"></label>';
    ?>
    <!-- poprawianie produktu użytkownika: wszystkie pola + podgląd etykiety obok -->
    <dialog id="fa_edit" class="fa-dialog w-[min(980px,calc(100vw-24px))]">
        <form id="fa_edit_form" method="dialog" class="flex max-h-[calc(100dvh-48px)] flex-col">
            <div class="flex items-center gap-3 px-5 pt-5 pb-3">
                <h2 class="hl-popup-title !p-0 flex-1 min-w-0 truncate">Popraw produkt</h2>
                <span id="fa_edit_status" class="fa-badge"></span>
                <button type="button" data-fa-close class="hl-icon-btn" aria-label="Zamknij"><?=$icon('M6 18 18 6M6 6l12 12', 'size-5')?></button>
            </div>
            <!-- relative: ukryte pola wyboru (sr-only, absolute) zostają w przewijanej części, zamiast rozpychać okno -->
            <div class="relative min-h-0 flex-1 overflow-y-auto px-5 pb-4">
                <div id="fa_edit_grid" class="grid gap-5">
                    <div id="fa_edit_photo" class="hidden md:sticky md:top-0 self-start">
                        <a target="_blank" rel="noopener" class="block overflow-hidden rounded-2xl bg-black ring-1 ring-black/5 dark:ring-white/10"><img alt="Zdjęcie etykiety" class="w-full max-h-[70vh] object-contain"></a>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Zdjęcie etykiety od autora – kliknij, żeby otworzyć w pełnym rozmiarze.</p>
                    </div>
                    <div class="flex flex-col gap-3">
                        <p id="fa_edit_note" class="hidden rounded-xl bg-gray-50 dark:bg-white/5 px-3 py-2 text-xs text-gray-600 dark:text-gray-300"></p>
                        <label class="block"><span class="hl-label">Nazwa</span><input name="name" type="text" maxlength="200" class="hl-input" required></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block"><span class="hl-label">Marka</span><input name="brand" type="text" maxlength="120" class="hl-input"></label>
                            <label class="block"><span class="hl-label">Kod kreskowy</span><input name="code" type="text" inputmode="numeric" class="hl-input"></label>
                        </div>
                        <div>
                            <span class="hl-label">Wartości na 100</span>
                            <?=$fa_radio('per_ml', ['0' => 'g', '1' => 'ml'])?>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3"><?=$fa_num('kcal', 'kcal')?><?=$fa_num('protein', 'Białko g')?><?=$fa_num('fat', 'Tłuszcz g')?><?=$fa_num('carbs', 'Węglowodany g')?></div>
                        <p id="fa_edit_check" class="rounded-xl px-3 py-1.5 text-xs font-medium"></p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3"><?=$fa_num('sat_fat', 'w tym nasycone g')?><?=$fa_num('sugars', 'w tym cukry g')?><?=$fa_num('fiber', 'Błonnik g')?><?=$fa_num('salt', 'Sól g')?></div>
                        <div class="grid grid-cols-3 gap-3"><?=$fa_num('package_g', 'Opakowanie g/ml')?><label class="block"><span class="hl-label">Porcja – nazwa</span><input name="unit_label" type="text" maxlength="60" class="hl-input" placeholder="np. plaster"></label><?=$fa_num('unit_g', 'Porcja g/ml')?></div>
                        <label class="block"><span class="hl-label">Skład</span><textarea name="ingredients" rows="4" maxlength="5000" class="hl-input !h-auto py-2.5"></textarea></label>
                        <div><span class="hl-label">Alergeny</span><?=$fa_checks('allergens')?></div>
                        <div><span class="hl-label">Może zawierać</span><?=$fa_checks('traces')?></div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div><span class="hl-label">Nutri-Score</span><?=$fa_radio('nutriscore', ['' => 'brak'] + array_combine(['a', 'b', 'c', 'd', 'e'], array_map(fn($x) => '<span class="fd-ns ' . $x . '" style="width:18px;height:18px;font-size:10px;border-radius:5px">' . $x . '</span>', ['a', 'b', 'c', 'd', 'e'])))?></div>
                            <div><span class="hl-label">NOVA</span><?=$fa_radio('nova', ['' => 'brak', 1 => '1', 2 => '2', 3 => '3', 4 => '4'])?></div>
                            <div><span class="hl-label">Olej palmowy</span><?=$fa_radio('palm_oil', ['' => 'nie wiadomo', 1 => 'zawiera', 0 => 'bez'])?></div>
                            <div><span class="hl-label">Dieta</span><?=$fa_radio('diet', ['' => 'nie wiadomo', 'vegan' => 'wegańskie', 'vegetarian' => 'wegetariańskie', 'none' => 'z mięsem / rybą'])?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 border-t border-black/5 dark:border-white/10 px-5 py-4">
                <button type="button" data-fa-close class="hl-btn hl-btn-ghost">Anuluj</button>
                <button type="button" data-fa-save="0" class="hl-btn hl-btn-soft">Zapisz poprawki</button>
                <button type="button" data-fa-save="1" class="hl-btn hl-btn-green"><?=$icon($check)?>Zapisz i akceptuj</button>
            </div>
        </form>
    </dialog>

    <!-- wszystkie jednostki porcji produktu: poprawki, nowe, decyzje (rysuje foodUnits z units_admin.php) -->
    <dialog id="fa_units" class="fa-dialog w-[min(620px,calc(100vw-24px))]">
        <div class="flex max-h-[calc(100dvh-48px)] flex-col">
            <div class="flex items-start gap-3 px-5 pt-5 pb-3">
                <div class="min-w-0 flex-1">
                    <h2 id="fa_units_title" class="hl-popup-title !p-0 truncate">Jednostki produktu</h2>
                    <p id="fa_units_sub" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"></p>
                </div>
                <button type="button" data-fa-close class="hl-icon-btn" aria-label="Zamknij"><?=$icon('M6 18 18 6M6 6l12 12', 'size-5')?></button>
            </div>
            <div id="fa_units_body" class="relative min-h-0 flex-1 overflow-y-auto px-5 pb-5"></div>
        </div>
    </dialog>

    <!-- powód odrzucenia – autor zobaczy go w ustawieniach Jedzenia („Twój wkład”) -->
    <dialog id="fa_reject" class="fa-dialog w-[min(460px,calc(100vw-24px))]">
        <form method="dialog" class="p-5">
            <h2 class="hl-popup-title !p-0">Odrzucić?</h2>
            <p id="fa_reject_text" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Produkt zostanie tylko u autora. Napisz, co poprawić – zobaczy to w „Twój wkład” i będzie mógł wysłać poprawkę.</p>
            <div class="mt-3 flex flex-wrap gap-1.5">
                <?php foreach (['Wartości nie zgadzają się z etykietą', 'Brak zdjęcia etykiety', 'Duplikat produktu', 'Niepełna nazwa'] as $r): ?>
                <button type="button" data-fa-reason class="rounded-full bg-gray-100 dark:bg-white/[.06] px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300"><?=$r?></button>
                <?php endforeach; ?>
            </div>
            <textarea id="fa_reject_note" rows="3" maxlength="300" class="hl-input !h-auto mt-3 py-2.5" placeholder="Powód (opcjonalnie)"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" data-fa-close class="hl-btn hl-btn-ghost">Anuluj</button>
                <button type="button" id="fa_reject_ok" class="hl-btn hl-btn-danger">Odrzuć</button>
            </div>
        </form>
    </dialog>
    <?php endif; ?>
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
/* atrybut hidden musi wygrać z klasami flex / hl-btn (zakładki, przyciski zależne od statusu) */
#food_admin_root [hidden] { display: none !important; }
#food_admin_root .fa-dialog { margin: auto; padding: 0; border: 0; border-radius: 24px; overflow: hidden; max-height: calc(100dvh - 48px); background: var(--hl-card); color: var(--hl-ink); box-shadow: 0 30px 80px -20px rgba(0, 0, 0, .5); }
#food_admin_root .fa-dialog::backdrop { background: rgba(0, 0, 0, .45); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }
#food_admin_root .fa-dialog[open] { animation: fa-in .25s cubic-bezier(.2, .8, .2, 1); }
@keyframes fa-in { from { opacity: 0; transform: translateY(12px) scale(.98); } }
#fa_edit_grid.has-photo { grid-template-columns: 1fr; }
@media (min-width: 768px) { #fa_edit_grid.has-photo { grid-template-columns: minmax(0, 1fr) minmax(0, 1.3fr); } }
#fa_edit_check.is-ok { background: rgba(74, 222, 128, .14); color: #15803d; }
#fa_edit_check.is-warn { background: rgba(245, 158, 11, .16); color: #b45309; }
@media (prefers-color-scheme: dark) {
    #fa_edit_check.is-ok { color: #4ade80; }
    #fa_edit_check.is-warn { color: #fcd34d; }
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
        var q = foodNorm(document.getElementById('fa_hq').value).split(' ').filter(Boolean);
        root.querySelectorAll('[id^="fh_"]').forEach(function (r) {
            r.hidden = !(histFilter === 'all' || r.dataset.kind === histFilter || r.dataset.status === histFilter)
                || q.some(function (t) { return r.dataset.q.indexOf(t) < 0; });
            // przyciski zależne od statusu (akceptacja tylko gdy nie jest zaakceptowane itd.)
            r.querySelectorAll('[data-show]').forEach(function (b) { b.hidden = b.dataset.show.split(' ').indexOf(r.dataset.status) < 0; });
        });
    }
    root.querySelectorAll('[data-ftab]').forEach(function (b) { b.addEventListener('click', function () { showTab(b.dataset.ftab); }); });
    root.querySelectorAll('[data-fhist]').forEach(function (b) { b.addEventListener('click', function () { histFilter = b.dataset.fhist; applyHist(); }); });
    document.getElementById('fa_hq').addEventListener('input', applyHist);
    applyHist();

    // jak hl_food_norm(): małe litery, bez polskich znaków
    function foodNorm(s) {
        return String(s || '').toLowerCase().replace(/ł/g, 'l').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9%]+/g, ' ').trim();
    }

    function fade(el) {
        if (!el) return;
        el.style.opacity = '0';
        el.style.transform = 'scale(.97)';
        setTimeout(function () { el.remove(); }, 300);
    }
    function pad(n) { return String(n).padStart(2, '0'); }

    window.foodModerate = function (id, action, type, note, fields) {
        var kind = type === 'unit' || type === 'dish' || type === 'edit' ? type : 'product';
        if (action === 'delete' && !confirm(kind === 'unit' ? 'Usunąć tę jednostkę?' : kind === 'edit' ? 'Usunąć tę propozycję zmiany?' : 'Usunąć ten produkt z bazy?')) return;
        var fd = new FormData();
        fd.append('id', id);
        fd.append('action', action);
        if (type === 'unit' || type === 'edit') fd.append('type', type);
        if (note) fd.append('note', note);
        if (fields) fd.append('fields', fields);
        hlJson('scripts/food/moderate.php', fd).then(function (r) {
            showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
            if (r.status !== 'success') return;
            // karta w kolejce znika po każdej decyzji
            var card = document.getElementById((kind === 'unit' ? 'fu_' : kind === 'edit' ? 'fe_' : 'fp_') + id);
            if (card && !card.dataset.gone) {
                card.dataset.gone = '1';
                fade(card);
                var c = root.querySelector('[data-fcount="' + (kind === 'unit' ? 'units' : kind === 'edit' ? 'edits' : 'products') + '"]');
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
            if (dot && !root.querySelector('[id^="fp_"]:not([data-gone]), [id^="fu_"]:not([data-gone]), [id^="fe_"]:not([data-gone])')) dot.remove();
        });
    };

    // poprawka: w kolejce akceptujemy tylko zaznaczone pola, w historii – całość
    window.foodEditApprove = function (id) {
        var card = document.getElementById('fe_' + id), fields;
        if (card) {
            fields = [].map.call(card.querySelectorAll('[data-efield]:checked'), function (i) { return i.dataset.efield; });
            if (!fields.length) return showAlert('warning', 'Zaznacz co najmniej jedną zmianę albo odrzuć propozycję');
        }
        foodModerate(id, 'approve', 'edit', null, fields ? fields.join(',') : null);
    };

    var edit = document.getElementById('fa_edit'), rej = document.getElementById('fa_reject');
    if (!edit) return;
    root.querySelectorAll('[data-fa-close]').forEach(function (b) { b.addEventListener('click', function () { b.closest('dialog').close(); }); });
    // klik w tło zamyka
    [edit, rej].forEach(function (d) { d.addEventListener('click', function (ev) { if (ev.target === d) d.close(); }); });

    // odrzucenie z powodem
    var rejectFor = null;
    window.foodReject = function (id, kind) {
        rejectFor = { id: id, kind: kind };
        document.getElementById('fa_reject_note').value = '';
        document.getElementById('fa_reject_text').textContent = kind === 'edit'
            ? 'Produkt zostanie bez zmian. Napisz dlaczego – autor propozycji zobaczy to w „Twój wkład”.'
            : 'Produkt zostanie tylko u autora. Napisz, co poprawić – zobaczy to w „Twój wkład” i będzie mógł wysłać poprawkę.';
        rej.showModal();
    };
    rej.querySelectorAll('[data-fa-reason]').forEach(function (b) {
        b.addEventListener('click', function () {
            var t = document.getElementById('fa_reject_note');
            t.value = t.value.trim() ? t.value.trim() + '. ' + b.textContent : b.textContent;
            t.focus();
        });
    });
    document.getElementById('fa_reject_ok').addEventListener('click', function () {
        if (!rejectFor) return;
        rej.close();
        foodModerate(rejectFor.id, 'reject', rejectFor.kind === 'dish' || rejectFor.kind === 'edit' ? rejectFor.kind : undefined, document.getElementById('fa_reject_note').value.trim());
    });

    // poprawianie produktu: dane z product_edit.php, zapis przez product_save.php (moderator może od razu zaakceptować)
    var form = document.getElementById('fa_edit_form'), editId = null;
    var setVal = function (name, v) {
        form.querySelectorAll('[name="' + name + '"]').forEach(function (i) {
            if (i.type === 'radio') i.checked = i.value === (v === null || v === undefined ? '' : String(v));
            else if (i.type === 'checkbox') i.checked = (v || []).indexOf(i.value) >= 0;
            else i.value = v === null || v === undefined ? '' : String(v);
        });
    };
    var num = function (name) { return parseFloat(String(form.elements[name].value).replace(',', '.')); };
    var check = function () {
        var c = document.getElementById('fa_edit_check'), k = num('kcal');
        var est = 4 * (num('protein') || 0) + 4 * (num('carbs') || 0) + 9 * (num('fat') || 0) + 2 * (num('fiber') || 0);
        if (isNaN(k)) { c.className = 'rounded-xl px-3 py-1.5 text-xs font-medium'; c.textContent = ''; return; }
        var ok = Math.abs(est - k) <= Math.max(25, k * 0.15);
        c.className = 'rounded-xl px-3 py-1.5 text-xs font-medium ' + (ok ? 'is-ok' : 'is-warn');
        c.textContent = ok ? 'Kalorie zgadzają się z makro (~' + Math.round(est) + ' kcal)' : 'Z makro wychodzi ~' + Math.round(est) + ' kcal, a wpisano ' + k;
    };
    form.addEventListener('input', check);
    window.foodEdit = function (id) {
        fetch('scripts/food/product_edit.php?id=' + id, { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (r) {
            if (r.status !== 'success') return showAlert(r.status || 'error', r.message || 'Nie udało się wczytać produktu');
            var p = r.product;
            editId = p.id;
            form.reset();
            ['name', 'brand', 'code', 'kcal', 'protein', 'fat', 'carbs', 'sat_fat', 'sugars', 'fiber', 'salt', 'package_g', 'unit_label', 'unit_g', 'ingredients',
             'nutriscore', 'nova', 'palm_oil', 'diet', 'allergens', 'traces'].forEach(function (k) { setVal(k, p[k]); });
            setVal('per_ml', p.per_ml ? 1 : 0);
            edit.dataset.status = p.status;
            var photo = document.getElementById('fa_edit_photo');
            photo.classList.toggle('hidden', !p.label_photo);
            document.getElementById('fa_edit_grid').classList.toggle('has-photo', !!p.label_photo);
            if (p.label_photo) { photo.querySelector('img').src = p.label_photo; photo.querySelector('a').href = p.label_photo; }
            var note = document.getElementById('fa_edit_note');
            note.classList.toggle('hidden', !p.review_note);
            note.textContent = p.review_note ? 'Uwaga przy odrzuceniu: „' + p.review_note + '”' : '';
            edit.querySelector('[data-fa-save="1"]').hidden = p.status === 'approved';
            check();
            edit.showModal();
        });
    };
    edit.querySelectorAll('[data-fa-save]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!form.reportValidity()) return;
            var fd = new FormData(), approve = b.dataset.faSave === '1';
            fd.append('id', editId);
            ['name', 'brand', 'code', 'ingredients', 'unit_label'].forEach(function (k) { fd.append(k, form.elements[k].value.trim()); });
            ['kcal', 'protein', 'fat', 'carbs', 'sat_fat', 'sugars', 'fiber', 'salt', 'package_g', 'unit_g'].forEach(function (k) { fd.append(k, form.elements[k].value.trim().replace(',', '.')); });
            ['per_ml', 'nutriscore', 'nova', 'palm_oil', 'diet'].forEach(function (k) { var c = form.querySelector('[name="' + k + '"]:checked'); fd.append(k, c ? c.value : ''); });
            ['allergens', 'traces'].forEach(function (k) {
                fd.append(k, [].map.call(form.querySelectorAll('[name="' + k + '"]:checked'), function (i) { return i.value; }).join(','));
            });
            if (approve) fd.append('approve', 1);
            b.disabled = true;
            hlJson('scripts/food/product_save.php', fd).then(function (r) {
                b.disabled = false;
                showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
                if (r.status !== 'success') return;
                edit.close();
                // karta z nowymi wartościami – najprościej przeładować podstronę na tej samej zakładce
                var tab = root.querySelector('[data-ftab][aria-selected="true"]');
                if (typeof openPanelSite === 'function') openPanelSite('food', 'tab=' + (tab ? tab.dataset.ftab : 'products'));
                else location.reload();
            });
        });
    });
    // jednostki produktu: lista wszystkich (każdy status), poprawki w miejscu, nowe od razu zaakceptowane
    var unitsDlg = document.getElementById('fa_units'), unitsPid = 0, unitsDirty = false;
    var escH = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
    var numPl = function (v) { return String(Math.round(v * 10) / 10).replace('.', ','); };
    var dateTxt = function (d) { if (!d) return ''; var x = new Date(d.replace(' ', 'T')); return x.getDate() + '.' + pad(x.getMonth() + 1) + '.' + x.getFullYear(); };
    var SRC = { off: 'Open Food Facts', base: 'podstawowy', user: 'od użytkownika', dish: 'danie' };
    unitsDlg.addEventListener('click', function (ev) { if (ev.target === unitsDlg) unitsDlg.close(); });
    // zmiany w jednostkach zmieniają kolejkę i historię – po zamknięciu odświeżamy podstronę
    unitsDlg.addEventListener('close', function () {
        if (!unitsDirty) return;
        unitsDirty = false;
        var tab = root.querySelector('[data-ftab][aria-selected="true"]');
        if (typeof openPanelSite === 'function') openPanelSite('food', 'tab=' + (tab ? tab.dataset.ftab : 'units'));
    });

    function unitsRender(r) {
        var p = r.product, u = p.per_ml ? 'ml' : 'g', body = document.getElementById('fa_units_body');
        document.getElementById('fa_units_title').textContent = p.name;
        document.getElementById('fa_units_sub').textContent = [p.brand, p.code ? 'kod ' + p.code : 'bez kodu', p.quantity, SRC[p.source], numPl(p.kcal) + ' kcal / 100 ' + u].filter(Boolean).join(' · ');
        var field = 'h-10 rounded-xl bg-gray-50 dark:bg-white/5 px-3 text-sm text-gray-900 dark:text-gray-100 outline-none focus:ring-2 focus:ring-green-400';
        var html = '<p class="hl-eyebrow mt-1">Jednostki (' + r.units.length + ')</p><div class="mt-2 flex flex-col gap-2">';
        if (!r.units.length) html += '<p class="text-sm text-gray-500 dark:text-gray-400">Produkt nie ma jeszcze własnych jednostek.</p>';
        r.units.forEach(function (x) {
            var who = x.owner ? 'Dodał(a): ' + escH(x.owner) + ' · ' + dateTxt(x.created_at) : 'Od autora produktu / z bazy';
            var dec = x.status === 'pending' ? 'czeka na decyzję' : x.reviewer ? (x.status === 'approved' ? 'zaakceptował(a) ' : 'odrzucił(a) ') + escH(x.reviewer) + ' · ' + dateTxt(x.reviewed_at) : '';
            html += '<div class="rounded-2xl ring-1 ring-black/5 dark:ring-white/10 p-3" data-unit="' + x.id + '" data-status="' + x.status + '">'
                + '<div class="flex flex-wrap items-center gap-2">'
                + '<input data-ulabel class="' + field + ' min-w-0 flex-1" maxlength="30" value="' + escH(x.label) + '" aria-label="Nazwa jednostki">'
                + '<span class="text-sm text-gray-400">=</span>'
                + '<label class="flex items-center gap-1"><input data-ugrams inputmode="decimal" class="' + field + ' w-20 text-right" value="' + numPl(x.grams) + '" aria-label="Waga"><span class="text-xs text-gray-500">' + u + '</span></label>'
                + '<span class="fa-badge"></span></div>'
                + '<p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">' + who + (dec ? ' · ' + dec : '') + '</p>'
                + '<div class="mt-2 flex flex-wrap gap-1.5">'
                + '<button type="button" data-uact="save" class="hl-btn hl-btn-soft hl-btn-sm" hidden>Zapisz zmianę</button>'
                + (x.status !== 'approved' ? '<button type="button" data-uact="approve" class="hl-btn hl-btn-green hl-btn-sm">Akceptuj</button>' : '')
                + (x.status !== 'rejected' && x.owner ? '<button type="button" data-uact="reject" class="hl-btn hl-btn-ghost hl-btn-sm">Odrzuć</button>' : '')
                + (r.can_delete ? '<button type="button" data-uact="delete" class="hl-btn hl-btn-danger hl-btn-sm">Usuń</button>' : '')
                + '</div></div>';
        });
        html += '</div>';
        if (r.label_units.length) {
            html += '<p class="hl-eyebrow mt-5">Z etykiety produktu</p><div class="mt-2 flex flex-wrap gap-1.5">'
                + r.label_units.map(function (x) { return '<span class="rounded-full bg-gray-100 dark:bg-white/[.06] px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300">' + escH(x.label) + ' = ' + numPl(x.grams) + ' ' + u + '</span>'; }).join('')
                + '</div><p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">' + (p.source === 'off' ? 'Z Open Food Facts – zmienia je import.' : 'Opakowanie zmienisz w „Popraw” produktu.') + '</p>';
        }
        html += '<p class="hl-eyebrow mt-5">Nowa jednostka</p>'
            + '<div class="mt-2 flex flex-wrap gap-1.5">' + ['opakowanie', 'sztuka', 'plaster', 'kromka', 'łyżka', 'szklanka', 'porcja'].map(function (l) {
                return '<button type="button" data-upreset class="rounded-full bg-gray-100 dark:bg-white/[.06] px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300">' + l + '</button>';
            }).join('') + '</div>'
            + '<div class="mt-2 flex items-center gap-2"><input id="fa_unew_label" class="' + field + ' min-w-0 flex-1" maxlength="30" placeholder="np. opakowanie" aria-label="Nazwa nowej jednostki">'
            + '<label class="flex items-center gap-1"><input id="fa_unew_grams" inputmode="decimal" class="' + field + ' w-20 text-right" placeholder="150" aria-label="Waga nowej jednostki"><span class="text-xs text-gray-500">' + u + '</span></label>'
            + '<button type="button" id="fa_unew_add" class="hl-btn hl-btn-primary hl-btn-sm">Dodaj</button></div>'
            + '<p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Dodana tutaj jest od razu widoczna dla wszystkich.</p>';
        body.innerHTML = html;

        body.querySelectorAll('[data-unit]').forEach(function (row) {
            var id = +row.dataset.unit, x = r.units.filter(function (y) { return y.id === id; })[0];
            var lab = row.querySelector('[data-ulabel]'), gr = row.querySelector('[data-ugrams]'), save = row.querySelector('[data-uact="save"]');
            var changed = function () { save.hidden = lab.value.trim().toLowerCase() === x.label && parseFloat(gr.value.replace(',', '.')) === x.grams; };
            lab.addEventListener('input', changed);
            gr.addEventListener('input', changed);
            [lab, gr].forEach(function (i) { i.addEventListener('keydown', function (ev) { if (ev.key === 'Enter' && !save.hidden) save.click(); }); });
            row.querySelectorAll('[data-uact]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var act = b.dataset.uact;
                    if (act === 'delete' && !confirm('Usunąć jednostkę „' + x.label + '”? Wpisy w dziennikach zostaną bez zmian.')) return;
                    unitsPost({ action: act, id: id, label: lab.value, grams: gr.value.replace(',', '.') }, b);
                });
            });
        });
        var nl = document.getElementById('fa_unew_label'), ng = document.getElementById('fa_unew_grams');
        body.querySelectorAll('[data-upreset]').forEach(function (b) { b.addEventListener('click', function () { nl.value = b.textContent; ng.focus(); }); });
        var add = function () { unitsPost({ action: 'save', label: nl.value, grams: ng.value.replace(',', '.') }, document.getElementById('fa_unew_add')); };
        document.getElementById('fa_unew_add').addEventListener('click', add);
        ng.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') add(); });
    }

    function unitsPost(data, btn) {
        var fd = new FormData();
        fd.append('product_id', unitsPid);
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        if (btn) btn.disabled = true;
        hlJson('scripts/food/units_admin.php', fd).then(function (r) {
            if (btn) btn.disabled = false;
            showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
            if (r.status !== 'success') return;
            unitsDirty = true;
            var top = document.getElementById('fa_units_body').scrollTop;
            unitsRender(r);
            document.getElementById('fa_units_body').scrollTop = top;
        });
    }

    window.foodUnits = function (productId) {
        fetch('scripts/food/units_admin.php?product_id=' + productId, { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (r) {
            if (r.status !== 'success') return showAlert(r.status || 'error', r.message || 'Nie udało się wczytać jednostek');
            unitsPid = productId;
            unitsRender(r);
            if (!unitsDlg.open) unitsDlg.showModal();
            document.getElementById('fa_units_body').scrollTop = 0;
        });
    };
})();
</script>
