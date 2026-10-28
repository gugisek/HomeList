<?php
// Jedzenie – licznik kalorii (dashboard/food.php + js/food.js). Makieta dnia z pierścieniem kcal, arkusz porcji
// z podglądem „zanim dodasz” (js: [data-food]), skaner kodów, dania domowe i skład produktu.
$lp_meals = [
    1 => ['Śniadanie', [['Owsianka z bananem', '300 g', 342], ['Kawa z mlekiem', '250 ml', 48]]],
    2 => ['II śniadanie', [['Jogurt naturalny 2%', '150 g', 92], ['Jabłko', '180 g · 1 × sztuka', 94]]],
    3 => ['Obiad', []],
    5 => ['Kolacja', []],
];
// stan dnia przed obiadem i produkty z arkusza (wartości na 100 g / ml)
$lp_food = [
    'base' => ['kcal' => 576, 'protein' => 20, 'fat' => 12, 'carbs' => 91],
    'goal' => ['kcal' => 2100, 'protein' => 110, 'fat' => 70, 'carbs' => 260],
    'products' => [
        ['name' => 'Pierogi ruskie', 'tag' => 'Podstawowy', 'ns' => 'c', 'unit' => ['sztuka', 35], 'u' => 'g', 'grams' => 280, 'max' => 560,
         'chips' => [[140, 'Pół porcji'], [280, 'Porcja'], [420, 'Duża']], 'p100' => ['kcal' => 205, 'protein' => 6.6, 'fat' => 6.2, 'carbs' => 30.5]],
        ['name' => 'Surówka z marchewki', 'tag' => 'Podstawowy', 'ns' => 'a', 'unit' => ['miseczka', 150], 'u' => 'g', 'grams' => 150, 'max' => 400,
         'chips' => [[75, 'Pół miseczki'], [150, 'Miseczka'], [300, 'Dwie']], 'p100' => ['kcal' => 62, 'protein' => .9, 'fat' => 2.6, 'carbs' => 8.4]],
        ['name' => 'Kompot z jabłek', 'tag' => 'Danie domowe', 'ns' => 'c', 'unit' => ['szklanka', 250], 'u' => 'ml', 'grams' => 250, 'max' => 750,
         'chips' => [[250, 'Szklanka'], [500, 'Dwie'], [750, 'Dzbanek']], 'p100' => ['kcal' => 46, 'protein' => .1, 'fat' => 0, 'carbs' => 11.4]],
    ],
    'yesterday' => [['Zupa pomidorowa z ryżem', '350 g', 238, 6, 7, 37], ['Kotlet z indyka', '120 g · 1 × sztuka', 226, 26, 11, 6]],
];
$lp_macros = [['protein', 'Białko', 'p'], ['fat', 'Tłuszcz', 'f'], ['carbs', 'Węglowodany', 'c']];
$lp_p0 = $lp_food['products'][0];
$lp_n = fn($v) => number_format($v, 0, ',', ' ');

$lp_dish = [['Udka z kurczaka', '500 g', 900], ['Marchew', '300 g', 99], ['Pietruszka', '150 g', 74], ['Seler', '150 g', 63], ['Makaron nitki', '250 g', 900]];
$lp_quality = [
    'oats' => ['Płatki owsiane', 'a', [['fat', 'Tłuszcz', 'low', '7'], ['sugars', 'Cukry', 'low', '1'], ['salt', 'Sól', 'low', '0']], ['gluten']],
    'granola' => ['Granola czekoladowa', 'd', [['fat', 'Tłuszcz', 'moderate', '15'], ['sugars', 'Cukry', 'high', '24'], ['salt', 'Sól', 'low', '0,2']], ['gluten', 'mleko', 'orzechy']],
];
$lp_lv = ['low' => 'mało', 'moderate' => 'średnio', 'high' => 'dużo'];
?>
<section class="lp-sec" id="jedzenie" data-sky="food">
    <div class="lp-wrap">
        <div class="lp-food-top">
            <div class="lp-sec-head">
                <p class="lp-eyebrow lp-tone-green"><?=lp_icon('fork')?><span>Obiad</span><em>nowość · licznik kalorii</em></p>
                <h2 class="lp-h2">Kalorie policzone,<br><span class="lp-food-grad">zanim zjesz.</span></h2>
                <p class="lp-p">Dziennik posiłków prosto w HomeList. Wybierasz produkt, przesuwasz porcję, a pierścień dnia i makro od razu pokazują, co się zmieni. Dodajesz dopiero wtedy, gdy liczby się zgadzają.</p>
            </div>
            <ul class="lp-food-facts" data-reveal="up">
                <li><b data-food-count="11000">0</b><span>produktów po polsku<br>i z kodem kreskowym</span></li>
                <li><b>5</b><span>posiłków w dniu,<br>włączasz te, które jesz</span></li>
                <li><b>0</b><span>reklam. Dziennik<br>widzisz tylko Ty</span></li>
            </ul>
        </div>

        <div class="lp-food" data-food="<?=htmlspecialchars(json_encode($lp_food, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')?>">
            <div class="lp-screen lp-food-app" data-reveal="left">
                <div class="lp-feed-head">
                    <div><b>Jedzenie</b><small><?=hl_long_date(date('Y-m-d'))?></small></div>
                    <span class="lp-plus"><?=lp_icon('plus', 'lp-i', '2')?></span>
                </div>
                <div class="lp-strip lp-kstrip">
                    <?php
                    $fill = [-3 => 92, -2 => 100, -1 => 78];
                    for ($i = -3; $i <= 8; $i++):
                        $d = hl_add_days(date('Y-m-d'), $i);
                        $cls = 'lp-day'.($i === 0 ? ' is-sel' : '').($i < 0 ? ' is-past' : '').((int)date('N', strtotime($d)) === 7 ? ' is-sun' : '');
                    ?>
                    <span class="<?=$cls?>"><small><?=hl_day_name($d, true)?></small><b><?=(int)date('j', strtotime($d))?></b><em class="lp-kbar<?=$i === -2 ? ' is-over' : ''?>"><i<?=$i === 0 ? ' data-food-daybar' : ''?> style="width:<?=$fill[$i] ?? 0?>%"></i></em></span>
                    <?php endfor; ?>
                </div>

                <div class="lp-food-body">
                    <div class="lp-food-hero">
                        <div class="lp-kring">
                            <svg viewBox="0 0 120 120" aria-hidden="true">
                                <circle cx="60" cy="60" r="52" class="lp-kring-bg" />
                                <circle cx="60" cy="60" r="52" pathLength="100" class="lp-kring-ghost" data-food-ghost stroke-dasharray="0 0 0 100" />
                                <circle cx="60" cy="60" r="52" pathLength="100" class="lp-kring-fg" data-food-ring stroke-dasharray="0 100" />
                            </svg>
                            <div><b data-food-left>2 100</b><small data-food-left-label>kcal zostało</small></div>
                        </div>
                        <div class="lp-kbars">
                            <?php foreach ($lp_macros as [$k, $label, $c]): ?>
                            <div data-food-bar="<?=$k?>">
                                <p><span><?=$label?></span><em data-food-bar-txt></em></p>
                                <div class="lp-kbar-track"><i class="lp-m-<?=$c?>" data-food-bar-fill></i><i class="lp-m-<?=$c?> is-ghost" data-food-bar-ghost></i></div>
                            </div>
                            <?php endforeach; ?>
                            <p class="lp-kbars-sum">Zjedzone <b data-food-eaten>0</b> z <?=$lp_n($lp_food['goal']['kcal'])?> kcal</p>
                        </div>
                        <div class="lp-food-hero-foot"><span>Statystyki <?=lp_icon('right', 'lp-i-xs', '2')?></span><em><?=lp_icon('target', 'lp-i-xs', '2')?>Cel <?=$lp_n($lp_food['goal']['kcal'])?> kcal</em></div>
                    </div>

                    <div class="lp-meals">
                        <?php foreach ($lp_meals as $m => [$name, $entries]): $sum = array_sum(array_column($entries, 2)); ?>
                        <div class="lp-meal<?=$m === 3 ? ' is-now' : ''?>"<?=$m === 3 ? ' data-food-meal' : ''?>>
                            <div class="lp-meal-head">
                                <div><p><b><?=$name?></b><?=$m === 3 ? '<span class="lp-meal-now">Teraz</span>' : ''?></p><small<?=$m === 3 ? ' data-food-meal-sub' : ''?>><?=$entries ? count($entries).' '.(count($entries) === 1 ? 'produkt' : 'produkty') : 'Nic jeszcze nie dodano'?></small></div>
                                <span class="lp-meal-kcal"><b<?=$m === 3 ? ' data-food-meal-kcal' : ''?>><?=$sum?></b> kcal</span>
                                <span class="lp-meal-plus"><?=lp_icon('plus', 'lp-i-sm', '2')?></span>
                            </div>
                            <ul<?=$m === 3 ? ' data-food-entries' : ''?>>
                                <?php foreach ($entries as $e): ?>
                                <li><div><b><?=$e[0]?></b><small><?=$e[1]?></small></div><span><b><?=$e[2]?></b> kcal</span></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($m === 3): ?>
                            <button type="button" class="lp-meal-copy" data-food-copy><?=lp_icon('repeat', 'lp-i-xs', '2')?>Jak wczoraj · 2 produkty · <?=$lp_n(array_sum(array_column($lp_food['yesterday'], 2)))?> kcal</button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lp-food-side">
                <div class="lp-sheet" data-reveal="right" data-food-sheet>
                    <span class="lp-sheet-grab"></span>
                    <div class="lp-sheet-head">
                        <div>
                            <b data-food-name><?=$lp_p0['name']?></b>
                            <p><span class="lp-ftag" data-food-tag><?=$lp_p0['tag']?></span><small data-food-per><?=$lp_p0['p100']['kcal']?> kcal / 100 g</small></p>
                        </div>
                        <div class="lp-nsmini" data-food-ns="<?=$lp_p0['ns']?>"><small>Nutri</small><span><?php foreach (['a', 'b', 'c', 'd', 'e'] as $l): ?><i class="lp-ns-<?=$l?>"><?=$l?></i><?php endforeach; ?></span></div>
                    </div>

                    <div class="lp-portion">
                        <div class="lp-portion-big"><b data-food-kcal>0</b><span>kcal</span></div>
                        <div class="lp-portion-macros">
                            <?php foreach ($lp_macros as [$k, $label, $c]): ?><span><i class="lp-m-<?=$c?>"></i><?=mb_substr($label, 0, 1)?> <b data-food-m="<?=$k?>">0</b> g</span><?php endforeach; ?>
                        </div>
                    </div>

                    <div class="lp-range">
                        <p><b data-food-grams><?=$lp_p0['grams']?> g</b><small data-food-units></small></p>
                        <input type="range" min="10" max="<?=$lp_p0['max']?>" step="5" value="<?=$lp_p0['grams']?>" data-food-range aria-label="Porcja w gramach">
                    </div>
                    <div class="lp-chips" data-food-chips></div>

                    <button type="button" class="lp-sheet-cta" data-food-add><span data-food-cta>Dodaj do obiadu</span><?=lp_icon('check', 'lp-i-sm', '2.5')?></button>
                </div>
                <p class="lp-hint lp-food-hint"><span></span>Przesuń porcję i patrz na pierścień</p>
            </div>
        </div>

        <div class="lp-food-cards">
            <div class="lp-card" data-reveal="up">
                <p class="lp-card-kicker"><?=lp_icon('scan', 'lp-i-sm', '1.8')?>Skaner kodów</p>
                <div class="lp-scan" data-scan>
                    <div class="lp-scan-pack"><i></i><i></i></div>
                    <svg class="lp-scan-code" viewBox="0 0 120 56" aria-hidden="true"><?php
                        $x = 4;
                        foreach ([2, 1, 1, 3, 1, 2, 2, 1, 1, 1, 3, 2, 1, 2, 1, 1, 3, 1, 2, 1, 1, 2, 3, 1, 1, 2, 1, 1, 2, 2, 1, 3, 1, 1, 2, 1] as $k => $w) {
                            if ($k % 2 === 0) echo '<rect x="'.$x.'" y="2" width="'.$w * 1.5.'" height="44" />';
                            $x += $w * 1.5 + 1.2;
                        }
                    ?><text x="60" y="55" text-anchor="middle" data-scan-ean>5 900000 012345</text></svg>
                    <span class="lp-scan-corners"></span>
                    <span class="lp-scan-line"></span>
                    <div class="lp-scan-hit">
                        <span class="lp-ns lp-ns-a" data-scan-ns>a</span>
                        <div><b data-scan-name>Jogurt naturalny 2%</b><small data-scan-kcal>60 kcal / 100 g</small></div>
                        <em><?=lp_icon('plus', 'lp-i-xs', '2.4')?></em>
                    </div>
                </div>
                <p class="lp-card-text">Nakierowujesz aparat na kod i produkt jest w dzienniku. Baza Open Food Facts plus podstawowe produkty, a czego nie ma, dodajesz sam.</p>
            </div>

            <div class="lp-card" data-reveal="up" data-dish>
                <p class="lp-card-kicker"><?=lp_icon('pot', 'lp-i-sm', '1.8')?>Dania domowe</p>
                <div class="lp-dish-head">
                    <b>Rosół mamy</b>
                    <span class="lp-avas"><?=lp_avatar('M', 'amber').lp_avatar('O', 'rose').lp_avatar('K', 'sky')?></span>
                </div>
                <ul class="lp-dish">
                    <?php foreach ($lp_dish as $i => $d): ?>
                    <li style="--i:<?=$i?>"><span><?=$d[0]?> <small><?=$d[1]?></small></span><em><?=$d[2]?> kcal</em></li>
                    <?php endforeach; ?>
                </ul>
                <div class="lp-dish-sum">
                    <span><?=lp_icon('pot', 'lp-i-xs', '2')?>Garnek na 6 porcji</span>
                    <b><span data-dish-count="<?=round(array_sum(array_column($lp_dish, 2)) / 6)?>">0</span> kcal<small> / porcja</small></b>
                </div>
                <p class="lp-card-text">Wpisujesz przepis raz, a porcja liczy się sama. Danie widzą domownicy, więc rosół mamy dodaje cała rodzina jednym kliknięciem.</p>
            </div>

            <div class="lp-card" data-reveal="up" data-quality>
                <p class="lp-card-kicker"><?=lp_icon('beaker', 'lp-i-sm', '1.8')?>Skład bez lupy</p>
                <div class="lp-seg" role="tablist">
                    <?php foreach ($lp_quality as $k => $q): ?><button type="button" role="tab" data-q="<?=$k?>" aria-selected="<?=$k === 'oats' ? 'true' : 'false'?>"><?=$q[0]?></button><?php endforeach; ?>
                </div>
                <?php foreach ($lp_quality as $k => [$name, $ns, $levels, $allergens]): ?>
                <div class="lp-q" data-q-pane="<?=$k?>"<?=$k !== 'oats' ? ' hidden' : ''?>>
                    <div class="lp-nsbig" data-ns="<?=$ns?>"><?php foreach (['a', 'b', 'c', 'd', 'e'] as $l): ?><i class="lp-ns-<?=$l?>"><?=$l?></i><?php endforeach; ?></div>
                    <ul class="lp-levels">
                        <?php foreach ($levels as [$key, $label, $lv, $g]): ?>
                        <li><i class="is-<?=$lv?>"></i><span><?=$label?></span><em><?=$lp_lv[$lv]?></em><small><?=$g?> g / 100 g</small></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="lp-allergens"><?=lp_icon('warning', 'lp-i-xs', '1.8')?>Alergeny: <?=implode(', ', $allergens)?></p>
                </div>
                <?php endforeach; ?>
                <p class="lp-card-text">Nutri-Score, alergeny oraz tłuszcz, cukry i sól w kolorach. Od razu widać, który produkt lepiej wrzucić do koszyka.</p>
            </div>
        </div>
    </div>
</section>
