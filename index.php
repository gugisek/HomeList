<?php
// zalogowany (także przez remember-me) od razu trafia do panelu
require_once "scripts/session_restore.php";
if (hl_session_start()) {
    header("Location: panel.php");
    exit();
}
include 'components/landing/ui.php';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>HomeList – plan dnia, rutyny i wspólne listy dla domu</title>
    <meta name="description" content="HomeList to listy zadań dla domu i dla siebie: plan dnia w porach, rutyny z seriami, wspólne listy z domownikami i przypomnienia bez spamu.">
    <meta name="theme-color" content="#f5f5f3" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#262626" media="(prefers-color-scheme: dark)">
    <meta name="color-scheme" content="light dark">
    <link rel="icon" href="img/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="img/apple-touch-icon.png">
    <link rel="manifest" href="manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/landing.css?v=<?=filemtime(__DIR__.'/css/landing.css')?>" rel="stylesheet">
</head>
<body class="lp">
    <div class="lp-sky" aria-hidden="true">
        <span data-sky-layer="green"></span><span data-sky-layer="amber"></span><span data-sky-layer="orange"></span><span data-sky-layer="indigo"></span><span data-sky-layer="gray"></span>
    </div>
    <main>
        <?php include 'components/landing/hero.php'; ?>
        <?php include 'components/landing/morning.php'; ?>
        <?php include 'components/landing/together.php'; ?>
        <?php include 'components/landing/evening.php'; ?>
        <?php include 'components/landing/anytime.php'; ?>
    </main>
    <?php include 'components/landing/finale.php'; ?>
    <script src="js/landing.js?v=<?=filemtime(__DIR__.'/js/landing.js')?>"></script>
</body>
</html>
