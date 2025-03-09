<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Payment.php';

// Check if payment ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID do pagamento não fornecido';
    $_SESSION['flash_type'] = 'danger';
    header('Location: payments.php');
    exit;
}

// Get payment ID
$paymentId = (int)$_GET['id'];

// Initialize payment model
$paymentModel = new Payment();

try {
    // Confirm manual payment and update subscription due date
    $paymentModel->confirmManualPayment($paymentId);
    
    $_SESSION['flash_message'] = 'Pagamento confirmado manualmente e data de vencimento atualizada com sucesso!';
    $_SESSION['flash_type'] = 'success';
    header('Location: payment_view.php?id=' . $paymentId);
    exit;
} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Erro: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header('Location: payment_view.php?id=' . $paymentId);
    exit;
}