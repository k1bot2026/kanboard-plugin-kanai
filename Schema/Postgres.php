<?php

namespace Kanboard\Plugin\KanAI\Schema;

use PDO;

const VERSION = 2;

function version_2(PDO $pdo): void
{
    // Shared, multi-conversation model. Pre-1.0 (per-user) data is discarded.
    $pdo->exec('DROP TABLE IF EXISTS kanai_proposals');
    $pdo->exec('DROP TABLE IF EXISTS kanai_messages');

    $pdo->exec('CREATE TABLE kanai_conversations (
        id SERIAL PRIMARY KEY,
        project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
        title TEXT NOT NULL DEFAULT \'\',
        created_by INTEGER DEFAULT NULL,
        created_at INTEGER NOT NULL DEFAULT 0,
        updated_at INTEGER NOT NULL DEFAULT 0
    )');
    $pdo->exec('CREATE INDEX idx_kanai_conv_project ON kanai_conversations(project_id, updated_at)');

    $pdo->exec('CREATE TABLE kanai_messages (
        id SERIAL PRIMARY KEY,
        conversation_id INTEGER NOT NULL REFERENCES kanai_conversations(id) ON DELETE CASCADE,
        project_id INTEGER NOT NULL,
        user_id INTEGER DEFAULT NULL,
        role VARCHAR(20) NOT NULL,
        content TEXT NOT NULL,
        created_at INTEGER NOT NULL DEFAULT 0
    )');
    $pdo->exec('CREATE INDEX idx_kanai_messages_conv ON kanai_messages(conversation_id, id)');

    $pdo->exec('CREATE TABLE kanai_proposals (
        id SERIAL PRIMARY KEY,
        conversation_id INTEGER NOT NULL REFERENCES kanai_conversations(id) ON DELETE CASCADE,
        project_id INTEGER NOT NULL,
        user_id INTEGER DEFAULT NULL,
        message_id INTEGER DEFAULT NULL,
        payload TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT \'pending\',
        created_at INTEGER NOT NULL DEFAULT 0
    )');
    $pdo->exec('CREATE INDEX idx_kanai_prop_conv ON kanai_proposals(conversation_id, status)');
}

function version_1(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS kanai_messages (
        id SERIAL PRIMARY KEY,
        project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        role VARCHAR(20) NOT NULL,
        content TEXT NOT NULL,
        created_at INTEGER NOT NULL DEFAULT 0
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_kanai_messages_project_user
        ON kanai_messages(project_id, user_id, id)');

    $pdo->exec('CREATE TABLE IF NOT EXISTS kanai_proposals (
        id SERIAL PRIMARY KEY,
        project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        message_id INTEGER DEFAULT NULL,
        payload TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT \'pending\',
        created_at INTEGER NOT NULL DEFAULT 0
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_kanai_proposals_project_status
        ON kanai_proposals(project_id, status)');
}
