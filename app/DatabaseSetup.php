<?php
/**
 * Establishes a connection to the MySQL database and creates the database and users table if they do not exist.
 *
 * @throws PDOException If an error occurs while connecting to the database or creating the database and users table.
 */
try {
    // Verbindung zum MySQL-Server (ohne Datenbank)
    $pdo = new PDO("mysql:host={$db['host']}", $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Prüfen, ob die Datenbank existiert
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$db['name']}'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE DATABASE `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        echo "Datenbank '{$db['name']}' wurde erstellt.\n";
    }

    // Verbindung zur neuen Datenbank
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']}", $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Prüfen, ob die Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `firstname` varchar(255) DEFAULT '',
        `lastname` varchar(255) DEFAULT '',
        `email` varchar(255) NOT NULL,
        `username` varchar(255) NOT NULL,
        `password` varchar(255) NOT NULL,
        `status` int(11) NOT NULL DEFAULT 1,
        `roles` varchar(255) NOT NULL,
        `reset_token` varchar(255) DEFAULT '',
        `reset_token_expires` datetime DEFAULT NULL,
        `mfaStartSetup` int(11) NOT NULL DEFAULT 0,
        `mfaEnabled` int(11) NOT NULL DEFAULT 0,
        `mfaEnforced` int(11) NOT NULL DEFAULT 0,
        `mfaSecret` varchar(2500) NOT NULL DEFAULT '',
        'mfaBackupCodes' TEXT NULL DEFAULT NULL,
        `ldapEnabled` int(11) NOT NULL DEFAULT 0,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        `activeSessionId` varchar(255) DEFAULT '',
        `lastKnownIp` varchar(255) DEFAULT '',
        'verification_token' VARCHAR(255) DEFAULT '',
        'verification_token' VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`),
        UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        //echo "Tabelle 'users' wurde erstellt.\n";
    }

    // Prüfen, ob bereits ein Benutzer existiert
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash("Test1000!", PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (firstname, lastname, email, username, password, status, roles) VALUES ('Super', 'Admin', 'super.admin@test.local', 'super.admin', '{$hashedPassword}', 1, 'Admin')");
        //echo "Admin-Benutzer wurde eingefügt.\n";
    }

    // prüfen ob tabelle failed_logins existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'failed_logins'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE `failed_logins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ip_address` varchar(45) NOT NULL,
            `email` varchar(255) NOT NULL,
            `attempts` int(11) DEFAULT 1,
            `last_attempt` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        //echo "Tabelle 'failed_logins' wurde erstellt.\n";
    }

    // prüfen ob logs tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'logs'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE `logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `datum_zeit` datetime DEFAULT current_timestamp(),
            `type` enum('ERROR','AUDIT','REQUEST','SYSTEM','MAIL','SQL', 'SECURITY') NOT NULL,
            `user` varchar(255) NOT NULL,
            `context` text NOT NULL,
            `message` text NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        //echo "Tabelle 'logs' wurde erstellt.\n";
    }

    // prüfen ob tabelle roles existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE `roles` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `roleName` varchar(255) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        //echo "Tabelle 'roles' wurde erstellt.\n";
    }

    // Prüfen, ob Admin rolle existiert
    $stmt = $pdo->query("SELECT COUNT(*) FROM roles WHERE roleName = 'Admin'");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO roles (roleName) VALUES ('Admin')");
    }

    // Prüfen, ob User rolle existiert
    $stmt = $pdo->query("SELECT COUNT(*) FROM roles WHERE roleName = 'User'");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO roles (roleName) VALUES ('User')");
    }
    // Verbindung schließen
    unset($pdo);
} catch (PDOException $e) {
    die("Fehler beim Initialisieren der Datenbank: " . $e->getMessage());
}
