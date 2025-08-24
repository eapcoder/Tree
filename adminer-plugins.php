<?php
include_once "./adminer-plugins/login-password-less.php";
return array(
    new AdminerLoginPasswordLess(password_hash("1985", PASSWORD_DEFAULT)),
    // You can specify all plugins here or just the ones needing configuration.
);

include "./index.php";
