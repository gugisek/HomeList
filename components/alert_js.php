<?php // toasty (alerty) – showAlert(type, message), wygląd w css/toast.css ?>
<link href="css/toast.css?v=<?=filemtime(__DIR__ . '/../css/toast.css')?>" rel="stylesheet">
<div id="alertsContainer" class="hl-toasts" aria-live="polite"></div>
<script src="js/toast.js?v=<?=filemtime(__DIR__ . '/../js/toast.js')?>"></script>
