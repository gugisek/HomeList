<section class="lp-sec lp-evening" id="wieczor" data-sky="indigo">
    <div class="lp-wrap">
        <div class="lp-evening-grid">
            <div class="lp-sec-head">
                <p class="lp-eyebrow lp-tone-indigo"><?=lp_icon('moon')?><span>Wieczór</span><em>rutyny</em></p>
                <h2 class="lp-h2">Serie, których<br>szkoda przerwać.</h2>
                <p class="lp-p">Rutyny pojawiają się w planie same: w swoje dni i o swojej porze. Odhaczasz, a seria rośnie. Domowe obowiązki mogą mieć wspólne odhaczenie, więc jak ktoś wyniesie śmieci, zadanie znika u wszystkich.</p>
                <div class="lp-bignum" data-count="23">
                    <span class="lp-tone-orange"><?=lp_icon('fire', 'lp-i', '1.2')?></span><b>0</b>
                    <small>dni z rzędu<br><em>Podlać kwiaty</em></small>
                </div>
            </div>

            <div class="lp-screen lp-screen-routines" data-reveal="right" data-tilt-hover data-feed>
                <div class="lp-list-head">
                    <div><b>Rutyny</b><span class="lp-badge-routine"><?=lp_icon('repeat', 'lp-i-xs', '2')?>rutyny</span></div>
                    <span class="lp-plus"><?=lp_icon('plus', 'lp-i', '2')?></span>
                </div>
                <ul class="lp-list">
                    <?=lp_item('Leki', ['routine' => true, 'done' => true, 'streak' => 41, 'week' => ['done', 'done', 'done', 'done', 'done', 'done', 'done'], 'meta' => [lp_icon('repeat', 'lp-i-xs').'Codziennie', '<span class="lp-tone-amber">'.lp_icon('sunrise', 'lp-i-xs').'</span>Rano']])?>
                    <?=lp_item('Podlać kwiaty', ['routine' => true, 'streak' => 22, 'week' => ['done', 'off', 'done', 'off', 'done', 'off', 'missed'], 'attrs' => ' data-streak-item', 'meta' => [lp_icon('repeat', 'lp-i-xs').'Pn, Śr, Pt', '<span class="lp-tone-indigo">'.lp_icon('moon', 'lp-i-xs').'</span>Wieczór']])?>
                    <?=lp_item('Trening', ['routine' => true, 'streak' => 6, 'week' => ['off', 'done', 'off', 'done', 'off', 'off', 'missed'], 'meta' => [lp_icon('repeat', 'lp-i-xs').'Wt, Cz', '<span class="lp-tone-orange">'.lp_icon('sun', 'lp-i-xs').'</span>Popołudnie']])?>
                    <?=lp_item('Wynieść śmieci', ['routine' => true, 'streak' => 9, 'week' => ['off', 'off', 'off', 'done', 'off', 'off', 'missed'], 'meta' => [lp_icon('repeat', 'lp-i-xs').'Co czwartek', lp_icon('users', 'lp-i-xs').'wspólne']])?>
                </ul>
            </div>
        </div>

        <div class="lp-notify">
            <div class="lp-lock" data-reveal="up" data-notify>
                <div class="lp-lock-time"><small><?=hl_long_date(date('Y-m-d'))?></small><b>18:00</b></div>
                <div class="lp-notes">
                    <?php
                    $notes = [['Podlać kwiaty', 'Wieczorna rutyna'], ['Zapłacić za prąd', 'Deadline 20:00'], ['Pranie ciemnych', 'Zaplanowane na wieczór']];
                    foreach ($notes as $i => $n): ?>
                    <div class="lp-note lp-glass-card" style="--i:<?=$i?>">
                        <span class="lp-push-ico"><img src="img/icon.svg" alt=""></span>
                        <div><p><b>HomeList</b><small>teraz</small></p><span><?=$n[0]?> · <?=$n[1]?></span></div>
                    </div>
                    <?php endforeach; ?>
                    <div class="lp-note lp-note-merged lp-glass-card">
                        <span class="lp-push-ico"><img src="img/icon.svg" alt=""></span>
                        <div><p><b>Wieczór · 3 rzeczy</b><small>teraz</small></p><span>Podlać kwiaty, Zapłacić za prąd (do 20:00), Pranie ciemnych</span></div>
                    </div>
                </div>
            </div>
            <div class="lp-notify-copy">
                <p class="lp-eyebrow lp-tone-indigo"><?=lp_icon('bell')?><span>Przypomnienia</span></p>
                <h3 class="lp-h3">Jedno powiadomienie<br>zamiast trzech.</h3>
                <p class="lp-p">Na początku każdej pory dostajesz plan, a gdy o czymś zapomnisz, krótkie przypomnienie. Kilka zadań naraz łączy się w jedną wiadomość. Godziny pór ustawiasz sam, a hałaśliwą rutynę możesz wyciszyć.</p>
            </div>
        </div>
    </div>
</section>
