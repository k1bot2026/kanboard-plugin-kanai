<?php

namespace Kanboard\Plugin\KanAI\Security;

/**
 * Authenticated symmetric encryption for secrets at rest (API keys).
 * AES-256-GCM with a random 12-byte IV; output is base64(iv | tag | ciphertext).
 * No Kanboard dependency — unit-testable in isolation.
 */
class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LEN = 12;
    private const TAG_LEN = 16;

    private string $key;

    public function __construct(string $key)
    {
        // Derive a fixed 32-byte key from whatever secret string we're given.
        $this->key = hash('sha256', $key, true);
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(self::IV_LEN);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LEN);
        if ($cipher === false) {
            return '';
        }
        return base64_encode($iv . $tag . $cipher);
    }

    public function decrypt(string $ciphertext): string
    {
        $raw = base64_decode($ciphertext, true);
        if ($raw === false || strlen($raw) < self::IV_LEN + self::TAG_LEN) {
            return '';
        }
        $iv = substr($raw, 0, self::IV_LEN);
        $tag = substr($raw, self::IV_LEN, self::TAG_LEN);
        $cipher = substr($raw, self::IV_LEN + self::TAG_LEN);
        $plain = openssl_decrypt($cipher, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? '' : $plain;
    }

    public function mask(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }
        return '••••' . substr($plaintext, -4);
    }
}
