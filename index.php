<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_in_user($pdo);
$total_debt = get_total_debt($pdo);
$debts = get_all_debts($pdo);
$avalanche = get_avalanche_target($pdo);
$snowball = get_snowball_target($pdo);
$summary = get_debt_summary($pdo);
$recent_payments = get_payment_history($pdo);
$reminders = get_payment_reminders($pdo);
$upcoming_count = count($reminders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['full_name']); ?>'s Dashboard</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('Service Worker registered successfully');
                    })
                    .catch(function(error) {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto p-4 md:p-8">
        <!-- Header -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                    👋 <?php echo htmlspecialchars($user['full_name']); ?>
                </h1>
                <p class="text-gray-600">
                    Total Debt: <span class="text-2xl font-bold text-red-600"><?php echo format_currency($total_debt); ?></span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="add-debt.php" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-1">
                    ➕ Add Debt
                </a>
                <a href="charts.php" class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 transition text-sm flex items-center gap-1">
                    📊 Analytics
                </a>
                <a href="calculator.php" class="bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-1">
                    📈 Calculator
                </a>
                <a href="reminders.php" class="bg-yellow-600 text-white px-3 py-2 rounded-lg hover:bg-yellow-700 transition text-sm flex items-center gap-1 relative">
                    🔔 Reminders
                    <?php if ($upcoming_count > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        <?php echo $upcoming_count; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <?php if ($_SESSION['is_admin'] == 1): ?>
                <a href="family-dashboard.php" class="bg-pink-600 text-white px-3 py-2 rounded-lg hover:bg-pink-700 transition text-sm flex items-center gap-1">
                    👨‍👩‍👧‍👦 Family View
                </a>
                <?php endif; ?>
                <a href="logout.php" class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition text-sm flex items-center gap-1">
                    🚪 Logout
                </a>
            </div>
        </div>

        <?php if (count($debts) > 0): ?>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
            <div class="bg-white p-3 md:p-4 rounded-lg shadow dashboard-card">
                <h3 class="text-xs md:text-sm font-medium text-gray-500">Total Minimum</h3>
                <p class="text-lg md:text-2xl font-bold text-gray-800"><?php echo format_currency($summary['total_minimum']); ?></p>
                <p class="text-xs text-gray-500">per month</p>
            </div>
            <div class="bg-white p-3 md:p-4 rounded-lg shadow dashboard-card">
                <h3 class="text-xs md:text-sm font-medium text-gray-500">Highest Interest</h3>
                <p class="text-lg md:text-2xl font-bold text-red-600"><?php echo $summary['highest_rate']; ?>%</p>
                <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($summary['highest_rate_name']); ?></p>
            </div>
            <div class="bg-white p-3 md:p-4 rounded-lg shadow dashboard-card">
                <h3 class="text-xs md:text-sm font-medium text-gray-500">Smallest Balance</h3>
                <p class="text-lg md:text-2xl font-bold text-green-600"><?php echo format_currency($summary['smallest_balance']); ?></p>
                <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($summary['smallest_name']); ?></p>
            </div>
            <div class="bg-white p-3 md:p-4 rounded-lg shadow dashboard-card">
                <h3 class="text-xs md:text-sm font-medium text-gray-500">Debt Count</h3>
                <p class="text-lg md:text-2xl font-bold text-purple-600"><?php echo $summary['debt_count']; ?></p>
                <p class="text-xs text-gray-500">active debts</p>
            </div>
        </div>

        <!-- Strategy Recommendations -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border-2 border-blue-200">
                <h3 class="font-bold text-blue-800 flex items-center gap-2 text-sm md:text-base">
                    ⚡ Avalanche (Save Money)
                    <span class="text-xs bg-blue-200 px-2 py-1 rounded-full">Mathematical</span>
                </h3>
                <?php if ($avalanche): ?>
                    <p class="text-sm">Pay extra on: <strong><?php echo htmlspecialchars($avalanche['name']); ?></strong></p>
                    <p class="text-xs text-gray-600">Balance: <?php echo format_currency($avalanche['balance']); ?> at <?php echo $avalanche['interest_rate']; ?>% APR</p>
                <?php else: ?>
                    <p class="text-sm text-green-600">✅ No debts! You're debt-free!</p>
                <?php endif; ?>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border-2 border-green-200">
                <h3 class="font-bold text-green-800 flex items-center gap-2 text-sm md:text-base">
                    ❄️ Snowball (Quick Wins)
                    <span class="text-xs bg-green-200 px-2 py-1 rounded-full">Psychological</span>
                </h3>
                <?php if ($snowball): ?>
                    <p class="text-sm">Pay extra on: <strong><?php echo htmlspecialchars($snowball['name']); ?></strong></p>
                    <p class="text-xs text-gray-600">Balance: <?php echo format_currency($snowball['balance']); ?></p>
                <?php else: ?>
                    <p class="text-sm text-green-600">✅ No debts! You're debt-free!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Debt Table -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="font-bold text-gray-700">📋 Your Debts</h3>
                <div class="flex gap-2">
                    <a href="export.php?type=debts" class="text-green-600 hover:text-green-800 text-sm font-medium">
                        📥 Export
                    </a>
                </div>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Debt</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase mobile-hide">APR</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase mobile-hide">Min. Payment</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase mobile-hide">Progress</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($debts as $debt): 
                        // Estimate original balance (assume 20% paid off)
                        $original_balance = $debt['balance'] * 1.2;
                        $percent_paid = max(0, min(100, (1 - ($debt['balance'] / $original_balance)) * 100));
                        $days_until = $debt['due_date'] - date('j');
                        if ($days_until <= 0) $days_until += 30;
                        $is_due_soon = $days_until <= 5;
                    ?>
                    <tr class="debt-row <?php echo $debt['balance'] <= 0 ? 'paid-off' : ''; ?>">
                        <td class="px-3 py-3 font-medium text-sm">
                            <?php echo htmlspecialchars($debt['name']); ?>
                            <?php if ($is_due_soon && $debt['balance'] > 0): ?>
                            <span class="badge-due-soon text-xs px-2 py-0.5 rounded-full ml-1">Due Soon!</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3 text-red-600 font-bold text-sm"><?php echo format_currency($debt['balance']); ?></td>
                        <td class="px-3 py-3 text-sm mobile-hide <?php echo $debt['interest_rate'] > 15 ? 'badge-high-interest px-2 py-0.5 rounded' : ''; ?>">
                            <?php echo $debt['interest_rate']; ?>%
                        </td>
                        <td class="px-3 py-3 text-sm mobile-hide"><?php echo format_currency($debt['minimum_payment']); ?></td>
                        <td class="px-3 py-3 text-sm">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                                Day <?php echo $debt['due_date']; ?>
                            </span>
                            <?php if ($debt['balance'] > 0): ?>
                            <span class="text-xs text-gray-500 block">(<?php echo $days_until; ?> days)</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3 mobile-hide">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full progress-bar" style="width: <?php echo $percent_paid; ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-500"><?php echo round($percent_paid); ?>% paid</span>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex flex-col gap-1">
                                <a href="add-payment.php?id=<?php echo $debt['id']; ?>" class="btn-pay text-white px-3 py-1 rounded text-xs text-center font-medium">
                                    💰 Pay
                                </a>
                                <a href="#" onclick="deleteDebt(<?php echo $debt['id']; ?>)" class="btn-delete text-white px-3 py-1 rounded text-xs text-center font-medium">
                                    🗑️ Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Payments -->
        <?php if (count($recent_payments) > 0): ?>
        <div class="mt-6 bg-white rounded-lg shadow p-4">
            <h3 class="font-bold text-gray-700 mb-2">📋 Recent Payments</h3>
            <div class="space-y-1">
                <?php foreach (array_slice($recent_payments, 0, 5) as $payment): ?>
                <div class="flex justify-between items-center border-b border-gray-100 py-1">
                    <span class="text-sm"><?php echo htmlspecialchars($payment['debt_name'] ?? 'Unknown'); ?></span>
                    <span class="text-sm text-green-600 font-bold">-<?php echo format_currency($payment['amount']); ?></span>
                    <span class="text-xs text-gray-500"><?php echo $payment['payment_date']; ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (count($recent_payments) > 5): ?>
                <p class="text-xs text-gray-500 text-center mt-2">+ <?php echo count($recent_payments) - 5; ?> more payments</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <div class="text-6xl mb-4">🎉</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">No Debts Yet!</h2>
            <p class="text-gray-600 mb-4">You're doing great! Add your first debt to start tracking.</p>
            <a href="add-debt.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition inline-block">
                ➕ Add Your First Debt
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-gray-500">
            💪 You've got this! Every payment brings you closer to financial freedom.
        </div>
    </div>

    <!-- Floating Action Button (Mobile) -->
    <a href="add-debt.php" class="fab">
        ➕
    </a>

    <script>
        function deleteDebt(id) {
            if (confirm('Are you sure you want to delete this debt? This cannot be undone.')) {
                window.location.href = 'delete-debt.php?id=' + id;
            }
        }

        // Animate progress bars
        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('.progress-bar');
            bars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });

        // Check for URL parameters (success messages)
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        if (success) {
            const messages = {
                'debt_added': '✅ Debt added successfully!',
                'payment_made': '✅ Payment recorded successfully!',
                'paid_off': '🎉 Congratulations! You paid off a debt!',
                'deleted': '🗑️ Debt deleted successfully!'
            };
            if (messages[success]) {
                const toast = document.createElement('div');
                toast.className = 'toast toast-success';
                toast.textContent = messages[success];
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            }
        }
    </script>
</body>
</html>