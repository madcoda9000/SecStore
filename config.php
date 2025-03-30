<?php

/**
 * database credentials
 */
$db = [
    'host' => 'localhost',
    'name' => 'SecStore_dev',
    'user' => 'root',
    'pass' => 'Heineu.9000',
];

/**
 * Logging settings
 */
$logging = [
  'enableSqlLogging' => false,
  'enableRequestLogging' => false,
  'enableAuditLogging' => true,
  'enableMailLogging' => true,
  'enableSystemLogging' => true,
];

/**
 * Application settings
 */
$application = [
  'appUrl' => 'http://localhost:8000',
  'sessionTimeout' => 600,
];

/**
 * CORS: define allowd Hosts
 */
$allowedHosts = [
    'capacitor://localhost',
    'ionic://localhost',
    'http://localhost',
    'http://localhost:4200',
    'http://localhost:8080',
    'http://localhost:8000',
];

/**
 * Brute force settings
 */
$bruteForceSettings = [
  'enableBruteForce' => true,
  'maxAttempts' => 5,
  'lockTime' => 1000,
];

/**
 * Mail configuration
 */
$mail = [
  'host' => 'smtp.gmail.com',
  'username' => 'sascha.heimann@gmail.com',
  'password' => 'nbxcwqpzahpejydh',
  'encryption' => 'tls',
  'port' => 587,
  'fromEmail' => 'sascha.heimann@gmail.com',
  'fromName' => 'SecStore',
  'enableWelcomeMail' => false,
];

// Alle Konfigurationen in einem Array zusammenfassen
return [
    'db' => $db,
    'allowedHosts' => $allowedHosts,
    'bruteForceSettings' => $bruteForceSettings,
    'mail' => $mail,
    'application' => $application,
    'logging' => $logging,
];
