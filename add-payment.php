<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$debt_id = $_GET['id'] ?? null;
$debt = null;
$message = '';

if ($debt_id) {
    $debt = get_debt_by_id($pdo, $debt_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debt_id = $_POST['debt_id'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    $debt = get_debt_by_id($pdo, $debt_id);
    
    if ($debt && $amount > 0) {
        if ($amount > $debt['balance']) {
            $message = "⚠️ Payment cannot exceed the current balance of " . format_currency($debt['balance']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO payments (debt_id, user_id, amount, payment_date, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$debt_id, $_SESSION['user_id'], $amount, $payment_date, $notes]);
            
            $new_balance = $debt['balance'] - $amount;
            $stmt = $pdo->prepare("UPDATE debts SET balance = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$new_balance, $debt_id, $_SESSION['user_id']]);
            
            if ($new_balance <= 0) {
                $stmt = $pdo->prepare("INSERT INTO debt_history (user_id, name, original_balance, paid_off_date, total_paid) 
                                       VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $debt['name'], $debt['balance'] + $amount, date('Y-m-d'), $debt['balance'] + $amount]);
                
                $stmt = $pdo->prepare("DELETE FROM debts WHERE id = ? AND user_id = ?");
                $stmt->execute([$debt_id, $_SESSION['user_id']]);
                
                header('Location: index.php?success=paid_off');
            } else {
                header('Location: index.php?success=payment_made');
            }
            exit;
        }
    }
}

if ($debt_id) {
    $debt = get_debt_by_id($pdo, $debt_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - Family Debt Manager</title>
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
        <h2 class="text-2xl font-bold text-gray-800 mb-2">💰 Make a Payment</h2>
        
        <?php if ($message): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <p class="text-yellow-700"><?php echo $message; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($debt): ?>
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <p class="text-sm text-gray-600">Paying toward:</p>
            <p class="font-bold text-gray-800"><?php echo htmlspecialchars($debt['name']); ?></p>
            <p class="text-sm">Current Balance: <span class="font-bold text-red-600"><?php echo format_currency($debt['balance']); ?></span></p>
            <p class="text-sm">Interest Rate: <?php echo $debt['interest_rate']; ?>% APR</p>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="debt_id" value="<?php echo $debt['id']; ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount ($) *</label>
                <input type="number" step="0.01" name="amount" required 
                       max="<?php echo $debt['balance']; ?>"
                       placeholder="0.00"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Maximum: <?php echo format_currency($debt['balance']); ?></p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                <input type="date" name="payment_date" 
                       value="<?php echo date('Y-m-d'); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                <input type="text" name="notes" 
                       placeholder="e.g., January payment"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition font-medium">
                ✅ Submit Payment
            </button>
        </form>
        <?php else: ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
            <p class="text-red-700">Debt not found or you don't have permission to access it.</p>
        </div>
        <?php endif; ?>
        
        <a href="index.php" class="block text-center mt-4 text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Dashboard
        </a>
    </div>
</body>
</html>