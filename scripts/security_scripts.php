<?php
// Ochrona endpointów AJAX: ten sam guard co scripts/security.php, ale odmowa zawsze jako JSON
// ({status, code, session_expired, message}).
$SECURITY_JSON = true;
include __DIR__ . '/security.php';
