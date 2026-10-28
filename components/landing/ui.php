<?php
// Klocki makiet aplikacji na stronie głównej – odwzorowują karty z feedu (feed_parts.php) i listy (todo.php)
include_once __DIR__.'/../../scripts/planner/planner_lib.php';

// ikony spoza planner_lib (heroicons outline)
function lp_icon($name, $class = 'lp-i', $stroke = '1.5') {
    $extra = [
        'more'    => 'M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
        'login'   => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25',
        'phone'   => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'shield'  => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
        'archive' => 'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
        'bell'    => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0',
        'hash'    => 'M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5',
        'arrow'   => 'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3',
        'gear'    => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'folder'  => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
        'cloud'   => 'M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z',
        'home'    => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'bulb'    => 'M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18',
        'lock'    => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
        'code'    => 'M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5',
        'tablet'  => 'M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-15a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15a2.25 2.25 0 0 0 2.25 2.25Z',
        'desktop' => 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25',
        'fork'    => 'M6 3v6a2 2 0 0 0 4 0V3M8 3v18M17 21V3c-2 1.4-3 4-3 7.5V13h3',
        'scan'    => 'M3.75 7.5V5.25a1.5 1.5 0 0 1 1.5-1.5H7.5M16.5 3.75h2.25a1.5 1.5 0 0 1 1.5 1.5V7.5M20.25 16.5v2.25a1.5 1.5 0 0 1-1.5 1.5H16.5M7.5 20.25H5.25a1.5 1.5 0 0 1-1.5-1.5V16.5M8 8.5v7M11 8.5v7M13.5 8.5v7M16 8.5v7',
        'pot'     => 'M4 10h16v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-6ZM2.5 10h19M9 6.5c0-1 .8-1.5.8-2.5M12.5 6.5c0-1 .8-1.5.8-2.5',
        'target'  => 'M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'beaker'  => 'M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5',
        'bug'     =>'M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0 1 12 12.75Zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 0 1-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44.125 2.104.52 4.136 1.153 6.06M12 12.75a2.25 2.25 0 0 0 2.248-2.354M12 12.75a2.25 2.25 0 0 1-2.248-2.354M12 8.25c.995 0 1.971-.08 2.922-.236.403-.066.74-.358.795-.762a3.778 3.778 0 0 0-.399-2.25M12 8.25c-.995 0-1.97-.08-2.922-.236-.402-.066-.74-.358-.795-.762a3.734 3.734 0 0 1 .4-2.253M12 8.25a2.25 2.25 0 0 0-2.248 2.146M12 8.25a2.25 2.25 0 0 1 2.248 2.146',
    ];
    if (isset($extra[$name])) {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="'.$stroke.'" stroke="currentColor" class="'.$class.'"><path stroke-linecap="round" stroke-linejoin="round" d="'.$extra[$name].'" /></svg>';
    }
    return hl_icon($name, $class, $stroke);
}

function lp_dot() {
    return '<svg viewBox="0 0 2 2" class="lp-sep"><circle cx="1" cy="1" r="1" /></svg>';
}

/**
 * Karta zadania z feedu dnia.
 * $o: meta (array html), done, routine, streak, week (array stanów), tone (overdue|deadline), extra (html po prawej), key, attrs
 */
function lp_item($title, array $o = []) {
    $cls = 'lp-item';
    if (!empty($o['done'])) $cls .= ' is-done';
    if (!empty($o['tone'])) $cls .= ' is-'.$o['tone'];
    $check = 'lp-check'.(!empty($o['routine']) ? ' is-routine' : '');
    $meta = implode(lp_dot(), $o['meta'] ?? []);
    $html = '<li class="'.$cls.'"'.(!empty($o['key']) ? ' data-key="'.$o['key'].'"' : '').($o['attrs'] ?? '').'>';
    $html .= '<button type="button" class="'.$check.'" aria-label="Odhacz" data-check><svg viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg></button>';
    $html .= '<div class="lp-item-body"><p class="lp-item-title"><span>'.$title.'</span></p>';
    if ($meta) $html .= '<div class="lp-item-meta">'.$meta.'</div>';
    $html .= '</div><div class="lp-item-end">';
    if (isset($o['streak'])) {
        $html .= '<span class="lp-streak'.($o['streak'] ? '' : ' is-zero').'">'.lp_icon('fire', 'lp-i-sm').'<b>'.(int)$o['streak'].'</b></span>';
    }
    if (!empty($o['week'])) {
        $html .= '<span class="lp-week">';
        foreach ($o['week'] as $i => $w) $html .= '<i class="is-'.$w.($i === count($o['week']) - 1 ? ' is-today' : '').'"></i>';
        $html .= '</span>';
    }
    $html .= $o['extra'] ?? '';
    $html .= '</div></li>';
    return $html;
}

// nagłówek sekcji pory dnia w feedzie
function lp_part_head($part, $count = '') {
    $p = hl_parts()[$part];
    $tone = [1 => 'amber', 2 => 'orange', 3 => 'indigo', 0 => 'gray'][$part];
    return '<div class="lp-part-head"><span class="lp-tone-'.$tone.'">'.lp_icon($p['icon']).'</span><h4>'.$p['label'].'</h4><span class="lp-part-count">'.$count.'</span><span class="lp-part-plus">'.lp_icon('plus', 'lp-i-sm', '2').'</span></div>';
}

// pasek dni jak w today.php; $done – mapa przesunięcie => [zrobione, wszystkie]
function lp_day_strip($from = -3, $to = 10) {
    $today = date('Y-m-d');
    $html = '<div class="lp-strip">';
    for ($i = $from; $i <= $to; $i++) {
        $d = hl_add_days($today, $i);
        $total = [2, 3, 1, 4, 2, 3, 0, 3, 2, 1, 4, 2, 3, 1][($i - $from) % 14];
        $done = $i < 0 ? $total : ($i === 0 ? 3 : 0);
        $cls = 'lp-day'.($i === 0 ? ' is-sel' : '').($i < 0 ? ' is-past' : '').((int)date('N', strtotime($d)) === 7 ? ' is-sun' : '');
        $html .= '<span class="'.$cls.'"><small>'.hl_day_name($d, true).'</small><b>'.(int)date('j', strtotime($d)).'</b><em'.($i === 0 ? ' data-strip-dots' : '').'>';
        $shown = min($total, 4);
        for ($k = 0; $k < $shown; $k++) $html .= '<i'.($k < round($done / max($total, 1) * $shown) ? ' class="is-on"' : '').'></i>';
        $html .= '</em></span>';
    }
    return $html.'</div>';
}

// pierścień postępu dnia
function lp_ring($done, $total, $attr = '') {
    $pct = $total ? round($done / $total * 100) : 0;
    return '<div class="lp-ring"'.$attr.'><svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.9155" class="lp-ring-bg" /><circle cx="18" cy="18" r="15.9155" class="lp-ring-fg" stroke-dasharray="'.$pct.' 100" /></svg><span>'.$done.'/'.$total.'</span></div>';
}

// pływający pasek list z aplikacji (liquid glass)
function lp_app_dock($active = 'Dziś') {
    $html = '<div class="lp-app-dock"><div class="lp-glass lp-app-pill"><span class="'.($active === 'Dziś' ? 'is-on' : '').'">'.lp_icon('sun', 'lp-i-sm', '1.8').'<em>Dziś</em></span><i></i>';
    foreach (['Dom', 'Zakupy', 'Rutyny'] as $l) $html .= '<span class="'.($active === $l ? 'is-on' : '').'">'.$l.'</span>';
    return $html.'</div><div class="lp-glass lp-app-more">'.lp_icon('more', 'lp-i').'</div></div>';
}

// awatar inicjałowy (zamiast zdjęć profilowych)
function lp_avatar($letter, $tone) {
    return '<span class="lp-ava lp-ava-'.$tone.'">'.$letter.'</span>';
}
