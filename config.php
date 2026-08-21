<?php
// config.php
session_start();

// ===== CURRENCY SETTINGS =====
define('CURRENCY_SYMBOL', '₦');
define('CURRENCY_CODE', 'NGN');

// ===== DATABASE CONFIGURATION (InfinityFree MySQL) =====
$db_host = 'sql307.infinityfree.com'; // Usually sqlXXX.infinityfree.com
$db_name = 'if0_42714899_family_debt'; // Your database name
$db_user = 'if0_42714899';              // Your username
$db_pass = 'YOUR_PASSWORD_HERE';        // IMPORTANT: Replace with your actual password

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ===== CREATE TABLES =====
    
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        is_admin INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Debts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS debts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        balance DECIMAL(10,2) NOT NULL,
        interest_rate DECIMAL(5,2) NOT NULL,
        minimum_payment DECIMAL(10,2) NOT NULL,
        due_date INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Payments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        debt_id INT NOT NULL,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        notes TEXT,
        FOREIGN KEY (debt_id) REFERENCES debts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Debt history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS debt_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        original_balance DECIMAL(10,2) NOT NULL,
        paid_off_date DATE NOT NULL,
        total_paid DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $admin_exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($admin_exists == 0) {
        // Create default admin
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, is_admin) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@family.com', 'Family Admin', $hashed_password, 1]);
        
        // Create default wife user
        $hashed_password2 = password_hash('wife123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, is_admin) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['wife', 'wife@family.com', 'Wife', $hashed_password2, 0]);
    }
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
