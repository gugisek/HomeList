<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['food', 'read']; include __DIR__.'/../../../scripts/security.php'; ?>
<?php
// Baza produktów: produkty dodane przez użytkowników czekające na akceptację (widzą je już autor i domownicy).
// Akceptacja = widoczne dla wszystkich; odrzucenie = zostaje tylko u autora; usunięcie nie rusza dzienników (kopie wartości).
include "../../../scripts/conn_db.php";
require_once __DIR__ . '/../../../scripts/planner/planner_lib.php';
require_once __DIR__ . '/../../../scripts/food/food_lib.php';

$can_edit = hl_can('food', 'edit');
$can_delete = hl_can('food', 'full');
$items = [];
$res = $conn->query("SELECT p.*, u.name AS owner_name, u.sur_name AS owner_sur, u.profile_picture,
                            (SELECT COUNT(*) FROM food_log l WHERE l.product_id = p.id) AS uses
                     FROM food_products p LEFT JOIN users u ON u.id = p.owner_id
                     WHERE p.source = 'user' AND p.status = 'pending' ORDER BY p.id LIMIT 200");
while ($row = $res->fetch_assoc()) $items[] = $row;
$stats = $conn->query("SELECT source, COUNT(*) FROM food_products WHERE status = 'approved' GROUP BY source")->fetch_all(MYSQLI_NUM);
$stats = array_column($stats, 1, 0);
$fmt = fn($v) => rtrim(rtrim(number_format((float)$v, 1, ',', ''), '0'), ',');
$icon = fn($d, $cls = 'size-4') => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="' . $cls . ' flex-none"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '" /></svg>';
?>
<div id="food_admin_root" class="max-w-4xl mx-auto mb-28 md:mb-10">
    <div data-aos="fade-up" data-aos-delay="100" class="pt-2">
        <h1 class="hl-title">Baza produktów</h1>
        <p class="hl-sub">
            <?=number_format((int)($stats['off'] ?? 0), 0, ',', ' ')?> z Open Food Facts · <?=(int)($stats['base'] ?? 0)?> podstawowych ·
            od użytkowników: <?=(int)($stats['user'] ?? 0)?> · dania domowe: <?=(int)($stats['dish'] ?? 0)?>
        </p>
    </div>

    <p data-aos="fade-up" data-aos-delay="120" class="mt-6 ml-1 text-sm font-medium text-gray-900 dark:text-gray-200">Czeka na akceptację<?=$items ? ' (' . count($items) . ')' : ''?></p>
    <?php if (!$items): ?>
    <div data-aos="fade-up" data-aos-delay="150" class="hl-empty">
        <div class="hl-empty-ico"><?=$icon('M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'size-8')?></div>
        <h3>Wszystko sprawdzone</h3>
        <p>Nowe produkty od użytkowników pojawią się tutaj.</p>
    </div>
    <?php endif; ?>

    <div class="mt-3 flex flex-col gap-3">
        <?php foreach ($items as $i => $p):
            $match = hl_food_macros_match((float)$p['kcal'], (float)$p['protein'], (float)$p['fat'], (float)$p['carbs'], 0.15, 25, 2 * (float)$p['fiber']);
            $est = round(hl_food_kcal_from_macros((float)$p['protein'], (float)$p['fat'], (float)$p['carbs']));
            $u = $p['per_ml'] ? 'ml' : 'g';
        ?>
        <div id="fp_<?=(int)$p['id']?>" data-aos="fade-up" data-aos-delay="<?=min(150 + $i * 40, 400)?>" class="hl-card hl-card-pad duration-300">
            <div class="flex items-start gap-3">
                <img class="size-10 flex-none rounded-full object-cover border border-black/10" src="img/users_images/<?=hl_e($p['profile_picture'] ?: 'default.png')?>" alt="">
                <div class="min-w-0 flex-1">
                    <p class="text-base font-medium text-gray-900 dark:text-gray-100"><?=hl_e($p['name'])?><?php if ($p['brand']): ?> <span class="text-gray-500 dark:text-gray-400 font-normal">· <?=hl_e($p['brand'])?></span><?php endif; ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <?=hl_e(trim($p['owner_name'] . ' ' . $p['owner_sur']) ?: 'Usunięte konto')?> · <?=hl_e(date('j.m.Y H:i', strtotime($p['created_at'])))?>
                        <?=$p['code'] ? ' · kod ' . hl_e($p['code']) : ''?> · w dziennikach: <?=(int)$p['uses']?> · wartości na 100 <?=$u?>
                    </p>
                    <div class="mt-3 grid grid-cols-4 gap-2 text-center">
                        <?php foreach ([['kcal', 'kcal', 'var(--fd-k)'], ['protein', 'białko', 'var(--fd-p)'], ['fat', 'tłuszcz', 'var(--fd-f)'], ['carbs', 'węgle', 'var(--fd-c)']] as [$k, $label, $c]): ?>
                        <div class="rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 py-2">
                            <p class="text-base font-semibold" style="color: <?=$k === 'kcal' ? 'inherit' : $c?>"><?=$fmt($p[$k])?></p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400"><?=$label?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-2 inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-medium <?=$match ? 'bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300'?>">
                        <?=$match ? 'Kalorie zgadzają się z makro (~' . $est . ' kcal)' : 'Sprawdź: z makro wychodzi ~' . $est . ' kcal, a podano ' . $fmt($p['kcal'])?>
                    </p>
                    <?php if ($can_edit): ?>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" onclick="foodModerate(<?=(int)$p['id']?>, 'approve')" class="hl-btn hl-btn-green hl-btn-sm"><?=$icon('m4.5 12.75 6 6 9-13.5')?>Akceptuj</button>
                        <button type="button" onclick="foodModerate(<?=(int)$p['id']?>, 'reject')" class="hl-btn hl-btn-ghost hl-btn-sm">Odrzuć</button>
                        <?php if ($can_delete): ?>
                        <button type="button" onclick="foodModerate(<?=(int)$p['id']?>, 'delete')" class="hl-btn hl-btn-danger hl-btn-sm">Usuń</button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="mt-6 ml-1 text-xs text-gray-500 dark:text-gray-400">Odrzucony produkt zostaje widoczny tylko dla autora. Usunięcie nie zmienia wpisów w dziennikach – mają własną kopię wartości.</p>
</div>

<script>
function foodModerate(id, action) {
    if (action === 'delete' && !confirm('Usunąć ten produkt z bazy?')) return;
    var fd = new FormData();
    fd.append('id', id);
    fd.append('action', action);
    hlJson('scripts/food/moderate.php', fd).then(function (r) {
        showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi');
        if (r.status !== 'success') return;
        var card = document.getElementById('fp_' + id);
        if (card) { card.style.opacity = '0'; card.style.transform = 'scale(.97)'; setTimeout(function () { card.remove(); }, 300); }
        var dot = document.getElementById('food_nav_dot');
        if (dot && !document.querySelector('[id^="fp_"]:not([style*="opacity"])')) dot.remove();
    });
}
</script>
