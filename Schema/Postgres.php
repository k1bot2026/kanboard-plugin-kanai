<?php

namespace Kanboard\Plugin\KanAI\Schema;

use PDO;

const VERSION = 1;

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
