<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - Family Debt Manager</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
        <!-- Icon -->
        <div class="text-7xl mb-6">📡</div>
        
        <h1 class="text-2xl font-bold text-gray-800 mb-2">You're Offline</h1>
        <p class="text-gray-600 mb-6">
            Don't worry! Your data is safe. Connect to the internet to sync with the family database.
        </p>
        
        <!-- Offline Features -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <h3 class="font-medium text-gray-700 mb-2">What you can still do:</h3>
            <ul class="text-sm text-gray-600 space-y-1">
                <li>✅ View cached debts</li>
                <li>✅ See your dashboard</li>
                <li>❌ Add new debts (needs internet)</li>
                <li>❌ Make payments (needs internet)</li>
            </ul>
        </div>
        
        <!-- Buttons -->
        <div class="space-y-3">
            <button onclick="location.reload()" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                🔄 Try Again
            </button>
            
            <a href="/index.php" class="block w-full bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition">
                📊 Go to Dashboard
            </a>
        </div>
        
        <p class="text-xs text-gray-400 mt-6">
            💡 Once you're back online, everything will sync automatically.
        </p>
    </div>
</body>
</html>