<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Encrypts refund IBAN at rest using libsodium secretbox (XSalsa20-Poly1305).
 *
 * The key comes from LUXE_CAR_IBAN_ENCRYPTION_KEY: a 32-byte key encoded as
 * 64 hex characters. Generate one with:
 *     php -r "echo bin2hex(random_bytes(32));"
 */
class RefundIbanEncryptionService
{
    private string $key;

    public function __construct(string $ibanEncryptionKey)
    {
        if (!extension_loaded('sodium')) {
            throw new \RuntimeException('The sodium extension is required.');
        }
        if (\strlen($ibanEncryptionKey) !== 64 || !ctype_xdigit($ibanEncryptionKey)) {
            throw new \RuntimeException('LUXE_CAR_IBAN_ENCRYPTION_KEY must be 64 hex characters (32 bytes).');
        }
        $this->key = (string) sodium_hex2bin($ibanEncryptionKey);
    }

    public function encrypt(string $plaintextIban): string
    {
        $nonce      = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintextIban, $nonce, $this->key);
        // Prepend nonce so we can decrypt later. Store base64 for safe TEXT column storage.
        return base64_encode($nonce.$ciphertext);
    }

    public function decrypt(string $stored): string
    {
        $blob = base64_decode($stored, true);
        if ($blob === false || \strlen($blob) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            throw new \RuntimeException('Invalid encrypted IBAN payload.');
        }
        $nonce      = substr($blob, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($blob, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain      = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
        if ($plain === false) {
            throw new \RuntimeException('IBAN decryption failed (bad key or tampered data).');
        }
        return $plain;
    }

    public function lastFour(string $plaintextIban): string
    {
        $clean = preg_replace('/\s+/', '', $plaintextIban) ?? '';
        return substr($clean, -4);
    }

    public function normalizeIban(string $raw): string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', $raw) ?? '');
        // Very basic sanity check: 15-34 alphanumeric characters.
        if (preg_match('/^[A-Z0-9]{15,34}$/', $clean) !== 1) {
            throw new \InvalidArgumentException('IBAN format invalide.');
        }
        return $clean;
    }
}
