<?php

namespace App\Utils;

class EncryptionUtil
{
    // AES-256-CBC ist sicherer als AES-128-CBC
    // und wird von den meisten modernen Systemen unterstützt
    private static string $method = 'aes-256-cbc';
    // Der geheime Schlüssel sollte sicher und geheim gehalten werden
    // und nicht im Code hardcodiert sein
    private static string $secretKey = 'DEIN_GEHEIMER_SCHLÜSSEL'; // Aus config laden
    // Separator für die verschiedenen Teile der verschlüsselten Daten
    private static string $separator = '::';

    
    /**
     * Generates a cryptographic key by hashing the secret key using the SHA-256 algorithm.
     *
     * @return string The generated cryptographic key in binary format.
     */
    private static function getKey(): string
    {
        return hash('sha256', self::$secretKey, true);
    }

    /**
     * Encrypts the given plaintext using AES-256-CBC encryption.
     *
     * @param string $plainText The plaintext to encrypt.
     * @return string|false The encrypted text in base64 format, or false on failure.
     */
    public static function encrypt(string $plainText): string
    {
        $key = self::getKey();
        $ivLength = openssl_cipher_iv_length(self::$method);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $cipherText = openssl_encrypt($plainText, self::$method, $key, 0, $iv);
        if ($cipherText === false) {
            return false;
        }

        // Authentifizierungs-Tag (HMAC)
        $hmac = hash_hmac('sha256', $cipherText, $key, true);

        // Kombinieren: base64(cipherText) + IV + HMAC
        $data = base64_encode($cipherText) . self::$separator .
                base64_encode($iv) . self::$separator .
                base64_encode($hmac);

        return base64_encode($data); // Alles nochmals codieren für Speicher
    }

    /**
     * Decrypts the given encrypted text using AES-256-CBC decryption.
     *
     * @param string $encryptedInput The encrypted text in base64 format.
     * @return string|false The decrypted plaintext, or false on failure.
     */
    public static function decrypt(string $encryptedInput): string|false
    {
        $decodedData = base64_decode($encryptedInput, true);
        if ($decodedData === false || !str_contains($decodedData, self::$separator)) {
            return false;
        }

        [$cipherBase64, $ivBase64, $hmacBase64] = explode(self::$separator, $decodedData);

        $cipherText = base64_decode($cipherBase64, true);
        $iv = base64_decode($ivBase64, true);
        $hmac = base64_decode($hmacBase64, true);

        $key = self::getKey();

        // HMAC prüfen
        $calculatedHmac = hash_hmac('sha256', $cipherText, $key, true);
        if (!hash_equals($hmac, $calculatedHmac)) {
            return false; // Daten wurden manipuliert
        }

        return openssl_decrypt($cipherText, self::$method, $key, 0, $iv);
    }
}
