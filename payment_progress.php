<?php
require_once 'config.php';
require_once 'includes/Database.php';

// Initialize database connection
$db = Database::getInstance();

// Get payment distribution data with total amounts
$paymentDistribution = $db->fetchAll("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM payments
    GROUP BY status
");

// Format payment distribution data
$paidCount = 0;
$paidAmount = 0;
$overdueCount = 0;
$overdueAmount = 0;

foreach ($paymentDistribution as $item) {
    if ($item['status'] == 'paid') {
        $paidCount += (int)$item['count'];
        $paidAmount += (float)$item['total_amount'];
    } else {
        // Group 'pending' and 'overdue' as 'Em Atraso'
        $overdueCount += (int)$item['count'];
        $overdueAmount += (float)$item['total_amount'];
    }
}

// Calculate total and percentages
$totalCount = $paidCount + $overdueCount;
$paidPercentage = $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0;
$overduePercentage = $totalCount > 0 ? round(($overdueCount / $totalCount) * 100) : 0;

// Prepare data for the chart
$labels = ['Pago', 'Em Atraso'];
$data = [$paidCount, $overdueCount];
$amounts = [$paidAmount, $overdueAmount];
$percentages = [$paidPercentage, $overduePercentage];

// Include header
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Progresso de Pagamentos</h1>
            <p class="text-muted">Visualização da distribuição de pagamentos</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Distribuição de Pagamentos</h5>
                </div>
                <div class="card-body" style="height: 400px;">
                    <canvas id="paymentDistributionChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detalhes dos Pagamentos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Quantidade</th>
                                    <th>Percentual</th>
                                    <th>Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="badge bg-success">Pago</span>
                                    </td>
                                    <td><?php echo $paidCount; ?></td>
                                    <td><?php echo $paidPercentage; ?>%</td>
                                    <td>R$ <?php echo number_format($paidAmount, 2, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">Em Atraso</span>
                                    </td>
                                    <td><?php echo $overdueCount; ?></td>
                                    <td><?php echo $overduePercentage; ?>%</td>
                                    <td>R$ <?php echo number_format($overdueAmount, 2, ',', '.'); ?></td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>Total</strong></td>
                                    <td><strong><?php echo $totalCount; ?></strong></td>
                                    <td><strong>100%</strong></td>
                                    <td><strong>R$ <?php echo number_format($paidAmount + $overdueAmount, 2, ',', '.'); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Payment Distribution Chart (Pie)
new Chart(document.getElementById('paymentDistributionChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            data: <?php echo json_encode($data); ?>,
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
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const percentage = <?php echo json_encode($percentages); ?>[context.dataIndex];
                        const amount = <?php echo json_encode($amounts); ?>[context.dataIndex];
                        const formattedAmount = new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(amount);
                        return `${label}: ${value} (${percentage}%) - ${formattedAmount}`;
                    }
                }
            }
        }
    }
});
</script>

<?php include 'templates/footer.php'; ?>