<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if not logged in or not admin
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['is_admin'] != 1) {
    header('Location: index.php');
    exit;
}

$user = get_logged_in_user($pdo);
$all_debts = get_all_users_debts($pdo);
$user_totals = get_all_users_total($pdo);
$grand_total = array_sum(array_column($user_totals, 'total'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Dashboard - Admin</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto p-4 md:p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">👨‍👩‍👧‍👦 Family Dashboard</h1>
                <p class="text-gray-600">Total Family Debt: <span class="text-2xl font-bold text-red-600"><?php echo format_currency($grand_total); ?></span></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                    📊 My Dashboard
                </a>
                <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">
                    🚪 Logout
                </a>
            </div>
        </div>

        <!-- User Totals -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <?php foreach ($user_totals as $ut): ?>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars($ut['full_name']); ?></h3>
                <p class="text-2xl font-bold <?php echo $ut['total'] > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                    <?php echo format_currency($ut['total']); ?>
                </p>
                <p class="text-xs text-gray-500">@<?php echo htmlspecialchars($ut['username']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- All Debts Table -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <h3 class="font-bold text-gray-700 p-4 border-b">All Family Debts</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Debt</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">APR</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Min. Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Day</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($all_debts) > 0): ?>
                        <?php foreach ($all_debts as $debt): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium text-sm">
                                <span class="text-gray-800"><?php echo htmlspecialchars($debt['user_name']); ?></span>
                                <span class="text-xs text-gray-500">(@<?php echo htmlspecialchars($debt['username']); ?>)</span>
                            </td>
                            <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($debt['name']); ?></td>
                            <td class="px-4 py-3 text-red-600 font-bold"><?php echo format_currency($debt['balance']); ?></td>
                            <td class="px-4 py-3 <?php echo $debt['interest_rate'] > 15 ? 'text-red-600 font-bold' : ''; ?>">
                                <?php echo $debt['interest_rate']; ?>%
                            </td>
                            <td class="px-4 py-3"><?php echo format_currency($debt['minimum_payment']); ?></td>
                            <td class="px-4 py-3">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                                    Day <?php echo $debt['due_date']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                🎉 No debts in the family! Everyone is debt-free!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 text-center text-sm text-gray-500">
            <p>👑 You are viewing this as an admin. You can see everyone's debts.</p>
        </div>
    </div>
</body>
</html>