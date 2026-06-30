<?php

namespace Kanboard\Plugin\KanAI\Tests\Model;

use Kanboard\Plugin\KanAI\Model\AssistantSkills;
use PHPUnit\Framework\TestCase;

final class AssistantSkillsTest extends TestCase
{
    public function testAllReturnsSixSkillsWithRequiredKeys(): void
    {
        $skills = AssistantSkills::all();
        $this->assertCount(6, $skills);
        foreach ($skills as $s) {
            $this->assertArrayHasKey('key', $s);
            $this->assertArrayHasKey('label', $s);
            $this->assertArrayHasKey('instruction', $s);
            $this->assertNotSame('', $s['instruction']);
        }
    }

    public function testInstructionForKnownAndUnknownKey(): void
    {
        $this->assertNotNull(AssistantSkills::instructionFor('summary'));
        $this->assertNull(AssistantSkills::instructionFor('nope'));
    }
}
