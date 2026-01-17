<?php
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

try {
    Database::getInstance()->getConnection();
    echo 'DB CONNECT OK';
} catch (Throwable $e) {
    echo 'DB FAIL';
}

