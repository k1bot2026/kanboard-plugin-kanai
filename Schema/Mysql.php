<?php

namespace Kanboard\Plugin\KanAI\Schema;

use PDO;

const VERSION = 4;

function version_4(PDO $pdo): void
{
    // Observability: which model answered and how long it took.
    $pdo->exec('ALTER TABLE kanai_messages ADD COLUMN model VARCHAR(100) NOT NULL DEFAULT \'\'');
    $pdo->exec('ALTER TABLE kanai_messages ADD COLUMN duration_ms INT NOT NULL DEFAULT 0');
}

function version_3(PDO $pdo): void
{
    // Purge scans by updated_at across projects; give it a dedicated index.
    // (MySQL has no CREATE INDEX IF NOT EXISTS on older versions; migrations run once.)
    $pdo->exec('CREATE INDEX idx_kanai_conv_updated ON kanai_conversations(updated_at)');
}

function version_2(PDO $pdo): void
{
    // Shared, multi-conversation model. Pre-1.0 (per-user) data is discarded.
    $pdo->exec('DROP TABLE IF EXISTS kanai_proposals');
    $pdo->exec('DROP TABLE IF EXISTS kanai_messages');

    $pdo->exec('CREATE TABLE kanai_conversations (
        id INT NOT NULL AUTO_INCREMENT,
        project_id INT NOT NULL,
        title VARCHAR(191) NOT NULL DEFAULT \'\',
        created_by INT DEFAULT NULL,
        created_at INT NOT NULL DEFAULT 0,
        updated_at INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        INDEX idx_kanai_conv_project (project_id, updated_at),
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE kanai_messages (
        id INT NOT NULL AUTO_INCREMENT,
        conversation_id INT NOT NULL,
        project_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        role VARCHAR(20) NOT NULL,
        content TEXT NOT NULL,
        created_at INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        INDEX idx_kanai_messages_conv (conversation_id, id),
        FOREIGN KEY (conversation_id) REFERENCES kanai_conversations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE kanai_proposals (
        id INT NOT NULL AUTO_INCREMENT,
        conversation_id INT NOT NULL,
        project_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        message_id INT DEFAULT NULL,
        payload MEDIUMTEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT \'pending\',
        created_at INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        INDEX idx_kanai_prop_conv (conversation_id, status),
        FOREIGN KEY (conversation_id) REFERENCES kanai_conversations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB CHARSET=utf8mb4');
}

function version_1(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS kanai_messages (
        id INT NOT NULL AUTO_INCREMENT,
        project_id INT NOT NULL,
        user_id INT NOT NULL,
        role VARCHAR(20) NOT NULL,
        content TEXT NOT NULL,
        created_at INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        INDEX idx_kanai_messages_project_user (project_id, user_id, id),
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS kanai_proposals (
        id INT NOT NULL AUTO_INCREMENT,
        project_id INT NOT NULL,
        user_id INT NOT NULL,
        message_id INT DEFAULT NULL,
        payload MEDIUMTEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT \'pending\',
        created_at INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        INDEX idx_kanai_proposals_project_status (project_id, status),
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB CHARSET=utf8mb4');
}
