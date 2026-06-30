<?php

namespace Kanboard\Plugin\KanAI\Tests\LLM;

use Kanboard\Plugin\KanAI\LLM\ProposalValidator;
use PHPUnit\Framework\TestCase;

final class ProposalValidatorTest extends TestCase
{
    public function testParseCleanJson(): void
    {
        $raw = '{"answer":"Done.","proposals":[{"action":"close_task","task_id":5,"reason":"stale"}]}';
        $out = ProposalValidator::parse($raw);
        $this->assertSame('Done.', $out['answer']);
        $this->assertCount(1, $out['proposals']);
        $this->assertSame('close_task', $out['proposals'][0]['action']);
    }

    public function testParseExtractsJsonFromFencedProse(): void
    {
        $raw = "Sure!\n```json\n{\"answer\":\"hi\",\"proposals\":[]}\n```\nHope that helps.";
        $out = ProposalValidator::parse($raw);
        $this->assertSame('hi', $out['answer']);
        $this->assertSame([], $out['proposals']);
    }

    public function testParseThrowsWhenNoJson(): void
    {
        $this->expectException(\RuntimeException::class);
        ProposalValidator::parse('I cannot help with that.');
    }

    public function testValidateDropsUnknownActionsAndMissingTaskId(): void
    {
        $proposals = [
            ['action' => 'close_task', 'task_id' => 1],
            ['action' => 'delete_everything', 'task_id' => 2],   // not whitelisted
            ['action' => 'move_task'],                            // missing task_id
            ['action' => 'create_task', 'params' => ['title' => 'New']], // create needs no task_id
        ];
        $clean = ProposalValidator::validateProposals($proposals);
        $this->assertCount(2, $clean);
        $this->assertSame('close_task', $clean[0]['action']);
        $this->assertSame('create_task', $clean[1]['action']);
    }

    public function testValidateKeepsNewStructuringActions(): void
    {
        $proposals = [
            ['action' => 'update_task', 'task_id' => 3, 'params' => ['description' => 'clearer']],
            ['action' => 'add_subtask', 'task_id' => 3, 'params' => ['title' => 'step 1']],
            ['action' => 'link_tasks', 'task_id' => 3, 'params' => ['opposite_task_id' => 4]],
            ['action' => 'update_task'], // missing task_id -> dropped
        ];
        $clean = ProposalValidator::validateProposals($proposals);
        $this->assertCount(3, $clean);
        $this->assertSame('update_task', $clean[0]['action']);
        $this->assertSame('add_subtask', $clean[1]['action']);
        $this->assertSame('link_tasks', $clean[2]['action']);
    }
}
