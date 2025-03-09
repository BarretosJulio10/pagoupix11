<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent any output before headers
ob_start();

// Include required files
require_once 'config.php';
require_once 'includes/Database.php';

// Set JSON content type header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_id'])) {
    $db = Database::getInstance();
    $templateId = (int)$_POST['template_id'];
    
    try {
        // Verify template exists before attempting deletion
        $template = $db->fetchOne("SELECT id FROM message_templates WHERE id = ?", [$templateId]);
        if (!$template) {
            throw new Exception("Template not found");
        }

        // Start transaction
        $db->beginTransaction();
        
        // Delete in reverse order of dependencies
        // First, delete collection rules as they depend on the template
        $db->query("DELETE FROM collection_rules WHERE template_id = ?", [$templateId]);
        
        // Delete communication logs that reference this template
        $db->query("DELETE FROM communication_logs WHERE template_id = ?", [$templateId]);
        
        // Delete client template associations
        $db->query("DELETE FROM client_templates WHERE template_id = ?", [$templateId]);
        
        // Finally delete the template itself
        $result = $db->query("DELETE FROM message_templates WHERE id = ?", [$templateId]);
        
        // Commit transaction
        $db->commit();
        
        // Clear any previous output and send success response
        ob_clean();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback on error
        $db->rollback();
        
        // Clear any previous output and send error response
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // Clear any previous output and send invalid request response
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}

// End output buffering and send the response
ob_end_flush();
exit; // Ensure no additional content is sent