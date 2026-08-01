<?php
require_once __DIR__ . '/config/Auth.php';

authLogout();

header('Location: /');
exit;
