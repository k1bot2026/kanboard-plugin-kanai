<?php

namespace Kanboard\Plugin\KanAI\Tests\Model;

use Kanboard\Plugin\KanAI\Model\AssistantSkills;
use PHPUnit\Framework\TestCase;

final class AssistantSkillsTest extends TestCase
{
    public function testAllReturnsSkillsWithRequiredKeys(): void
    {
        $skills = AssistantSkills::all();
        $this->assertCount(10, $skills);
        $keys = [];
        foreach ($skills as $s) {
            $this->assertArrayHasKey('key', $s);
            $this->assertArrayHasKey('label', $s);
            $this->assertArrayHasKey('instruction', $s);
            $this->assertNotSame('', $s['instruction']);
            $keys[] = $s['key'];
        }
        // keys are unique
        $this->assertSame($keys, array_values(array_unique($keys)));
    }

    public function testInstructionForKnownAndUnknownKey(): void
    {
        $this->assertNotNull(AssistantSkills::instructionFor('summary'));
        $this->assertNotNull(AssistantSkills::instructionFor('standup'));
        $this->assertNull(AssistantSkills::instructionFor('nope'));
    }
}
