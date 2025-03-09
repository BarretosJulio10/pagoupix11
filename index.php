<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Client.php';
require_once 'includes/Subscription.php';
require_once 'includes/Payment.php';
require_once 'includes/SingleInvoice.php';

// Initialize models
$clientModel = new Client();
$subscriptionModel = new Subscription();
$paymentModel = new Payment();
$singleInvoiceModel = new SingleInvoice();

// Get dashboard data
$totalClients = count($clientModel->getAll());
$overdueSubscriptions = $subscriptionModel->getOverdue();
$dueSoonSubscriptions = $subscriptionModel->getDueSoon();
$recentPayments = $paymentModel->getAll();
$overdueSingleInvoices = $singleInvoiceModel->getOverdue();

// Limit recent payments to 5
if (count($recentPayments) > 5) {
    $recentPayments = array_slice($recentPayments, 0, 5);
}

// Page title
$pageTitle = 'Dashboard - ' . APP_NAME;

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Dashboard</h1>
            <p class="text-muted">Bem-vindo ao sistema de gerenciamento de mensalidades</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Clientes</h5>
                    <h2><?php echo $totalClients; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Mensalidades Vencidas</h5>
                    <h2><?php echo count($overdueSubscriptions) + count($overdueSingleInvoices); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Vencendo em Breve</h5>
                    <h2><?php echo count($dueSoonSubscriptions); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Pagamentos Recentes</h5>
                    <h2><?php echo count($recentPayments); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Progresso de Pagamentos</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="paymentDistributionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Mensalidades por Mês</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="monthlySubscriptionsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tendência de Pagamentos</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="paymentTrendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Subscriptions -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Mensalidades Vencidas</h5>
                </div>
                <div class="card-body">
                    <?php if (count($overdueSubscriptions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Valor</th>
                                        <th>Vencimento</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdueSubscriptions as $subscription): ?>
                                        <tr>
                                            <td><?php echo $subscription['id']; ?></td>
                                            <td><a href="client_view.php?id=<?php echo $subscription['client_id']; ?>"><?php echo $subscription['client_name']; ?></a></td>
                                            <td>R$ <?php echo number_format($subscription['amount'], 2, ',', '.'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($subscription['due_date'])); ?></td>
                                            <td>
                                                <a href="subscription_detail.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver detalhes da mensalidade"><i class="fas fa-info-circle"></i></a>
                                                <a href="send_reminder.php?id=<?php echo $subscription['id']; ?>&type=overdue" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Enviar lembrete de pagamento"><i class="fas fa-bell"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">Não há mensalidades vencidas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Due Soon Subscriptions -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Mensalidades a Vencer</h5>
                </div>
                <div class="card-body">
                    <?php if (count($dueSoonSubscriptions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Valor</th>
                                        <th>Vencimento</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dueSoonSubscriptions as $subscription): ?>
                                        <tr>
                                            <td><?php echo $subscription['id']; ?></td>
                                            <td><a href="client_view.php?id=<?php echo $subscription['client_id']; ?>"><?php echo $subscription['client_name']; ?></a></td>
                                            <td>R$ <?php echo number_format($subscription['amount'], 2, ',', '.'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($subscription['due_date'])); ?></td>
                                            <td>
                                                <a href="subscription_detail.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver detalhes da mensalidade"><i class="fas fa-info-circle"></i></a>
                                                <a href="send_reminder.php?id=<?php echo $subscription['id']; ?>&type=reminder" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Enviar lembrete de pagamento"><i class="fas fa-bell"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">Não há mensalidades a vencer nos próximos dias.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Payments -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Pagamentos Recentes</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recentPayments) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Valor</th>
                                        <th>Método</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPayments as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['id']; ?></td>
                                            <td><a href="client_view.php?id=<?php echo $payment['client_id']; ?>"><?php echo $payment['client_name']; ?></a></td>
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
                                            <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">Não há pagamentos recentes.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fetch dashboard data
fetch('ajax_dashboard_data.php')
    .then(response => response.json())
    .then(data => {
        // Payment Distribution Chart (Pie)
        new Chart(document.getElementById('paymentDistributionChart'), {
            type: 'pie',
            data: {
                labels: data.distribution.labels,
                datasets: [{
                    data: data.distribution.data,
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${data.distribution.amounts[context.dataIndex].toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'})}`;
                            }
                        }
                    }
                }
            }
        });

        // Monthly Subscriptions Chart (Bar)
        new Chart(document.getElementById('monthlySubscriptionsChart'), {
            type: 'bar',
            data: {
                labels: data.monthly.labels,
                datasets: [{
                    label: 'Valor Total',
                    data: data.monthly.amounts,
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Valor Total: R$ ${context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                            }
                        }
                    }
                }
            }
        });

        // Payment Trends Chart (Line)
        new Chart(document.getElementById('paymentTrendsChart'), {
            type: 'line',
            data: {
                labels: data.trends.labels,
                datasets: [{
                    label: 'Total de Pagamentos',
                    data: data.trends.amounts,
                    borderColor: '#ffc107',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Total de Pagamentos: R$ ${context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                            }
                        }
                    }
                }
            }
        });
    })
    .catch(error => console.error('Error loading dashboard data:', error));
</script>
<?php include 'templates/footer.php'; ?>