<?php

namespace Kanboard\Plugin\KanAI\Model;

use Kanboard\Core\Base;

class ConversationModel extends Base
{
    public const T_MESSAGES = 'kanai_messages';
    public const T_PROPOSALS = 'kanai_proposals';

    public function addMessage(int $projectId, int $userId, string $role, string $content): int
    {
        $this->db->table(self::T_MESSAGES)->insert([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => $role,
            'content' => $content,
            'created_at' => time(),
        ]);
        return (int) $this->db->getLastId();
    }

    public function getMessages(int $projectId, int $userId, int $limit = 20): array
    {
        $rows = $this->db->table(self::T_MESSAGES)
            ->eq('project_id', $projectId)
            ->eq('user_id', $userId)
            ->desc('id')
            ->limit($limit)
            ->findAll();
        return array_reverse($rows);
    }

    public function addProposalSet(int $projectId, int $userId, ?int $messageId, array $proposals): int
    {
        $this->db->table(self::T_PROPOSALS)->insert([
            'project_id' => $projectId,
            'user_id' => $userId,
            'message_id' => $messageId,
            'payload' => json_encode($proposals),
            'status' => 'pending',
            'created_at' => time(),
        ]);
        return (int) $this->db->getLastId();
    }

    public function getPendingProposals(int $projectId): array
    {
        $rows = $this->db->table(self::T_PROPOSALS)
            ->eq('project_id', $projectId)
            ->eq('status', 'pending')
            ->asc('id')
            ->findAll();
        foreach ($rows as &$row) {
            $row['actions'] = json_decode($row['payload'], true) ?: [];
        }
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

    public function clearProject(int $projectId): void
    {
        $this->db->table(self::T_MESSAGES)->eq('project_id', $projectId)->remove();
        $this->db->table(self::T_PROPOSALS)->eq('project_id', $projectId)->remove();
    }

    public function purgeOlderThan(int $retentionDays, int $now): int
    {
        if ($retentionDays <= 0) {
            return 0;
        }
        $cutoff = $now - ($retentionDays * 86400);
        $a = $this->db->table(self::T_MESSAGES)->lt('created_at', $cutoff)->remove();
        $b = $this->db->table(self::T_PROPOSALS)->lt('created_at', $cutoff)->remove();
        return (int) $a + (int) $b;
    }
}
