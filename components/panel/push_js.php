<?php include_once __DIR__ . '/../../scripts/push/push_lib.php'; ?>
<script>
// ===== Powiadomienia push (sw.js + scripts/push/) – globalnie, bo używa ich i dashboard, i „Konto i ustawienia” =====
window.HL_VAPID_KEY = '<?=hl_vapid()['public'] ?? ''?>';

// unsupported | ios-install (iPhone: tylko z ekranu głównego) | denied | default | granted
function hlPushState() {
    const ios = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
    if (ios && !standalone && !('PushManager' in window)) return 'ios-install';
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window) || !window.HL_VAPID_KEY) return 'unsupported';
    return Notification.permission;
}

function hlPushKey() {
    const b64 = (window.HL_VAPID_KEY + '='.repeat((4 - window.HL_VAPID_KEY.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(b64), c => c.charCodeAt(0));
}

// tworzy (albo odświeża) subskrypcję tego urządzenia i zapisuje ją na serwerze
function hlPushSubscribe() {
    return navigator.serviceWorker.register('sw.js')
        .then(() => navigator.serviceWorker.ready)
        .then(reg => reg.pushManager.getSubscription().then(sub => {
            // subskrypcja z innym kluczem VAPID (np. po zmianie kluczy na serwerze) – push by ją odrzucił, zakładamy nową
            const key = hlPushKey();
            const old = sub && sub.options && sub.options.applicationServerKey ? new Uint8Array(sub.options.applicationServerKey) : null;
            if (sub && old && old.length === key.length && old.every((b, i) => b === key[i])) return sub;
            return (sub ? sub.unsubscribe() : Promise.resolve())
                .then(() => reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key }));
        }))
        .then(sub => {
            const fd = new FormData();
            fd.append('endpoint', sub.endpoint);
            return hlJson('scripts/push/subscribe.php', fd);
        })
        .then(r => r.status === 'success');
}

function hlPushEnable() {
    const state = hlPushState();
    if (state === 'ios-install') { showAlert('warning', 'Na iPhonie dodaj HomeList do ekranu głównego (Udostępnij → Do ekranu początkowego) i włącz powiadomienia stamtąd'); return Promise.resolve(false); }
    if (state === 'unsupported') { showAlert('warning', 'Ta przeglądarka nie obsługuje powiadomień push'); return Promise.resolve(false); }
    if (state === 'denied') { showAlert('warning', 'Powiadomienia są zablokowane – odblokuj je w ustawieniach przeglądarki dla tej strony'); return Promise.resolve(false); }
    return Notification.requestPermission()
        .then(p => p === 'granted' ? hlPushSubscribe() : false)
        .then(ok => {
            if (ok) showAlert('success', 'Powiadomienia włączone');
            else if (Notification.permission !== 'granted') showAlert('warning', 'Nie zezwolono na powiadomienia');
            else showAlert('error', 'Nie udało się zapisać subskrypcji');
            return ok;
        })
        .catch(() => { showAlert('error', 'Nie udało się włączyć powiadomień'); return false; });
}

function hlPushDisable() {
    return navigator.serviceWorker.getRegistration()
        .then(reg => reg && reg.pushManager.getSubscription())
        .then(sub => {
            if (!sub) return;
            const fd = new FormData();
            fd.append('endpoint', sub.endpoint);
            fd.append('action', 'unsubscribe');
            return hlJson('scripts/push/subscribe.php', fd).then(() => sub.unsubscribe());
        });
}

// ciche odświeżenie subskrypcji przy wejściu (raz na załadowanie strony) – np. po zmianie kont lub wygaśnięciu
function hlPushSync() {
    if (window.hlPushSynced || hlPushState() !== 'granted') return;
    window.hlPushSynced = true;
    hlPushSubscribe().catch(() => {});
}
</script>
