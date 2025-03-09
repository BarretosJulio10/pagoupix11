<?php
// Disable error reporting and display
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to prevent any output before headers
ob_start();

// Include required files
require_once 'config.php';
require_once 'includes/Database.php';

// Set JSON content type header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id'])) {
    $db = Database::getInstance();
    $clientId = (int)$_POST['client_id'];
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Get all subscription IDs for this client
        $subscriptions = $db->fetchAll("SELECT id FROM subscriptions WHERE client_id = ?", [$clientId]);
        
        // Delete payments associated with subscriptions
        foreach ($subscriptions as $subscription) {
            $db->delete('payments', 'subscription_id = ?', [$subscription['id']]);
        }
        
        // Delete subscriptions
        $db->delete('subscriptions', 'client_id = ?', [$clientId]);
        
        // Delete single invoices
        $db->delete('single_invoices', 'client_id = ?', [$clientId]);
        
        // Delete collection rules
        $db->delete('collection_rules', 'client_id = ?', [$clientId]);
        
        // Finally delete the client
        $db->delete('clients', 'id = ?', [$clientId]);
        
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