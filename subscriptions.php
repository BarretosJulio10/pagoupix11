<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Subscription.php';

// Initialize subscription model
$subscriptionModel = new Subscription();

// Get all subscriptions
$subscriptions = $subscriptionModel->getAll();

// Page title
$pageTitle = 'Mensalidades - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Mensalidades</h1>
            <p class="text-muted">Gerenciamento de mensalidades</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="subscription_form.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Nova Mensalidade
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if (count($subscriptions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped" id="subscriptionsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $subscription): ?>
                                <tr data-subscription-id="<?php echo $subscription['id']; ?>">
                                    <td><?php echo $subscription['id']; ?></td>
                                    <td data-field="client"><a href="client_view.php?id=<?php echo $subscription['client_id']; ?>"><?php echo $subscription['client_name']; ?></a></td>
                                    <td>R$ <?php echo number_format($subscription['amount'], 2, ',', '.'); ?></td>
                                    <td data-due-date="<?php echo $subscription['due_date']; ?>"><?php echo date('d/m/Y', strtotime($subscription['due_date'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'active' => 'success',
                                            'inactive' => 'warning',
                                            'cancelled' => 'danger'
                                        ][$subscription['status']];
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>" data-status="<?php echo $subscription['status']; ?>">
                                            <?php echo ucfirst($subscription['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="subscription_view.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="subscription_form.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($subscription['status'] === 'active'): ?>
                                            <button onclick="showPaymentMethodSelection(<?php echo $subscription['id']; ?>)" class="btn btn-sm btn-success">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="confirmDelete(<?php echo $subscription['id']; ?>)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Nenhuma mensalidade cadastrada.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<!-- Include filters script -->
<script src="assets/js/filters.js"></script>

<script>
function confirmDelete(subscriptionId) {
    if (confirm('Tem certeza que deseja excluir esta mensalidade? Esta ação não pode ser desfeita.')) {
        fetch('delete_subscription.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'subscription_id=' + subscriptionId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the row from the table
                const row = document.querySelector(`tr[data-subscription-id="${subscriptionId}"]`);
                if (row) {
                    row.remove();
                } else {
                    location.reload();
                }
            } else {
                alert('Erro ao excluir mensalidade: ' + (data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao excluir mensalidade. Por favor, tente novamente.');
        });
    }
}
</script>