<?php if (!isset($acc)) exit; // część account.php
$acc_push_toggles = [
    'notify_parts' => ['Plan na początku pory dnia', 'Rano, popołudniu i wieczorem – co masz zaplanowane na tę porę'],
    'notify_missed' => ['Przypomnienie o zapomnianych', 'Gdy pora dnia minie, a coś zostało niezrobione (scalone z kolejnym powiadomieniem) + na koniec dnia'],
    'notify_deadlines' => ['Terminy', 'Rano lista terminów na dziś, a przy terminie z godziną – godzinę wcześniej'],
    'notify_timed' => ['Zadania z godziną', '5 minut przed zaplanowaną godziną'],
    'notify_routines' => ['Rutyny', 'Uwzględniaj rutyny (pojedyncze możesz wyciszyć w ich szczegółach)'],
    'notify_lists' => ['Nowe zadania na listach wspólnych', 'Gdy ktoś inny doda zadania – seria scalona w jedno powiadomienie (pojedyncze listy wyciszysz w ich szczegółach)'],
    'notify_bills' => ['Opłaty', 'Rano: płatności (ile dni wcześniej – ustawiasz przy każdej opłacie), zaległe, koniec okresu próbnego, termin wypowiedzenia, koniec umowy i polisy'],
];
if (hl_can('reports', 'read')) {
    $acc_push_toggles['notify_reports'] = ['Zgłoszenia błędów', 'Gdy użytkownik zgłosi błąd w aplikacji (dla administratorów)'];
}
$acc_row = 'flex items-center justify-between gap-3 rounded-2xl bg-gray-50 dark:bg-[#3d3d3d]/60 p-3 cursor-pointer';
?>
<p class="<?=$acc_title?>">Powiadomienia</p>
<p class="<?=$acc_subtitle?>">Przypomnienia push o planie dnia, terminach i rutynach.</p>

<div class="flex flex-col gap-6">
    <!-- To urządzenie -->
    <div class="<?=$acc_card?> flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="font-medium text-lg text-black dark:text-gray-200 font-[poppins]">To urządzenie</p>
            <p id="push-device-state" class="text-xs text-gray-500 dark:text-gray-400">Sprawdzam to urządzenie…</p>
        </div>
        <button type="button" id="push-device-btn" onclick="pushDeviceToggle()" class="hidden flex-none text-xs font-medium px-3 py-1.5 rounded-full ring-1 ring-green-400 text-green-500 hover:bg-green-400 hover:text-white active:scale-95 duration-150"></button>
    </div>

    <form id="push-settings" onsubmit="pushSettingsSave(event)" class="<?=$acc_card?>">
        <label class="<?=$acc_row?>">
            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Wysyłaj przypomnienia</span>
            <input type="checkbox" name="enabled" value="1" <?=$ps['enabled'] ? 'checked' : ''?> class="size-5 accent-green-400">
        </label>

        <p class="mt-6 ml-1 text-sm font-medium text-gray-900 dark:text-gray-200">Pory dnia zaczynają się o</p>
        <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
            <?php foreach (['morning_start' => 'Rano', 'afternoon_start' => 'Popołudnie', 'evening_start' => 'Wieczór', 'day_end' => 'Koniec dnia'] as $k => $label): ?>
            <label class="text-xs text-gray-500 dark:text-gray-400 pl-1"><?=$label?>
                <input type="time" name="<?=$k?>" value="<?=$acc_e($ps[$k])?>" required class="mt-1 <?=$acc_input?>">
            </label>
            <?php endforeach; ?>
        </div>

        <p class="mt-6 ml-1 text-sm font-medium text-gray-900 dark:text-gray-200">Przypominaj o</p>
        <div class="mt-2 flex flex-col gap-2">
            <?php foreach ($acc_push_toggles as $k => [$label, $hint]): ?>
            <label class="<?=$acc_row?>">
                <span class="min-w-0">
                    <span class="block text-sm text-gray-800 dark:text-gray-200"><?=$label?></span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400"><?=$hint?></span>
                </span>
                <input type="checkbox" name="<?=$k?>" value="1" <?=$ps[$k] ? 'checked' : ''?> class="size-5 flex-none accent-green-400">
            </label>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row-reverse gap-2">
            <button class="<?=$acc_btn?>">Zapisz powiadomienia</button>
            <button type="button" onclick="pushSendTest()" class="<?=$acc_btn_muted?>">Wyślij testowe</button>
        </div>
    </form>
</div>

<script>
function pushSettingsSave(e) {
    e.preventDefault();
    hlJson('scripts/push/settings.php', new FormData(document.getElementById('push-settings')))
        .then(r => showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi'));
}

function pushSendTest() {
    hlJson('scripts/push/test.php', new FormData())
        .then(r => showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi'));
}

function pushDeviceRender() {
    const txt = document.getElementById('push-device-state'), btn = document.getElementById('push-device-btn');
    if (!txt) return;
    const state = hlPushState();
    btn.classList.add('hidden');
    if (state === 'granted') {
        navigator.serviceWorker.getRegistration().then(reg => reg && reg.pushManager.getSubscription()).then(sub => {
            txt.textContent = sub ? 'Otrzymuje powiadomienia' : 'Nie otrzymuje powiadomień';
            btn.textContent = sub ? 'Wyłącz tutaj' : 'Włącz tutaj';
            btn.dataset.on = sub ? '1' : '';
            btn.classList.remove('hidden');
        });
    } else if (state === 'default') {
        txt.textContent = 'Nie otrzymuje powiadomień';
        btn.textContent = 'Włącz tutaj';
        btn.dataset.on = '';
        btn.classList.remove('hidden');
    } else {
        txt.textContent = { denied: 'Powiadomienia zablokowane w przeglądarce', 'ios-install': 'Na iPhonie dodaj HomeList do ekranu głównego, aby włączyć powiadomienia', unsupported: 'Przeglądarka nie obsługuje powiadomień push' }[state];
    }
}

function pushDeviceToggle() {
    const on = document.getElementById('push-device-btn').dataset.on;
    (on ? hlPushDisable().then(() => showAlert('success', 'Powiadomienia wyłączone na tym urządzeniu')) : hlPushEnable()).then(pushDeviceRender);
}

pushDeviceRender();
</script>
