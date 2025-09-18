<?php

namespace App\Utils;

use InvalidArgumentException;

/**
 * Class Name: InputValidator
 *
 * Hilfsklasse zur Validierung und Sanitisierung von Benutzereingaben.
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2024-06-10
 *
 * Änderungen:
 * - 1.0 (2024-06-10): Erstellt.
 */
class InputValidator {

    /**
     * Validate and sanitize login inputs.
     *
     * @param string $username
     * @param string $password
     * @return array
     * @throws InvalidArgumentException
     */   
    public static function validateLogin($username, $password) {
        // Length checks
        if (strlen($username) > 255 || strlen($password) > 255) {
            throw new InvalidArgumentException('Input too long');
        }
        
        // XSS Prevention
        $username = htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8');
        
        // Format validation
        if (empty($username) || empty($password)) {
            throw new InvalidArgumentException('Empty fields not allowed');
        }
        
        return ['username' => $username, 'password' => $password];
    }

    /**
     * Validate and sanitize registration inputs.
     *
     * @param string $username
     * @param string $password
     * @param string firstName
     * @param string lastName
     * @param string $email
     * @return array
     * @throws InvalidArgumentException
     */   
    public static function validateRegistration($username, $password, $email, $firstName, $lastName) {
        // Length checks
        if (strlen($username) > 255 || strlen($password) > 255 || strlen($email) > 255 || strlen($firstName) > 255 || strlen($lastName) > 255) {
            throw new InvalidArgumentException('Input too long');       
        }
        // XSS Prevention
        $username = htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars(trim($email), ENT_QUOTES, 'UTF-8');
        $firstName = htmlspecialchars(trim($firstName), ENT_QUOTES, 'UTF-8');
        $lastName = htmlspecialchars(trim($lastName), ENT_QUOTES, 'UTF-8'); 
        // Format validation
        if (empty($username) || empty($password) || empty($email) || empty($firstName) || empty($lastName)) {
            throw new InvalidArgumentException('Empty fields not allowed');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format'); 
        }

        if (strlen($password) < 12 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            throw new InvalidArgumentException('Password too weak');
        }

        return ['username' => $username, 'password' => $password, 'email' => $email, 'firstName' => $firstName, 'lastName' => $lastName];
    }

    /**
     * Validate and sanitize OTP input.
     *
     * @param string $otp
     * @return string
     * @throws InvalidArgumentException
     */
    public static function validateOtp($otp) {
        // Length check
        if (strlen($otp) != 6) {
            throw new InvalidArgumentException('Invalid OTP length');       
        }
        // XSS Prevention
        $otp = htmlspecialchars(trim($otp), ENT_QUOTES, 'UTF-8');
        // Format validation
        if (!preg_match('/^\d{6}$/', $otp)) {
            throw new InvalidArgumentException('Invalid OTP format'); 
        }
        return $otp;
    }


    /**
     * Validate and sanitize email input.
     *
     * @param string $email
     * @return string
     * @throws InvalidArgumentException
     */
    public static function validateEmail($email) {
        // Length check
        if (strlen($email) > 255) {
            throw new InvalidArgumentException('Input too long');       
        }
        // XSS Prevention
        $email = htmlspecialchars(trim($email), ENT_QUOTES, 'UTF-8');
        // Format validation
        if (empty($email)) {
            throw new InvalidArgumentException('Empty fields not allowed');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format'); 
        }
        return $email;
    }

    /**
     * Validate and sanitize reset password inputs.
     *
     * @param string $token
     * @param string $newPassword
     * @return array
     * @throws InvalidArgumentException
     */
    public static function validateResetPassword($token, $newPassword) {
        // Length checks
        if (strlen($token) > 255 || strlen($newPassword) > 255) {
            throw new InvalidArgumentException('Input too long');       
        }
        // XSS Prevention
        $token = htmlspecialchars(trim($token), ENT_QUOTES, 'UTF-8');
        // Format validation
        if (empty($token) || empty($newPassword)) {
            throw new InvalidArgumentException('Empty fields not allowed');
        }
        if (strlen($newPassword) < 12 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
            throw new InvalidArgumentException('Password too weak');
        }
        return ['token' => $token, 'newPassword' => $newPassword];
    }   
}