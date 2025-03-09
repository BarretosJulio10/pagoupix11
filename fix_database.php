<?php
require_once 'config.php';
require_once 'includes/Database.php';

echo "Fixing database structure...\n";

// Initialize database connection
$db = Database::getInstance();

// Check if interest_settings table exists
$tableExists = $db->fetchOne("SHOW TABLES LIKE 'interest_settings'");

if (!$tableExists) {
    echo "Creating interest_settings table...\n";
    
    // Create the table
    $sql = "CREATE TABLE interest_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        interest_type ENUM('percentage', 'daily_value') NOT NULL DEFAULT 'percentage',
        interest_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        interest_enabled BOOLEAN DEFAULT FALSE,
        penalty_type ENUM('percentage', 'fixed_value') NOT NULL DEFAULT 'percentage',
        penalty_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        penalty_enabled BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->execute($sql);
    
    // Insert default values
    $sql = "INSERT INTO interest_settings (interest_type, interest_value, interest_enabled, penalty_type, penalty_value, penalty_enabled)
            VALUES ('percentage', 1.00, FALSE, 'percentage', 2.00, FALSE)";
    
    $db->execute($sql);
    
    echo "Table created and default values inserted.\n";
} else {
    echo "interest_settings table already exists.\n";
    
    // Check if there are any records
    $settings = $db->fetchOne("SELECT * FROM interest_settings LIMIT 1");
    
    if (!$settings) {
        echo "No settings found. Inserting default values...\n";
        
        // Insert default values
        $sql = "INSERT INTO interest_settings (interest_type, interest_value, interest_enabled, penalty_type, penalty_value, penalty_enabled)
                VALUES ('percentage', 1.00, FALSE, 'percentage', 2.00, FALSE)";
        
        $db->execute($sql);
        
        echo "Default values inserted.\n";
    } else {
        echo "Settings found. Checking structure...\n";
        
        // Check if penalty_type exists and has the correct values
        $columns = $db->fetchAll("SHOW COLUMNS FROM interest_settings LIKE 'penalty_type'");
        
        if (empty($columns)) {
            echo "Adding penalty_type column...\n";
            
            $sql = "ALTER TABLE interest_settings ADD COLUMN penalty_type ENUM('percentage', 'fixed_value') NOT NULL DEFAULT 'percentage' AFTER interest_enabled";
            $db->execute($sql);
            
            echo "penalty_type column added.\n";
        } else {
            echo "penalty_type column exists.\n";
        }
        
        // Update any NULL values in penalty_type
        $sql = "UPDATE interest_settings SET penalty_type = 'percentage' WHERE penalty_type IS NULL";
        $db->execute($sql);
        
        echo "NULL values in penalty_type updated to 'percentage'.\n";
    }
}

echo "Database structure check completed.\n";

// Check current settings
$settings = $db->fetchOne("SELECT * FROM interest_settings ORDER BY id DESC LIMIT 1");

if ($settings) {
    echo "\nCurrent settings:\n";
    echo "interest_type: " .    ($settings['interest_type']     ?? 'NULL') . "\n";
    echo "interest_value: " .   ($settings['interest_value']    ?? 'NULL') . "\n";
    echo "interest_enabled: " . ($settings['interest_enabled']  ? 'TRUE' : 'FALSE') . "\n";
    echo "penalty_type: " .     ($settings['penalty_type']      ?? 'NULL') . "\n";
    echo "penalty_value: " .    ($settings['penalty_value']     ?? 'NULL') . "\n";
    echo "penalty_enabled: " .  ($settings['penalty_enabled']   ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "No settings found.\n";
}

echo "\nDone!\n";