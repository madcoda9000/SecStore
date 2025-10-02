<?php

namespace App\Utils;

/**
 * Class Name: BackupCodeUtil
 *
 * Utility class for generating, hashing, and verifying 2FA backup codes.
 * Backup codes provide a recovery method when users lose access to their
 * authenticator device.
 *
 * @package App\Utils
 * @author SecStore
 * @version 1.0
 * @since 2025-10-02
 */
class BackupCodeUtil
{
    /**
     * Number of backup codes to generate per user
     */
    private const CODE_COUNT = 10;

    /**
     * Length of each backup code (in characters)
     */
    private const CODE_LENGTH = 8;

    /**
     * Format pattern for backup codes (XXXX-XXXX)
     */
    private const CODE_FORMAT_LENGTH = 4;

    /**
     * Generates a specified number of random backup codes.
     *
     * Each code is 8 characters long and formatted as XXXX-XXXX for readability.
     * Uses cryptographically secure random_bytes() for generation.
     *
     * @return array Array of plain-text backup codes
     * @throws \Exception If random_bytes() fails
     */
    public static function generateBackupCodes(): array
    {
        $codes = [];
        
        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $code = self::generateSingleCode();
            $codes[] = $code;
        }
        
        return $codes;
    }

    /**
     * Generates a single backup code.
     *
     * Format: XXXX-XXXX (8 characters total, dash for readability)
     * Uses uppercase alphanumeric characters excluding ambiguous ones (0, O, I, 1)
     *
     * @return string Formatted backup code
     * @throws \Exception If random_bytes() fails
     */
    private static function generateSingleCode(): string
    {
        // Character set without ambiguous characters
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $charsLength = strlen($chars);
        
        $part1 = '';
        $part2 = '';
        
        // Generate first part (4 chars)
        for ($i = 0; $i < self::CODE_FORMAT_LENGTH; $i++) {
            $randomIndex = random_int(0, $charsLength - 1);
            $part1 .= $chars[$randomIndex];
        }
        
        // Generate second part (4 chars)
        for ($i = 0; $i < self::CODE_FORMAT_LENGTH; $i++) {
            $randomIndex = random_int(0, $charsLength - 1);
            $part2 .= $chars[$randomIndex];
        }
        
        return $part1 . '-' . $part2;
    }

    /**
     * Hashes an array of backup codes for secure storage.
     *
     * Each code is hashed using bcrypt (PASSWORD_DEFAULT).
     * Returns an associative array with the hash and a used flag.
     *
     * @param array $codes Array of plain-text backup codes
     * @return string JSON-encoded array of hashed codes with metadata
     */
    public static function hashBackupCodes(array $codes): string
    {
        $hashedCodes = [];
        
        foreach ($codes as $code) {
            $hashedCodes[] = [
                'hash' => password_hash($code, PASSWORD_DEFAULT),
                'used' => false
            ];
        }
        
        return json_encode($hashedCodes);
    }

    /**
     * Verifies a backup code against stored hashed codes.
     *
     * Checks if the provided code matches any of the stored hashes
     * and has not been used yet.
     *
     * @param string $providedCode The backup code to verify
     * @param string|null $storedCodesJson JSON string of stored hashed codes
     * @return int|false Index of matched code if valid, false otherwise
     */
    public static function verifyBackupCode(string $providedCode, ?string $storedCodesJson)
    {
        if (empty($storedCodesJson)) {
            return false;
        }
        
        $storedCodes = json_decode($storedCodesJson, true);
        
        if (!is_array($storedCodes)) {
            return false;
        }
        
        // Remove dash for comparison (user might enter with or without)
        $providedCode = strtoupper(str_replace('-', '', $providedCode));
        
        foreach ($storedCodes as $index => $codeData) {
            // Skip already used codes
            if ($codeData['used'] === true) {
                continue;
            }
            
            // Extract the code from hash for comparison
            // We need to format the provided code back to XXXX-XXXX for verification
            $formattedCode = substr($providedCode, 0, 4) . '-' . substr($providedCode, 4, 4);
            
            if (password_verify($formattedCode, $codeData['hash'])) {
                return $index;
            }
        }
        
        return false;
    }

    /**
     * Marks a backup code as used.
     *
     * Updates the JSON structure to mark the code at the specified index as used.
     * Once marked, the code cannot be used again.
     *
     * @param string $storedCodesJson JSON string of stored codes
     * @param int $index Index of the code to mark as used
     * @return string Updated JSON string with code marked as used
     */
    public static function markCodeAsUsed(string $storedCodesJson, int $index): string
    {
        $storedCodes = json_decode($storedCodesJson, true);
        
        if (isset($storedCodes[$index])) {
            $storedCodes[$index]['used'] = true;
        }
        
        return json_encode($storedCodes);
    }

    /**
     * Counts the number of remaining (unused) backup codes.
     *
     * @param string|null $storedCodesJson JSON string of stored codes
     * @return int Number of unused backup codes
     */
    public static function countRemainingCodes(?string $storedCodesJson): int
    {
        if (empty($storedCodesJson)) {
            return 0;
        }
        
        $storedCodes = json_decode($storedCodesJson, true);
        
        if (!is_array($storedCodes)) {
            return 0;
        }
        
        $remaining = 0;
        foreach ($storedCodes as $codeData) {
            if ($codeData['used'] === false) {
                $remaining++;
            }
        }
        
        return $remaining;
    }

    /**
     * Formats backup codes for display to the user.
     *
     * Adds line numbers for easy reference.
     *
     * @param array $codes Array of plain-text backup codes
     * @return string Formatted string with numbered codes
     */
    public static function formatCodesForDisplay(array $codes): string
    {
        $formatted = '';
        foreach ($codes as $index => $code) {
            $formatted .= sprintf("%2d. %s\n", $index + 1, $code);
        }
        return trim($formatted);
    }

    /**
     * Validates if a string looks like a backup code.
     *
     * Checks format without verifying against database.
     * Useful for client-side validation.
     *
     * @param string $code The code to validate
     * @return bool True if format is valid
     */
    public static function isValidFormat(string $code): bool
    {
        // Remove any whitespace
        $code = trim($code);
        
        // Check if it matches XXXX-XXXX or XXXXXXXX pattern
        $pattern = '/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}-?[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/i';
        
        return preg_match($pattern, $code) === 1;
    }
}