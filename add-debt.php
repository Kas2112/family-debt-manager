<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_in_user($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $balance = $_POST['balance'];
    $interest_rate = $_POST['interest_rate'];
    $minimum_payment = $_POST['minimum_payment'];
    $due_date = $_POST['due_date'];
    
    $stmt = $pdo->prepare("INSERT INTO debts (user_id, name, balance, interest_rate, minimum_payment, due_date) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $name, $balance, $interest_rate, $minimum_payment, $due_date]);
    
    header('Location: index.php?success=debt_added');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Debt - Family Debt Manager</title>
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
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">➕ Add New Debt</h2>
        <p class="text-gray-600 text-sm mb-6">Enter the details of your debt below.</p>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Debt Name *</label>
                <input type="text" name="name" required 
                       placeholder="e.g., Visa Credit Card"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Balance (₦) *</label>
                <input type="number" step="0.01" name="balance" required 
                       placeholder="0.00"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Interest Rate (APR %) *</label>
                <input type="number" step="0.01" name="interest_rate" required 
                       placeholder="0.00"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Payment (₦) *</label>
                <input type="number" step="0.01" name="minimum_payment" required 
                       placeholder="0.00"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Day of Month *</label>
                <input type="number" min="1" max="31" name="due_date" required 
                       placeholder="15"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">The day of the month your payment is due (1-31)</p>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                💾 Save Debt
            </button>
        </form>
        
        <a href="index.php" class="block text-center mt-4 text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Dashboard
        </a>
    </div>
</body>
</html>