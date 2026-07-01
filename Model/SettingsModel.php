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
        $crypto = $this->crypto();
        $out['kanai_openai_key'] = $crypto->decrypt($this->configModel->get('kanai_openai_key', ''));
        $out['kanai_anthropic_key'] = $crypto->decrypt($this->configModel->get('kanai_anthropic_key', ''));
        return $out;
    }

    public function saveGlobal(array $values): void
    {
        $crypto = $this->crypto();
        $save = [
            'kanai_external_enabled' => empty($values['kanai_external_enabled']) ? '0' : '1',
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
        ];
        // The admin form leaves a key field EMPTY to keep the stored key (the
        // saved key is shown only as a masked hint, never pre-filled into the
        // input). So any non-empty submitted value is a genuine new key. This
        // avoids depending on a fragile multibyte mask sentinel.
        foreach (['kanai_openai_key', 'kanai_anthropic_key'] as $field) {
            $new = trim($values[$field] ?? '');
            if ($new !== '') {
                $save[$field] = $crypto->encrypt($new);
            }
        }
        $this->configModel->save($save);
    }

    public function isExternalEnabled(): bool
    {
        return $this->configModel->get('kanai_external_enabled', '0') === '1';
    }

    public function getProjectEnabled(int $projectId): bool
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
        $this->projectMetadataModel->save($projectId, [
            'kanai_enabled' => $enabled ? '1' : '0',
            'kanai_external_opt_in' => $externalOptIn ? '1' : '0',
            'kanai_auto_digest' => $autoDigest ? '1' : '0',
        ]);
    }
}
