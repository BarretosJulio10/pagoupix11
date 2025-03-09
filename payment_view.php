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

// Get payment details
$payment = $paymentModel->getById($paymentId);

if (!$payment) {
    $_SESSION['flash_message'] = 'Pagamento não encontrado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: payments.php');
    exit;
}

// Page title
$pageTitle = 'Detalhes do Pagamento - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Detalhes do Pagamento</h1>
            <p class="text-muted">Informações detalhadas do pagamento</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="payments.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Informações do Pagamento</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Cliente:</strong> <?php echo $payment['client_name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $payment['client_email']; ?></p>
                    <p><strong>Valor:</strong> R$ <?php echo number_format($payment['amount'], 2, ',', '.'); ?></p>
                    <p><strong>Data de Vencimento:</strong> <?php echo date('d/m/Y', strtotime($payment['due_date'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p>
                        <strong>Status:</strong> 
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
                    </p>
                    <p>
                        <strong>Método de Pagamento:</strong>
                        <?php 
                        $methodIcons = [
                            'pix' => '<i class="fas fa-qrcode"></i> PIX',
                            'boleto' => '<i class="fas fa-barcode"></i> Boleto',
                            'credit_card' => '<i class="fas fa-credit-card"></i> Cartão'
                        ];
                        echo $methodIcons[$payment['payment_method']] ?? 'Não definido';
                        ?>
                    </p>
                    <p><strong>Data de Criação:</strong> <?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></p>
                    <p><strong>Data de Pagamento:</strong> <?php echo $payment['paid_at'] ? date('d/m/Y H:i', strtotime($payment['paid_at'])) : 'Não pago'; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($payment['status'] === 'pending'): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Ações</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($payment['payment_link'])): ?>
            <div class="mb-3">
                <label class="form-label">Link para compartilhar:</label>
                <div class="input-group">
                    <input type="text" class="form-control" value="<?php echo $payment['payment_link']; ?>" id="paymentLink" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('paymentLink')">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?php echo $payment['payment_link']; ?>" class="btn btn-primary btn-lg" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Acessar Link de Pagamento
                </a>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="confirm_manual_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-success btn-lg" onclick="return confirm('Tem certeza que deseja confirmar este pagamento manualmente? A data de vencimento será atualizada automaticamente.')">
                    <i class="fas fa-check-circle"></i> Confirmar Pagamento Manual
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    document.execCommand("copy");
    
    // Show feedback
    alert("Link copiado para a área de transferência!");
}
</script>

<?php include 'templates/footer.php'; ?>