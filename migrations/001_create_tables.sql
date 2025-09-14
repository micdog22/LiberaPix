-- Tabelas básicas
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id TEXT NOT NULL,
    amount_centavos INTEGER NOT NULL,
    email TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    mp_payment_id TEXT,
    qr_code TEXT,
    qr_code_base64 TEXT,
    created_at TEXT NOT NULL,
    paid_at TEXT,
    download_token TEXT,
    download_expires_at TEXT
);

CREATE TABLE IF NOT EXISTS webhook_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    received_at TEXT NOT NULL,
    raw_payload TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS idempotency_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL
);
