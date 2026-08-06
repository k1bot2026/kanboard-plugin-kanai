<?php

namespace Kanboard\Plugin\KanAI\Settings;

use RuntimeException;

/**
 * Pure data-egress / provider-selection policy. Enforced server-side; the UI
 * must never be the only thing standing between project data and an external
 * provider. No Kanboard dependency.
 */
class GatingPolicy
{
    public const LOCAL = 'local';
    public const EXTERNAL = ['openai', 'anthropic'];

    // Application-admin external-AI policy (project managers cannot change it).
    public const POLICY_DISABLED = 'disabled';        // no external AI anywhere
    public const POLICY_PROJECT_OPTIN = 'project_optin'; // project managers may opt in
    public const POLICY_ALL_PROJECTS = 'all_projects';   // forced on for every project
    public const POLICIES = [self::POLICY_DISABLED, self::POLICY_PROJECT_OPTIN, self::POLICY_ALL_PROJECTS];

    // Application-admin enablement policy: where KanAI itself is available.
    // Default keeps the per-project restriction (managers turn it on), which is
    // what larger installs want; ALL_PROJECTS turns it on instance-wide.
    public const ENABLE_PER_PROJECT = 'per_project';
    public const ENABLE_ALL_PROJECTS = 'all_projects';
    public const ENABLE_POLICIES = [self::ENABLE_PER_PROJECT, self::ENABLE_ALL_PROJECTS];

    public static function normalizePolicy(string $policy): string
    {
        return in_array($policy, self::POLICIES, true) ? $policy : self::POLICY_DISABLED;
    }

    public static function normalizeEnablePolicy(string $policy): string
    {
        return in_array($policy, self::ENABLE_POLICIES, true) ? $policy : self::ENABLE_PER_PROJECT;
    }

    /**
     * Whether KanAI is enabled for a project, given the admin enablement policy
     * and the project's own opt-in flag. ALL_PROJECTS overrides the per-project
     * flag; PER_PROJECT defers to it.
     */
    public static function projectEnabled(string $enablePolicy, bool $projectFlag): bool
    {
        return $enablePolicy === self::ENABLE_ALL_PROJECTS ? true : $projectFlag;
    }

    /** Is external AI available at all under this admin policy? */
    public static function externalEnabled(string $policy): bool
    {
        return $policy !== self::POLICY_DISABLED;
    }

    /** Whether project managers may set the per-project opt-in under this policy. */
    public static function projectMayOptIn(string $policy): bool
    {
        return $policy === self::POLICY_PROJECT_OPTIN;
    }

    /** The effective per-project opt-in after applying the admin policy. */
    public static function effectiveOptIn(string $policy, bool $projectOptIn): bool
    {
        if ($policy === self::POLICY_ALL_PROJECTS) {
            return true;
        }
        if ($policy === self::POLICY_PROJECT_OPTIN) {
            return $projectOptIn;
        }
        return false;
    }

    public static function isExternalProvider(string $provider): bool
    {
        return in_array($provider, self::EXTERNAL, true);
    }

    public static function canUseExternal(bool $globalExternalEnabled, bool $projectOptIn): bool
    {
        return $globalExternalEnabled && $projectOptIn;
    }

    public static function resolveProvider(
        bool $projectEnabled,
        string $requested,
        bool $globalExternalEnabled,
        bool $projectOptIn
    ): string {
        if (! $projectEnabled) {
            throw new RuntimeException('KanAI is disabled for this project.');
        }
        if (self::isExternalProvider($requested)
            && ! self::canUseExternal($globalExternalEnabled, $projectOptIn)) {
            throw new RuntimeException('External AI provider is not permitted (kill switch off or project not opted in).');
        }
        return $requested;
    }
}
