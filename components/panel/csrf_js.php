<?php require_once __DIR__ . '/../../scripts/csrf.php'; ?>
<meta name="csrf-token" content="<?=htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')?>">
<script>
// Globalnie: każdy fetch do własnego serwera dostaje nagłówek X-CSRF-Token (POST/...) oraz X-HL-Ajax,
// a każdy wysyłany formularz POST – ukryte pole csrf_token. Dzięki temu istniejące wywołania
// (addElement, hlPost, popupy...) nie wymagają zmian. Odpowiedź z X-Session-Expired przenosi do logowania.
(function () {
    if (window.__hlFetchWrapped) return;
    window.__hlFetchWrapped = true;
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const nativeFetch = window.fetch.bind(window);

    window.fetch = function (input, init) {
        init = init || {};
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        let sameOrigin = true;
        try { sameOrigin = new URL(url, window.location.href).origin === window.location.origin; } catch (e) {}
        if (sameOrigin) {
            const method = (init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
            const headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
            headers.set('X-HL-Ajax', '1');
            if (method !== 'GET' && method !== 'HEAD') headers.set('X-CSRF-Token', token);
            init.headers = headers;
            init.credentials = init.credentials || 'same-origin';
        }
        return nativeFetch(input, init).then(function (response) {
            if (sameOrigin && response.headers.get('X-Session-Expired') === '1' && !window.__hlRedirecting) {
                window.__hlRedirecting = true;
                setTimeout(function () { window.location.href = 'login.php'; }, 1500);
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
        input.value = token;
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
