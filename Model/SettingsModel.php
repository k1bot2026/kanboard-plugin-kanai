<?php

namespace Kanboard\Plugin\KanAI\Model;

use Kanboard\Core\Base;
use Kanboard\Plugin\KanAI\Security\Crypto;

class SettingsModel extends Base
{
    private const DEFAULTS = [
        'kanai_external_enabled' => '0',
        'kanai_default_provider' => 'local',
        'kanai_local_base_url' => 'http://localhost:11434/v1',
        'kanai_local_model' => 'llama3.1',
        'kanai_openai_model' => 'gpt-4o-mini',
        'kanai_anthropic_model' => 'claude-sonnet-4-6',
        'kanai_max_context_tokens' => '8000',
        'kanai_max_output_tokens' => '1024',
        'kanai_request_timeout' => '120',
        'kanai_history_retention_days' => '0',
        'kanai_rate_limit_per_hour' => '30',
        'kanai_async' => '1',
        'kanai_php_binary' => 'php',
    ];

    public function crypto(): Crypto
    {
        // Prefer an admin-supplied secret in config.php; otherwise a per-install
        // key generated once and stored in settings (weaker, but never plaintext).
        if (defined('KANAI_SECRET') && KANAI_SECRET !== '') {
            return new Crypto(KANAI_SECRET);
        }
        $key = $this->configModel->get('kanai_crypto_key', '');
        if ($key === '') {
            $key = bin2hex(random_bytes(32));
            $this->configModel->save(['kanai_crypto_key' => $key]);
        }
        return new Crypto($key);
    }

    public function getGlobal(): array
    {
        $out = [];
        foreach (self::DEFAULTS as $k => $default) {
            $out[$k] = $this->configModel->get($k, $default);
        }
        // The external-AI policy lives outside DEFAULTS (it has a legacy-boolean
        // fallback), so surface it explicitly — otherwise the admin config select
        // has no stored value to bind to and always renders as "Disabled".
        $out['kanai_external_policy'] = $this->getExternalPolicy();
        $out['kanai_enable_policy'] = $this->getEnablePolicy();
        $crypto = $this->crypto();
        $out['kanai_openai_key'] = $crypto->decrypt($this->configModel->get('kanai_openai_key', ''));
        $out['kanai_anthropic_key'] = $crypto->decrypt($this->configModel->get('kanai_anthropic_key', ''));
        $out['kanai_local_api_key'] = $crypto->decrypt($this->configModel->get('kanai_local_api_key', ''));
        return $out;
    }

    public function saveGlobal(array $values): void
    {
        $crypto = $this->crypto();
        $policy = \Kanboard\Plugin\KanAI\Settings\GatingPolicy::normalizePolicy($values['kanai_external_policy'] ?? 'disabled');
        $enablePolicy = \Kanboard\Plugin\KanAI\Settings\GatingPolicy::normalizeEnablePolicy($values['kanai_enable_policy'] ?? 'per_project');
        $save = [
            'kanai_external_policy' => $policy,
            'kanai_enable_policy' => $enablePolicy,
            // Keep the legacy boolean in sync for any external reader.
            'kanai_external_enabled' => $policy === \Kanboard\Plugin\KanAI\Settings\GatingPolicy::POLICY_DISABLED ? '0' : '1',
            'kanai_default_provider' => in_array($values['kanai_default_provider'] ?? 'local', ['local', 'openai', 'anthropic'], true)
                ? $values['kanai_default_provider'] : 'local',
            'kanai_local_base_url' => trim($values['kanai_local_base_url'] ?? self::DEFAULTS['kanai_local_base_url']),
            'kanai_local_model' => trim($values['kanai_local_model'] ?? self::DEFAULTS['kanai_local_model']),
            'kanai_openai_model' => trim($values['kanai_openai_model'] ?? self::DEFAULTS['kanai_openai_model']),
            'kanai_anthropic_model' => trim($values['kanai_anthropic_model'] ?? self::DEFAULTS['kanai_anthropic_model']),
            'kanai_max_context_tokens' => (string) max(500, (int) ($values['kanai_max_context_tokens'] ?? 8000)),
            'kanai_max_output_tokens' => (string) max(128, (int) ($values['kanai_max_output_tokens'] ?? 1024)),
            'kanai_history_retention_days' => (string) max(0, (int) ($values['kanai_history_retention_days'] ?? 0)),
            'kanai_request_timeout' => (string) max(10, (int) ($values['kanai_request_timeout'] ?? 120)),
            'kanai_rate_limit_per_hour' => (string) max(0, (int) ($values['kanai_rate_limit_per_hour'] ?? 30)),
            'kanai_async' => empty($values['kanai_async']) ? '0' : '1',
            'kanai_php_binary' => trim($values['kanai_php_binary'] ?? 'php') !== '' ? trim($values['kanai_php_binary']) : 'php',
        ];
        // The admin form leaves a key field EMPTY to keep the stored key (the
        // saved key is shown only as a masked hint, never pre-filled into the
        // input). So any non-empty submitted value is a genuine new key. This
        // avoids depending on a fragile multibyte mask sentinel.
        foreach (['kanai_openai_key', 'kanai_anthropic_key', 'kanai_local_api_key'] as $field) {
            $new = trim($values[$field] ?? '');
            if ($new !== '') {
                $save[$field] = $crypto->encrypt($new);
            }
        }
        $this->configModel->save($save);

        // Quick actions are saved only when the editor was part of the submitted
        // form (a hidden marker says so), so unrelated saves never wipe them.
        // With the marker present, an empty list clears back to the defaults.
        if (! empty($values['kanai_skills_present'])) {
            $this->saveSkills($values['kanai_skill_label'] ?? [], $values['kanai_skill_instruction'] ?? []);
        }
    }

    /**
     * Application-admin external-AI policy: disabled | project_optin | all_projects.
     * Falls back to the legacy boolean kill switch for pre-1.6 installs.
     */
    public function getExternalPolicy(): string
    {
        $policy = $this->configModel->get('kanai_external_policy', '');
        if ($policy === '') {
            return $this->configModel->get('kanai_external_enabled', '0') === '1'
                ? \Kanboard\Plugin\KanAI\Settings\GatingPolicy::POLICY_PROJECT_OPTIN
                : \Kanboard\Plugin\KanAI\Settings\GatingPolicy::POLICY_DISABLED;
        }
        return \Kanboard\Plugin\KanAI\Settings\GatingPolicy::normalizePolicy($policy);
    }

    public function isExternalEnabled(): bool
    {
        return \Kanboard\Plugin\KanAI\Settings\GatingPolicy::externalEnabled($this->getExternalPolicy());
    }

    /**
     * Application-admin enablement policy: per_project (default) | all_projects.
     * Controls whether KanAI must be turned on per project or is available
     * instance-wide.
     */
    public function getEnablePolicy(): string
    {
        return \Kanboard\Plugin\KanAI\Settings\GatingPolicy::normalizeEnablePolicy(
            $this->configModel->get('kanai_enable_policy', \Kanboard\Plugin\KanAI\Settings\GatingPolicy::ENABLE_PER_PROJECT)
        );
    }

    /**
     * The effective quick actions: the admin-customized set, or the built-in
     * defaults when none has been saved.
     * @return array<int,array{key:string,label:string,instruction:string}>
     */
    public function getSkills(): array
    {
        return \Kanboard\Plugin\KanAI\Settings\QuickActions::decode(
            $this->configModel->get('kanai_skills', ''),
            \Kanboard\Plugin\KanAI\Model\AssistantSkills::defaults()
        );
    }

    /** Resolve a clicked quick action's key to its instruction (null if unknown). */
    public function skillInstruction(string $key): ?string
    {
        return \Kanboard\Plugin\KanAI\Settings\QuickActions::instructionFor($this->getSkills(), $key);
    }

    /**
     * Save the admin-customized quick actions from the parallel label/instruction
     * arrays the config form submits. An empty set clears the customization,
     * restoring the built-in defaults.
     */
    public function saveSkills(array $labels, array $instructions): void
    {
        $rows = \Kanboard\Plugin\KanAI\Settings\QuickActions::fromForm($labels, $instructions);
        $this->configModel->save(['kanai_skills' => \Kanboard\Plugin\KanAI\Settings\QuickActions::encode($rows)]);
    }

    public function getProjectEnabled(int $projectId): bool
    {
        // Under the all-projects policy KanAI is on everywhere; otherwise it
        // defers to the project manager's per-project opt-in.
        return \Kanboard\Plugin\KanAI\Settings\GatingPolicy::projectEnabled(
            $this->getEnablePolicy(),
            $this->projectMetadataModel->get($projectId, 'kanai_enabled', '0') === '1'
        );
    }

    /** The project's own stored opt-in flag, ignoring the admin enablement policy. */
    public function getProjectEnabledFlag(int $projectId): bool
    {
        return $this->projectMetadataModel->get($projectId, 'kanai_enabled', '0') === '1';
    }

    public function getProjectExternalOptIn(int $projectId): bool
    {
        return $this->projectMetadataModel->get($projectId, 'kanai_external_opt_in', '0') === '1';
    }

    public function getProjectAutoDigest(int $projectId): bool
    {
        return $this->projectMetadataModel->get($projectId, 'kanai_auto_digest', '0') === '1';
    }

    public function saveProject(int $projectId, bool $enabled, bool $externalOptIn, bool $autoDigest = false): void
    {
        // A project manager may only change the external opt-in when the admin
        // policy allows it; otherwise the stored value is preserved (the manager
        // cannot enable external AI the application admin has locked down).
        if (! \Kanboard\Plugin\KanAI\Settings\GatingPolicy::projectMayOptIn($this->getExternalPolicy())) {
            $externalOptIn = $this->getProjectExternalOptIn($projectId);
        }
        $this->projectMetadataModel->save($projectId, [
            'kanai_enabled' => $enabled ? '1' : '0',
            'kanai_external_opt_in' => $externalOptIn ? '1' : '0',
            'kanai_auto_digest' => $autoDigest ? '1' : '0',
        ]);
    }
}
