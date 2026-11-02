<?php
// Stopnie rozmiaru tekstu (users.text_scale, procent) – używane w panel.php (--hl-fs), tab_display.php i text_scale.php
if (!defined('HL_TEXT_SCALES')) {
    define('HL_TEXT_SCALES', [100 => 'Standardowy', 110 => 'Nieco większy', 120 => 'Większy', 135 => 'Duży', 150 => 'Bardzo duży']);

    // wartość dla --hl-fs (np. 1.35); nieznany stopień = standard
    function hl_text_scale_css($scale): string
    {
        $scale = (int)$scale;
        return isset(HL_TEXT_SCALES[$scale]) ? rtrim(rtrim(number_format($scale / 100, 2, '.', ''), '0'), '.') : '1';
    }
}
