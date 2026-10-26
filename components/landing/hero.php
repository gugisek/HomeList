<?php
$lp_today = date('Y-m-d');
$lp_long = hl_long_date($lp_today);
?>
<header class="lp-top">
    <a href="./" class="lp-brand"><img src="img/icon.svg" alt="" width="28" height="28"><span>HomeList</span></a>
    <a href="login.php" class="lp-top-login">Zaloguj się</a>
</header>

<section class="lp-hero" id="dzis" data-sky="green">
    <div class="lp-wrap lp-hero-copy">
        <p class="lp-hero-date"><b>Dziś</b><span><?=$lp_long?></span></p>
        <h1 class="lp-h1">
            <span class="lp-h1-line">Twój dzień,</span>
            <span class="lp-h1-line"><span class="lp-h1-check" data-hero-check><svg viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg></span>odhaczony.</span>
        </h1>
        <p class="lp-lead">Listy zadań dla domu i dla siebie. Plan dnia ułożony w porach, rutyny z seriami, wspólne listy z domownikami i przypomnienia, które nie zasypują telefonu.</p>
        <div class="lp-hero-cta">
            <a href="register.php" class="lp-btn lp-btn-main">Załóż konto <?=lp_icon('arrow', 'lp-i-sm', '2')?></a>
            <a href="login.php" class="lp-btn lp-btn-ghost">Mam już konto</a>
        </div>
    </div>

    <div class="lp-stage">
        <div class="lp-rise">
            <div class="lp-tilt" data-hero-tilt>
                <div class="lp-screen lp-screen-desk" data-feed>
                    <aside class="lp-side">
                        <div class="lp-side-logo"><?=lp_icon('list', 'lp-i')?><small>HomeList</small></div>
                        <nav>
                            <span class="is-on"><?=lp_icon('list', 'lp-i')?></span>
                            <span><?=lp_icon('users', 'lp-i')?></span>
                            <span><?=lp_icon('folder', 'lp-i')?></span>
                            <span><?=lp_icon('gear', 'lp-i')?></span>
                        </nav>
                        <span class="lp-side-ava"></span>
                    </aside>
                    <div class="lp-screen-main">
                        <div class="lp-feed-head">
                            <div><b>Dziś</b><small><?=$lp_long?></small></div>
                            <div class="lp-feed-head-end"><?=lp_ring(3, 7, ' data-ring')?><span class="lp-plus"><?=lp_icon('plus', 'lp-i', '2')?></span></div>
                        </div>
                        <?=lp_day_strip(-3, 12)?>
                        <div class="lp-pills"><span><?=lp_icon('queue', 'lp-i-sm')?>Zaplanuj z list</span><span><?=lp_icon('calendar', 'lp-i-sm')?>Miesiąc</span></div>

                        <div class="lp-celebrate" data-celebrate>
                            <div><span class="lp-celebrate-ico"><?=lp_icon('check', 'lp-i', '2.5')?></span><p><b>Dzień zaliczony!</b><small>Wszystko z planu odhaczone. Dobra robota.</small></p></div>
                        </div>

                        <div class="lp-feed-cols">
                            <div>
                                <?=lp_part_head(1, '2/2')?>
                                <ul class="lp-list">
                                    <?=lp_item('Leki', ['routine' => true, 'done' => true, 'streak' => 41, 'meta' => [lp_icon('repeat', 'lp-i-xs').'Rutyny', lp_icon('clock', 'lp-i-xs').'08:00']])?>
                                    <?=lp_item('Wyprowadzić Burka', ['routine' => true, 'done' => true, 'streak' => 12, 'meta' => [lp_icon('repeat', 'lp-i-xs').'Rutyny', lp_icon('users', 'lp-i-xs')]])?>
                                </ul>
                                <?=lp_part_head(2, '1/3')?>
                                <ul class="lp-list">
                                    <?=lp_item('Odebrać paczkę z paczkomatu', ['meta' => ['Dom', lp_icon('clock', 'lp-i-xs').'15:30', '<span class="lp-tone-sky">'.lp_icon('link', 'lp-i-xs', '1.8').'</span>']])?>
                                    <?=lp_item('Zakupy na kolację', ['done' => true, 'meta' => ['Zakupy', lp_icon('users', 'lp-i-xs')]])?>
                                    <?=lp_item('Oddać wiertarkę sąsiadowi', ['meta' => ['Dom']])?>
                                </ul>
                            </div>
                            <div>
                                <?=lp_part_head(3, '0/2')?>
                                <ul class="lp-list">
                                    <?=lp_item('Podlać kwiaty', ['routine' => true, 'streak' => 23, 'meta' => [lp_icon('repeat', 'lp-i-xs').'Rutyny']])?>
                                    <?=lp_item('Zapłacić za prąd', ['tone' => 'deadline', 'meta' => ['Rachunki', '<span class="lp-tone-rose">Deadline 20:00</span>']])?>
                                </ul>
                                <?=lp_part_head(0)?>
                                <ul class="lp-list">
                                    <li class="lp-empty">Pusto – przeciągnij tu lub dodaj +</li>
                                </ul>
                            </div>
                        </div>
                        <?=lp_app_dock('Dziś')?>
                    </div>
                </div>

                <div class="lp-float lp-float-push lp-glass-card">
                    <span class="lp-push-ico"><img src="img/icon.svg" alt=""></span>
                    <div><p><b>Popołudnie</b><small>teraz</small></p><span>2 zadania na start: Odebrać paczkę, Oddać wiertarkę</span></div>
                </div>
                <div class="lp-float lp-float-streak lp-glass-card">
                    <span class="lp-tone-orange"><?=lp_icon('fire', 'lp-i')?></span><b>41</b><small>dni z rzędu</small>
                </div>
            </div>
        </div>
        <p class="lp-hint"><span></span>To nie zrzut ekranu – odhacz coś</p>
    </div>
</section>
