<?php
declare(strict_types=1);

namespace App\Utils;

use Exception;

/**
 * Class Name: SessionUtil
 *
 * Utility class for encryption and decryption using libsodium. 
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 2.0
 * @since 2025-02-24
 *
 */
final class SodiumEncryption
{
    private const VERSION = 1;
    private const KEY_LEN = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES; // 32
    private const NONCE_LEN = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES; // 24

    private function __construct() { /* static usage */ }

    /**
     * Generate a cryptographically secure random key (binary).
     * Store this securely (config vault, env var, file with correct perms).
     */
    public static function generateKey(): string
    {
        return random_bytes(self::KEY_LEN);
    }

    /**
     * Encode key to safe storage representation (base64 url-safe).
     */
    public static function encodeKey(string $key): string
    {
        return rtrim(strtr(base64_encode($key), '+/', '-_'), '=');
    }

    /**
     * Decode the stored key from base64 url-safe back to binary.
     */
    public static function decodeKey(string $encoded): string
    {
        $b64 = strtr($encoded, '-_', '+/');
        $pad = 4 - (strlen($b64) % 4);
        if ($pad < 4) {
            $b64 .= str_repeat('=', $pad);
        }
        $bin = base64_decode($b64, true);
        if ($bin === false) {
            throw new Exception('Invalid encoded key format.');
        }
        if (strlen($bin) !== self::KEY_LEN) {
            throw new Exception('Invalid key length.');
        }
        return $bin;
    }

    /**
     * Derive a key from a passphrase using Argon2id.
     * Returns binary key of KEY_LEN bytes.
     *
     * @param string $passphrase
     * @param string|null $salt - provide persistent salt (16 bytes binary) or let it be generated and returned embedded
     * @param array $options - optional Argon2id options: memory_cost, time_cost, threads
     *
     * If salt is null, the function will return an array with ['key'=>..., 'salt'=>...]
     * If salt is provided, it returns binary key directly.
     */
    public static function deriveKeyFromPassphrase(string $passphrase, ?string $salt = null, array $options = [])
    {
        $memory = (int)($options['memory_cost'] ?? 1<<16); // 64 MiB
        $time   = (int)($options['time_cost'] ?? 4);
        $threads= (int)($options['threads'] ?? 2);

        if ($salt === null) {
            $salt = random_bytes(16);
            $key = sodium_crypto_pwhash(
                self::KEY_LEN,
                $passphrase,
                $salt,
                SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
                SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
                SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
            );
            return ['key' => $key, 'salt' => $salt];
        } else {
            if (strlen($salt) < 8) {
                throw new Exception('Salt too short; use at least 8 bytes.');
            }
            $key = sodium_crypto_pwhash(
                self::KEY_LEN,
                $passphrase,
                $salt,
                SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
                SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
                SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
            );
            return $key;
        }
    }

    /**
     * Encrypt a plaintext string.
     *
     * Returns a compact envelope (base64url) that contains:
     * version || nonce || ciphertext
     *
     * Optionally pass associatedData (AAD) which will be authenticated but not encrypted.
     */
    public static function encrypt(string $plaintext, string $key, string $associatedData = ''): string
    {
        self::ensureSodium();

        if (strlen($key) !== self::KEY_LEN) {
            throw new Exception('Invalid key length for encryption.');
        }

        $nonce = random_bytes(self::NONCE_LEN);
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            $associatedData,
            $nonce,
            $key
        );

        // Envelope: Version (1 byte) || nonce || cipher
        $envelope = pack('C', self::VERSION) . $nonce . $cipher;
        $b64 = rtrim(strtr(base64_encode($envelope), '+/', '-_'), '=');

        // zero sensitive buffers
        sodium_memzero($nonce);
        sodium_memzero($cipher);

        return $b64;
    }

    /**
     * Decrypt an envelope produced by encrypt().
     *
     * Returns plaintext string or throws Exception on auth failure.
     */
    public static function decrypt(string $envelopeB64, string $key, string $associatedData = ''): string
    {
        self::ensureSodium();

        if (strlen($key) !== self::KEY_LEN) {
            throw new Exception('Invalid key length for decryption.');
        }

        // base64url decode
        $b64 = strtr($envelopeB64, '-_', '+/');
        $pad = 4 - (strlen($b64) % 4);
        if ($pad < 4) {
            $b64 .= str_repeat('=', $pad);
        }
        $envelope = base64_decode($b64, true);
        if ($envelope === false) {
            throw new Exception('Invalid ciphertext encoding.');
        }

        // parse envelope
        $minLen = 1 + self::NONCE_LEN + 16; // version + nonce + tag at least
        if (strlen($envelope) < $minLen) {
            throw new Exception('Ciphertext too short.');
        }

        $version = ord($envelope[0]);
        if ($version !== self::VERSION) {
            throw new Exception('Unsupported envelope version.');
        }

        $nonce = substr($envelope, 1, self::NONCE_LEN);
        $cipher = substr($envelope, 1 + self::NONCE_LEN);

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $cipher,
            $associatedData,
            $nonce,
            $key
        );

        if ($plaintext === false) {
            throw new Exception('Decryption failed: authentication failure.');
        }

        sodium_memzero($nonce);
        sodium_memzero($cipher);

        return $plaintext;
    }

    private static function ensureSodium(): void
    {
        if (!extension_loaded('sodium')) {
            throw new Exception('The sodium extension is required for secure encryption.');
        }
    }
}
