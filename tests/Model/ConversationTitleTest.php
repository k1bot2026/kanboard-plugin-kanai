<?php

namespace Kanboard\Plugin\KanAI\Tests\Model;

use Kanboard\Plugin\KanAI\Model\ConversationTitle;
use PHPUnit\Framework\TestCase;

final class ConversationTitleTest extends TestCase
{
    public function testCollapsesWhitespace(): void
    {
        $this->assertSame('a b c', ConversationTitle::from("a\n b\t  c"));
    }

    public function testEmptyStaysEmpty(): void
    {
        $this->assertSame('', ConversationTitle::from("   \n "));
    }

    public function testLongTitleIsTruncatedWithEllipsis(): void
    {
        $long = str_repeat('woord ', 20); // 120 chars
        $t = ConversationTitle::from($long);
        $this->assertSame(49, mb_strlen($t)); // 48 + ellipsis
        $this->assertSame('…', mb_substr($t, -1));
    }

    public function testShortTitleUnchanged(): void
    {
        $this->assertSame('Sprint planning', ConversationTitle::from('Sprint planning'));
    }

    public function testMultibyteSafe(): void
    {
        $t = ConversationTitle::from(str_repeat('é', 60));
        $this->assertSame(49, mb_strlen($t));
    }
}
