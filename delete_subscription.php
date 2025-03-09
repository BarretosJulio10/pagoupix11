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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscription_id'])) {
    $db = Database::getInstance();
    $subscriptionId = (int)$_POST['subscription_id'];
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Delete payments associated with this subscription
        $db->delete('payments', 'subscription_id = ?', [$subscriptionId]);
        
        // Delete communication logs associated with this subscription
        $db->delete('communication_logs', 'subscription_id = ?', [$subscriptionId]);
        
        // Finally delete the subscription
        $db->delete('subscriptions', 'id = ?', [$subscriptionId]);
        
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