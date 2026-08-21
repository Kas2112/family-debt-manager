<?php
require_once 'config.php';
require_once 'functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_in_user($pdo);
$projection = get_payoff_projection($pdo);
$extra_payment = isset($_GET['extra']) ? floatval($_GET['extra']) : 0;

// Calculate with extra payment
if ($extra_payment > 0) {
    $total_monthly = $projection['total_min_payment'] + $extra_payment;
    $new_months = $total_monthly > 0 ? ceil($projection['total_balance'] / $total_monthly) : 0;
    $projection['total_months'] = $new_months;
    $projection['payoff_date'] = date('Y-m-d', strtotime("+$new_months months"));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debt Payoff Calculator</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto p-4 md:p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">📈 Payoff Calculator</h1>
                <p class="text-gray-600">See when you'll be debt-free</p>
            </div>
            <div class="flex gap-2">
                <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                    ← Back
                </a>
            </div>
        </div>

        <?php if ($projection['debt_count'] > 0): ?>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-sm font-medium text-gray-500">Total Debt</h3>
                <p class="text-2xl font-bold text-red-600"><?php echo format_currency($projection['total_balance']); ?></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-sm font-medium text-gray-500">Monthly Minimum</h3>
                <p class="text-2xl font-bold text-gray-800"><?php echo format_currency($projection['total_min_payment']); ?></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-sm font-medium text-gray-500">Time to Payoff</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $projection['total_months']; ?> months</p>
                <p class="text-xs text-gray-500"><?php echo date('M Y', strtotime($projection['payoff_date'])); ?></p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-sm font-medium text-gray-500">Number of Debts</h3>
                <p class="text-2xl font-bold text-purple-600"><?php echo $projection['debt_count']; ?></p>
            </div>
        </div>

        <!-- Extra Payment Calculator -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="font-bold text-gray-700 mb-4">💪 What if you pay extra?</h3>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Extra Payment per Month (₦)</label>
                    <input type="number" id="extraPayment" step="10" min="0" 
                           value="<?php echo $extra_payment; ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button onclick="updateCalculator()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                    Calculate
                </button>
                <?php if ($extra_payment > 0): ?>
                <a href="calculator.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition">
                    Reset
                </a>
                <?php endif; ?>
            </div>
            
            <?php if ($extra_payment > 0): ?>
            <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-800">
                    🎯 With an extra <strong><?php echo format_currency($extra_payment); ?></strong> per month, 
                    you'll be debt-free in <strong><?php echo $projection['total_months']; ?> months</strong> 
                    (by <strong><?php echo date('M Y', strtotime($projection['payoff_date'])); ?></strong>)
                </p>
                <p class="text-sm text-green-600 mt-1">
                    That's <?php echo max(0, $projection['total_months'] - ceil($projection['total_balance'] / $projection['total_min_payment'])); ?> months sooner!
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Debt List with Projections -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <h3 class="font-bold text-gray-700 p-4 border-b">📋 Debt Payoff Timeline</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Debt</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Min. Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Months to Pay</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payoff Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($projection['debts'] as $debt): ?>
                    <?php 
                        $original = $debt['balance'] * 1.2;
                        $percent = min(100, max(0, (1 - ($debt['balance'] / $original)) * 100));
                    ?>
                    <tr>
                        <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($debt['name']); ?></td>
                        <td class="px-4 py-3 text-red-600 font-bold"><?php echo format_currency($debt['balance']); ?></td>
                        <td class="px-4 py-3"><?php echo format_currency($debt['min_payment']); ?></td>
                        <td class="px-4 py-3">
                            <?php echo $debt['months']; ?> months
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php echo date('M Y', strtotime($debt['payoff_date'])); ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="w-32 bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <div class="text-6xl mb-4">🎉</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">No Debts to Calculate!</h2>
            <p class="text-gray-600 mb-4">You're already debt-free! Keep up the great work!</p>
            <a href="index.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition inline-block">
                ← Back to Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function updateCalculator() {
            const extra = document.getElementById('extraPayment').value;
            if (extra > 0) {
                window.location.href = 'calculator.php?extra=' + extra;
            } else {
                window.location.href = 'calculator.php';
            }
        }
    </script>
</body>
</html>