<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Subscription.php';
require_once 'includes/Payment.php';

// Check if subscription ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID da mensalidade não fornecido';
    $_SESSION['flash_type'] = 'danger';
    header('Location: subscriptions.php');
    exit;
}

// Get subscription ID
$subscriptionId = (int)$_GET['id'];

// Initialize models
$subscriptionModel = new Subscription();
$paymentModel = new Payment();

// Get subscription details
$subscription = $subscriptionModel->getById($subscriptionId);

if (!$subscription) {
    $_SESSION['flash_message'] = 'Mensalidade não encontrada';
    $_SESSION['flash_type'] = 'danger';
    header('Location: subscriptions.php');
    exit;
}

// Get payments for this subscription
$payments = $paymentModel->getBySubscriptionId($subscriptionId);

// Page title
$pageTitle = 'Detalhes da Mensalidade - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Detalhes da Mensalidade</h1>
            <p class="text-muted">Informações detalhadas da mensalidade</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="subscriptions.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <a href="subscription_form.php?id=<?php echo $subscription['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Informações da Mensalidade</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Cliente:</strong> <?php echo $subscription['client_name']; ?></p>
                    <p><strong>Valor:</strong> R$ <?php echo number_format($subscription['amount'], 2, ',', '.'); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Data de Vencimento:</strong> <?php echo date('d/m/Y', strtotime($subscription['due_date'])); ?></p>
                    <p>
                        <strong>Status:</strong>
                        <?php
                        $statusClass = [
                            'active' => 'success',
                            'inactive' => 'warning',
                            'cancelled' => 'danger'
                        ][$subscription['status']];
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?>">
                            <?php echo ucfirst($subscription['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <?php if ($subscription['status'] === 'active'): ?>
            <div class="mt-4">
                <button onclick="showPaymentMethodSelection(<?php echo $subscription['id']; ?>)" class="btn btn-success">
                    <i class="fas fa-money-bill-wave"></i> Gerar Pagamento
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Histórico de Pagamentos</h5>
        </div>
        <div class="card-body">
            <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Método</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>R$ <?php echo number_format($payment['amount'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        $methodIcons = [
                                            'pix' => '<i class="fas fa-qrcode"></i> PIX',
                                            'boleto' => '<i class="fas fa-barcode"></i> Boleto',
                                            'credit_card' => '<i class="fas fa-credit-card"></i> Cartão'
                                        ];
                                        echo $methodIcons[$payment['payment_method']] ?? '-';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'failed' => 'danger'
                                        ][$payment['status']];
                                        $statusLabels = [
                                            'pending' => 'Pendente',
                                            'paid' => 'Pago',
                                            'failed' => 'Falhou'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo $statusLabels[$payment['status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="payment_view.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($payment['status'] === 'pending'): ?>
                                        <a href="confirm_manual_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Tem certeza que deseja confirmar este pagamento manualmente? A data de vencimento será atualizada automaticamente.')">
                                            <i class="fas fa-check-circle"></i> Confirmar
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Nenhum pagamento registrado para esta mensalidade.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>