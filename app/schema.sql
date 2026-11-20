CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT,
    role TEXT NOT NULL CHECK (role IN ('admin','investor')),
    active INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS requests (
    id INTEGER PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    kind TEXT NOT NULL CHECK (kind IN ('Institutional','Individual','SACCO','Advisory')),
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    organization TEXT NOT NULL,
    country TEXT NOT NULL,
    topic TEXT NOT NULL,
    message TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Received' CHECK (status IN ('Received','In review','Approved','Closed')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS invites (
    token_hash TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL UNIQUE REFERENCES users(id),
    expires INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS assets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mime TEXT NOT NULL,
    name TEXT NOT NULL,
    data BLOB NOT NULL
);
CREATE TABLE IF NOT EXISTS content (
    id INTEGER PRIMARY KEY,
    kind TEXT NOT NULL CHECK (kind IN ('projects','products','portfolio','insights')),
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    category TEXT NOT NULL,
    summary TEXT NOT NULL,
    body TEXT NOT NULL,
    external_url TEXT NOT NULL,
    image_id INTEGER REFERENCES assets(id),
    file_id INTEGER REFERENCES assets(id),
    published INTEGER NOT NULL DEFAULT 0,
    first_published_at TEXT,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(kind,slug)
);
CREATE TABLE IF NOT EXISTS rate_limits (
    bucket TEXT NOT NULL,
    window INTEGER NOT NULL,
    hits INTEGER NOT NULL,
    expires INTEGER NOT NULL,
    PRIMARY KEY(bucket, window)
);
CREATE INDEX IF NOT EXISTS content_public_order ON content(kind, published, first_published_at DESC, id DESC);
CREATE INDEX IF NOT EXISTS requests_by_user ON requests(user_id, id DESC);
