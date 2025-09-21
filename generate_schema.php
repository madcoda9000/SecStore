<?php
/**
 * Schema Export Tool für SecStore
 * 
 * Exportiert das aktuelle Datenbankschema als SQL-Datei
 * Verwendung: php generate_schema.php
 */

// Config laden
if (!file_exists('config.php')) {
    die("Error: config.php not found. Please run this script from the SecStore root directory.\n");
}

$config = include 'config.php';

if (!isset($config['db'])) {
    die("Error: Database configuration not found in config.php\n");
}

try {
    // Datenbankverbindung
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']}", 
        $config['db']['user'], 
        $config['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "🔍 Extracting schema from database '{$config['db']['name']}'...\n";

    // Alle Tabellen abrufen
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        die("Error: No tables found in database.\n");
    }

    echo "📋 Found tables: " . implode(', ', $tables) . "\n";

    // Schema-Datei erstellen
    $schemaFile = 'database/schema.sql';
    $dataFile = 'database/default_data.sql';
    
    // Verzeichnis erstellen falls nicht vorhanden
    if (!is_dir('database')) {
        mkdir('database', 0755, true);
        echo "📁 Created database/ directory\n";
    }

    // Schema-Header
    $schemaContent = "-- SecStore Database Schema\n";
    $schemaContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $schemaContent .= "-- Database: {$config['db']['name']}\n";
    $schemaContent .= "-- Description: Complete database structure for SecStore\n\n";
    $schemaContent .= "-- Note: This file contains only the structure (CREATE TABLE statements)\n";
    $schemaContent .= "-- For default data, see default_data.sql\n\n";

    $dataContent = "-- SecStore Default Data\n";
    $dataContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $dataContent .= "-- Database: {$config['db']['name']}\n";
    $dataContent .= "-- Description: Default roles and admin user for SecStore\n\n";

    // Jede Tabelle verarbeiten
    foreach ($tables as $table) {
        echo "🔧 Processing table: {$table}\n";
        
        // CREATE TABLE Statement abrufen
        $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $createTable = $stmt->fetch();
        
        $schemaContent .= "-- ===================================\n";
        $schemaContent .= "-- Table: {$table}\n";
        $schemaContent .= "-- ===================================\n";
        $schemaContent .= $createTable['Create Table'] . ";\n\n";

        // Für bestimmte Tabellen Default-Daten exportieren
        if (in_array($table, ['roles', 'users'])) {
            $dataContent .= "-- ===================================\n";
            $dataContent .= "-- Default data for: {$table}\n";
            $dataContent .= "-- ===================================\n";
            
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll();
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, array_values($row));
                    
                    $valueList = implode(', ', $values);
                    $dataContent .= "INSERT INTO `{$table}` ({$columnList}) VALUES ({$valueList});\n";
                }
                $dataContent .= "\n";
            }
        }
    }

    // Zusätzliche Indexes und Constraints hinzufügen
    $schemaContent .= "-- ===================================\n";
    $schemaContent .= "-- Additional Indexes for Performance\n";
    $schemaContent .= "-- ===================================\n";
    $schemaContent .= "-- These indexes improve query performance\n";
    $schemaContent .= "-- but are not strictly required for basic functionality\n\n";

    // Performance-Indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS `idx_users_status` ON `users` (`status`);",
        "CREATE INDEX IF NOT EXISTS `idx_users_roles` ON `users` (`roles`);", 
        "CREATE INDEX IF NOT EXISTS `idx_users_created_at` ON `users` (`created_at`);",
        "CREATE INDEX IF NOT EXISTS `idx_logs_type_date` ON `logs` (`type`, `datum_zeit`);",
        "CREATE INDEX IF NOT EXISTS `idx_logs_user_date` ON `logs` (`user`, `datum_zeit`);",
        "CREATE INDEX IF NOT EXISTS `idx_failed_logins_ip_time` ON `failed_logins` (`ip_address`, `last_attempt`);",
    ];

    foreach ($indexes as $index) {
        $schemaContent .= $index . "\n";
    }

    // Security Notes hinzufügen
    $dataContent .= "-- ===================================\n";
    $dataContent .= "-- 🔐 SECURITY NOTES\n";
    $dataContent .= "-- ===================================\n";
    $dataContent .= "-- \n";
    $dataContent .= "-- Default Admin Credentials:\n";
    $dataContent .= "-- Username: super.admin\n";
    $dataContent .= "-- Password: Test1000!\n";
    $dataContent .= "-- Email: super.admin@test.local\n";
    $dataContent .= "-- \n";
    $dataContent .= "-- ⚠️  IMPORTANT: Change the admin password immediately after first login!\n";
    $dataContent .= "-- \n";
    $dataContent .= "-- Production Recommendations:\n";
    $dataContent .= "-- 1. Change admin password\n";
    $dataContent .= "-- 2. Update admin email to real address\n";
    $dataContent .= "-- 3. Enable 2FA for admin accounts\n";
    $dataContent .= "-- 4. Create additional admin users\n";
    $dataContent .= "-- 5. Consider disabling default admin after setup\n";

    // Dateien schreiben
    file_put_contents($schemaFile, $schemaContent);
    file_put_contents($dataFile, $dataContent);

    echo "✅ Schema exported to: {$schemaFile}\n";
    echo "✅ Default data exported to: {$dataFile}\n";
    echo "\n📖 Usage:\n";
    echo "   mysql -u username -p database_name < {$schemaFile}\n";
    echo "   mysql -u username -p database_name < {$dataFile}\n";
    echo "\n🎉 Schema export completed successfully!\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}