<section id="sticky_nav_body" data-aos="fade-up" data-aos-delay="100" class="sticky top-0 z-[1] pt-6 bg-gradient-to-b -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 from-[#fdfdfd] via-[#fdfdfd]/70 via-70% to-white/0 dark:from-[#3d3d3d] dark:via-[#3d3d3d]/70 dark:to-[#3d3d3d]/0">
    <div class="flex items-center justify-between">
        <div class="text-gray-400">
            <span class="font-medium text-2xl text-black font-[poppins] dark:text-gray-200">Ustawienia</span>
        </div>
        <span></span>
    </div>
</section>
<section>
<?php
include_once __DIR__.'/../../../scripts/session_restore.php'; hl_session_start();
$id = $_SESSION['login_id'];
include "../../../scripts/conn_db.php";
$sql = "SELECT users.id, users.name, users.profile_picture, users.sur_name, users.mail, role_id, status_id, description FROM users where users.id = $id;";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0)
{
    while($row = mysqli_fetch_assoc($result))
    {
        //rozdziel imie i nazwisko
        $name = $row['name'];
        $sur_name = $row['sur_name'];
        $email = $row['mail'];
        $role_id = $row['role_id'];
        $description = $row['description'];
        $status_id = $row['status_id'];
        if ($row['profile_picture'] == NULL) {
            $img = 'default.png';
        }else{
            $img = $row['profile_picture'];
        }
    }
}
?>
<form data-aos="fade-up" data-aos-delay="150" action="scripts/users/edit_profile.php" method="POST" enctype="multipart/form-data" class="flex flex-col gap-8 items-center justify-center">
    <input type="hidden" name="id" value="<?=$id?>">
    <div class="mt-4 flex flex-col items-center justify-center mt-8">
            <label for="photo_img" class="h-24 w-24 flex-shrink-0 hover:scale-105 hover:shadow-xl duration-150 active:scale-95 cursor-pointer rounded-full">
                <img id="popup_img_inpt_photo_img" class="h-24 w-24 aspect-square rounded-full object-cover border border-black/10" src="img/users_images/<?=$img?>" alt="">
            </label>
            <input onchange="imgPrevProduct('photo_img')" type="file" name="photo_img" id="photo_img" class="hidden">
    </div>
    <div>
        
    <div class="mt-4 flex md:flex-row flex-col gap-2">
    <div class="w-full">
        <label for="name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Imię</label>
        <div class="mt-2">
            <input name="name" id="name" type="text" value="<?=$name?>" placeholder="Wpisz imię" class="border rounded-xl w-full py-1.5 px-4 text-sm border-gray-400 dark:dark:bg-[#2c2c2c] dark:text-gray-200 dark:border-[#2c2c2c] focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white" required>
        </div>
    </div>
    <div class="w-full">
        <label for="sur_name" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Nazwisko</label>
        <div class="mt-2">
            <input name="sur_name" id="sur_name" type="text" value="<?=$sur_name?>" placeholder="Wpisz nazwisko" class="border rounded-xl w-full py-1.5 px-4 text-sm border-gray-400 dark:dark:bg-[#2c2c2c] dark:text-gray-200 dark:border-[#2c2c2c] focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white" required>
        </div>
    </div>
    </div>

    <div class="mt-4 flex md:flex-row flex-col gap-2">
    <div class="w-full">
        <label for="email" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Email</label>
        <div class="mt-2">
            <input name="email" id="email" type="email" value="<?=$email?>" placeholder="Wpisz email" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 dark:dark:bg-[#2c2c2c] dark:text-gray-200 dark:border-[#2c2c2c] focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white" required>
        </div>
    </div>
    </div>

    
   

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-full">
            <label for="description" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Opis</label>
            <div class="mt-2">
                <textarea name="description" id="description" type="text" placeholder="Nic tu nie ma..." class="border rounded-2xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 dark:dark:bg-[#2c2c2c] dark:text-gray-200 dark:border-[#2c2c2c] focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white"><?=$description?></textarea>
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-row gap-2">
        <div class="w-1/2">
            <label for="pswd" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Hasło</label>
            <div class="mt-2">
                <input name="pswd" id="pswd" type="password" placeholder="Wpisz hasło" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 focus:bg-[#1c1c1c] dark:dark:bg-[#2c2c2c] dark:text-gray-200 dark:border-[#2c2c2c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white">
            </div>
        </div>
        <div class="w-1/2">
            <label for="repeat_pswd" class="ml-px block pl-2 text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">Powtórz hasło</label>
            <div class="mt-2">
                <input name="repeat_pswd" id="repeat_pswd" type="password" placeholder="Powtórz hasło" class="border rounded-xl py-1.5 w-full px-4 text-sm border-gray-400 focus:ring-0 focus:outline-0 dark:dark:bg-[#2c2c2c] dark:text-gray-200 dark:border-[#2c2c2c] focus:bg-[#1c1c1c] focus:border-[#1c1c1c] focus:shadow-xl duration-150 font-medium focus:text-white">
            </div>
        </div>
    </div>

    <div class="mt-6 sm:mt-6 mb-2 sm:flex sm:flex-row-reverse">
        <button class="mt-4 active:scale-95 duration-150 flex w-full justify-center cursor-pointer rounded-full ring-green-400 ring-1 duration-150 px-4 py-2 text-sm text-green-400 items-center gap-1 shadow-sm hover:shadow-xl hover:bg-green-400 hover:text-white sm:ml-2 sm:w-auto">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        Zapisz
        </button>
        <a href="scripts/login/logout.php" class="mt-4 active:scale-95 duration-150 flex w-full justify-center cursor-pointer rounded-full ring-rose-400 ring-1 duration-150 px-4 py-2 text-sm text-rose-400 items-center gap-1 shadow-sm hover:shadow-xl hover:bg-rose-400 hover:text-white sm:ml-2 sm:w-auto">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
            </svg>
        Wyloguj
        </a>
        

        </div>
    </div>
</form>

<?php
include_once __DIR__.'/../../../scripts/push/push_lib.php';
$ps = hl_push_settings($conn, $id);
$time_cls = 'border rounded-xl w-full py-1.5 px-3 text-sm border-gray-400 dark:bg-[#2c2c2c] dark:text-gray-200 dark:border-[#2c2c2c] focus:ring-0 focus:outline-0 font-medium';
$toggles = [
    'notify_parts' => ['Plan na początku pory dnia', 'Rano, popołudniu i wieczorem – co masz zaplanowane na tę porę'],
    'notify_missed' => ['Przypomnienie o zapomnianych', 'Gdy pora dnia minie, a coś zostało niezrobione (scalone z kolejnym powiadomieniem) + na koniec dnia'],
    'notify_deadlines' => ['Terminy', 'Rano lista terminów na dziś, a przy terminie z godziną – godzinę wcześniej'],
    'notify_timed' => ['Zadania z godziną', '5 minut przed zaplanowaną godziną'],
    'notify_routines' => ['Rutyny', 'Uwzględniaj rutyny (pojedyncze możesz wyciszyć w ich szczegółach)'],
];
?>
<form id="push-settings" data-aos="fade-up" data-aos-delay="200" onsubmit="pushSettingsSave(event)" class="mt-12 mb-10 max-w-xl mx-auto">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="font-medium text-lg text-black dark:text-gray-200 font-[poppins]">Powiadomienia</p>
            <p id="push-device-state" class="text-xs text-gray-500 dark:text-gray-400">Sprawdzam to urządzenie…</p>
        </div>
        <button type="button" id="push-device-btn" onclick="pushDeviceToggle()" class="hidden flex-none text-xs font-medium px-3 py-1.5 rounded-full ring-1 ring-green-400 text-green-500 hover:bg-green-400 hover:text-white active:scale-95 duration-150"></button>
    </div>

    <label class="mt-5 flex items-center justify-between gap-3 rounded-2xl bg-gray-50 dark:bg-[#2c2c2c] p-3 cursor-pointer">
        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Wysyłaj przypomnienia</span>
        <input type="checkbox" name="enabled" value="1" <?=$ps['enabled'] ? 'checked' : ''?> class="size-5 accent-green-400">
    </label>

    <p class="mt-6 ml-2 text-sm font-medium text-gray-900 dark:text-gray-200">Pory dnia zaczynają się o</p>
    <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
        <?php foreach (['morning_start' => 'Rano', 'afternoon_start' => 'Popołudnie', 'evening_start' => 'Wieczór', 'day_end' => 'Koniec dnia'] as $k => $label): ?>
        <label class="text-xs text-gray-500 dark:text-gray-400 pl-2"><?=$label?>
            <input type="time" name="<?=$k?>" value="<?=$ps[$k]?>" required class="mt-1 <?=$time_cls?>">
        </label>
        <?php endforeach; ?>
    </div>

    <p class="mt-6 ml-2 text-sm font-medium text-gray-900 dark:text-gray-200">Przypominaj o</p>
    <div class="mt-2 flex flex-col gap-2">
        <?php foreach ($toggles as $k => [$label, $hint]): ?>
        <label class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50 dark:bg-[#2c2c2c] p-3 cursor-pointer">
            <span class="min-w-0">
                <span class="block text-sm text-gray-800 dark:text-gray-200"><?=$label?></span>
                <span class="block text-xs text-gray-500 dark:text-gray-400"><?=$hint?></span>
            </span>
            <input type="checkbox" name="<?=$k?>" value="1" <?=$ps[$k] ? 'checked' : ''?> class="size-5 flex-none accent-green-400">
        </label>
        <?php endforeach; ?>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row-reverse gap-2">
        <button class="active:scale-95 duration-150 flex justify-center rounded-full ring-green-400 ring-1 px-4 py-2 text-sm text-green-400 hover:shadow-xl hover:bg-green-400 hover:text-white">Zapisz powiadomienia</button>
        <button type="button" onclick="pushSendTest()" class="active:scale-95 duration-150 flex justify-center rounded-full ring-gray-300 dark:ring-gray-600 ring-1 px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5">Wyślij testowe</button>
    </div>
</form>
<script>
function pushSettingsSave(e) {
    e.preventDefault();
    hlPost('scripts/push/settings.php', new FormData(document.getElementById('push-settings')))
        .then(list => { const r = list[0] || {}; showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi'); })
        .catch(() => showAlert('error', 'Wystąpił problem połączenia z serwerem'));
}

function pushSendTest() {
    hlPost('scripts/push/test.php', new FormData())
        .then(list => { const r = list[0] || {}; showAlert(r.status || 'error', r.message || 'Nieznany status odpowiedzi'); })
        .catch(() => showAlert('error', 'Wystąpił problem połączenia z serwerem'));
}

function pushDeviceRender() {
    const txt = document.getElementById('push-device-state'), btn = document.getElementById('push-device-btn');
    if (!txt) return;
    const state = hlPushState();
    btn.classList.add('hidden');
    if (state === 'granted') {
        navigator.serviceWorker.getRegistration().then(reg => reg && reg.pushManager.getSubscription()).then(sub => {
            txt.textContent = sub ? 'To urządzenie otrzymuje powiadomienia' : 'To urządzenie nie otrzymuje powiadomień';
            btn.textContent = sub ? 'Wyłącz tutaj' : 'Włącz tutaj';
            btn.dataset.on = sub ? '1' : '';
            btn.classList.remove('hidden');
        });
    } else if (state === 'default') {
        txt.textContent = 'To urządzenie nie otrzymuje powiadomień';
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

</section>