<?php

namespace Kanboard\Plugin\KanAI\Tests\Model;

use Kanboard\Plugin\KanAI\Model\ContextBuilderModel;
use PHPUnit\Framework\TestCase;

final class ContextBuilderFormatTest extends TestCase
{
    public function testEstimateTokens(): void
    {
        $this->assertSame(0, ContextBuilderModel::estimateTokens(''));
        $this->assertSame(1, ContextBuilderModel::estimateTokens('abc'));   // 3/4 -> 1
        $this->assertSame(2, ContextBuilderModel::estimateTokens('abcdefgh')); // 8/4 -> 2
    }

    public function testTruncateKeepsItemsUntilBudgetThenCountsDropped(): void
    {
        $items = [str_repeat('a', 40), str_repeat('b', 40), str_repeat('c', 40)]; // ~10 tokens each
        $out = ContextBuilderModel::truncateToBudget($items, 15); // room for one
        $this->assertCount(1, $out['items']);
        $this->assertSame(2, $out['dropped']);
    }

    public function testFormatContextDelimitsDataAsNonInstruction(): void
    {
        $text = ContextBuilderModel::formatContext(
            ['name' => 'Proj X'],
            ['Task 1: do thing', 'Comment: nice']
        );
        $this->assertStringContainsString('Proj X', $text);
        $this->assertStringContainsString('do thing', $text);
        // Must explicitly frame the block as data, not instructions:
        $this->assertStringContainsString('not instructions', strtolower($text));
    }
}
