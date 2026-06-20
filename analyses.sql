CREATE TABLE analyses (
    id           SERIAL PRIMARY KEY,
    user_id      INTEGER REFERENCES users(id) ON DELETE CASCADE,
    channel_name VARCHAR(255) NOT NULL,
    data         JSONB,
    email        VARCHAR(50),
    created_at   TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_user_id     ON analyses(user_id);
CREATE INDEX idx_created_at  ON analyses(created_at);
