<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Elite Diecast</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900">
    <nav class="w-full bg-black/95 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="index.php" class="text-white font-bold">Elite Diecast</a>
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-white hover:text-red-600">Home</a>
                <a href="logout.php" class="text-white hover:text-red-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-3xl font-bold text-white mb-6">Welcome, <?php echo htmlspecialchars($user['name'] ?? ($user['email'] ?? 'Collector')); ?>!</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
                <h2 class="text-white font-semibold mb-2">Orders</h2>
                <p class="text-gray-400 text-sm">You have 0 orders. Start your collection today!</p>
                <a href="index.php#inventory" class="inline-block mt-4 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">Shop Now</a>
            </div>

            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
                <h2 class="text-white font-semibold mb-2">Wishlist</h2>
                <p class="text-gray-400 text-sm">No items in wishlist yet.</p>
                <a href="index.php#inventory" class="inline-block mt-4 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg">Browse Models</a>
            </div>

            <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
                <h2 class="text-white font-semibold mb-2">Account</h2>
                <div class="text-gray-300 text-sm">
                    <div><span class="text-gray-400">Name:</span> <?php echo htmlspecialchars($user['name'] ?? '-'); ?></div>
                    <div><span class="text-gray-400">Email:</span> <?php echo htmlspecialchars($user['email'] ?? '-'); ?></div>
                </div>
                <a href="account.php" class="inline-block mt-4 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg">Manage Account</a>
            </div>
        </div>

        <div class="mt-10 bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
            <h2 class="text-white font-semibold mb-4">Recent Activity</h2>
            <ul class="text-gray-400 text-sm space-y-2">
                <li>No recent activity yet. Actions will appear here.</li>
            </ul>
        </div>
    </div>
</body>
</html>



