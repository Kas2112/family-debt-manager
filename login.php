<?php
require_once 'config.php';
require_once 'functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $user = verify_user($pdo, $username, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Family Debt Manager</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="text-6xl mb-2">🏠</div>
            <h1 class="text-3xl font-bold text-gray-800">Family Debt Manager</h1>
            <p class="text-gray-600">Track your family's debt together</p>
        </div>
        
        <!-- Login Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome Back</h2>
            <p class="text-gray-600 text-sm mb-6">Sign in to manage your debts.</p>
            
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                    <input type="text" name="username" required 
                           placeholder="Enter your username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                    <input type="password" name="password" required 
                           placeholder="Enter your password"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                    🔐 Sign In
                </button>
            </form>
            
            <div class="mt-4 text-center text-sm">
                <span class="text-gray-600">Don't have an account?</span>
                <a href="register.php" class="text-blue-600 hover:text-blue-800 font-medium">Create Account</a>
            </div>
            
            <!-- Demo credentials -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center">Demo Accounts:</p>
                <div class="grid grid-cols-2 gap-2 text-xs mt-2">
                    <div class="bg-gray-50 p-2 rounded text-center">
                        <p class="font-medium">Admin</p>
                        <p class="text-gray-500">admin / admin123</p>
                    </div>
                    <div class="bg-gray-50 p-2 rounded text-center">
                        <p class="font-medium">Wife</p>
                        <p class="text-gray-500">wife / wife123</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>