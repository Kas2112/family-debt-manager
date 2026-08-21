<?php
// config.php
session_start(); // Start session for user management
// ===== CURRENCY SETTINGS =====
define('CURRENCY_SYMBOL', '₦');
define('CURRENCY_CODE', 'NGN');

$db_file = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        full_name TEXT NOT NULL,
        is_admin INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create debts table with user_id foreign key
    $pdo->exec("CREATE TABLE IF NOT EXISTS debts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        balance DECIMAL(10,2) NOT NULL,
        interest_rate DECIMAL(5,2) NOT NULL,
        minimum_payment DECIMAL(10,2) NOT NULL,
        due_date INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Create payments table with user_id
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        debt_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        notes TEXT,
        FOREIGN KEY (debt_id) REFERENCES debts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Create debt_history table with user_id
    $pdo->exec("CREATE TABLE IF NOT EXISTS debt_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        original_balance DECIMAL(10,2) NOT NULL,
        paid_off_date DATE NOT NULL,
        total_paid DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Check if admin user exists, if not create default
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $admin_exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($admin_exists == 0) {
        // Create default admin user (password: admin123)
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, is_admin) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@family.com', 'Family Admin', $hashed_password, 1]);
        
        // Create default test user (wife)
        $hashed_password2 = password_hash('wife123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, is_admin) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['wife', 'wife@family.com', 'Wife', $hashed_password2, 0]);
    }
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>