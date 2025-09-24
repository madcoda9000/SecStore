<?php
namespace App\Utils;

use Exception;
use InvalidArgumentException;

/**
 * SecureEncryptionUtil
 * 
 * Verbesserte Verschlüsselungs- und Entschlüsselungsmethoden mit
 * sicherer Schlüsselableitung, Authentifizierung und Eingabevalidierung.
 * 
 * Anforderungen:
 * - PHP 7.4+
 * - OpenSSL Erweiterung
 * 
 * Sicherheitsmerkmale:
 * - PBKDF2 Schlüsselableitung mit 100.000 Iterationen
 * - AES-256-CBC Verschlüsselung
 * - HMAC-SHA256 für Datenintegrität und Authentifizierung
 * - Eingabevalidierung und Längenbeschränkungen
 * - Verwendung von kryptographisch sicheren Zufallswerten
 * 
 * Hinweis: Der Master-Schlüssel muss mindestens 256 Bit (32 Bytes) lang sein.
 */
class SecureEncryptionUtil
{
    private static $config;
    private static string $method = 'aes-256-cbc';
    private static string $separator = '::';
    
    // Sicherheitskonfiguration
    private const PBKDF2_ITERATIONS = 100000;  // Mindestens 100.000 Iterationen
    private const MAX_INPUT_LENGTH = 65536;    // 64KB Maximum
    private const SALT_LENGTH = 32;            // 256-bit Salt
    
    /**
     * Konfiguration laden
     */
    public static function loadConfig(): void
    {
        if (!self::$config) {
            self::$config = include __DIR__ . '/../../config.php';
        }
    }
    
    /**
     * Sichere Schlüsselableitung mit PBKDF2
     * 
     * @param string $salt Zufälliger Salt für die Ableitung
     * @return string Abgeleiteter Schlüssel
     */
    private static function deriveKey(string $salt): string
    {
        self::loadConfig();
        $masterKey = self::$config['security']['key'];
        
        if (strlen($masterKey) < 32) {
            throw new Exception('Master key must be at least 256 bits (32 bytes)');
        }
        
        return hash_pbkdf2(
            'sha256', 
            $masterKey, 
            $salt, 
            self::PBKDF2_ITERATIONS, 
            32, 
            true
        );
    }
    
    /**
     * Sichere Verschlüsselung mit AEAD-ähnlichen Eigenschaften
     * 
     * @param string $plainText Zu verschlüsselnder Text
     * @return string Verschlüsselte Daten (Base64)
     * @throws Exception Bei Verschlüsselungsfehlern
     */
    public static function encrypt(string $plainText): string
    {
        // Eingabevalidierung
        if (strlen($plainText) > self::MAX_INPUT_LENGTH) {
            throw new InvalidArgumentException('Input exceeds maximum length');
        }
        
        if (empty($plainText)) {
            throw new InvalidArgumentException('Input cannot be empty');
        }
        
        // Zufällige Werte generieren (kryptographisch sicher)
        $salt = random_bytes(self::SALT_LENGTH);
        $iv = random_bytes(openssl_cipher_iv_length(self::$method));
        
        if ($salt === false || $iv === false) {
            throw new Exception('Failed to generate cryptographic random bytes');
        }
        
        // Schlüssel ableiten
        $key = self::deriveKey($salt);
        
        // Verschlüsselung durchführen
        $cipherText = openssl_encrypt($plainText, self::$method, $key, OPENSSL_RAW_DATA, $iv);
        
        if ($cipherText === false) {
            throw new Exception('Encryption failed');
        }
        
        // HMAC für Authentifizierung (über alle Komponenten)
        $dataForHmac = $salt . $iv . $cipherText;
        $hmac = hash_hmac('sha256', $dataForHmac, $key, true);
        
        // Komponenten kombinieren: Salt + IV + CipherText + HMAC
        $combined = base64_encode($salt) . self::$separator .
                   base64_encode($iv) . self::$separator .
                   base64_encode($cipherText) . self::$separator .
                   base64_encode($hmac);
        
        // Alles nochmals kodieren für sichere Speicherung
        return base64_encode($combined);
    }
    
    /**
     * Sichere Entschlüsselung mit Authentifizierung
     * 
     * @param string $encryptedData Verschlüsselte Daten
     * @return string|false Entschlüsselter Text oder false bei Fehler
     */
    public static function decrypt(string $encryptedData): string|false
    {
        try {
            // Eingabevalidierung
            if (empty($encryptedData)) {
                return false;
            }
            
            // Erste Base64-Dekodierung
            $combined = base64_decode($encryptedData, true);
            if ($combined === false) {
                return false;
            }
            
            // Komponenten trennen
            $parts = explode(self::$separator, $combined);
            if (count($parts) !== 4) {
                return false;
            }
            
            [$saltB64, $ivB64, $cipherB64, $hmacB64] = $parts;
            
            // Base64-Dekodierung der Komponenten
            $salt = base64_decode($saltB64, true);
            $iv = base64_decode($ivB64, true);
            $cipherText = base64_decode($cipherB64, true);
            $receivedHmac = base64_decode($hmacB64, true);
            
            if ($salt === false || $iv === false || 
                $cipherText === false || $receivedHmac === false) {
                return false;
            }
            
            // Längenvalidierung
            if (strlen($salt) !== self::SALT_LENGTH || 
                strlen($iv) !== openssl_cipher_iv_length(self::$method) ||
                strlen($receivedHmac) !== 32) {
                return false;
            }
            
            // Schlüssel ableiten
            $key = self::deriveKey($salt);
            
            // HMAC verifizieren (konstante-Zeit Vergleich)
            $dataForHmac = $salt . $iv . $cipherText;
            $calculatedHmac = hash_hmac('sha256', $dataForHmac, $key, true);
            
            if (!hash_equals($receivedHmac, $calculatedHmac)) {
                return false; // Daten wurden manipuliert oder beschädigt
            }
            
            // Entschlüsselung
            $plainText = openssl_decrypt($cipherText, self::$method, $key, OPENSSL_RAW_DATA, $iv);
            
            return $plainText;
            
        } catch (Exception $e) {
            // Logging könnte hier hinzugefügt werden
            return false;
        }
    }
    
    /**
     * Überprüft, ob die Verschlüsselungsumgebung korrekt konfiguriert ist
     * 
     * @return bool True wenn korrekt konfiguriert
     */
    public static function validateEnvironment(): bool
    {
        // OpenSSL verfügbar?
        if (!extension_loaded('openssl')) {
            return false;
        }
        
        // Algorithmus verfügbar?
        if (!in_array(self::$method, openssl_get_cipher_methods())) {
            return false;
        }
        
        // Konfiguration laden und prüfen
        try {
            self::loadConfig();
            $masterKey = self::$config['security']['key'] ?? '';
            
            if (strlen($masterKey) < 32) {
                return false; // Schlüssel zu schwach
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}