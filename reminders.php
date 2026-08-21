<?php
require_once 'config.php';
require_once 'functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_in_user($pdo);
$reminders = get_payment_reminders($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reminders</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto p-4 md:p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">🔔 Payment Reminders</h1>
                <p class="text-gray-600">Upcoming payments in the next 7 days</p>
            </div>
            <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                ← Back
            </a>
        </div>

        <?php if (count($reminders) > 0): ?>
        <div class="space-y-4">
            <?php foreach ($reminders as $reminder): ?>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 
                        <?php echo $reminder['urgency'] === 'high' ? 'border-red-500' : 
                                   ($reminder['urgency'] === 'medium' ? 'border-yellow-500' : 'border-green-500'); ?>">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($reminder['name']); ?></h3>
                        <div class="text-sm text-gray-600">
                            <span>Due in <strong><?php echo $reminder['days_until']; ?></strong> days</span>
                            <span class="mx-2">•</span>
                            <span>Balance: <?php echo format_currency($reminder['balance']); ?></span>
                            <span class="mx-2">•</span>
                            <span>Min Payment: <?php echo format_currency($reminder['min_payment']); ?></span>
                        </div>
                    </div>
                    <div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                                   <?php echo $reminder['urgency'] === 'high' ? 'bg-red-100 text-red-800' : 
                                              ($reminder['urgency'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                            <?php echo ucfirst($reminder['urgency']); ?> priority
                        </span>
                    </div>
                </div>
                <div class="mt-3 flex gap-2">
                    <a href="add-payment.php?id=<?php echo $reminder['id']; ?>" class="bg-green-600 text-white px-4 py-1 rounded text-sm hover:bg-green-700 transition">
                        💰 Pay Now
                    </a>
                    <button onclick="setReminder('<?php echo htmlspecialchars($reminder['name']); ?>', <?php echo $reminder['days_until']; ?>)" 
                            class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700 transition">
                        ⏰ Set Reminder
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <div class="text-6xl mb-4">✅</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">No Upcoming Payments!</h2>
            <p class="text-gray-600">All your payments are more than 7 days away. Keep up the good work!</p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function setReminder(name, days) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('💳 Payment Reminder', {
                    body: `${name} is due in ${days} days!`,
                    icon: '/icons/icon-192x192.png'
                });
                alert(`✅ Reminder set for ${name}! (Due in ${days} days)`);
            } else if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification('💳 Payment Reminder', {
                            body: `${name} is due in ${days} days!`,
                            icon: '/icons/icon-192x192.png'
                        });
                        alert(`✅ Reminder set for ${name}! (Due in ${days} days)`);
                    }
                });
            } else {
                alert(`💡 Reminder: ${name} is due in ${days} days!`);
            }
        }

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>
</body>
</html>