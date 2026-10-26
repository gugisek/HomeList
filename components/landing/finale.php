<section class="lp-final" id="start" data-sky="green">
    <div class="lp-wrap">
        <div class="lp-final-ico" data-reveal="up"><?=lp_icon('check', 'lp-i', '2.5')?></div>
        <h2 class="lp-h2 lp-final-h" data-reveal="up">Jutro rano będziesz<br>wiedzieć, od czego zacząć.</h2>
        <p class="lp-p lp-final-p" data-reveal="up">Załóż konto, wrzuć kilka rzeczy do planu i zobacz, jak wygląda dzień, który się domyka.</p>
        <div class="lp-hero-cta" data-reveal="up">
            <a href="register.php" class="lp-btn lp-btn-main">Załóż konto <?=lp_icon('arrow', 'lp-i-sm', '2')?></a>
            <a href="login.php" class="lp-btn lp-btn-ghost">Zaloguj się</a>
        </div>
    </div>
</section>

<footer class="lp-foot">
    <div class="lp-wrap">
        <a href="./" class="lp-brand"><img src="img/icon.svg" alt="" width="24" height="24"><span>HomeList</span></a>
        <p><?=date('Y')?> · wersja <?php include __DIR__.'/../../version.php'; ?> · zaprojektował i zbudował <a href="https://github.com/gugisek" target="_blank" rel="noopener">gugisek</a> · powered by <a href="https://rgbpc.pl/" target="_blank" rel="noopener" class="lp-rgb"><span>R</span><span>G</span><span>B</span>pc.pl</a></p>
    </div>
</footer>

<nav class="lp-dock" aria-label="Sekcje">
    <div class="lp-glass lp-dock-pill">
        <a href="#dzis" data-nav="dzis"><?=lp_icon('list', 'lp-i-sm', '1.8')?><em>Dziś</em></a>
        <i></i>
        <a href="#rano" data-nav="rano"><?=lp_icon('sunrise', 'lp-i-sm', '1.8')?><em>Pory dnia</em></a>
        <a href="#razem" data-nav="razem"><?=lp_icon('users', 'lp-i-sm', '1.8')?><em>Razem</em></a>
        <a href="#wieczor" data-nav="wieczor"><?=lp_icon('fire', 'lp-i-sm', '1.8')?><em>Rutyny</em></a>
        <a href="#reszta" data-nav="reszta"><?=lp_icon('inbox', 'lp-i-sm', '1.8')?><em>Reszta</em></a>
    </div>
    <a href="login.php" class="lp-glass lp-dock-more" title="Zaloguj się"><?=lp_icon('login', 'lp-i', '1.6')?></a>
</nav>
