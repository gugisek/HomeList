<?php
// te same zadania na tablecie i telefonie – odhaczenie na jednym ekranie pojawia się na drugim (js: [data-sync])
function lp_sync_items() {
    return lp_item('Wynieść śmieci', ['routine' => true, 'streak' => 9, 'key' => 'trash', 'meta' => [lp_icon('repeat', 'lp-i-xs').'Rutyny', lp_icon('users', 'lp-i-xs')]])
        .lp_item('Zakupy na kolację', ['done' => true, 'key' => 'dinner', 'meta' => ['Zakupy', lp_icon('users', 'lp-i-xs')]])
        .lp_item('Podlać kwiaty', ['routine' => true, 'streak' => 23, 'key' => 'plants', 'meta' => [lp_icon('repeat', 'lp-i-xs').'Rutyny']]);
}
$lp_devices = [
    ['phone', 'Telefon', 'w kieszeni'],
    ['desktop', 'Laptop', 'w pracy'],
    ['tablet', 'Tablet w kuchni', 'panel domowy'],
];
?>
<section class="lp-sec" id="wszedzie" data-sky="sky">
    <div class="lp-wrap">
        <div class="lp-sec-head">
            <p class="lp-eyebrow lp-tone-sky"><?=lp_icon('cloud')?><span>Wszędzie</span><em>chmura i panele</em></p>
            <h2 class="lp-h2">Jedno konto,<br>każdy ekran.</h2>
            <p class="lp-p">Listy i plan dnia są w chmurze, na Twoim koncie, a nie w jednym telefonie. Odhaczasz w kuchni, a na laptopie i w telefonie już jest zrobione. Nowy telefon? Logujesz się i wszystko jest na miejscu.</p>
        </div>

        <div class="lp-everywhere">
            <div class="lp-wall" data-reveal="left">
                <div class="lp-tablet">
                    <div class="lp-home">
                        <div class="lp-home-bar">
                            <b><?=lp_icon('home', 'lp-i-sm', '1.8')?>Dom</b>
                            <span><?=date('H:i')?></span>
                        </div>
                        <div class="lp-home-grid">
                            <div class="lp-home-tiles">
                                <div class="lp-tile is-on"><span><?=lp_icon('bulb', 'lp-i-sm', '1.8')?></span><b>Salon</b><small>Włączone</small></div>
                                <div class="lp-tile"><span class="lp-tone-orange"><?=lp_icon('sun', 'lp-i-sm', '1.8')?></span><b>21,5°</b><small>Temperatura</small></div>
                                <div class="lp-tile"><span class="lp-tone-sky"><?=lp_icon('lock', 'lp-i-sm', '1.8')?></span><b>Drzwi</b><small>Zamknięte</small></div>
                                <div class="lp-tile"><span class="lp-tone-indigo"><?=lp_icon('clock', 'lp-i-sm', '1.8')?></span><b>Pralka</b><small>Jeszcze 12 min</small></div>
                            </div>
                            <div class="lp-frame">
                                <em class="lp-frame-tag"><?=lp_icon('code', 'lp-i-xs', '2')?>iframe</em>
                                <div class="lp-screen lp-frame-app" data-feed data-sync="wall">
                                    <div class="lp-feed-head">
                                        <div><b>Dziś</b><small><?=hl_long_date(date('Y-m-d'))?></small></div>
                                        <?=lp_ring(1, 3, ' data-ring')?>
                                    </div>
                                    <ul class="lp-list"><?=lp_sync_items()?></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-sync-phone">
                    <div class="lp-screen" data-feed data-sync="phone">
                        <div class="lp-feed-head"><div><b>Dziś</b></div><?=lp_ring(1, 3, ' data-ring')?></div>
                        <ul class="lp-list"><?=lp_sync_items()?></ul>
                    </div>
                </div>
                <p class="lp-hint"><span></span>Odhacz na tablecie albo w telefonie</p>
            </div>

            <div class="lp-together-side">
                <div class="lp-card" data-reveal="up">
                    <p class="lp-card-kicker"><?=lp_icon('cloud', 'lp-i-sm')?>Konto w chmurze</p>
                    <ul class="lp-devices" data-devices>
                        <?php foreach ($lp_devices as $d): ?>
                        <li><span class="lp-device-ico"><?=lp_icon($d[0], 'lp-i-sm')?></span><div><b><?=$d[1]?></b><small><?=$d[2]?></small></div><em><i></i>aktualne</em></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="lp-card-text">Zmiany trafiają na wszystkie urządzenia, na których jesteś zalogowany. Telefon, komputer, tablet – ile chcesz, bez kabli i eksportów.</p>
                </div>

                <div class="lp-card" data-reveal="up">
                    <p class="lp-card-kicker"><?=lp_icon('tablet', 'lp-i-sm')?>Panel domowy na ścianie</p>
                    <code class="lp-snippet"><span>&lt;iframe</span> src=<b>"https://homelist.rgbpc.pl/panel"</b><span>&gt;</span></code>
                    <p class="lp-card-text">HomeList działa w ramce iframe, więc wstawisz go do panelu domowego, np. Home Assistant, obok świateł i termostatu. Tablet w kuchni pokazuje plan dnia całej rodzinie.</p>
                </div>
            </div>
        </div>
    </div>
</section>
