<?php
declare(strict_types=1);

namespace App\Utils;

use Exception;

/**
 * Class Name: EncryptionUtil
 *
 * Wrapper-Klasse für SodiumEncryption, um die Nutzung zu vereinfachen.
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 2.0
 * @since 2025-02-24
 *
 * 
 * Example CLI Usage:
 * ```php
 * use App\Utils\EncryptionUtil;
 * 
 * // Schlüssel generieren und in config.php speichern
 * $key = App\Utils\EncryptionUtil::generateKey();
 * $encodedKey = App\Utils\EncryptionUtil::encodeKey($key);
 * echo "Save this in config.php as ENCRYPTION_KEY: $encodedKey\n";
 * 
 * // Verschlüsseln und Entschlüsseln
 * $plaintext = "Hallo Welt";
 * $key = App\Utils\SodiumEncryption::decodeKey($config['encryption_key']);
 *
 * $cipher = App\Utils\EncryptionUtil::encrypt($plaintext, $key);
 * echo "Cipher: $cipher\n";
 *
 * $plain = App\Utils\EncryptionUtil::decrypt($cipher, $key);
 * echo "Plain: $plain\n";
 * ```
 */
final class EncryptionUtil
{
    private function __construct() { /* static only */ }

    /**
     * Erzeugt einen neuen Schlüssel (binär).
     */
    public static function generateKey(): string
    {
        return SodiumEncryption::generateKey();
    }

    /**
     * Gibt eine Base64-URL-freundliche Repräsentation zurück.
     */
    public static function encodeKey(string $key): string
    {
        return SodiumEncryption::encodeKey($key);
    }

    /**
     * Wandelt Base64-URL zurück in den binären Schlüssel.
     */
    public static function decodeKey(string $encoded): string
    {
        return SodiumEncryption::decodeKey($encoded);
    }

    /**
     * Verschlüsselt einen String.
     *
     * @param string $plaintext
     * @param string $key       - binärer Schlüssel (32 Bytes)
     * @param string $context   - optionaler Kontext/AAD (z.B. User-ID)
     */
    public static function encrypt(string $plaintext, string $key, string $context = ''): string
    {
        return SodiumEncryption::encrypt($plaintext, $key, $context);
    }

    /**
     * Entschlüsselt einen String.
     *
     * @param string $ciphertext - Envelope (Base64URL)
     * @param string $key
     * @param string $context    - muss identisch mit encrypt() sein
     */
    public static function decrypt(string $ciphertext, string $key, string $context = ''): string
    {
        return SodiumEncryption::decrypt($ciphertext, $key, $context);
    }

    /**
     * (Optional) Passphrase-basiert Schlüssel ableiten.
     * Gibt Array mit ['key'=>..., 'salt'=>...] zurück, wenn kein Salt angegeben wird.
     */
    public static function deriveKeyFromPassphrase(string $passphrase, ?string $salt = null, array $options = [])
    {
        return SodiumEncryption::deriveKeyFromPassphrase($passphrase, $salt, $options);
    }
}
