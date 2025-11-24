<?php

return [
    'id' => '20241124001_create_core_tables',
    'description' => 'Création des tables users et entries + admin par défaut',
    'statements' => [
        'sqlite' => [
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT "user" CHECK(role IN ("admin","user")),
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                last_login TEXT
            )',
            'CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)',
            'CREATE TABLE IF NOT EXISTS entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                date TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ("work","break","course")),
                description TEXT DEFAULT "",
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(user_id, date, start_time, end_time, type) ON CONFLICT IGNORE
            )',
            'CREATE INDEX IF NOT EXISTS idx_entries_date ON entries(date)',
            'CREATE INDEX IF NOT EXISTS idx_entries_type ON entries(type)',
            'CREATE INDEX IF NOT EXISTS idx_entries_datetime ON entries(date, start_time)',
            'CREATE INDEX IF NOT EXISTS idx_entries_user_id ON entries(user_id)'
        ],
        'mysql' => [
            'CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(191) NOT NULL UNIQUE,
                email VARCHAR(191) NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM("admin","user") NOT NULL DEFAULT "user",
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                last_login DATETIME NULL,
                INDEX idx_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION,
            'CREATE TABLE IF NOT EXISTS entries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                date DATE NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                type ENUM("work","break","course") NOT NULL DEFAULT "work",
                description TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY unique_entry (user_id, date, start_time, end_time, type),
                INDEX idx_entries_date (date),
                INDEX idx_entries_type (type),
                INDEX idx_entries_datetime (date, start_time),
                INDEX idx_entries_user_id (user_id),
                CONSTRAINT fk_entries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION,
        ],
    ],
    'seed' => static function (\PDO $pdo): void {
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
        $count = (int)($stmt->fetch()['count'] ?? 0);

        if ($count === 0) {
            $password = password_hash('admin', PASSWORD_DEFAULT);
            $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?)');
            $insert->execute([
                'admin',
                'admin@timedesk.local',
                $password,
                'admin',
                1,
                $now,
                $now,
            ]);
        }
    },
];

