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

    public function testNormalizePolicyRejectsUnknown(): void
    {
        $this->assertSame('disabled', GatingPolicy::normalizePolicy('nonsense'));
        $this->assertSame('all_projects', GatingPolicy::normalizePolicy('all_projects'));
        $this->assertSame('project_optin', GatingPolicy::normalizePolicy('project_optin'));
    }

    public function testExternalEnabledPerPolicy(): void
    {
        $this->assertFalse(GatingPolicy::externalEnabled('disabled'));
        $this->assertTrue(GatingPolicy::externalEnabled('project_optin'));
        $this->assertTrue(GatingPolicy::externalEnabled('all_projects'));
    }

    public function testProjectMayOptInOnlyForOptinPolicy(): void
    {
        $this->assertTrue(GatingPolicy::projectMayOptIn('project_optin'));
        $this->assertFalse(GatingPolicy::projectMayOptIn('disabled'));
        $this->assertFalse(GatingPolicy::projectMayOptIn('all_projects'));
    }

    public function testEffectiveOptInAppliesAdminPolicy(): void
    {
        // disabled: never, regardless of the project flag
        $this->assertFalse(GatingPolicy::effectiveOptIn('disabled', true));
        // all_projects: always, even if the project flag is off
        $this->assertTrue(GatingPolicy::effectiveOptIn('all_projects', false));
        // project_optin: follows the project flag
        $this->assertTrue(GatingPolicy::effectiveOptIn('project_optin', true));
        $this->assertFalse(GatingPolicy::effectiveOptIn('project_optin', false));
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

    public function testNormalizeEnablePolicyDefaultsToPerProject(): void
    {
        $this->assertSame('per_project', GatingPolicy::normalizeEnablePolicy('nonsense'));
        $this->assertSame('per_project', GatingPolicy::normalizeEnablePolicy(''));
        $this->assertSame('per_project', GatingPolicy::normalizeEnablePolicy('per_project'));
        $this->assertSame('all_projects', GatingPolicy::normalizeEnablePolicy('all_projects'));
    }

    public function testProjectEnabledAppliesEnablePolicy(): void
    {
        // per_project: defers to the project flag
        $this->assertTrue(GatingPolicy::projectEnabled('per_project', true));
        $this->assertFalse(GatingPolicy::projectEnabled('per_project', false));
        // all_projects: on everywhere, even when the project flag is off
        $this->assertTrue(GatingPolicy::projectEnabled('all_projects', false));
        $this->assertTrue(GatingPolicy::projectEnabled('all_projects', true));
    }
}
