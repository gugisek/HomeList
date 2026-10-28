<?php if (!isset($acc)) exit; // część account.php
require_once __DIR__ . '/../../../scripts/food/food_lib.php';
$acc_home_food = hl_food_settings($conn, $acc_id);
$acc_home_goal = $acc_home_food['enabled'] ? hl_food_goal($conn, $acc_id, date('Y-m-d')) : null;
$acc_home_cards = [
    'personal' => ['Imię, zdjęcie, email i opis', $acc['mail']],
    'security' => ['Hasło i weryfikacja dwuetapowa', 'Weryfikacja dwuetapowa ' . ($acc_2fa ? 'włączona' : 'wyłączona')],
    'devices' => ['Zapamiętane logowania', $acc_devices ? count($acc_devices) . ' ' . $acc_plural(count($acc_devices), 'urządzenie', 'urządzenia', 'urządzeń') : 'Brak zapamiętanych urządzeń'],
    'notifications' => ['Przypomnienia push i pory dnia', 'Przypomnienia ' . ($ps['enabled'] ? 'włączone' : 'wyłączone')],
    'food' => ['Licznik kalorii, posiłki i dzienny cel', !$acc_home_food['enabled'] ? 'Licznik kalorii wyłączony' : ($acc_home_goal ? 'Cel: ' . number_format($acc_home_goal['kcal'], 0, ',', ' ') . ' kcal' : 'Licznik kalorii włączony')],
    'report' => ['Coś nie działa? Daj znać twórcom', 'Krótki opis i zrzut ekranu'],
];
?>
<div class="flex flex-col items-center text-center pt-2 md:pt-6">
    <button type="button" onclick="accTab('personal', true)" title="Zmień zdjęcie" class="rounded-full hover:scale-105 hover:shadow-xl active:scale-95 duration-150">
        <img class="size-24 rounded-full object-cover border border-black/10" src="img/users_images/<?=$acc_e($acc_img)?>" alt="">
    </button>
    <p class="mt-4 font-medium text-2xl text-black dark:text-gray-200 font-[poppins]">Witaj, <?=$acc_e($acc['name'])?></p>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Zarządzaj swoimi danymi, bezpieczeństwem i powiadomieniami w HomeList.</p>
    <p class="mt-1 text-xs text-gray-400">Rola: <?=$acc_e($acc['role'] ?? '—')?> · konto od <?=$acc_e(date('d.m.Y', strtotime($acc['create_date'])))?></p>
</div>

<?php if (!$acc_2fa): ?>
<div class="mt-8 flex items-center gap-4 rounded-3xl bg-amber-50 dark:bg-amber-400/10 ring-1 ring-amber-200 dark:ring-amber-400/20 p-4 sm:p-5">
    <span class="flex-none rounded-full bg-amber-100 dark:bg-amber-400/20 p-2.5 text-amber-600 dark:text-amber-300"><?=$acc_icon($acc_tabs['security'][1])?></span>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Zabezpiecz konto</p>
        <p class="text-xs text-amber-700/80 dark:text-amber-300/70">Włącz weryfikację dwuetapową – nawet ktoś z Twoim hasłem nie zaloguje się bez telefonu.</p>
    </div>
    <button type="button" onclick="accTab('security', true)" class="flex-none text-xs font-medium px-3 py-1.5 rounded-full ring-1 ring-amber-400 text-amber-600 dark:text-amber-300 hover:bg-amber-400 hover:text-white active:scale-95 duration-150">Włącz</button>
</div>
<?php endif; ?>

<div class="mt-6 grid sm:grid-cols-2 gap-3">
    <?php foreach ($acc_home_cards as $k => [$hint, $value]): ?>
    <button type="button" onclick="accTab('<?=$k?>', true)" class="group <?=$acc_card?> !p-4 sm:!p-5 flex items-center gap-4 text-left hover:shadow-xl hover:scale-[1.01] active:scale-[0.98] duration-150">
        <span class="flex-none rounded-full bg-green-50 dark:bg-green-400/10 p-2.5 text-green-600 dark:text-green-400"><?=$acc_icon($acc_tabs[$k][1])?></span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-gray-900 dark:text-gray-200"><?=$acc_tabs[$k][0]?></span>
            <span class="block text-xs text-gray-500 dark:text-gray-400"><?=$hint?></span>
            <span class="block mt-1 text-xs font-medium text-gray-700 dark:text-gray-300 truncate"><?=$acc_e($value)?></span>
        </span>
        <span class="text-gray-300 dark:text-gray-500 group-hover:text-green-500 group-hover:translate-x-0.5 duration-150"><?=$acc_icon($acc_icon_chevron)?></span>
    </button>
    <?php endforeach; ?>
</div>

<a href="scripts/login/logout.php" class="md:hidden mt-6 flex justify-center items-center gap-2 rounded-full ring-1 ring-rose-400 px-4 py-2.5 text-sm text-rose-400 hover:bg-rose-400 hover:text-white active:scale-95 duration-150">
    <?=$acc_icon($acc_icon_logout)?>Wyloguj
</a>
