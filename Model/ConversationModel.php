<?php

namespace Kanboard\Plugin\KanAI\Model;

use Kanboard\Core\Base;

/**
 * Persistence for KanAI conversations, messages and proposals.
 *
 * Conversations belong to a PROJECT and are shared across all project members:
 * any member can read and continue any of the project's conversations. Each
 * message records its author (user_id) so the UI can show the right avatar/name.
 */
class ConversationModel extends Base
{
    public const T_CONVERSATIONS = 'kanai_conversations';
    public const T_MESSAGES = 'kanai_messages';
    public const T_PROPOSALS = 'kanai_proposals';

    // ── Conversations ────────────────────────────────────────────────────

    public function createConversation(int $projectId, int $userId, string $title): int
    {
        $now = time();
        $this->db->table(self::T_CONVERSATIONS)->insert([
            'project_id' => $projectId,
            'title' => $title !== '' ? $title : t('New conversation'),
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->db->getLastId();
    }

    /** All conversations in a project, most recently active first (shared). */
    public function listConversations(int $projectId): array
    {
        return $this->db->table(self::T_CONVERSATIONS)
            ->eq('project_id', $projectId)
            ->desc('updated_at')
            ->desc('id')
            ->findAll();
    }

    public function getConversation(int $id): ?array
    {
        $row = $this->db->table(self::T_CONVERSATIONS)->eq('id', $id)->findOne();
        return $row ?: null;
    }

    public function getLatestConversationId(int $projectId): int
    {
        $id = $this->db->table(self::T_CONVERSATIONS)
            ->eq('project_id', $projectId)
            ->desc('updated_at')
            ->desc('id')
            ->findOneColumn('id');
        return (int) $id;
    }

    public function renameConversation(int $id, string $title): void
    {
        $this->db->table(self::T_CONVERSATIONS)->eq('id', $id)->update(['title' => $title]);
    }

    public function touchConversation(int $id): void
    {
        $this->db->table(self::T_CONVERSATIONS)->eq('id', $id)->update(['updated_at' => time()]);
    }

    public function deleteConversation(int $id): void
    {
        // Explicit child deletes (SQLite FK cascade is not always enabled).
        $this->db->table(self::T_MESSAGES)->eq('conversation_id', $id)->remove();
        $this->db->table(self::T_PROPOSALS)->eq('conversation_id', $id)->remove();
        $this->db->table(self::T_CONVERSATIONS)->eq('id', $id)->remove();
    }

    // ── Messages ─────────────────────────────────────────────────────────

    public function addMessage(int $conversationId, int $projectId, int $userId, string $role, string $content): int
    {
        $this->db->table(self::T_MESSAGES)->insert([
            'conversation_id' => $conversationId,
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => $role,
            'content' => $content,
            'created_at' => time(),
        ]);
        $this->touchConversation($conversationId);
        return (int) $this->db->getLastId();
    }

    /**
     * Messages of a conversation in chronological order. Each 'user' message is
     * enriched with an 'author' (the user record) for avatar + name rendering.
     */
    public function getMessages(int $conversationId, int $limit = 100): array
    {
        $rows = $this->db->table(self::T_MESSAGES)
            ->eq('conversation_id', $conversationId)
            ->asc('id')
            ->limit($limit)
            ->findAll();

        foreach ($rows as &$row) {
            $row['author'] = null;
            if ($row['role'] === 'user' && ! empty($row['user_id'])) {
                $user = $this->userModel->getById((int) $row['user_id']);
                if ($user) {
                    $row['author'] = $user;
                }
            }
        }
        unset($row);

        return $rows;
    }

    // ── Proposals ────────────────────────────────────────────────────────

    public function addProposalSet(int $conversationId, int $projectId, int $userId, ?int $messageId, array $proposals): int
    {
        $this->db->table(self::T_PROPOSALS)->insert([
            'conversation_id' => $conversationId,
            'project_id' => $projectId,
            'user_id' => $userId,
            'message_id' => $messageId,
            'payload' => json_encode($proposals),
            'status' => 'pending',
            'created_at' => time(),
        ]);
        return (int) $this->db->getLastId();
    }

    public function getPendingProposals(int $conversationId): array
    {
        $rows = $this->db->table(self::T_PROPOSALS)
            ->eq('conversation_id', $conversationId)
            ->eq('status', 'pending')
            ->asc('id')
            ->findAll();
        foreach ($rows as &$row) {
            $row['actions'] = json_decode($row['payload'], true) ?: [];
        }
        unset($row);
        return $rows;
    }

    public function getProposalSet(int $id): ?array
    {
        $row = $this->db->table(self::T_PROPOSALS)->eq('id', $id)->findOne();
        if (! $row) {
            return null;
        }
        $row['actions'] = json_decode($row['payload'], true) ?: [];
        return $row;
    }

    public function setProposalStatus(int $id, string $status): void
    {
        $this->db->table(self::T_PROPOSALS)->eq('id', $id)->update(['status' => $status]);
    }

    // ── Maintenance ──────────────────────────────────────────────────────

    /** Delete conversations (and their messages/proposals) idle longer than N days. */
    public function purgeOlderThan(int $retentionDays, int $now): int
    {
        if ($retentionDays <= 0) {
            return 0;
        }
        $cutoff = $now - ($retentionDays * 86400);
        $ids = $this->db->table(self::T_CONVERSATIONS)->lt('updated_at', $cutoff)->findAllByColumn('id');
        $count = 0;
        foreach ($ids as $id) {
            $this->deleteConversation((int) $id);
            $count++;
        }
        return $count;
    }

    /** Build a short conversation title from the first user message. */
    public static function titleFrom(string $text): string
    {
        $t = trim(preg_replace('/\s+/', ' ', $text));
        if ($t === '') {
            return '';
        }
        if (mb_strlen($t) > 48) {
            $t = mb_substr($t, 0, 48) . '…';
        }
        return $t;
    }
}
