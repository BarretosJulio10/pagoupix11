<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Payment.php';
require_once 'includes/Subscription.php';

// Check if payment ID and method are provided
if (!isset($_GET['id']) || !isset($_GET['method'])) {
    $_SESSION['flash_message'] = 'Parâmetros inválidos';
    $_SESSION['flash_type'] = 'danger';
    header('Location: subscriptions.php');
    exit;
}

// Get parameters
$subscriptionId = (int)$_GET['id'];
$paymentMethod = $_GET['method'];

// Validate payment method
$validMethods = ['pix', 'boleto', 'credit_card'];
if (!in_array($paymentMethod, $validMethods)) {
    $_SESSION['flash_message'] = 'Método de pagamento inválido';
    $_SESSION['flash_type'] = 'danger';
    header('Location: subscriptions.php');
    exit;
}

// Initialize models
$paymentModel = new Payment();
$subscriptionModel = new Subscription();

// Check if subscription exists
$subscription = $subscriptionModel->getById($subscriptionId);
if (!$subscription) {
    $_SESSION['flash_message'] = 'Mensalidade não encontrada';
    $_SESSION['flash_type'] = 'danger';
    header('Location: subscriptions.php');
    exit;
}

try {
    // Create payment link
    $payment = $paymentModel->createPaymentLink($subscriptionId);
    
    // Update payment method
    $paymentModel->updatePaymentMethod($payment['id'], $paymentMethod);
    
    // Get updated payment
    $payment = $paymentModel->getById($payment['id']);
    
    // Redirect to payment view
    $_SESSION['flash_message'] = 'Link de pagamento gerado com sucesso!';
    $_SESSION['flash_type'] = 'success';
    header('Location: payment_view.php?id=' . $payment['id']);
    exit;
} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Erro: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header('Location: subscriptions.php');
    exit;
}