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

// Get monthly subscriptions data with amounts
$monthlySubscriptions = $db->fetchAll("
    SELECT 
        MONTH(due_date) as month,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM payments
    WHERE YEAR(due_date) = YEAR(CURRENT_DATE)
    GROUP BY MONTH(due_date)
    ORDER BY month
");

// Get payment trends data with amounts
$paymentTrends = $db->fetchAll("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM payments
    WHERE status = 'paid'
    AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
    GROUP BY DATE(created_at)
    ORDER BY date
");

// Format data for charts
$response = [
    'distribution' => [
        'labels' => [],
        'data' => [],
        'amounts' => []
    ],
    'monthly' => [
        'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        'data' => array_fill(0, 12, 0),
        'amounts' => array_fill(0, 12, 0)
    ],
    'trends' => [
        'labels' => [],
        'data' => [],
        'amounts' => []
    ]
];

// Format payment distribution data
// Simplify to just 'Pago' and 'Em Atraso' as shown in the image
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

// Add the simplified data
$response['distribution']['labels'] = ['Pago', 'Em Atraso'];
$response['distribution']['data'] = [$paidCount, $overdueCount];
$response['distribution']['amounts'] = [$paidAmount, $overdueAmount];

// Format monthly subscriptions data
foreach ($monthlySubscriptions as $item) {
    $monthIndex = (int)$item['month'] - 1;
    $response['monthly']['data'][$monthIndex] = (int)$item['count'];
    $response['monthly']['amounts'][$monthIndex] = (float)$item['total_amount'];
}

// Format payment trends data
foreach ($paymentTrends as $item) {
    $response['trends']['labels'][] = date('d/m/Y', strtotime($item['date']));
    $response['trends']['data'][] = (int)$item['count'];
    $response['trends']['amounts'][] = (float)$item['total_amount'];
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
echo json_encode($response);