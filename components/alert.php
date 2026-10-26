<?php
// komunikat z sesji (np. po przekierowaniu) – pokazywany jako toast po załadowaniu strony (showAlert z js/toast.js)
if (isset($_SESSION['alert'])) {
    $alert_type = $_SESSION['alert_type'] ?? 'info';
    echo '<script>document.addEventListener("DOMContentLoaded", () => showAlert('
        . json_encode($alert_type, JSON_HEX_TAG) . ', '
        . json_encode((string)$_SESSION['alert'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '));</script>';
}
unset($_SESSION['alert']);
unset($_SESSION['alert_type']);
?>
