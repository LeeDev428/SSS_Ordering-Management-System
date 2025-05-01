<?php
session_start();
include '../middleware.php';
userOnly();
include '../database/db_connection.php';

// First, check if the sales table has an is_read column
$check_column_query = "SHOW COLUMNS FROM sales LIKE 'is_read'";
$column_result = $conn->query($check_column_query);
if ($column_result->num_rows == 0) {
    // Add the is_read column to the sales table
    $add_column_query = "ALTER TABLE sales ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0";
    $conn->query($add_column_query);
}

// Fetch order history from sales table
$userId = $_SESSION['user_id'];
$query = "SELECT sales.*, products.name AS product_name, products.image_path 
          FROM sales 
          JOIN products ON sales.product_id = products.id 
          WHERE sales.user_id = ? 
          ORDER BY sales.sale_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Do not close the connection here to ensure navbar.php can use it
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
        });
    </script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <!-- Include shared navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto mt-12">
        <h1 class="text-3xl font-bold text-green-700 mb-6">Transaction History</h1>
        <?php if (empty($orders)): ?>
            <p class="text-gray-700">You have no completed transactions.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <img src="../<?php echo htmlspecialchars($order['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Item Image" class="w-full h-48 object-cover rounded-lg mb-4">
                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($order['product_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="text-gray-700 mb-2">Quantity: <?php echo $order['quantity']; ?></p>
                        <p class="text-gray-700 mb-2">Total Amount: ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                        <p class="text-gray-700 mb-2">Date: <?php echo $order['sale_date']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
