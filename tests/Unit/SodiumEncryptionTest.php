<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Utils\SodiumEncryption;
use Exception;

/**
 * SodiumEncryption Unit Tests
 * 
 * Comprehensive tests for cryptographic operations including:
 * - Key generation and encoding
 * - Encryption and decryption
 * - Associated authenticated data (AAD)
 * - Key derivation from passphrase
 * - Error handling and validation
 * - Edge cases and security properties
 * 
 * @package Tests\Unit
 */
class SodiumEncryptionTest extends TestCase
{
    private string $testKey;
    private string $encodedKey;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Check if sodium extension is available
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension is not available');
        }
        
        // Generate a test key for use in tests
        $this->testKey = SodiumEncryption::generateKey();
        $this->encodedKey = SodiumEncryption::encodeKey($this->testKey);
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        // Zero sensitive data
        if (function_exists('sodium_memzero')) {
            @sodium_memzero($this->testKey);
        }
        
        parent::tearDown();
    }

    // ==========================================
    // TESTS: KEY GENERATION
    // ==========================================

    /** @test */
    public function it_generates_key_with_correct_length(): void
    {
        // Act
        $key = SodiumEncryption::generateKey();
        
        // Assert
        $this->assertIsString($key);
        $this->assertEquals(32, strlen($key)); // SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES
    }

    /** @test */
    public function it_generates_unique_keys(): void
    {
        // Act
        $key1 = SodiumEncryption::generateKey();
        $key2 = SodiumEncryption::generateKey();
        $key3 = SodiumEncryption::generateKey();
        
        // Assert
        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key2, $key3);
        $this->assertNotEquals($key1, $key3);
    }

    /** @test */
    public function it_generates_cryptographically_random_keys(): void
    {
        // Act - Generate multiple keys
        $keys = [];
        for ($i = 0; $i < 10; $i++) {
            $keys[] = SodiumEncryption::generateKey();
        }
        
        // Assert - All keys should be unique
        $uniqueKeys = array_unique($keys);
        $this->assertCount(10, $uniqueKeys);
    }

    // ==========================================
    // TESTS: KEY ENCODING/DECODING
    // ==========================================

    /** @test */
    public function it_encodes_key_to_base64url(): void
    {
        // Act
        $encoded = SodiumEncryption::encodeKey($this->testKey);
        
        // Assert
        $this->assertIsString($encoded);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $encoded);
        $this->assertStringNotContainsString('=', $encoded); // No padding
        $this->assertStringNotContainsString('+', $encoded); // URL-safe
        $this->assertStringNotContainsString('/', $encoded); // URL-safe
    }

    /** @test */
    public function it_decodes_key_from_base64url(): void
    {
        // Arrange
        $encoded = SodiumEncryption::encodeKey($this->testKey);
        
        // Act
        $decoded = SodiumEncryption::decodeKey($encoded);
        
        // Assert
        $this->assertEquals($this->testKey, $decoded);
        $this->assertEquals(32, strlen($decoded));
    }

    /** @test */
    public function it_performs_encode_decode_roundtrip(): void
    {
        // Act
        $encoded = SodiumEncryption::encodeKey($this->testKey);
        $decoded = SodiumEncryption::decodeKey($encoded);
        
        // Assert
        $this->assertEquals($this->testKey, $decoded);
    }

    /** @test */
    public function it_throws_exception_for_invalid_encoded_key_format(): void
    {
        // Arrange
        $invalidKey = 'not-valid-base64!!!';
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid encoded key format');
        
        // Act
        SodiumEncryption::decodeKey($invalidKey);
    }

    /** @test */
    public function it_throws_exception_for_invalid_decoded_key_length(): void
    {
        // Arrange - Create a valid base64 string but with wrong length
        $shortKey = base64_encode('tooshort');
        $encoded = rtrim(strtr($shortKey, '+/', '-_'), '=');
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid key length');
        
        // Act
        SodiumEncryption::decodeKey($encoded);
    }

    // ==========================================
    // TESTS: ENCRYPTION/DECRYPTION
    // ==========================================

    /** @test */
    public function it_encrypts_plaintext_successfully(): void
    {
        // Arrange
        $plaintext = 'Secret message';
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        
        // Assert
        $this->assertIsString($ciphertext);
        $this->assertNotEquals($plaintext, $ciphertext);
        $this->assertNotEmpty($ciphertext);
    }

    /** @test */
    public function it_decrypts_ciphertext_successfully(): void
    {
        // Arrange
        $plaintext = 'Secret message';
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        
        // Act
        $decrypted = SodiumEncryption::decrypt($ciphertext, $this->testKey);
        
        // Assert
        $this->assertEquals($plaintext, $decrypted);
    }

    /** @test */
    public function it_performs_encrypt_decrypt_roundtrip(): void
    {
        // Arrange
        $plaintexts = [
            'Simple text',
            'Text with special chars: äöü ß € @',
            'Numbers: 1234567890',
            'Long text: ' . str_repeat('Lorem ipsum ', 100),
            '', // Empty string
        ];
        
        foreach ($plaintexts as $plaintext) {
            // Act
            $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
            $decrypted = SodiumEncryption::decrypt($ciphertext, $this->testKey);
            
            // Assert
            $this->assertEquals($plaintext, $decrypted);
        }
    }

    /** @test */
    public function it_produces_different_ciphertexts_for_same_plaintext(): void
    {
        // Arrange
        $plaintext = 'Same message';
        
        // Act
        $ciphertext1 = SodiumEncryption::encrypt($plaintext, $this->testKey);
        $ciphertext2 = SodiumEncryption::encrypt($plaintext, $this->testKey);
        $ciphertext3 = SodiumEncryption::encrypt($plaintext, $this->testKey);
        
        // Assert - Ciphertexts should differ due to random nonces
        $this->assertNotEquals($ciphertext1, $ciphertext2);
        $this->assertNotEquals($ciphertext2, $ciphertext3);
        $this->assertNotEquals($ciphertext1, $ciphertext3);
        
        // But all should decrypt to same plaintext
        $this->assertEquals($plaintext, SodiumEncryption::decrypt($ciphertext1, $this->testKey));
        $this->assertEquals($plaintext, SodiumEncryption::decrypt($ciphertext2, $this->testKey));
        $this->assertEquals($plaintext, SodiumEncryption::decrypt($ciphertext3, $this->testKey));
    }

    /** @test */
    public function it_throws_exception_for_invalid_key_length_on_encrypt(): void
    {
        // Arrange
        $plaintext = 'Test message';
        $invalidKey = 'too_short';
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid key length for encryption');
        
        // Act
        SodiumEncryption::encrypt($plaintext, $invalidKey);
    }

    /** @test */
    public function it_throws_exception_for_invalid_key_length_on_decrypt(): void
    {
        // Arrange
        $ciphertext = 'AW5vdF9hX3ZhbGlkX2NpcGhlcnRleHQ';
        $invalidKey = 'too_short';
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid key length for decryption');
        
        // Act
        SodiumEncryption::decrypt($ciphertext, $invalidKey);
    }

    /** @test */
    public function it_throws_exception_for_invalid_ciphertext_encoding(): void
    {
        // Arrange
        $invalidCiphertext = 'not!!!valid!!!base64!!!';
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid ciphertext encoding');
        
        // Act
        SodiumEncryption::decrypt($invalidCiphertext, $this->testKey);
    }

    /** @test */
    public function it_throws_exception_for_too_short_ciphertext(): void
    {
        // Arrange
        $shortCiphertext = base64_encode('short');
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ciphertext too short');
        
        // Act
        SodiumEncryption::decrypt($shortCiphertext, $this->testKey);
    }

    /** @test */
    public function it_throws_exception_for_wrong_decryption_key(): void
    {
        // Arrange
        $plaintext = 'Secret message';
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        $wrongKey = SodiumEncryption::generateKey();
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Decryption failed: authentication failure');
        
        // Act
        SodiumEncryption::decrypt($ciphertext, $wrongKey);
    }

    /** @test */
    public function it_throws_exception_for_tampered_ciphertext(): void
    {
        // Arrange
        $plaintext = 'Secret message';
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        
        // Tamper with ciphertext
        $tamperedCiphertext = substr($ciphertext, 0, -3) . 'XXX';
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Decryption failed: authentication failure');
        
        // Act
        SodiumEncryption::decrypt($tamperedCiphertext, $this->testKey);
    }

    // ==========================================
    // TESTS: ASSOCIATED AUTHENTICATED DATA (AAD)
    // ==========================================

    /** @test */
    public function it_encrypts_and_decrypts_with_associated_data(): void
    {
        // Arrange
        $plaintext = 'Secret message';
        $associatedData = 'user_id:12345';
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey, $associatedData);
        $decrypted = SodiumEncryption::decrypt($ciphertext, $this->testKey, $associatedData);
        
        // Assert
        $this->assertEquals($plaintext, $decrypted);
    }

    /** @test */
    public function it_fails_decryption_with_wrong_associated_data(): void
    {
        // Arrange
        $plaintext = 'Secret message';
        $associatedData = 'user_id:12345';
        $wrongAssociatedData = 'user_id:99999';
        
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey, $associatedData);
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Decryption failed: authentication failure');
        
        // Act
        SodiumEncryption::decrypt($ciphertext, $this->testKey, $wrongAssociatedData);
    }

    /** @test */
    public function it_fails_decryption_with_missing_associated_data(): void
    {
        // Arrange
        $plaintext = 'Secret message';
        $associatedData = 'user_id:12345';
        
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey, $associatedData);
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Decryption failed: authentication failure');
        
        // Act - Try to decrypt without providing AAD
        SodiumEncryption::decrypt($ciphertext, $this->testKey);
    }

    /** @test */
    public function it_encrypts_without_associated_data_by_default(): void
    {
        // Arrange
        $plaintext = 'Secret message';
        
        // Act - Encrypt without AAD
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        $decrypted = SodiumEncryption::decrypt($ciphertext, $this->testKey);
        
        // Assert
        $this->assertEquals($plaintext, $decrypted);
    }

    // ==========================================
    // TESTS: PASSPHRASE KEY DERIVATION
    // ==========================================

    /** @test */
    public function it_derives_key_from_passphrase_without_salt(): void
    {
        // Arrange
        $passphrase = 'my-secure-passphrase-123';
        
        // Act
        $result = SodiumEncryption::deriveKeyFromPassphrase($passphrase);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('key', $result);
        $this->assertArrayHasKey('salt', $result);
        $this->assertEquals(32, strlen($result['key']));
        $this->assertGreaterThanOrEqual(16, strlen($result['salt']));
    }

    /** @test */
    public function it_derives_key_from_passphrase_with_salt(): void
    {
        // Arrange
        $passphrase = 'my-secure-passphrase-123';
        $salt = random_bytes(16);
        
        // Act
        $key = SodiumEncryption::deriveKeyFromPassphrase($passphrase, $salt);
        
        // Assert
        $this->assertIsString($key);
        $this->assertEquals(32, strlen($key));
    }

    /** @test */
    public function it_derives_same_key_for_same_passphrase_and_salt(): void
    {
        // Arrange
        $passphrase = 'my-secure-passphrase-123';
        $salt = random_bytes(16);
        
        // Act
        $key1 = SodiumEncryption::deriveKeyFromPassphrase($passphrase, $salt);
        $key2 = SodiumEncryption::deriveKeyFromPassphrase($passphrase, $salt);
        
        // Assert
        $this->assertEquals($key1, $key2);
    }

    /** @test */
    public function it_derives_different_keys_for_different_passphrases(): void
    {
        // Arrange
        $passphrase1 = 'passphrase-one';
        $passphrase2 = 'passphrase-two';
        $salt = random_bytes(16);
        
        // Act
        $key1 = SodiumEncryption::deriveKeyFromPassphrase($passphrase1, $salt);
        $key2 = SodiumEncryption::deriveKeyFromPassphrase($passphrase2, $salt);
        
        // Assert
        $this->assertNotEquals($key1, $key2);
    }

    /** @test */
    public function it_derives_different_keys_for_different_salts(): void
    {
        // Arrange
        $passphrase = 'same-passphrase';
        $salt1 = random_bytes(16);
        $salt2 = random_bytes(16);
        
        // Act
        $key1 = SodiumEncryption::deriveKeyFromPassphrase($passphrase, $salt1);
        $key2 = SodiumEncryption::deriveKeyFromPassphrase($passphrase, $salt2);
        
        // Assert
        $this->assertNotEquals($key1, $key2);
    }

    /** @test */
    public function it_throws_exception_for_too_short_salt(): void
    {
        // Arrange
        $passphrase = 'my-passphrase';
        $shortSalt = random_bytes(7); // Too short
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Salt too short');
        
        // Act
        SodiumEncryption::deriveKeyFromPassphrase($passphrase, $shortSalt);
    }

    /** @test */
    public function it_uses_derived_key_for_encryption(): void
    {
        // Arrange
        $passphrase = 'my-secure-passphrase';
        $plaintext = 'Secret message';
        $result = SodiumEncryption::deriveKeyFromPassphrase($passphrase);
        $derivedKey = $result['key'];
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $derivedKey);
        $decrypted = SodiumEncryption::decrypt($ciphertext, $derivedKey);
        
        // Assert
        $this->assertEquals($plaintext, $decrypted);
    }

    // ==========================================
    // TESTS: EDGE CASES
    // ==========================================

    /** @test */
    public function it_handles_empty_plaintext(): void
    {
        // Arrange
        $plaintext = '';
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        $decrypted = SodiumEncryption::decrypt($ciphertext, $this->testKey);
        
        // Assert
        $this->assertEquals($plaintext, $decrypted);
    }

    /** @test */
    public function it_handles_large_plaintext(): void
    {
        // Arrange
        $plaintext = str_repeat('Lorem ipsum dolor sit amet. ', 10000); // ~280KB
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        $decrypted = SodiumEncryption::decrypt($ciphertext, $this->testKey);
        
        // Assert
        $this->assertEquals($plaintext, $decrypted);
    }

    /** @test */
    public function it_handles_unicode_characters(): void
    {
        // Arrange
        $plaintext = '日本語 中文 한국어 العربية עברית Ελληνικά Кириллица 🔒🔐';
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        $decrypted = SodiumEncryption::decrypt($ciphertext, $this->testKey);
        
        // Assert
        $this->assertEquals($plaintext, $decrypted);
    }

    /** @test */
    public function it_handles_binary_data(): void
    {
        // Arrange
        $plaintext = random_bytes(1024); // 1KB random binary
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        $decrypted = SodiumEncryption::decrypt($ciphertext, $this->testKey);
        
        // Assert
        $this->assertEquals($plaintext, $decrypted);
    }

    /** @test */
    public function it_produces_url_safe_ciphertext(): void
    {
        // Arrange
        $plaintext = 'Test message';
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        
        // Assert - Should be URL-safe Base64
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $ciphertext);
        $this->assertStringNotContainsString('+', $ciphertext);
        $this->assertStringNotContainsString('/', $ciphertext);
        $this->assertStringNotContainsString('=', $ciphertext);
    }

    // ==========================================
    // TESTS: VERSION HANDLING
    // ==========================================

    /** @test */
    public function it_includes_version_in_envelope(): void
    {
        // Arrange
        $plaintext = 'Test message';
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        
        // Decode to check envelope structure
        $b64 = strtr($ciphertext, '-_', '+/');
        $pad = 4 - (strlen($b64) % 4);
        if ($pad < 4) {
            $b64 .= str_repeat('=', $pad);
        }
        $envelope = base64_decode($b64, true);
        
        // Assert - First byte should be version (1)
        $version = ord($envelope[0]);
        $this->assertEquals(1, $version);
    }

    /** @test */
    public function it_rejects_unsupported_version(): void
    {
        // Arrange - Create ciphertext with wrong version
        $plaintext = 'Test message';
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        
        // Decode and modify version byte
        $b64 = strtr($ciphertext, '-_', '+/');
        $pad = 4 - (strlen($b64) % 4);
        if ($pad < 4) {
            $b64 .= str_repeat('=', $pad);
        }
        $envelope = base64_decode($b64, true);
        
        // Change version to 99
        $envelope[0] = chr(99);
        
        // Re-encode
        $tamperedCiphertext = rtrim(strtr(base64_encode($envelope), '+/', '-_'), '=');
        
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported envelope version');
        
        // Act
        SodiumEncryption::decrypt($tamperedCiphertext, $this->testKey);
    }

    // ==========================================
    // TESTS: SECURITY PROPERTIES
    // ==========================================

    /** @test */
    public function it_provides_authenticated_encryption(): void
    {
        // Arrange
        $plaintext = 'Secret message for testing authenticated encryption';
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        
        // Act - Tamper with ciphertext by flipping bits in different positions
        // Skip position 0 (version byte) to test authentication, not version validation
        // We'll modify by XORing with a byte to ensure actual change
        $testPositions = [
            5,  // In nonce region
            15, // In nonce region
            30, // In ciphertext region
            min(40, strlen($ciphertext) - 2), // Ensure we're within bounds
        ];
        
        foreach ($testPositions as $pos) {
            // Skip if position is out of bounds
            if ($pos >= strlen($ciphertext)) {
                continue;
            }
            
            // Create tampered version by XORing one byte
            // This ensures we always make a real change
            $tampered = $ciphertext;
            $originalChar = $tampered[$pos];
            $tamperedChar = chr(ord($originalChar) ^ 0xFF); // Flip all bits
            $tampered[$pos] = $tamperedChar;
            
            // Verify we actually changed something
            $this->assertNotEquals($ciphertext, $tampered, "Tampering at position $pos should change ciphertext");
            
            // Assert - Should fail authentication
            $exceptionCaught = false;
            try {
                SodiumEncryption::decrypt($tampered, $this->testKey);
            } catch (Exception $e) {
                $exceptionCaught = true;
                // Should fail due to authentication (tampering detected) or invalid encoding
                $this->assertTrue(
                    str_contains($e->getMessage(), 'authentication failure') ||
                    str_contains($e->getMessage(), 'Invalid ciphertext encoding') ||
                    str_contains($e->getMessage(), 'Ciphertext too short') ||
                    str_contains($e->getMessage(), 'Unsupported envelope version'),
                    "Expected authentication failure or decoding error, got: " . $e->getMessage()
                );
            }
            
            $this->assertTrue(
                $exceptionCaught,
                "Expected decryption to fail for tampered ciphertext at position $pos"
            );
        }
    }

    /** @test */
    public function it_uses_different_nonces_for_each_encryption(): void
    {
        // Arrange
        $plaintext = 'Same message';
        
        // Act - Encrypt same plaintext multiple times
        $ciphertexts = [];
        for ($i = 0; $i < 5; $i++) {
            $ciphertexts[] = SodiumEncryption::encrypt($plaintext, $this->testKey);
        }
        
        // Assert - All ciphertexts should be unique
        $uniqueCiphertexts = array_unique($ciphertexts);
        $this->assertCount(5, $uniqueCiphertexts);
    }

    /** @test */
    public function it_maintains_confidentiality(): void
    {
        // Arrange
        $plaintext = 'Secret message';
        
        // Act
        $ciphertext = SodiumEncryption::encrypt($plaintext, $this->testKey);
        
        // Assert - Ciphertext should not contain plaintext
        $this->assertStringNotContainsString($plaintext, $ciphertext);
        $this->assertStringNotContainsString(base64_encode($plaintext), $ciphertext);
    }
}