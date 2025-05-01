<?php
session_start();
include '../middleware.php';
adminOnly();
include '../database/db_connection.php';

// Fetch sales summary - only include accepted sales
$sales_sql = "SELECT SUM(total_amount) AS total_sales, COUNT(*) AS sales_count FROM sales WHERE status = 'accepted'";
$sales_result = $conn->query($sales_sql);
$sales_data = $sales_result->fetch_assoc();

// Fetch purchases summary
$purchases_sql = "SELECT SUM(total_amount) AS total_purchases, COUNT(*) AS purchases_count FROM purchases";
$purchases_result = $conn->query($purchases_sql);
$purchases_data = $purchases_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <!-- Sidebar -->
    <?php include 'admin_panel.php'; ?>

    <!-- Main Content -->
    <div id="mainContent" class="lg:ml-64 p-6">
        <h1 class="text-3xl font-bold text-green-700 mb-6">Reports</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold text-gray-800 mb-2">Total Sales</h2>
                <p class="text-green-700 font-bold">₱<?php echo number_format($sales_data['total_sales'] ?? 0, 2); ?></p>
                <p class="text-gray-600">Transactions: <?php echo $sales_data['sales_count'] ?? 0; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold text-gray-800 mb-2">Total Purchases</h2>
                <p class="text-green-700 font-bold">₱<?php echo number_format($purchases_data['total_purchases'] ?? 0, 2); ?></p>
                <p class="text-gray-600">Transactions: <?php echo $purchases_data['purchases_count'] ?? 0; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
