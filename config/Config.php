<?php
// require_once __DIR__ . '/../config/Config.php';
// require_once __DIR__ . '/../config/Database.php';

// require_once __DIR__ . '/../models/User.php';
// require_once __DIR__ . '/../models/Analysis.php';
// require_once __DIR__ . '/../services/AnalyzerService.php';
// require_once __DIR__ . '/../services/EmailService.php';


class Config {
    // Database Configuration
    const DB_HOST = 'localhost';
    const DB_NAME = 'tubeanm7u9e5_tubeanalyz';
    const DB_USER = 'tubeanm7u9e5_tubeapp'; //
    const DB_PASS = ',$AETMD69B~}j{{P';//
    
    // API Configuration
    const API_BASE_URL = 'https://tube-analyzer.onrender.com';
    
    // Site Configuration
    const SITE_NAME = 'TubeAnalyzer';
    const SITE_URL = 'http://tubeanalyzer.co.za';
    const SUPPORT_EMAIL = 'support@tubeanalyzer.co.za';
    
    // Rate Limiting
    const FREE_DAILY_LIMIT = 5;
    const PRO_DAILY_LIMIT = 100;
    
    // Pricing
    const PRICE_PRO_MONTHLY = 19;
    const PRICE_PRO_YEARLY = 190;
    const PRICE_AGENCY_MONTHLY = 99;
    const PRICE_AGENCY_YEARLY = 990;
}