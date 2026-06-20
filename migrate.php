<?php
require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';

$db = Database::getInstance()->getConnection();

$statements = [
    "CREATE TABLE IF NOT EXISTS users (
        id                SERIAL PRIMARY KEY,
        email             VARCHAR(255) NOT NULL UNIQUE,
        name              VARCHAR(255),
        password          VARCHAR(255),
        phone             VARCHAR(20),
        subscription_tier VARCHAR(10) NOT NULL DEFAULT 'free' CHECK (subscription_tier IN ('free', 'pro', 'agency')),
        created_at        TIMESTAMP NOT NULL DEFAULT NOW(),
        last_seen         TIMESTAMP
    )",
    "CREATE INDEX IF NOT EXISTS idx_email ON users(email)",
    "CREATE TABLE IF NOT EXISTS analyses (
        id           SERIAL PRIMARY KEY,
        user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        channel_name VARCHAR(255) NOT NULL,
        data         JSONB,
        email        VARCHAR(50),
        created_at   TIMESTAMP NOT NULL DEFAULT NOW()
    )",
    "CREATE INDEX IF NOT EXISTS idx_user_id    ON analyses(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_created_at ON analyses(created_at)",
    "ALTER TABLE analyses ALTER COLUMN user_id DROP NOT NULL",
    "ALTER TABLE analyses ADD COLUMN IF NOT EXISTS analyzed SMALLINT NOT NULL DEFAULT 0",
    "CREATE INDEX IF NOT EXISTS idx_analyzed ON analyses(analyzed)",
];

$results = [];
foreach ($statements as $sql) {
    try {
        $db->exec($sql);
        $results[] = ['ok', trim(substr($sql, 0, 60)) . '...'];
    } catch (PDOException $e) {
        $results[] = ['error', $e->getMessage()];
    }
}

header('Content-Type: text/plain');
foreach ($results as [$status, $msg]) {
    echo "[$status] $msg\n";
}
