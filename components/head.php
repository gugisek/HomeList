<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title> HomeList - <?=$title?></title>
<!-- meta description -->
<meta name="description" content="HomeList - listy ToDo w Twoim domu oraz w telefonie, działa z Home Assistant, powered by RGBpc.pl">
<script src="https://cdn.tailwindcss.com"></script>
<script>
// Rozmiar tekstu (Konto i ustawienia → Wygląd): text-* i leading-* mnożone przez --hl-fs ustawiane na <html> (panel.php).
// Odstępy, ikony i wysokości zostają bez zmian – rośnie tylko tekst. text-10px/11px/15px zamiast text-[Npx].
(function () {
    var fs = function (v) { return 'calc(' + v + ' * var(--hl-fs, 1))'; }, fontSize = {}, lineHeight = {};
    var sizes = { xs: ['0.75rem', '1rem'], sm: ['0.875rem', '1.25rem'], base: ['1rem', '1.5rem'], lg: ['1.125rem', '1.75rem'], xl: ['1.25rem', '1.75rem'],
        '2xl': ['1.5rem', '2rem'], '3xl': ['1.875rem', '2.25rem'], '4xl': ['2.25rem', '2.5rem'], '5xl': ['3rem', '1'], '6xl': ['3.75rem', '1'],
        '7xl': ['4.5rem', '1'], '8xl': ['6rem', '1'], '9xl': ['8rem', '1'], '10px': ['10px'], '11px': ['11px'], '15px': ['15px'] };
    Object.keys(sizes).forEach(function (k) {
        var s = sizes[k];
        fontSize[k] = s[1] ? [fs(s[0]), { lineHeight: /rem$/.test(s[1]) ? fs(s[1]) : s[1] }] : fs(s[0]);
    });
    [3, 4, 5, 6, 7, 8, 9, 10].forEach(function (n) { lineHeight[n] = fs(n * 0.25 + 'rem'); });
    tailwind.config = { theme: { extend: { fontSize: fontSize, lineHeight: lineHeight } } };
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="icon" href="img/favicon.png" type="image/png">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<link href="css/global.css?v=<?=filemtime(__DIR__ . '/../css/global.css')?>" rel="stylesheet">
<link href="css/app.css" rel="stylesheet">
<link href="css/food.css" rel="stylesheet">
<link href="css/bills.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<!-- <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" /> -->


<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" href="img/apple-touch-icon.png">
<meta name="theme-color" content="#3d3d3d">
