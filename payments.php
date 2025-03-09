<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Payment.php';

// Initialize payment model
$paymentModel = new Payment();

// Get all payments
$payments = $paymentModel->getAll();

// Page title
$pageTitle = 'Pagamentos - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Pagamentos</h1>
            <p class="text-muted">Histórico de pagamentos</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Data de Vencimento</th>
                                <th>Método</th>
                                <th>Status</th>
                                <th>Data de Pagamento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td data-field="client"><a href="client_view.php?id=<?php echo $payment['client_id']; ?>"><?php echo $payment['client_name']; ?></a></td>
                                    <td>R$ <?php echo number_format($payment['amount'], 2, ',', '.'); ?></td>
                                    <td data-due-date="<?php echo $payment['due_date']; ?>"><?php echo date('d/m/Y', strtotime($payment['due_date'])); ?></td>
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
                                        <span class="badge bg-<?php echo $statusClass; ?>" data-status="<?php echo $payment['status']; ?>">
                                            <?php echo $statusLabels[$payment['status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $payment['paid_at'] ? date('d/m/Y H:i', strtotime($payment['paid_at'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <a href="payment_view.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <a href="payment_link.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-link"></i>
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
                    Nenhum pagamento registrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<!-- Include filters script -->
<script src="assets/js/filters.js"></script>