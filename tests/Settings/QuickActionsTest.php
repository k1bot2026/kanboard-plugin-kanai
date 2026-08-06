<?php

namespace Kanboard\Plugin\KanAI\Tests\Settings;

use Kanboard\Plugin\KanAI\Settings\QuickActions;
use PHPUnit\Framework\TestCase;

final class QuickActionsTest extends TestCase
{
    private array $defaults = [
        ['key' => 'summary', 'label' => 'Summary', 'instruction' => 'Summarize.'],
    ];

    public function testSanitizeAssignsPositionalKeysAndDropsEmptyRows(): void
    {
        $rows = QuickActions::sanitize([
            ['label' => 'A', 'instruction' => 'do a'],
            ['label' => '', 'instruction' => 'no label'],   // dropped
            ['label' => 'B', 'instruction' => ''],           // dropped
            ['label' => '  C  ', 'instruction' => '  do c '], // trimmed
        ]);
        $this->assertCount(2, $rows);
        $this->assertSame('qa0', $rows[0]['key']);
        $this->assertSame('A', $rows[0]['label']);
        $this->assertSame('qa1', $rows[1]['key']);
        $this->assertSame('C', $rows[1]['label']);
        $this->assertSame('do c', $rows[1]['instruction']);
    }

    public function testKeysAreLanguageAgnostic(): void
    {
        // Non-Latin labels (the whole point of the feature) still get usable,
        // unique keys rather than empty slugs.
        $rows = QuickActions::sanitize([
            ['label' => 'Сводка', 'instruction' => 'Кратко опиши проект.'],
            ['label' => '要約', 'instruction' => '要約してください。'],
        ]);
        $this->assertSame(['qa0', 'qa1'], array_column($rows, 'key'));
    }

    public function testFromFormPairsParallelArrays(): void
    {
        $rows = QuickActions::fromForm(['A', 'B'], ['do a', 'do b']);
        $this->assertSame('A', $rows[0]['label']);
        $this->assertSame('do b', $rows[1]['instruction']);
    }

    public function testEncodeEmptyMeansUseDefaults(): void
    {
        $this->assertSame('', QuickActions::encode([]));
        $this->assertSame('', QuickActions::encode([['label' => '', 'instruction' => '']]));
    }

    public function testEncodeDecodeRoundTrip(): void
    {
        $json = QuickActions::encode([['label' => 'A', 'instruction' => 'do a']]);
        $this->assertNotSame('', $json);
        $decoded = QuickActions::decode($json, $this->defaults);
        $this->assertCount(1, $decoded);
        $this->assertSame('A', $decoded[0]['label']);
        $this->assertSame('qa0', $decoded[0]['key']);
    }

    public function testDecodeFallsBackToDefaults(): void
    {
        $this->assertSame($this->defaults, QuickActions::decode('', $this->defaults));
        $this->assertSame($this->defaults, QuickActions::decode('   ', $this->defaults));
        $this->assertSame($this->defaults, QuickActions::decode('not json', $this->defaults));
        $this->assertSame($this->defaults, QuickActions::decode('[]', $this->defaults));
    }

    public function testInstructionForResolvesByKey(): void
    {
        $skills = QuickActions::sanitize([['label' => 'A', 'instruction' => 'do a']]);
        $this->assertSame('do a', QuickActions::instructionFor($skills, 'qa0'));
        $this->assertNull(QuickActions::instructionFor($skills, 'missing'));
    }
}
