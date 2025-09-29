<?php

namespace App\Utils;

use InvalidArgumentException;

/**
 * Central Input Validation and Sanitization Class
 *
 * Provides comprehensive validation rules and sanitization methods
 * for all user input across the application.
 */
class InputValidator
{
    // Validation rules constants
    public const RULE_REQUIRED = 'required';
    public const RULE_EMAIL = 'email';
    public const RULE_MIN_LENGTH = 'min_length';
    public const RULE_MAX_LENGTH = 'max_length';
    public const RULE_NUMERIC = 'numeric';
    public const RULE_INTEGER = 'integer';
    public const RULE_BOOLEAN = 'boolean';
    public const RULE_ALPHA = 'alpha';
    public const RULE_ALPHANUMERIC = 'alphanumeric';
    public const RULE_PASSWORD_STRONG = 'password_strong';
    public const RULE_USERNAME = 'username';
    public const RULE_OTP = 'otp';
    public const RULE_URL = 'url';
    public const RULE_ROLE_NAME = 'role_name';
    public const RULE_IN_LIST = 'in_list';
    public const RULE_NOT_IN_LIST = 'not_in_list';
    public const RULE_REGEX = 'regex';

    /**
     * Validate and sanitize input data based on provided rules
     *
     * @param array $rules Validation rules for each field
     * @param array $data Input data to validate
     * @param bool $strict If true, throw exception on validation failure
     * @return array Validated and sanitized data
     * @throws InvalidArgumentException When validation fails in strict mode
     */
    public static function validateAndSanitize(array $rules, array $data, bool $strict = true): array
    {
        $validated = [];
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            try {
                $validated[$field] = self::validateField($field, $data[$field] ?? null, $fieldRules);
            } catch (InvalidArgumentException $e) {
                $errors[$field] = $e->getMessage();
                
                if ($strict) {
                    throw new InvalidArgumentException("Validation failed for field '{$field}': " . $e->getMessage());
                }
            }
        }

        if (!empty($errors) && $strict) {
            throw new InvalidArgumentException("Validation failed: " . json_encode($errors));
        }

        return $validated;
    }

    /**
     * Validate a single field with its rules
     *
     * @param string $fieldName Name of the field being validated
     * @param mixed $value Value to validate
     * @param array $rules Array of validation rules
     * @return mixed Validated and sanitized value
     * @throws InvalidArgumentException When validation fails
     */
    private static function validateField(string $fieldName, $value, array $rules)
    {
        // Handle required validation first
        if (in_array(self::RULE_REQUIRED, $rules) && self::isEmpty($value)) {
            throw new InvalidArgumentException("Field '{$fieldName}' is required");
        }

        // If value is empty and not required, return sanitized empty value
        if (self::isEmpty($value) && !in_array(self::RULE_REQUIRED, $rules)) {
            return self::sanitizeEmpty($value);
        }

        // Sanitize the value first
        $sanitized = self::sanitizeValue($value);

        // Apply validation rules
        foreach ($rules as $rule) {
            if (is_array($rule)) {
                // Rule with parameters (e.g., ['min_length' => 8])
                $ruleName = key($rule);
                $parameters = $rule[$ruleName];
                self::applyRule($sanitized, $ruleName, $parameters, $fieldName);
            } else {
                // Simple rule (e.g., 'required', 'email')
                self::applyRule($sanitized, $rule, null, $fieldName);
            }
        }

        return $sanitized;
    }

    /**
     * Apply a specific validation rule
     *
     * @param mixed $value Value to validate
     * @param string $rule Rule name
     * @param mixed $parameters Rule parameters
     * @param string $fieldName Field name for error messages
     * @throws InvalidArgumentException When rule validation fails
     */
    private static function applyRule($value, string $rule, $parameters, string $fieldName): void
    {
        switch ($rule) {
            case self::RULE_EMAIL:
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must be a valid email address");
                }
                break;

            case self::RULE_MIN_LENGTH:
                if (strlen($value) < $parameters) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must be at least {$parameters} characters long");
                }
                break;

            case self::RULE_MAX_LENGTH:
                if (strlen($value) > $parameters) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must not exceed {$parameters} characters");
                }
                break;

            case self::RULE_NUMERIC:
                if (!is_numeric($value)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must be numeric");
                }
                break;

            case self::RULE_INTEGER:
                if (!filter_var($value, FILTER_VALIDATE_INT)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must be an integer");
                }
                break;

            case self::RULE_BOOLEAN:
                if (!is_bool($value) && !in_array($value, ['0', '1', 'true', 'false', 0, 1], true)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must be a boolean value");
                }
                break;

            case self::RULE_ALPHA:
                if (!ctype_alpha($value)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must contain only letters");
                }
                break;

            case self::RULE_ALPHANUMERIC:
                if (!ctype_alnum($value)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must contain only letters and numbers");
                }
                break;

            case self::RULE_PASSWORD_STRONG:
                self::validateStrongPassword($value, $fieldName);
                break;

            case self::RULE_USERNAME:
                self::validateUsername($value, $fieldName);
                break;

            case self::RULE_OTP:
                self::validateOtp($value, $fieldName);
                break;

            case self::RULE_URL:
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must be a valid URL");
                }
                break;

            case self::RULE_ROLE_NAME:
                self::validateRoleName($value, $fieldName);
                break;

            case self::RULE_IN_LIST:
                if (!in_array($value, $parameters, true)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' must be one of: " . implode(', ', $parameters));
                }
                break;

            case self::RULE_NOT_IN_LIST:
                if (in_array($value, $parameters, true)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' cannot be one of: " . implode(', ', $parameters));
                }
                break;

            case self::RULE_REGEX:
                if (!preg_match($parameters, $value)) {
                    throw new InvalidArgumentException("Field '{$fieldName}' format is invalid");
                }
                break;
        }
    }

    /**
     * Validate strong password requirements
     *
     * @param string $password Password to validate
     * @param string $fieldName Field name for error messages
     * @throws InvalidArgumentException When password is not strong enough
     */
    private static function validateStrongPassword(string $password, string $fieldName): void
    {
        if (strlen($password) < 12) {
            throw new InvalidArgumentException("Password must be at least 12 characters long");
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException("Password must contain at least one uppercase letter");
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException("Password must contain at least one lowercase letter");
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException("Password must contain at least one number");
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            throw new InvalidArgumentException("Password must contain at least one special character");
        }
    }

    /**
     * Validate username format
     *
     * @param string $username Username to validate
     * @param string $fieldName Field name for error messages
     * @throws InvalidArgumentException When username format is invalid
     */
    private static function validateUsername(string $username, string $fieldName): void
    {
        if (strlen($username) < 3) {
            throw new InvalidArgumentException("Username must be at least 3 characters long");
        }

        if (strlen($username) > 50) {
            throw new InvalidArgumentException("Username must not exceed 50 characters");
        }

        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            throw new InvalidArgumentException("Username can only contain letters, numbers, dots, hyphens and underscores");
        }

        if (preg_match('/^[._-]|[._-]$/', $username)) {
            throw new InvalidArgumentException("Username cannot start or end with dots, hyphens or underscores");
        }
    }

    /**
     * Validate OTP code format
     *
     * @param string $otp OTP code to validate
     * @param string $fieldName Field name for error messages
     * @throws InvalidArgumentException When OTP format is invalid
     */
    private static function validateOtp(string $otp, string $fieldName): void
    {
        if (!preg_match('/^\d{6}$/', $otp)) {
            throw new InvalidArgumentException("OTP must be exactly 6 digits");
        }
    }

    /**
     * Validate role name format
     *
     * @param string $roleName Role name to validate
     * @param string $fieldName Field name for error messages
     * @throws InvalidArgumentException When role name format is invalid
     */
    private static function validateRoleName(string $roleName, string $fieldName): void
    {
        if (strlen($roleName) < 2) {
            throw new InvalidArgumentException("Role name must be at least 2 characters long");
        }

        if (strlen($roleName) > 50) {
            throw new InvalidArgumentException("Role name must not exceed 50 characters");
        }

        if (!preg_match('/^[a-zA-Z0-9\s_-]+$/', $roleName)) {
            throw new InvalidArgumentException("Role name can only contain letters, numbers, spaces, hyphens and underscores");
        }
    }

    /**
     * Sanitize input value
     *
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    private static function sanitizeValue($value)
    {
        if (is_string($value)) {
            // Trim whitespace
            $value = trim($value);
            
            // Remove null bytes
            $value = str_replace("\0", '', $value);
            
            // HTML encode special characters
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $value;
    }

    /**
     * Handle empty values
     *
     * @param mixed $value Value to check
     * @return mixed Sanitized empty value
     */
    private static function sanitizeEmpty($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return $value;
    }

    /**
     * Check if a value is considered empty
     *
     * @param mixed $value Value to check
     * @return bool True if value is empty
     */
    private static function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    // Predefined validation rule sets for common use cases

    /**
     * Get validation rules for user login
     *
     * @return array Validation rules
     */
    public static function getLoginRules(): array
    {
        return [
            'username' => [self::RULE_REQUIRED, self::RULE_USERNAME],
            'password' => [self::RULE_REQUIRED, [self::RULE_MIN_LENGTH => 1]]
        ];
    }

    /**
     * Get validation rules for user registration
     *
     * @return array Validation rules
     */
    public static function getRegistrationRules(): array
    {
        return [
            'username' => [self::RULE_REQUIRED, self::RULE_USERNAME],
            'email' => [self::RULE_REQUIRED, self::RULE_EMAIL, [self::RULE_MAX_LENGTH => 255]],
            'password' => [self::RULE_REQUIRED, self::RULE_PASSWORD_STRONG]
        ];
    }

    /**
     * Get validation rules for password reset
     *
     * @return array Validation rules
     */
    public static function getPasswordResetRules(): array
    {
        return [
            'password' => [self::RULE_REQUIRED, self::RULE_PASSWORD_STRONG],
            'passwordConfirm' => [self::RULE_REQUIRED],
            'token' => [self::RULE_REQUIRED, [self::RULE_MIN_LENGTH => 32]]
        ];
    }

    /**
     * Get validation rules for 2FA verification
     *
     * @return array Validation rules
     */
    public static function get2FAVerificationRules(): array
    {
        return [
            'otp' => [self::RULE_REQUIRED, self::RULE_OTP]
        ];
    }

    /**
     * Get validation rules for user creation/update by admin
     *
     * @return array Validation rules
     */
    public static function getAdminUserRules(): array
    {
        return [
            'username' => [self::RULE_REQUIRED, self::RULE_USERNAME],
            'email' => [self::RULE_REQUIRED, self::RULE_EMAIL, [self::RULE_MAX_LENGTH => 255]],
            'roles' => [self::RULE_REQUIRED, [self::RULE_MIN_LENGTH => 1]],
            'enabled' => [self::RULE_BOOLEAN],
            'mfaEnabled' => [self::RULE_BOOLEAN],
            'mfaEnforced' => [self::RULE_BOOLEAN]
        ];
    }

    /**
     * Get validation rules for role management
     *
     * @return array Validation rules
     */
    public static function getRoleRules(): array
    {
        return [
            'roleName' => [self::RULE_REQUIRED, self::RULE_ROLE_NAME]
        ];
    }

    /**
     * Get validation rules for email change
     *
     * @return array Validation rules
     */
    public static function getEmailChangeRules(): array
    {
        return [
            'newEmail' => [self::RULE_REQUIRED, self::RULE_EMAIL, [self::RULE_MAX_LENGTH => 255]]
        ];
    }

    /**
     * Get validation rules for password change
     *
     * @return array Validation rules
     */
    public static function getPasswordChangeRules(): array
    {
        return [
            'currentPassword' => [self::RULE_REQUIRED, [self::RULE_MIN_LENGTH => 1]],
            'newPassword' => [self::RULE_REQUIRED, self::RULE_PASSWORD_STRONG],
        ];
    }
}
