CREATE TABLE IF NOT EXISTS invitations (
    id         CHAR(16)     NOT NULL PRIMARY KEY,
    nom        VARCHAR(255) NOT NULL,
    email      VARCHAR(190) NOT NULL UNIQUE,
    sent_at    DATETIME NULL,
    used_at    DATETIME NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
