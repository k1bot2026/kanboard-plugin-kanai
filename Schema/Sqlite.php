<?php

namespace Kanboard\Plugin\KanAI\Schema;

use PDO;

const VERSION = 3;

function version_3(PDO $pdo): void
{
    // Purge scans by updated_at across projects; give it a dedicated index.
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_kanai_conv_updated ON kanai_conversations(updated_at)');
}

function version_2(PDO $pdo): void
{
    // Shared, multi-conversation model. Pre-1.0 (per-user) data is discarded.
    $pdo->exec('DROP TABLE IF EXISTS kanai_proposals');
    $pdo->exec('DROP TABLE IF EXISTS kanai_messages');

    $pdo->exec('CREATE TABLE kanai_conversations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        title TEXT NOT NULL DEFAULT \'\',
        created_by INTEGER DEFAULT NULL,
        created_at INTEGER NOT NULL DEFAULT 0,
        updated_at INTEGER NOT NULL DEFAULT 0,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )');
    $pdo->exec('CREATE INDEX idx_kanai_conv_project ON kanai_conversations(project_id, updated_at)');

    $pdo->exec('CREATE TABLE kanai_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        conversation_id INTEGER NOT NULL,
        project_id INTEGER NOT NULL,
        user_id INTEGER DEFAULT NULL,
        role TEXT NOT NULL,
        content TEXT NOT NULL,
        created_at INTEGER NOT NULL DEFAULT 0,
        FOREIGN KEY (conversation_id) REFERENCES kanai_conversations(id) ON DELETE CASCADE
    )');
    $pdo->exec('CREATE INDEX idx_kanai_messages_conv ON kanai_messages(conversation_id, id)');

    $pdo->exec('CREATE TABLE kanai_proposals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        conversation_id INTEGER NOT NULL,
        project_id INTEGER NOT NULL,
        user_id INTEGER DEFAULT NULL,
        message_id INTEGER DEFAULT NULL,
        payload TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT \'pending\',
        created_at INTEGER NOT NULL DEFAULT 0,
        FOREIGN KEY (conversation_id) REFERENCES kanai_conversations(id) ON DELETE CASCADE
    )');
    $pdo->exec('CREATE INDEX idx_kanai_prop_conv ON kanai_proposals(conversation_id, status)');
}

function version_1(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS kanai_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        role TEXT NOT NULL,
        content TEXT NOT NULL,
        created_at INTEGER NOT NULL DEFAULT 0,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_kanai_messages_project_user
        ON kanai_messages(project_id, user_id, id)');

    $pdo->exec('CREATE TABLE IF NOT EXISTS kanai_proposals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        message_id INTEGER DEFAULT NULL,
        payload TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT \'pending\',
        created_at INTEGER NOT NULL DEFAULT 0,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_kanai_proposals_project_status
        ON kanai_proposals(project_id, status)');
}
