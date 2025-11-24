<?php

return [
    'id' => '20241124003_add_course_type',
    'description' => 'Ajout du type course pour les entrées',
    'statements' => [
        'sqlite' => [
            'PRAGMA foreign_keys=off;',
            'CREATE TABLE entries_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                date TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ("work","break","course")),
                description TEXT DEFAULT "",
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(user_id, date, start_time, end_time, type) ON CONFLICT IGNORE
            )',
            'INSERT INTO entries_new (id, user_id, date, start_time, end_time, type, description, created_at, updated_at)
             SELECT id, user_id, date, start_time, end_time, type, description, created_at, updated_at FROM entries',
            'DROP TABLE entries',
            'ALTER TABLE entries_new RENAME TO entries',
            'CREATE INDEX IF NOT EXISTS idx_entries_date ON entries(date)',
            'CREATE INDEX IF NOT EXISTS idx_entries_type ON entries(type)',
            'CREATE INDEX IF NOT EXISTS idx_entries_datetime ON entries(date, start_time)',
            'CREATE INDEX IF NOT EXISTS idx_entries_user_id ON entries(user_id)',
            'PRAGMA foreign_keys=on;',
        ],
        'mysql' => [
            'ALTER TABLE entries MODIFY type ENUM("work","break","course") NOT NULL DEFAULT "work";',
        ],
    ],
];

