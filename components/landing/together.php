<?php
// wiersz listy w stylu todo.php
function lp_row($title, $who, $tone, $when, $status = 1, $link = false, $extra = '') {
    $st = [1 => ['Oczekuje', 'wait'], 2 => ['Zrobione!', 'done'], 3 => ['W trakcie', 'doing']][$status];
    return '<li class="lp-row is-'.$st[1].'"'.$extra.'>
        <span class="lp-row-dot"></span>
        <div class="lp-row-body"><p>'.$title.'</p><div class="lp-item-meta">'.lp_avatar(hl_substr($who, 1), $tone).'<span>Dodane przez '.$who.'</span>'.lp_dot().'<span>'.$when.'</span></div></div>
        <div class="lp-row-end">'.($link ? '<span class="lp-tone-sky">'.lp_icon('link', 'lp-i-sm', '1.8').'</span>' : '').'<span class="lp-status">'.$st[0].'</span><span class="lp-chev">'.lp_icon('right', 'lp-i-sm', '2').'</span></div>
    </li>';
}
?>
<section class="lp-sec" id="razem" data-sky="orange">
    <div class="lp-wrap">
        <div class="lp-sec-head">
            <p class="lp-eyebrow lp-tone-orange"><?=lp_icon('sun')?><span>Popołudnie</span><em>razem</em></p>
            <h2 class="lp-h2">Jedna lista<br>na cały dom.</h2>
            <p class="lp-p">Zakupy, sprzątanie, remont łazienki. Wspólną listę widzą wszyscy domownicy, a przy każdym zadaniu jest twarz osoby, która je dodała. Od razu wiadomo, kto chciał to mleko owsiane.</p>
        </div>

        <div class="lp-together">
            <div class="lp-screen lp-screen-list" data-reveal="left" data-tilt-hover>
                <div class="lp-list-head">
                    <div><b>Zakupy</b><span class="lp-avas"><?=lp_avatar('O', 'rose').lp_avatar('K', 'sky').lp_avatar('M', 'amber')?></span></div>
                    <span class="lp-plus"><?=lp_icon('plus', 'lp-i', '2')?></span>
                </div>
                <ul class="lp-rows" data-live-list>
                    <?=lp_row('Mleko owsiane', 'Kuba', 'sky', '14:20 dziś')?>
                    <?=lp_row('Chleb na zakwasie', 'Mama', 'amber', '12:05 dziś', 3)?>
                    <?=lp_row('Prezent dla Zosi', 'Ola', 'rose', 'Deadline 18:00 sob', 1, true)?>
                    <?=lp_row('Kapsułki do zmywarki', 'Kuba', 'sky', '21:40 wczoraj', 2)?>
                    <?=lp_row('Żarówka E27 do przedpokoju', 'Mama', 'amber', '19:12 wczoraj', 2)?>
                </ul>
            </div>

            <div class="lp-together-side">
                <div class="lp-card lp-code-card" data-reveal="up">
                    <p class="lp-card-kicker"><?=lp_icon('hash', 'lp-i-sm')?>Kod listy</p>
                    <div class="lp-flap" data-flap="H7K2QX" aria-label="Kod listy H7K2QX">
                        <?php for ($i = 0; $i < 6; $i++): ?><span><b>·</b></span><?php endfor; ?>
                    </div>
                    <p class="lp-card-text">Sześć znaków i domownik jest na liście. Bez zaproszeń mailem, bez zakładania grup.</p>
                </div>

                <div class="lp-card lp-og-card" data-reveal="up" data-og>
                    <p class="lp-card-kicker"><?=lp_icon('link', 'lp-i-sm', '1.8')?>Link w zadaniu</p>
                    <div class="lp-og-task"><b>Prezent dla Zosi</b><span>sklep-z-klockami.pl/zestaw-domek…</span></div>
                    <div class="lp-og">
                        <div class="lp-og-img"><svg viewBox="0 0 80 80" aria-hidden="true"><rect x="14" y="40" width="22" height="22" rx="3" fill="#fb7185"/><rect x="38" y="40" width="22" height="22" rx="3" fill="#38bdf8"/><rect x="26" y="18" width="22" height="22" rx="3" fill="#facc15"/><circle cx="21" cy="40" r="3" fill="#fb7185"/><circle cx="29" cy="40" r="3" fill="#fb7185"/><circle cx="45" cy="40" r="3" fill="#38bdf8"/><circle cx="53" cy="40" r="3" fill="#38bdf8"/><circle cx="33" cy="18" r="3" fill="#facc15"/><circle cx="41" cy="18" r="3" fill="#facc15"/></svg></div>
                        <div class="lp-og-txt"><b>Zestaw Domek na drzewie, 6+</b><span>Klocki, 412 elementów. Wysyłka w 24 h.</span><small>sklep-z-klockami.pl</small></div>
                        <div class="lp-og-skel"><i></i><i></i><i></i></div>
                    </div>
                    <p class="lp-card-text">Wklej link, a zadanie samo pokaże podgląd strony. Jak w Messengerze.</p>
                </div>
            </div>
        </div>
    </div>
</section>
