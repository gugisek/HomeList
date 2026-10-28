<?php if (!isset($acc)) exit; // część account.php
require_once __DIR__ . '/../../../scripts/food/food_lib.php';
$acc_food = hl_food_settings($conn, $acc_id);
$acc_food_goal = hl_food_goal($conn, $acc_id, date('Y-m-d'));
$acc_row = 'flex items-center justify-between gap-3 rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 p-3 cursor-pointer';

// Twój wkład: produkty dodane do bazy (status, uwaga moderatora, ile razy inni wpisali je do dziennika) i zgłoszone jednostki porcji
$stmt = $conn->prepare("SELECT p.id, p.name, p.brand, p.code, p.kcal, p.per_ml, p.quantity, p.status, p.review_note, p.created_at, p.reviewed_at, p.label_photo,
                               (SELECT COUNT(*) FROM food_log l WHERE l.product_id = p.id AND l.user_id <> p.owner_id) AS uses,
                               (SELECT COUNT(DISTINCT l.user_id) FROM food_log l WHERE l.product_id = p.id AND l.user_id <> p.owner_id) AS people
                        FROM food_products p WHERE p.source = 'user' AND p.owner_id = ? ORDER BY p.created_at DESC, p.id DESC");
$stmt->bind_param('i', $acc_id);
$stmt->execute();
$acc_contrib = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt = $conn->prepare('SELECT un.id, un.label, un.grams, un.status, p.name, p.per_ml FROM food_product_units un JOIN food_products p ON p.id = un.product_id
                        WHERE un.owner_id = ? ORDER BY un.id DESC');
$stmt->bind_param('i', $acc_id);
$stmt->execute();
$acc_contrib_units = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$acc_count = fn($status) => count(array_filter($acc_contrib, fn($p) => $p['status'] === $status));
$acc_uses = array_sum(array_column($acc_contrib, 'uses'));
$acc_people = 0;
if ($acc_contrib) {
    // różne osoby łącznie (ta sama osoba przy kilku produktach liczy się raz)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT l.user_id) FROM food_log l JOIN food_products p ON p.id = l.product_id
                            WHERE p.source = 'user' AND p.owner_id = ? AND l.user_id <> p.owner_id");
    $stmt->bind_param('i', $acc_id);
    $stmt->execute();
    $acc_people = (int)$stmt->get_result()->fetch_row()[0];
}
$acc_num = fn($v) => rtrim(rtrim(number_format((float)$v, 1, ',', ''), '0'), ',');
$acc_badge = [
    'approved' => ['HomeList', 'bg-green-100 text-green-700 dark:bg-green-400/15 dark:text-green-300'],
    'pending' => ['Czeka na sprawdzenie', 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300'],
    'rejected' => ['Odrzucone', 'bg-rose-100 text-rose-700 dark:bg-rose-400/15 dark:text-rose-300'],
];
$acc_sub_btn = 'food-sub flex-none inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium duration-150';
?>
<p class="<?=$acc_title?>">Jedzenie</p>
<p class="<?=$acc_subtitle?>">Licznik kalorii: które posiłki pokazywać, czy widać go na planie dnia i produkty, które dodałeś(-aś) do bazy.</p>

<div role="tablist" class="mb-4 inline-flex max-w-full overflow-x-auto rounded-full bg-gray-100 dark:bg-[#2c2c2e] p-1 gap-1">
    <button type="button" role="tab" data-food-sub="settings" aria-selected="true" class="<?=$acc_sub_btn?>">Ustawienia</button>
    <button type="button" role="tab" data-food-sub="contrib" aria-selected="false" class="<?=$acc_sub_btn?>">
        Twój wkład<?php if ($acc_contrib): ?><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold bg-black/5 dark:bg-white/10"><?=count($acc_contrib)?></span><?php endif; ?>
    </button>
</div>

<div data-food-panel="contrib" hidden>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        <?php foreach ([[count($acc_contrib), 'dodanych produktów'], [$acc_count('approved'), 'w bazie HomeList'], [$acc_uses, $acc_plural($acc_uses, 'wpis', 'wpisy', 'wpisów') . ' innych osób'], [$acc_people, $acc_plural($acc_people, 'osoba korzysta', 'osoby korzystają', 'osób korzysta')]] as [$v, $label]): ?>
        <div class="hl-card px-4 py-3">
            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?=number_format((int)$v, 0, ',', ' ')?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400"><?=$label?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$acc_contrib): ?>
    <div class="<?=$acc_card?> mt-3">
        <div class="hl-empty">
            <h3>Nie dodałeś(-aś) jeszcze produktu</h3>
            <p>Gdy czegoś nie ma w bazie, dodaj to w Jedzeniu przez „Własny produkt” albo skanując kod. Po sprawdzeniu przez administratora produkt trafi do bazy HomeList dla wszystkich.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="<?=$acc_card?> mt-3 !p-2 flex flex-col divide-y divide-black/5 dark:divide-white/10">
        <?php foreach ($acc_contrib as $p): [$badge, $badge_cls] = $acc_badge[$p['status']]; $u = $p['per_ml'] ? 'ml' : 'g'; ?>
        <div class="flex items-start gap-3 px-3 py-3">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 min-w-0"><?=$acc_e($p['name'])?><?php if ($p['brand']): ?> <span class="font-normal text-gray-500 dark:text-gray-400">· <?=$acc_e($p['brand'])?></span><?php endif; ?></p>
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?=$badge_cls?>"><?=$badge?></span>
                </div>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    <?=$acc_num($p['kcal'])?> kcal / 100 <?=$u?><?=$p['quantity'] ? ' · ' . $acc_e($p['quantity']) : ''?> · dodano <?=$acc_e(date('j.m.Y', strtotime($p['created_at'])))?>
                    <?=$p['status'] === 'approved' && $p['reviewed_at'] ? ' · zaakceptowano ' . $acc_e(date('j.m.Y', strtotime($p['reviewed_at']))) : ''?>
                </p>
                <p class="mt-1 text-xs <?=$p['uses'] ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500'?>">
                    <?php if ($p['uses']): ?>Wpisany <?=(int)$p['uses']?> <?=$acc_plural((int)$p['uses'], 'raz', 'razy', 'razy')?> przez <?=(int)$p['people']?> <?=$acc_plural((int)$p['people'], 'inną osobę', 'inne osoby', 'innych osób')?>
                    <?php else: ?>Nikt inny jeszcze go nie wpisał<?php endif; ?>
                    <?=$p['code'] ? ' · kod ' . $acc_e($p['code']) : ''?><?=$p['label_photo'] ? ' · ze zdjęciem etykiety' : ''?>
                </p>
                <?php if ($p['status'] === 'rejected'): ?>
                <p class="mt-2 rounded-xl bg-rose-50 dark:bg-rose-400/10 px-3 py-2 text-xs text-rose-700 dark:text-rose-300">
                    <?=$p['review_note'] ? 'Administrator: „' . $acc_e($p['review_note']) . '”' : 'Administrator odrzucił ten produkt'?> – widzisz go tylko Ty. Popraw dane, a wróci do sprawdzenia.
                </p>
                <?php elseif ($p['status'] === 'pending' && $p['review_note']): ?>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Poprzednia uwaga: „<?=$acc_e($p['review_note'])?>”</p>
                <?php endif; ?>
            </div>
            <?php if ($p['status'] !== 'approved' && $acc_food['enabled']): ?>
            <button type="button" onclick="foodOpenSheet('own:<?=(int)$p['id']?>')" class="hl-btn hl-btn-ghost hl-btn-sm flex-none">Popraw</button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="hl-hint">Zaakceptowanych produktów nie da się już zmienić – jeśli coś się nie zgadza, napisz w „Zgłoś błąd”.</p>
    <?php endif; ?>

    <?php if ($acc_contrib_units): ?>
    <p class="mt-6 ml-1 text-sm font-medium text-gray-900 dark:text-gray-200">Jednostki porcji</p>
    <div class="<?=$acc_card?> mt-2 !p-2 flex flex-col divide-y divide-black/5 dark:divide-white/10">
        <?php foreach ($acc_contrib_units as $un): [$badge, $badge_cls] = $acc_badge[$un['status']]; ?>
        <div class="flex items-center gap-3 px-3 py-2.5">
            <p class="min-w-0 flex-1 text-sm text-gray-800 dark:text-gray-200"><?=$acc_e($un['label'])?> = <?=$acc_num($un['grams'])?> <?=$un['per_ml'] ? 'ml' : 'g'?> <span class="text-gray-500 dark:text-gray-400">· <?=$acc_e($un['name'])?></span></p>
            <span class="flex-none rounded-full px-2 py-0.5 text-[11px] font-semibold <?=$badge_cls?>"><?=$un['status'] === 'approved' ? 'Dla wszystkich' : $badge?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<form id="food-settings" data-food-panel="settings" onsubmit="foodSettingsSave(event)" class="<?=$acc_card?>">
    <label class="<?=$acc_row?>">
        <span class="min-w-0">
            <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">Licznik kalorii</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">Po wyłączeniu dziennik zostaje zapisany – wrócisz do niego po ponownym włączeniu</span>
        </span>
        <input type="checkbox" name="enabled" <?=$acc_food['enabled'] ? 'checked' : ''?> class="size-5 flex-none">
    </label>
    <label class="<?=$acc_row?> mt-2">
        <span class="min-w-0">
            <span class="block text-sm text-gray-800 dark:text-gray-200">Kalorie na planie dnia</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">Mały pierścień „zostało kcal” w nagłówku zakładki Dziś</span>
        </span>
        <input type="checkbox" name="show_on_today" <?=$acc_food['show_on_today'] ? 'checked' : ''?> class="size-5 flex-none">
    </label>

    <p class="mt-6 ml-1 text-sm font-medium text-gray-900 dark:text-gray-200">Posiłki</p>
    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
        <?php foreach (hl_food_meals() as $m => $label): ?>
        <label class="<?=$acc_row?>">
            <span class="text-sm text-gray-800 dark:text-gray-200"><?=$label?></span>
            <input type="checkbox" name="meal" value="<?=$m?>" <?=in_array($m, $acc_food['meals'], true) ? 'checked' : ''?> class="size-5 flex-none">
        </label>
        <?php endforeach; ?>
    </div>
    <p class="hl-hint">Wyłączony posiłek znika z dnia, chyba że coś w nim zapisano.</p>

    <p class="mt-6 ml-1 text-sm font-medium text-gray-900 dark:text-gray-200">Czat AI do „Policz ze zdjęcia”</p>
    <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-label="Czat AI">
        <?php foreach (hl_food_ai_chats() as $key => $chat): ?>
        <label class="cursor-pointer">
            <input type="radio" name="ai_chat" value="<?=$key?>" <?=$acc_food['ai_chat'] === $key ? 'checked' : ''?> class="peer sr-only">
            <span class="inline-flex items-center h-10 px-4 rounded-full text-sm font-medium bg-gray-100 text-gray-700 dark:bg-white/[.06] dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/10 duration-150 peer-checked:bg-gray-900 peer-checked:text-white dark:peer-checked:bg-white dark:peer-checked:text-black peer-focus-visible:ring-2 peer-focus-visible:ring-green-400"><?=$chat['label']?></span>
        </label>
        <?php endforeach; ?>
    </div>
    <p class="hl-hint">Skrót w kreatorze otworzy ten czat. Polecenie działa w każdym z nich – wszystkie rozpoznają zdjęcia w darmowej wersji.</p>

    <div class="mt-6 flex flex-col sm:flex-row-reverse gap-2">
        <button class="<?=$acc_btn?>">Zapisz</button>
        <?php if ($acc_food['enabled']): ?>
        <button type="button" onclick="foodOpenSheet('dishes')" class="<?=$acc_btn_muted?>">Twoje dania</button>
        <button type="button" onclick="foodOpenSheet('goal')" class="<?=$acc_btn_muted?>"><?=$acc_food_goal ? 'Cel: ' . number_format($acc_food_goal['kcal'], 0, ',', ' ') . ' kcal – zmień' : 'Ustaw dzienny cel'?></button>
        <?php endif; ?>
    </div>
</form>

<style>
.food-sub { color: var(--hl-ink-2); }
.food-sub[aria-selected="true"] { background: var(--hl-card); color: var(--hl-ink); box-shadow: 0 4px 12px -6px rgba(0, 0, 0, .3); }
</style>
<script>
(function () {
    // Ustawienia / Twój wkład – zapamiętane w tej sesji (powrót z poprawiania produktu)
    var subs = document.querySelectorAll('[data-food-sub]');
    var show = function (name) {
        subs.forEach(function (b) { b.setAttribute('aria-selected', b.dataset.foodSub === name ? 'true' : 'false'); });
        document.querySelectorAll('[data-food-panel]').forEach(function (p) { p.hidden = p.dataset.foodPanel !== name; });
        try { sessionStorage.setItem('hl_food_acc_sub', name); } catch (e) {}
    };
    subs.forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.foodSub); }); });
    var saved = null;
    try { saved = sessionStorage.getItem('hl_food_acc_sub'); } catch (e) {}
    if (saved === 'contrib') show('contrib');
})();
function foodSettingsSave(e) {
    e.preventDefault();
    var form = document.getElementById('food-settings'), fd = new FormData();
    fd.append('enabled', form.enabled.checked ? '1' : '0');
    fd.append('show_on_today', form.show_on_today.checked ? '1' : '0');
    fd.append('meals', [].slice.call(form.querySelectorAll('[name=meal]:checked')).map(function (i) { return i.value; }).join(','));
    var chat = form.querySelector('[name=ai_chat]:checked');
    if (chat) fd.append('ai_chat', chat.value);
    hlJson('scripts/food/settings_save.php', fd).then(function (r) {
        showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
        if (r.status !== 'success') return;
        // ikona Jedzenia w pasku i pigułce zależy od włączenia modułu
        if (typeof refreshLists === 'function' && document.getElementById('lists')) refreshLists();
        if (typeof hlRailRefresh === 'function') hlRailRefresh();
        accReload();
    });
}
function foodOpenSheet(step) {
    // kalkulator celu i lista dań żyją w arkuszu zakładki Jedzenie
    sessionStorage.setItem('hl_food_open', step);
    if (typeof hlNav === 'function') hlNav('list=food');
}
</script>
