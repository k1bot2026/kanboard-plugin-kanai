<?php

namespace Kanboard\Plugin\KanAI\Tests\Security;

use Kanboard\Plugin\KanAI\Security\Crypto;
use PHPUnit\Framework\TestCase;

final class CryptoTest extends TestCase
{
    private function crypto(): Crypto
    {
        return new Crypto('test-key-0123456789-abcdef');
    }

    public function testRoundTrip(): void
    {
        $c = $this->crypto();
        $cipher = $c->encrypt('sk-secret-value');
        $this->assertNotSame('sk-secret-value', $cipher);
        $this->assertSame('sk-secret-value', $c->decrypt($cipher));
    }

    public function testEmptyStringRoundTrips(): void
    {
        $c = $this->crypto();
        $this->assertSame('', $c->decrypt($c->encrypt('')));
    }

    public function testCiphertextIsNonDeterministic(): void
    {
        $c = $this->crypto();
        $this->assertNotSame($c->encrypt('same'), $c->encrypt('same')); // random IV
    }

    public function testTamperedCiphertextReturnsEmpty(): void
    {
        $c = $this->crypto();
        $cipher = $c->encrypt('secret');
        $this->assertSame('', $c->decrypt($cipher . 'x'));
        $this->assertSame('', $c->decrypt('not-base64-at-all'));
    }

    public function testWrongKeyReturnsEmpty(): void
    {
        $cipher = $this->crypto()->encrypt('secret');
        $other = new Crypto('different-key-9999');
        $this->assertSame('', $other->decrypt($cipher));
    }

    public function testMask(): void
    {
        $this->assertSame('••••6789', $this->crypto()->mask('sk-1236789'));
        $this->assertSame('', $this->crypto()->mask(''));
    }
}
