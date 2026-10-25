<?php
$title = "nowe hasło";
$auth_heading = 'Ustaw nowe hasło';
$auth_form = 'reset_form.php';
// link z maila ma działać także w przeglądarce, w której ktoś jest zalogowany
$auth_allow_logged = true;
$auth_footer = '<a href="login.php" class="font-semibold leading-6 text-green-400 hover:text-green-300 duration-150">Wróć do logowania</a>';
include 'components/login/auth_layout.php';
