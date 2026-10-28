<?php require_once __DIR__ . '/../../scripts/csrf.php'; ?>
<meta name="csrf-token" content="<?=htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')?>">
<script>
// Globalnie: każdy fetch do własnego serwera dostaje nagłówek X-CSRF-Token (POST/...) oraz X-HL-Ajax,
// a każdy wysyłany formularz POST – ukryte pole csrf_token. Dzięki temu istniejące wywołania
// (addElement, hlPost, popupy...) nie wymagają zmian. Odpowiedź z X-Session-Expired przenosi do logowania.
(function () {
    if (window.__hlFetchWrapped) return;
    window.__hlFetchWrapped = true;
    const meta = document.querySelector('meta[name="csrf-token"]');
    let token = meta.content;
    const nativeFetch = window.fetch.bind(window);

    // token w nagłówku i dodatkowo w treści FormData – część serwerów/proxy gubi niestandardowe nagłówki
    function withToken(init) {
        init.headers.set('X-CSRF-Token', token);
        if (init.body instanceof FormData) init.body.set('csrf_token', token);
    }

    window.fetch = function (input, init) {
        init = init || {};
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        let sameOrigin = true;
        try { sameOrigin = new URL(url, window.location.href).origin === window.location.origin; } catch (e) {}
        const method = (init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
        const unsafe = sameOrigin && method !== 'GET' && method !== 'HEAD';
        if (sameOrigin) {
            init.headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
            init.headers.set('X-HL-Ajax', '1');
            if (unsafe) withToken(init);
            init.credentials = init.credentials || 'same-origin';
        }
        return nativeFetch(input, init).then(function (response) {
            // odrzucony token (np. sesja odnowiona z remember_me) – serwer podaje aktualny, ponawiamy raz
            const fresh = response.headers.get('X-CSRF-Token');
            if (unsafe && response.status === 403 && fresh && fresh !== init.headers.get('X-CSRF-Token') && !(input instanceof Request)) {
                token = meta.content = fresh;
                withToken(init);
                return nativeFetch(input, init);
            }
            return response;
        }).then(function (response) {
            if (sameOrigin && response.headers.get('X-Session-Expired') === '1' && !window.__hlRedirecting) {
                window.__hlRedirecting = true;
                setTimeout(function () { window.location.href = 'login'; }, 1500);
            }
            return response;
        });
    };

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || (form.getAttribute('method') || '').toLowerCase() !== 'post') return;
        if (form.querySelector('input[name="csrf_token"]')) return;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = meta.content;
        form.appendChild(input);
    }, true);
})();

// POST + pierwsza odpowiedź JSON {status, message, ...} – dostępne na każdej podstronie panelu
// (hlPost z pages/dashboard.php istnieje dopiero po wejściu na listy)
function hlJson(url, data) {
    return fetch(url, { method: 'POST', body: data || new FormData() })
        .then(r => r.text())
        .then(text => {
            try { return JSON.parse(text.trim().split(/(?<=\})\s*(?=\{)/)[0]); }
            catch (e) { return { status: 'error', message: 'Nieprawidłowa odpowiedź serwera' }; }
        })
        .catch(() => ({ status: 'error', message: 'Wystąpił problem połączenia z serwerem' }));
}
</script>
