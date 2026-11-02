<?php
include 'scripts/security.php';
require_once 'scripts/users/text_scale_lib.php';
// rozmiar tekstu z Konto i ustawienia → Wygląd (mnożnik --hl-fs, patrz components/head.php)
$stmt = $conn->prepare('SELECT text_scale FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['login_id']);
$stmt->execute();
$hl_fs = hl_text_scale_css($stmt->get_result()->fetch_row()[0] ?? 100);
?>
<!DOCTYPE html>
<html lang="pl" style="--hl-fs: <?=$hl_fs?>">
<head>
<?php
$title = "panel";
include 'components/head.php'; ?>
<?php include 'components/panel/csrf_js.php'; ?>
<?php include 'components/panel/push_js.php'; ?>
<?php include 'components/panel/install_js.php'; ?>
</head>
<body id="overflow_body" class="bg-[#fdfdfd] dark:bg-[#3d3d3d]">
    <?php include 'components/alert.php'; ?>
    <?php include 'components/alert_js.php'; ?>
    <?php include 'components/panel/panel_body.php'; ?>
    <script>
        // wejście treści panelu bez AOS: AOS odpalał animację według pozycji scrolla policzonej w chwili wstawienia,
        // więc po przejściu z długiej, przewiniętej zakładki nagłówek znikał (wracał dopiero w połowie scrolla),
        // a treść listy potrafiła w ogóle się nie pokazać. Tu animacja ([data-hl-enter] w app.css) rusza raz, od razu
        // przy wstawieniu – MutationObserver działa przed malowaniem, więc style AOS (opacity: 0) nie zdążą mignąć.
        // Tylko atrybuty data-* i style, bez zmian class – zmiana class budzi przebudowę Tailwind CDN, a nałożone
        // przebudowy potrafiły na iOS zgubić CSS właśnie wstawionej treści (lista, dolny pasek bez stylów).
        (function () {
            var kinds = { 'fade-up': 'up', 'fade-down': 'down', 'fade-right': 'right', 'fade-left': 'left' };
            function enter(el) {
                el.style.setProperty('--hl-enter-delay', (parseInt(el.getAttribute('data-aos-delay'), 10) || 0) + 'ms');
                el.setAttribute('data-hl-enter', kinds[el.getAttribute('data-aos')] || 'fade');
                el.removeAttribute('data-aos');
                el.removeAttribute('data-aos-delay');
                el.addEventListener('animationend', function done(e) {
                    if (e.target !== el) return;
                    el.removeEventListener('animationend', done);
                    el.removeAttribute('data-hl-enter');
                    el.style.removeProperty('--hl-enter-delay');
                });
            }
            function scan(node) {
                if (node.nodeType !== 1) return;
                if (node.hasAttribute('data-aos')) enter(node);
                node.querySelectorAll('[data-aos]').forEach(enter);
            }
            new MutationObserver(function (records) {
                records.forEach(function (r) { r.addedNodes.forEach(scan); });
            }).observe(document.body, { childList: true, subtree: true });
            scan(document.body);
        })();
    </script>
    <script>
        function highlightChanges() {
        const beforeDiv = document.getElementById("before");
        const afterDiv = document.getElementById("after");

        // Pobierz zawartość HTML, aby zachować znaczniki <br/>
        const beforeHTML = beforeDiv.innerHTML;
        const afterHTML = afterDiv.innerHTML;

        // Podziel tekst na fragmenty, zachowując <br/>
        const beforeParts = beforeHTML.split(/(<br\s*\/?>)/gi); // Dzielimy z zachowaniem <br/>
        const afterParts = afterHTML.split(/(<br\s*\/?>)/gi);

        // Funkcja czyszcząca znaczniki HTML z tekstu
        const stripHTML = str => str.replace(/<[^>]*>/g, '').trim();

        // Tworzymy zestaw dla szybkiego porównania
        const beforeTextSet = new Set(beforeParts.map(stripHTML));
        const afterTextSet = new Set(afterParts.map(stripHTML));

        // Szukamy nowych i usuniętych fragmentów
        const addedParts = afterParts.filter(part => !beforeTextSet.has(stripHTML(part)) && !/<br\s*\/?>/.test(part));
        const removedParts = beforeParts.filter(part => !afterTextSet.has(stripHTML(part)) && !/<br\s*\/?>/.test(part));

        // Zastępujemy odpowiednie fragmenty kolorami
        const highlightedBefore = beforeParts.map(part => {
            if (removedParts.includes(part)) {
                return `<span style="color: red;">${stripHTML(part)}</span>${part.includes('<br') ? '<br/>' : ''}`;
            }
            return part;
        }).join('');

        const highlightedAfter = afterParts.map(part => {
            if (addedParts.includes(part)) {
                return `<span style="color: green;">${stripHTML(part)}</span>${part.includes('<br') ? '<br/>' : ''}`;
            }
            return part;
        }).join('');

        // Zaktualizuj zawartość divów
        beforeDiv.innerHTML = highlightedBefore;
        afterDiv.innerHTML = highlightedAfter;
    }
    </script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script> -->
</body>
</html>