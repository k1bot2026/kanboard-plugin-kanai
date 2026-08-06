<?php

namespace Kanboard\Plugin\KanAI\Settings;

/**
 * Pure serialization/sanitization for admin-customizable quick actions
 * ("skills"). A quick action is a one-click preset whose instruction is sent
 * through the normal ask flow. Instructions are admin-authored trusted text —
 * the admin can already type anything into the ask box, so this adds no new
 * capability, only convenience. No Kanboard dependency (unit-testable).
 *
 * Keys are assigned positionally (qa0, qa1, …) on every sanitize, so a rendered
 * button's key always matches the same stored entry at ask time, regardless of
 * the label's language or characters — important for non-English installs.
 */
class QuickActions
{
    private const KEY_PREFIX = 'qa';

    /**
     * @param array<int,array<string,mixed>> $rows each with 'label'/'instruction'
     * @return array<int,array{key:string,label:string,instruction:string}>
     */
    public static function sanitize(array $rows): array
    {
        $out = [];
        $i = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $instruction = trim((string) ($row['instruction'] ?? ''));
            if ($label === '' || $instruction === '') {
                continue;
            }
            $out[] = [
                'key' => self::KEY_PREFIX . $i,
                'label' => $label,
                'instruction' => $instruction,
            ];
            $i++;
        }
        return $out;
    }

    /** Pair the parallel label[]/instruction[] arrays a form submits into rows. */
    public static function fromForm(array $labels, array $instructions): array
    {
        $rows = [];
        foreach (array_values($labels) as $idx => $label) {
            $rows[] = [
                'label' => $label,
                'instruction' => $instructions[$idx] ?? '',
            ];
        }
        return self::sanitize($rows);
    }

    /** JSON for storage, or '' when the set is empty (meaning "use defaults"). */
    public static function encode(array $rows): string
    {
        $sane = self::sanitize($rows);
        return $sane === [] ? '' : json_encode($sane);
    }

    /**
     * Decode stored JSON back to a skill list, falling back to $default when the
     * stored value is empty or unusable.
     * @return array<int,array{key:string,label:string,instruction:string}>
     */
    public static function decode(string $json, array $default): array
    {
        if (trim($json) === '') {
            return $default;
        }
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return $default;
        }
        $sane = self::sanitize($decoded);
        return $sane === [] ? $default : $sane;
    }

    public static function instructionFor(array $skills, string $key): ?string
    {
        foreach ($skills as $s) {
            if (($s['key'] ?? '') === $key) {
                return $s['instruction'] ?? null;
            }
        }
        return null;
    }
}
