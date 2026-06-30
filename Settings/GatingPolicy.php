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
