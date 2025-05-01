<?php
session_start();
include '../middleware.php';
userOnly();
include '../database/db_connection.php';

// Fetch orders by status
$userId = $_SESSION['user_id'];
$query = "SELECT sales.id, sales.status, sales.sale_date, sales.checked_at, sales.created_at, products.name AS product_name 
          FROM sales 
          JOIN products ON sales.product_id = products.id 
          WHERE sales.user_id = ? 
          ORDER BY sales.sale_date DESC"; // Sort by most recent date
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch products with pending status
$pendingProducts = [];
foreach ($orders as $order) {
    if ($order['status'] === 'pending') {
        $pendingProducts[] = $order;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons(); // Initialize lucide icons
        });
    </script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <!-- Include shared navbar -->
    <?php include 'navbar.php'; ?>
  

    <!-- Main Content -->
    <div class="container mx-auto mt-12">
        <h1 class="text-3xl font-bold text-green-700 mb-6 text-center">My Orders</h1>

        <!-- Pending Orders -->
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Pending Orders</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($orders as $order): ?>
                <?php if ($order['status'] === 'pending'): ?>
                    <div class="bg-white p-4 rounded-lg shadow-lg">
                        <p class="font-bold">Order ID: <span class="text-gray-700"><?php echo $order['id']; ?></span></p>
                        <p class="font-bold">Product: <span class="text-gray-700"><?php echo htmlspecialchars($order['product_name'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                        <p class="font-bold">Status: <span class="text-yellow-500"><?php echo ucfirst($order['status']); ?></span></p>
                        <p class="font-bold">Pending At: <span class="text-gray-700"><?php echo $order['created_at'] ? date('M d, Y h:i A', strtotime($order['created_at'])) : 'N/A'; ?></span></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    

        <!-- To Receive -->
        <h2 class="text-2xl font-bold text-gray-800 mb-4 mt-8">To Receive</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($orders as $order): ?>
                <?php if ($order['status'] === 'accepted'): ?>
                    <div class="bg-white p-4 rounded-lg shadow-lg">
                        <p class="font-bold">Order ID: <span class="text-gray-700"><?php echo $order['id']; ?></span></p>
                        <p class="font-bold">Product: <span class="text-gray-700"><?php echo htmlspecialchars($order['product_name'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                        <p class="font-bold">Status: <span class="text-green-500"><?php echo ucfirst($order['status']); ?></span></p>
                        <p class="font-bold">Confirmed At: <span class="text-gray-700"><?php echo $order['checked_at'] ? date('M d, Y h:i A', strtotime($order['checked_at'])) : 'N/A'; ?></span></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Rejected -->
        <h2 class="text-2xl font-bold text-gray-800 mb-4 mt-8">Rejected</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($orders as $order): ?>
                <?php if ($order['status'] === 'declined'): ?>
                    <div class="bg-white p-4 rounded-lg shadow-lg">
                        <p class="font-bold">Order ID: <span class="text-gray-700"><?php echo $order['id']; ?></span></p>
                        <p class="font-bold">Product: <span class="text-gray-700"><?php echo htmlspecialchars($order['product_name'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                        <p class="font-bold">Status: <span class="text-red-500"><?php echo ucfirst($order['status']); ?></span></p>
                        <p class="font-bold">Confirmed At: <span class="text-gray-700"><?php echo $order['checked_at'] ? date('M d, Y h:i A', strtotime($order['checked_at'])) : 'N/A'; ?></span></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>