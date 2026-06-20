CREATE TABLE users (
    id                SERIAL PRIMARY KEY,
    email             VARCHAR(255) NOT NULL UNIQUE,
    name              VARCHAR(255),
    password          VARCHAR(255),
    phone             VARCHAR(20),
    subscription_tier VARCHAR(10) NOT NULL DEFAULT 'free' CHECK (subscription_tier IN ('free', 'pro', 'agency')),
    created_at        TIMESTAMP NOT NULL DEFAULT NOW(),
    last_seen         TIMESTAMP
);

CREATE INDEX idx_email ON users(email);
