// Service worker HomeList – powiadomienia push.
// Push przychodzi bez treści; treść pobieramy z serwera (scripts/push/pull.php).
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));

self.addEventListener('push', event => {
    event.waitUntil((async () => {
        let items = [];
        try {
            const sub = await self.registration.pushManager.getSubscription();
            const body = new FormData();
            body.append('endpoint', sub ? sub.endpoint : '');
            const res = await fetch(new URL('scripts/push/pull.php', self.registration.scope), { method: 'POST', body });
            items = await res.json();
        } catch (e) {}
        if (!items.length) items = [{ title: 'HomeList', body: 'Masz rzeczy do zrobienia na dziś', tag: 'fallback', url: 'panel.php' }];
        // icon – kolorowa ikona aplikacji; badge – biała sylwetka (Android bierze z niej tylko kanał alfa)
        const icon = new URL('img/icon-192.png', self.registration.scope).href;
        const badge = new URL('img/badge-96.png', self.registration.scope).href;
        await Promise.all(items.map(it => self.registration.showNotification(it.title, {
            body: it.body,
            tag: it.tag,
            renotify: true,
            icon: icon,
            badge: badge,
            data: { url: new URL(it.url || 'panel.php', self.registration.scope).href },
        })));
    })());
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data && event.notification.data.url;
    event.waitUntil((async () => {
        const all = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const client = all.find(c => c.url.startsWith(self.registration.scope));
        if (client) return client.focus();
        return self.clients.openWindow(url || self.registration.scope);
    })());
});
