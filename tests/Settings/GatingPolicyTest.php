<?php

namespace Kanboard\Plugin\KanAI\Tests\Settings;

use Kanboard\Plugin\KanAI\Settings\GatingPolicy;
use PHPUnit\Framework\TestCase;

final class GatingPolicyTest extends TestCase
{
    public function testIsExternalProvider(): void
    {
        $this->assertFalse(GatingPolicy::isExternalProvider('local'));
        $this->assertTrue(GatingPolicy::isExternalProvider('openai'));
        $this->assertTrue(GatingPolicy::isExternalProvider('anthropic'));
    }

    public function testCanUseExternalRequiresBothFlags(): void
    {
        $this->assertTrue(GatingPolicy::canUseExternal(true, true));
        $this->assertFalse(GatingPolicy::canUseExternal(true, false));
        $this->assertFalse(GatingPolicy::canUseExternal(false, true));
        $this->assertFalse(GatingPolicy::canUseExternal(false, false));
    }

    public function testResolveLocalAlwaysAllowedWhenProjectEnabled(): void
    {
        $this->assertSame('local', GatingPolicy::resolveProvider(true, 'local', false, false));
    }

    public function testResolveThrowsWhenProjectDisabled(): void
    {
        $this->expectException(\RuntimeException::class);
        GatingPolicy::resolveProvider(false, 'local', true, true);
    }

    public function testResolveExternalAllowedWhenBothFlagsSet(): void
    {
        $this->assertSame('anthropic', GatingPolicy::resolveProvider(true, 'anthropic', true, true));
    }

    public function testResolveExternalRefusedWhenKillSwitchOff(): void
    {
        $this->expectException(\RuntimeException::class);
        GatingPolicy::resolveProvider(true, 'openai', false, true);
    }

    public function testResolveExternalRefusedWhenProjectNotOptedIn(): void
    {
        $this->expectException(\RuntimeException::class);
        GatingPolicy::resolveProvider(true, 'openai', true, false);
    }
}
