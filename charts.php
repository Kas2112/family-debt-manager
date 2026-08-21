<?php
require_once 'config.php';
require_once 'functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_in_user($pdo);
$total_debt = get_total_debt($pdo);
$debts = get_all_debts($pdo);
$distribution = get_debt_distribution($pdo);
$history = get_debt_history_data($pdo);
$summary = get_debt_summary($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charts - Family Debt Manager</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto p-4 md:p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">📊 Debt Analytics</h1>
            <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                ← Back
            </a>
        </div>

        <?php if (count($debts) > 0): ?>
        
        <!-- Charts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Pie Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-gray-700 mb-4">Debt Distribution</h3>
                <canvas id="pieChart" height="250"></canvas>
            </div>
            
            <!-- Bar Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-gray-700 mb-4">Debt Balances</h3>
                <canvas id="barChart" height="250"></canvas>
            </div>
            
            <!-- Payment History Chart -->
            <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
                <h3 class="font-bold text-gray-700 mb-4">Payment History (Last 12 Months)</h3>
                <canvas id="lineChart" height="200"></canvas>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-sm text-gray-500">Total Debt</p>
                <p class="text-2xl font-bold text-red-600"><?php echo format_currency($total_debt); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-sm text-gray-500">Number of Debts</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo count($debts); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-sm text-gray-500">Highest Rate</p>
                <p class="text-2xl font-bold text-orange-600"><?php echo $summary['highest_rate']; ?>%</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-sm text-gray-500">Smallest Debt</p>
                <p class="text-2xl font-bold text-green-600"><?php echo format_currency($summary['smallest_balance']); ?></p>
            </div>
        </div>

        <?php else: ?>
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <div class="text-6xl mb-4">📊</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">No Data to Chart!</h2>
            <p class="text-gray-600 mb-4">Add some debts to see your analytics.</p>
            <a href="add-debt.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition inline-block">
                ➕ Add Your First Debt
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (count($debts) > 0): ?>
    <script>
        // Prepare data
        const labels = <?php echo json_encode(array_column($distribution, 'name')); ?>;
        const balances = <?php echo json_encode(array_column($distribution, 'balance')); ?>;
        const colors = ['#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6'];

        // Pie Chart
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: balances,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Balance (₦)',
                    data: balances,
                    backgroundColor: colors.slice(0, labels.length),
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Line Chart (Payment History)
        <?php if (count($history) > 0): ?>
        const historyLabels = <?php echo json_encode(array_reverse(array_column($history, 'month'))); ?>;
        const historyData = <?php echo json_encode(array_reverse(array_column($history, 'total_paid'))); ?>;
        
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: historyLabels,
                datasets: [{
                    label: 'Monthly Payments (₦)',
                    data: historyData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
    <?php endif; ?>
</body>
</html>