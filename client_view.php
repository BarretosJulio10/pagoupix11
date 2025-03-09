<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Client.php';
require_once 'includes/Subscription.php';
require_once 'includes/Payment.php';

// Check if client ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID do cliente não fornecido';
    $_SESSION['flash_type'] = 'danger';
    header('Location: clients.php');
    exit;
}

// Get client ID
$clientId = (int)$_GET['id'];

// Initialize models
$clientModel = new Client();
$subscriptionModel = new Subscription();
$paymentModel = new Payment();

// Get client details
$client = $clientModel->getById($clientId);

if (!$client) {
    $_SESSION['flash_message'] = 'Cliente não encontrado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: clients.php');
    exit;
}

// Get client subscriptions
$subscriptions = $subscriptionModel->getByClientId($clientId);

// Get all payments for this client
$payments = $paymentModel->getByClientId($clientId);

// Page title
$pageTitle = 'Detalhes do Cliente - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Detalhes do Cliente</h1>
            <p class="text-muted">Informações detalhadas do cliente</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="clients.php" class="btn btn-secondary" data-bs-toggle="tooltip" title="Voltar para lista de clientes">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <a href="client_form.php?id=<?php echo $client['id']; ?>" class="btn btn-primary" data-bs-toggle="tooltip" title="Editar informações do cliente">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Informações do Cliente</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nome:</strong> <?php echo $client['name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $client['email']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Telefone:</strong> <?php echo $client['phone'] ?? 'Não informado'; ?></p>
                    <p><strong>Documento:</strong> <?php echo $client['document'] ?? 'Não informado'; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Mensalidades do Cliente</h5>
        </div>
        <div class="card-body">
            <?php if (count($subscriptions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $subscription): ?>
                                <tr>
                                    <td>R$ <?php echo number_format($subscription['amount'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($subscription['due_date'])); ?></td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <a href="subscription_view.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($subscription['status'] === 'active'): ?>
                                            <button onclick="showPaymentMethodSelection(<?php echo $subscription['id']; ?>)" class="btn btn-sm btn-success">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Este cliente não possui mensalidades cadastradas.
                </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="subscription_form.php?client_id=<?php echo $client['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Adicionar Mensalidade
                </a>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Histórico de Pagamentos</h5>
        </div>
        <div class="card-body">
            <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Método</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                                    <td>R$ <?php echo number_format($payment['amount'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php 
                                        switch ($payment['payment_method']) {
                                            case 'pix':
                                                echo 'PIX';
                                                break;
                                            case 'boleto':
                                                echo 'Boleto';
                                                break;
                                            case 'credit_card':
                                                echo 'Cartão de Crédito';
                                                break;
                                            default:
                                                echo 'Não definido';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        switch ($payment['status']) {
                                            case 'paid':
                                                echo '<span class="badge bg-success">Pago</span>';
                                                break;
                                            case 'pending':
                                                echo '<span class="badge bg-warning">Pendente</span>';
                                                break;
                                            case 'failed':
                                                echo '<span class="badge bg-danger">Falhou</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="payment_view.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver detalhes do pagamento">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">Nenhum pagamento registrado para este cliente.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>