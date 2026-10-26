<?php
function lp_mini_feed() {
    ob_start(); ?>
    <div class="lp-mini">
        <div class="lp-feed-head"><div><b>Dziś</b><small><?=hl_long_date(date('Y-m-d'))?></small></div><?=lp_ring(2, 4)?></div>
        <?=lp_part_head(1)?>
        <ul class="lp-list">
            <?=lp_item('Leki', ['routine' => true, 'done' => true, 'streak' => 41, 'meta' => ['Rutyny']])?>
            <?=lp_item('Mail do szkoły', ['done' => true, 'meta' => ['Dom']])?>
        </ul>
        <?=lp_part_head(3)?>
        <ul class="lp-list">
            <?=lp_item('Podlać kwiaty', ['routine' => true, 'streak' => 23, 'meta' => ['Rutyny']])?>
            <?=lp_item('Zapłacić za prąd', ['tone' => 'deadline', 'meta' => ['<span class="lp-tone-rose">Deadline 20:00</span>']])?>
        </ul>
    </div>
    <?php return ob_get_clean();
}
$lp_rest = [
    ['Jasny i ciemny motyw', 'przełącza się sam, razem z systemem'],
    ['Działa jak aplikacja', 'zainstaluj na telefonie i komputerze'],
    ['Szybkie dodawanie', 'tytuł, opis i link w jednym okienku'],
    ['Widok miesiąca', 'i pasek dni, który przewijasz palcem'],
    ['Archiwum', 'zrobione nie znikają, tylko odkładają się na bok'],
    ['Logowanie dwuetapowe', 'i lista urządzeń, na których jesteś zalogowany'],
    ['Zgłoś błąd ze zrzutem', 'prosto z ustawień konta, bez szukania maila'],
];
?>
<section class="lp-sec" id="reszta" data-sky="gray">
    <div class="lp-wrap">
        <div class="lp-sec-head">
            <p class="lp-eyebrow lp-tone-gray"><?=lp_icon('inbox')?><span>Kiedykolwiek</span><em>i cała reszta</em></p>
            <h2 class="lp-h2">Drobiazgi,<br>które robią różnicę.</h2>
        </div>

        <div class="lp-rest">
            <div class="lp-rest-list" data-feed data-reveal="left">
                <div class="lp-rest-head">
                    <p>Odhacz wszystko, co Ci się podoba</p>
                    <?=lp_ring(0, count($lp_rest), ' data-ring')?>
                </div>
                <div class="lp-celebrate" data-celebrate>
                    <div><span class="lp-celebrate-ico"><?=lp_icon('check', 'lp-i', '2.5')?></span><p><b>Dzień zaliczony!</b><small>Wszystko odhaczone. To teraz konto?</small></p></div>
                </div>
                <ul class="lp-list">
                    <?php foreach ($lp_rest as $r) echo lp_item($r[0], ['meta' => [$r[1]]]); ?>
                </ul>
            </div>

            <div class="lp-compare" data-compare data-reveal="right">
                <div class="lp-compare-pane theme-light"><?=lp_mini_feed()?></div>
                <div class="lp-compare-pane theme-dark" data-compare-top><?=lp_mini_feed()?></div>
                <div class="lp-compare-handle" data-compare-handle>
                    <em class="lp-compare-tag is-light">Jasny</em><em class="lp-compare-tag is-dark">Ciemny</em>
                    <span><?=lp_icon('left', 'lp-i-xs', '2.2')?><?=lp_icon('right', 'lp-i-xs', '2.2')?></span>
                </div>
            </div>
        </div>
    </div>
</section>
