<script>
// ===== Instalacja PWA (przycisk „Zainstaluj aplikację” w menu list – dashboard/lists.php) =====
// beforeinstallprompt przychodzi zanim navbar się załaduje, więc łapiemy go globalnie.
// Gdy przeglądarka nie daje promptu (iOS, Firefox, Samsung, Safari) – pokazujemy instrukcję.
window.hlInstallPrompt = null;

function hlIsStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}
function hlInstallSync() {
    document.documentElement.classList.toggle('hl-standalone', hlIsStandalone());
}
hlInstallSync();

window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    window.hlInstallPrompt = e;
});
window.addEventListener('appinstalled', () => {
    window.hlInstallPrompt = null;
    document.documentElement.classList.add('hl-standalone');
    hlInstallGuideClose();
    if (typeof showAlert === 'function') showAlert('success', 'Aplikacja HomeList została zainstalowana');
});
// zarejestrowany service worker jest warunkiem instalowalności w części przeglądarek
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('sw.js').catch(() => {}));
}

function hlInstallApp() {
    if (hlIsStandalone()) {
        showAlert('success', 'Aplikacja jest już zainstalowana');
        return;
    }
    const prompt = window.hlInstallPrompt;
    if (!prompt) {
        hlInstallGuide();
        return;
    }
    window.hlInstallPrompt = null; // prompt() można wywołać tylko raz
    prompt.prompt();
    prompt.userChoice.then(choice => {
        if (choice.outcome !== 'accepted') showAlert('warning', 'Instalacja anulowana');
    }).catch(() => hlInstallGuide());
}

function hlInstallPlatform() {
    const ua = navigator.userAgent;
    const ios = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (ios) return /CriOS|FxiOS|EdgiOS/.test(ua) ? 'ios_other' : 'ios_safari';
    if (/Android/.test(ua)) {
        if (/SamsungBrowser/.test(ua)) return 'android_samsung';
        if (/Firefox/.test(ua)) return 'android_firefox';
        return 'android_chrome';
    }
    if (/Firefox/.test(ua)) return 'desktop_firefox';
    if (/Edg\//.test(ua)) return 'desktop_edge';
    if (/Chrome|Chromium|OPR\//.test(ua)) return 'desktop_chrome';
    if (/Safari/.test(ua)) return 'desktop_safari';
    return 'other';
}

function hlInstallGuide() {
    hlInstallGuideClose();
    const ico = {
        share: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4 inline -mt-1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" /></svg>',
        dots: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 inline -mt-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" /></svg>',
        burger: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4 inline -mt-1"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>',
        plus: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4 inline -mt-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
    };
    const b = t => '<b class="font-semibold text-gray-900 dark:text-white">' + t + '</b>';
    const guides = {
        ios_safari: ['iPhone / iPad – Safari', [
            'Stuknij ' + b('Udostępnij') + ' ' + ico.share + ' na pasku Safari',
            'Przewiń listę w dół i wybierz ' + b('Do ekranu początkowego') + ' ' + ico.plus,
            'Potwierdź przyciskiem ' + b('Dodaj') + ' w prawym górnym rogu',
        ]],
        ios_other: ['iPhone / iPad', [
            'Stuknij ' + b('Udostępnij') + ' ' + ico.share + ' (w Chrome – w pasku adresu)',
            'Wybierz ' + b('Do ekranu początkowego') + ' ' + ico.plus,
            'Potwierdź przyciskiem ' + b('Dodaj'),
            'Jeśli brakuje tej opcji – otwórz HomeList w ' + b('Safari') + ' i powtórz kroki',
        ]],
        android_chrome: ['Android – Chrome', [
            'Stuknij menu ' + ico.dots + ' w prawym górnym rogu',
            'Wybierz ' + b('Zainstaluj aplikację') + ' lub ' + b('Dodaj do ekranu głównego'),
            'Potwierdź przyciskiem ' + b('Zainstaluj'),
        ]],
        android_samsung: ['Android – Samsung Internet', [
            'Stuknij menu ' + ico.burger + ' na dolnym pasku',
            'Wybierz ' + b('Dodaj stronę do') + ' → ' + b('Ekran startowy'),
            'Potwierdź przyciskiem ' + b('Dodaj'),
        ]],
        android_firefox: ['Android – Firefox', [
            'Stuknij menu ' + ico.dots,
            'Wybierz ' + b('Zainstaluj') + ' lub ' + b('Dodaj do ekranu głównego'),
            'Potwierdź przyciskiem ' + b('Dodaj'),
        ]],
        desktop_chrome: ['Komputer – Chrome', [
            'Kliknij ikonę instalacji po prawej stronie paska adresu',
            'albo menu ' + ico.dots + ' → ' + b('Przesyłaj, zapisz i udostępnij') + ' → ' + b('Zainstaluj stronę jako aplikację'),
            'Potwierdź przyciskiem ' + b('Zainstaluj'),
        ]],
        desktop_edge: ['Komputer – Edge', [
            'Kliknij ikonę instalacji po prawej stronie paska adresu',
            'albo menu ' + b('…') + ' → ' + b('Aplikacje') + ' → ' + b('Zainstaluj tę witrynę jako aplikację'),
            'Potwierdź przyciskiem ' + b('Zainstaluj'),
        ]],
        desktop_safari: ['Mac – Safari', [
            'Z paska menu wybierz ' + b('Plik') + ' → ' + b('Dodaj do Docka'),
            'Potwierdź przyciskiem ' + b('Dodaj'),
            'Opcja dostępna od macOS Sonoma (14)',
        ]],
        desktop_firefox: ['Komputer – Firefox', [
            'Firefox na komputerze nie obsługuje instalowania aplikacji webowych',
            'Otwórz HomeList w ' + b('Chrome') + ' lub ' + b('Edge') + ' i kliknij ' + b('Zainstaluj aplikację'),
        ]],
        other: ['Twoja przeglądarka', [
            'Otwórz menu przeglądarki',
            'Poszukaj opcji ' + b('Zainstaluj aplikację') + ' lub ' + b('Dodaj do ekranu głównego'),
            'Najlepiej działa w ' + b('Chrome') + ', ' + b('Edge') + ' i ' + b('Safari'),
        ]],
    };
    const [label, steps] = guides[hlInstallPlatform()];
    const warn = window.isSecureContext ? '' :
        '<p class="mt-4 text-xs rounded-xl px-3 py-2 bg-yellow-50 text-yellow-700 dark:bg-yellow-400/10 dark:text-yellow-300">Instalacja wymaga połączenia HTTPS – otwórz stronę przez https://</p>';

    const wrap = document.createElement('div');
    wrap.id = 'hlInstallGuide';
    wrap.className = 'fixed inset-0 z-[70] flex sm:items-center items-end justify-center bg-black/50 backdrop-blur-md opacity-0 transition-opacity duration-300 font-[poppins]';
    wrap.onclick = hlInstallGuideClose;
    wrap.innerHTML = `
        <div onclick="event.stopPropagation()" class="hl-install-card relative bg-white dark:bg-[#1e1e1e] shadow-2xl shadow-black/20 dark:shadow-black/60 ring-1 ring-black/[0.06] dark:ring-white/[0.06] sm:rounded-[28px] rounded-t-[32px] w-full sm:w-[420px] max-h-[92vh] overflow-y-auto px-6 pt-4 pb-8 sm:pb-6 translate-y-full sm:translate-y-0 sm:scale-95 transition-transform duration-300">
            <div class="sm:hidden flex justify-center pb-3"><div class="w-9 h-1 rounded-full bg-gray-300 dark:bg-white/20"></div></div>
            <button type="button" onclick="hlInstallGuideClose()" class="absolute top-2 right-2 sm:flex hidden items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-white/10 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-white transition-colors duration-150">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            <div class="flex items-center gap-3 sm:pt-2">
                <img src="img/icon-192.png" alt="" class="size-14 rounded-2xl shadow-lg shadow-black/20">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white leading-tight">Zainstaluj HomeList</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">${label}</p>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Przeglądarka nie pozwala zainstalować aplikacji jednym kliknięciem – zrób to w kilku krokach:</p>
            <ol class="mt-4 space-y-3">
                ${steps.map((s, i) => `
                <li class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <span class="shrink-0 size-6 rounded-full bg-gradient-to-br from-green-500 to-emerald-500 text-white text-xs font-semibold flex items-center justify-center shadow shadow-green-500/30">${i + 1}</span>
                    <span class="pt-0.5">${s}</span>
                </li>`).join('')}
            </ol>
            ${warn}
            <button type="button" onclick="hlInstallGuideClose()" class="mt-6 w-full rounded-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-400 hover:to-emerald-400 shadow-lg shadow-green-500/25 active:scale-95 duration-150">Rozumiem</button>
        </div>`;
    document.body.appendChild(wrap);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        wrap.classList.remove('opacity-0');
        wrap.querySelector('.hl-install-card').classList.remove('translate-y-full', 'sm:scale-95');
    }));
}

function hlInstallGuideClose() {
    const wrap = document.getElementById('hlInstallGuide');
    if (!wrap) return;
    wrap.classList.add('opacity-0');
    wrap.querySelector('.hl-install-card').classList.add('translate-y-full', 'sm:scale-95');
    setTimeout(() => wrap.remove(), 300);
}
</script>
