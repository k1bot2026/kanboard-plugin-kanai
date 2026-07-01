<?php

namespace Kanboard\Plugin\KanAI\Model;

/**
 * Builds a short conversation title from free text (first user message or a
 * rename input). Pure — no Kanboard dependency, unit-testable standalone.
 */
class ConversationTitle
{
    public static function from(string $text): string
    {
        $t = trim(preg_replace('/\s+/', ' ', $text));
        if ($t === '') {
            return '';
        }
        if (mb_strlen($t) > 48) {
            $t = mb_substr($t, 0, 48) . '…';
        }
        return $t;
    }
}
