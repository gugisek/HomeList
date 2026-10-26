<section class="lp-sec lp-story" id="rano" data-sky="amber">
    <div class="lp-wrap lp-story-grid">
        <div class="lp-story-steps">
            <div class="lp-step" data-step="1"><div class="lp-step-card">
                <p class="lp-eyebrow lp-tone-amber"><?=lp_icon('sunrise')?><span>Rano</span><em>pory dnia</em></p>
                <h2 class="lp-h2">Pory dnia<br>zamiast godzin.</h2>
                <p class="lp-p">Rano, popołudnie, wieczór i „kiedykolwiek”. Zadanie trafia tam, gdzie pasuje, bez wpisywania 14:37 do kalendarza. Godzinę dodasz, jeśli naprawdę jest potrzebna.</p>
                <p class="lp-p lp-p-dim">Zadania z dowolnej listy wrzucasz do planu jednym kliknięciem: „Zaplanuj z list”.</p>
            </div></div>
            <div class="lp-step" data-step="2"><div class="lp-step-card">
                <h3 class="lp-h3">Przeciągasz, gdzie chcesz.</h3>
                <p class="lp-p">Złap kartę i upuść w innej porze. Na telefonie przytrzymaj chwilę i przesuń. Plan zostaje ułożony tak, jak go zostawisz.</p>
                <p class="lp-p lp-p-dim">Następny dzień? Przesuń palcem w bok.</p>
            </div></div>
            <div class="lp-step" data-step="3"><div class="lp-step-card">
                <h3 class="lp-h3">Zaległe same się upomną.</h3>
                <p class="lp-p">To, co nie wyszło wczoraj, czeka na górze dnia w „Zaległych”. Jeden klik w „Na dziś” i wraca do planu. Nic nie przepada po cichu.</p>
                <p class="lp-p lp-p-dim lp-try">Kliknij „Na dziś” w telefonie.</p>
            </div></div>
        </div>

        <div class="lp-story-stick">
            <div class="lp-phone" data-reveal="right">
                <div class="lp-screen lp-screen-phone" data-feed data-story-phone data-step-now="1">
                    <div class="lp-feed-head">
                        <div><b>Dziś</b><small><?=hl_long_date(date('Y-m-d'))?></small></div>
                        <div class="lp-feed-head-end"><?=lp_ring(1, 5, ' data-ring')?></div>
                    </div>
                    <?=lp_day_strip(-2, 6)?>

                    <div class="lp-overdue" data-overdue>
                        <div class="lp-part-head lp-tone-amber"><?=lp_icon('warning')?><h4>Zaległe</h4></div>
                        <ul class="lp-list" data-overdue-list>
                            <?=lp_item('Zadzwonić do hydraulika', ['tone' => 'overdue', 'key' => 'plumber', 'meta' => ['Dom', '<span class="lp-tone-amber">'.lp_icon('warning', 'lp-i-xs').'Wczoraj</span>'], 'extra' => '<button type="button" class="lp-chip-today" data-to-today>Na dziś</button>'])?>
                        </ul>
                    </div>

                    <div class="lp-part" data-part="1"><?=lp_part_head(1)?><ul class="lp-list" data-list="1">
                        <?=lp_item('Śniadanie dla dzieci', ['done' => true, 'routine' => true, 'streak' => 8, 'meta' => [lp_icon('repeat', 'lp-i-xs').'Rutyny']])?>
                    </ul></div>
                    <div class="lp-part" data-part="2"><?=lp_part_head(2)?><ul class="lp-list" data-list="2">
                        <?=lp_item('Wizyta u weterynarza', ['meta' => ['Burek', lp_icon('clock', 'lp-i-xs').'16:00']])?>
                    </ul></div>
                    <div class="lp-part" data-part="3"><?=lp_part_head(3)?><ul class="lp-list" data-list="3">
                        <?=lp_item('Pranie ciemnych', ['meta' => ['Dom']])?>
                    </ul></div>
                    <div class="lp-part" data-part="0"><?=lp_part_head(0)?><ul class="lp-list" data-list="0">
                        <?=lp_item('Odebrać paczkę', ['key' => 'parcel', 'meta' => ['Dom', '<span class="lp-tone-sky">'.lp_icon('link', 'lp-i-xs', '1.8').'</span>']])?>
                    </ul></div>
                    <?=lp_app_dock('Dziś')?>
                </div>
            </div>
        </div>
    </div>
</section>
