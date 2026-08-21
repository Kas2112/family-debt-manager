<?php
// functions.php - Complete version with all new features

// ===== USER FUNCTIONS =====

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_logged_in_user($pdo) {
    if (!is_logged_in()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_user_by_username($pdo, $username) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_user_by_email($pdo, $email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function create_user($pdo, $username, $email, $full_name, $password) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $email, $full_name, $hashed_password]);
}

function verify_user($pdo, $username, $password) {
    $user = get_user_by_username($pdo, $username);
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

// ===== DEBT FUNCTIONS =====

function get_total_debt($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) as total FROM debts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

function get_all_debts($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    $stmt = $pdo->prepare("SELECT * FROM debts WHERE user_id = ? ORDER BY due_date ASC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_users_debts($pdo) {
    $stmt = $pdo->query("SELECT d.*, u.full_name as user_name, u.username 
                         FROM debts d 
                         JOIN users u ON d.user_id = u.id 
                         ORDER BY u.username, d.due_date ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_users_total($pdo) {
    $stmt = $pdo->query("SELECT u.full_name, u.username, COALESCE(SUM(d.balance), 0) as total
                         FROM users u
                         LEFT JOIN debts d ON u.id = d.user_id
                         GROUP BY u.id
                         ORDER BY total DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_debt_by_id($pdo, $id, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    $stmt = $pdo->prepare("SELECT * FROM debts WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_avalanche_target($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    $stmt = $pdo->prepare("SELECT id, name, interest_rate, balance FROM debts 
                           WHERE user_id = ? ORDER BY interest_rate DESC LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_snowball_target($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    $stmt = $pdo->prepare("SELECT id, name, balance, interest_rate FROM debts 
                           WHERE user_id = ? ORDER BY balance ASC LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_payment_history($pdo, $debt_id = null, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    
    if ($debt_id) {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE debt_id = ? AND user_id = ? ORDER BY payment_date DESC LIMIT 10");
        $stmt->execute([$debt_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, d.name as debt_name 
                               FROM payments p 
                               JOIN debts d ON p.debt_id = d.id 
                               WHERE p.user_id = ? 
                               ORDER BY p.payment_date DESC LIMIT 20");
        $stmt->execute([$user_id]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_debt_summary($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    
    $debts = get_all_debts($pdo, $user_id);
    $summary = [
        'total_balance' => 0,
        'total_minimum' => 0,
        'highest_rate' => 0,
        'highest_rate_name' => '',
        'smallest_balance' => PHP_FLOAT_MAX,
        'smallest_name' => '',
        'debt_count' => count($debts)
    ];
    
    foreach ($debts as $debt) {
        $summary['total_balance'] += $debt['balance'];
        $summary['total_minimum'] += $debt['minimum_payment'];
        
        if ($debt['interest_rate'] > $summary['highest_rate']) {
            $summary['highest_rate'] = $debt['interest_rate'];
            $summary['highest_rate_name'] = $debt['name'];
        }
        
        if ($debt['balance'] < $summary['smallest_balance']) {
            $summary['smallest_balance'] = $debt['balance'];
            $summary['smallest_name'] = $debt['name'];
        }
    }
    
    if ($summary['smallest_balance'] == PHP_FLOAT_MAX) {
        $summary['smallest_balance'] = 0;
        $summary['smallest_name'] = 'No debts';
    }
    
    return $summary;
}

// ===== NEW: PAYOFF CALCULATOR FUNCTIONS =====

function get_payoff_projection($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    
    $debts = get_all_debts($pdo, $user_id);
    $projections = [];
    $total_monthly_payment = 0;
    $total_balance = 0;
    
    foreach ($debts as $debt) {
        $total_balance += $debt['balance'];
        $total_monthly_payment += $debt['minimum_payment'];
        
        // Calculate months to payoff (simple estimation)
        if ($debt['minimum_payment'] > 0 && $debt['balance'] > 0) {
            $months_to_payoff = ceil($debt['balance'] / $debt['minimum_payment']);
            $projections[] = [
                'name' => $debt['name'],
                'balance' => $debt['balance'],
                'min_payment' => $debt['minimum_payment'],
                'months' => $months_to_payoff,
                'payoff_date' => date('Y-m-d', strtotime("+$months_to_payoff months")),
                'interest_rate' => $debt['interest_rate']
            ];
        }
    }
    
    $total_months = ($total_monthly_payment > 0 && $total_balance > 0) ? ceil($total_balance / $total_monthly_payment) : 0;
    
    return [
        'debts' => $projections,
        'total_balance' => $total_balance,
        'total_min_payment' => $total_monthly_payment,
        'total_months' => $total_months,
        'payoff_date' => $total_months > 0 ? date('Y-m-d', strtotime("+$total_months months")) : date('Y-m-d'),
        'debt_count' => count($debts)
    ];
}

// ===== NEW: CHART DATA FUNCTIONS =====

function get_debt_history_data($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    
    // Get last 12 months of payment data
    $stmt = $pdo->prepare("
        SELECT 
            strftime('%Y-%m', payment_date) as month,
            SUM(amount) as total_paid
        FROM payments
        WHERE user_id = ?
        GROUP BY strftime('%Y-%m', payment_date)
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_debt_distribution($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    
    $stmt = $pdo->prepare("SELECT name, balance FROM debts WHERE user_id = ? AND balance > 0 ORDER BY balance DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===== NEW: EXPORT FUNCTIONS =====

function export_debts_csv($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    
    $debts = get_all_debts($pdo, $user_id);
    $csv = "Name,Balance,Interest Rate,Minimum Payment,Due Day\n";
    
    foreach ($debts as $debt) {
        $csv .= "\"{$debt['name']}\",{$debt['balance']},{$debt['interest_rate']},{$debt['minimum_payment']},{$debt['due_date']}\n";
    }
    
    return $csv;
}

function export_payments_csv($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    
    $payments = get_payment_history($pdo, null, $user_id);
    $csv = "Date,Debt,Amount,Notes\n";
    foreach ($payments as $payment) {
        $csv .= "{$payment['payment_date']},\"{$payment['debt_name']}\",{$payment['amount']},\"{$payment['notes']}\"\n";
    }
    return $csv;
}

// ===== NEW: REMINDER FUNCTIONS =====

function get_payment_reminders($pdo, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }
    
    $debts = get_all_debts($pdo, $user_id);
    $reminders = [];
    $today = date('j');
    
    foreach ($debts as $debt) {
        // Skip paid off debts
        if ($debt['balance'] <= 0) continue;
        
        $days_until = $debt['due_date'] - $today;
        
        if ($days_until <= 0) {
            $days_until += 30; // Next month
        }
        
        if ($days_until <= 7) {
            $reminders[] = [
                'id' => $debt['id'],
                'name' => $debt['name'],
                'due_day' => $debt['due_date'],
                'days_until' => $days_until,
                'min_payment' => $debt['minimum_payment'],
                'balance' => $debt['balance'],
                'urgency' => $days_until <= 3 ? 'high' : ($days_until <= 5 ? 'medium' : 'low')
            ];
        }
    }
    
    // Sort by urgency (high to low)
    usort($reminders, function($a, $b) {
        $urgency_order = ['high' => 0, 'medium' => 1, 'low' => 2];
        return $urgency_order[$a['urgency']] - $urgency_order[$b['urgency']];
    });
    
    return $reminders;
}

// ===== UTILITY FUNCTIONS =====

function format_currency($amount) {
    return '₦' . number_format($amount, 2);
}

function calculate_interest($balance, $rate, $months = 1) {
    // Simple interest calculation for projection
    return $balance * ($rate / 100) * ($months / 12);
}

// ===== OLD FUNCTIONS KEPT FOR BACKWARDS COMPATIBILITY =====
// (These were already defined above, but keeping for reference)

?>