<?php

namespace Kanboard\Plugin\KanAI\Schema;

use PDO;

const VERSION = 1;

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
