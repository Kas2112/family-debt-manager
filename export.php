<?php
require_once 'config.php';
require_once 'functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_in_user($pdo);
$format = $_GET['format'] ?? 'csv';
$type = $_GET['type'] ?? 'debts';

if ($type === 'debts') {
    $data = export_debts_csv($pdo);
    $filename = 'debts_export_' . date('Y-m-d') . '.csv';
} elseif ($type === 'payments') {
    $payments = get_payment_history($pdo);
    $data = "Date,Debt,Amount,Notes\n";
    foreach ($payments as $payment) {
        $data .= "{$payment['payment_date']},\"{$payment['debt_name']}\",{$payment['amount']},\"{$payment['notes']}\"\n";
    }
    $filename = 'payments_export_' . date('Y-m-d') . '.csv';
} else {
    header('Location: index.php');
    exit;
}

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');

echo $data;
exit;
?>