<?php
require_once 'config.php';
require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Drop existing table if exists
    $db->query("DROP TABLE IF EXISTS client_templates");
    
    // Create the client_templates table
    $sql = "CREATE TABLE client_templates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        client_id INT NOT NULL,
        template_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (template_id) REFERENCES message_templates(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->query($sql);
    
    // Create indexes
    $db->query("CREATE INDEX idx_client_templates_client_id ON client_templates(client_id)");
    $db->query("CREATE INDEX idx_client_templates_template_id ON client_templates(template_id)");
    
    echo "Successfully created client_templates table with indexes.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}