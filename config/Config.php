<?php
// require_once __DIR__ . '/../config/Config.php';
// require_once __DIR__ . '/../config/Database.php';

// require_once __DIR__ . '/../models/User.php';
// require_once __DIR__ . '/../models/Analysis.php';
// require_once __DIR__ . '/../services/AnalyzerService.php';
// require_once __DIR__ . '/../services/EmailService.php';


// Load .env
$_envFile = __DIR__ . '/../.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#')) continue;
        [$_key, $_val] = explode('=', $_line, 2);
        $_val = trim($_val, " \t\n\r\0\x0B'\"");
        if (!defined(trim($_key))) define(trim($_key), $_val);
    }
} else {
    $_dbUrl = getenv('DATABASE_URL');
    if ($_dbUrl) {
        $_p = parse_url($_dbUrl);
        define('DB_HOST', $_p['host']);
        define('DB_PORT', (string) ($_p['port'] ?? 5432));
        define('DB_NAME', ltrim($_p['path'], '/'));
        define('DB_USER', $_p['user']);
        define('DB_PASS', $_p['pass']);
    } else {
        foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $_key) {
            if (!defined($_key)) define($_key, (string) getenv($_key));
        }
    }
}
foreach (['RESEND_API_KEY', 'APP_SECRET', 'ADMIN_PASSWORD'] as $_key) {
    if (!defined($_key)) define($_key, (string) getenv($_key));
}

class Config {
    // Database Configuration
    const DB_HOST = DB_HOST;
    const DB_PORT = DB_PORT;
    const DB_NAME = DB_NAME;
    const DB_USER = DB_USER;
    const DB_PASS = DB_PASS;
    
    // API Configuration
    const API_BASE_URL = 'https://tube-analyzer.onrender.com';

    // Site Configuration
    const SITE_NAME = 'TubeAnalyzer';
    const SITE_URL = 'https://yessherlock.com';
    const SUPPORT_EMAIL = 'support@yessherlock.com';

    // Email campaigns (Resend)
    const RESEND_API_KEY = RESEND_API_KEY;
    const RESEND_FROM    = 'TubeAnalyzer <hello@yessherlock.com>';
    const APP_SECRET     = APP_SECRET; // signs unsubscribe links — must match between wherever mailers run and the web app

    // Admin
    const ADMIN_PASSWORD = ADMIN_PASSWORD; // gates admin.php
    
    // Rate Limiting
    const FREE_DAILY_LIMIT = 5;
    const PRO_DAILY_LIMIT = 100;
    
    // Pricing
    const PRICE_PRO_MONTHLY = 19;
    const PRICE_PRO_YEARLY = 190;
    const PRICE_AGENCY_MONTHLY = 99;
    const PRICE_AGENCY_YEARLY = 990;
}