<?php
require_once 'config.php';
require_once 'functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username exists
        $existing_user = get_user_by_username($pdo, $username);
        if ($existing_user) {
            $error = 'Username already taken. Please choose another.';
        } else {
            $existing_email = get_user_by_email($pdo, $email);
            if ($existing_email) {
                $error = 'Email already registered. Please use another.';
            } else {
                // Create user
                if (create_user($pdo, $username, $email, $full_name, $password)) {
                    $success = 'Account created successfully! You can now login.';
                    // Clear form
                    $_POST = [];
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Family Debt Manager</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-6xl mb-2">👋</div>
            <h1 class="text-3xl font-bold text-gray-800">Create Account</h1>
            <p class="text-gray-600">Join the family debt management team</p>
        </div>
        
        <!-- Registration Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Get Started</h2>
            <p class="text-gray-600 text-sm mb-6">Create your account to start tracking.</p>
            
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
                <p class="text-green-700 text-sm"><?php echo htmlspecialchars($success); ?></p>
                <a href="login.php" class="text-green-700 font-medium underline mt-1 inline-block">Go to Login →</a>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="full_name" required 
                           placeholder="e.g., John Doe"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                    <input type="text" name="username" required 
                           placeholder="e.g., johndoe"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">At least 3 characters</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" required 
                           placeholder="e.g., john@family.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                    <input type="password" name="password" required 
                           placeholder="At least 6 characters"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                    <input type="password" name="confirm_password" required 
                           placeholder="Type your password again"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition font-medium">
                    ✅ Create Account
                </button>
            </form>
            
            <div class="mt-4 text-center text-sm">
                <span class="text-gray-600">Already have an account?</span>
                <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>