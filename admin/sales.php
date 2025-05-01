<?php
session_start();
include '../middleware.php';
adminOnly();
include '../database/db_connection.php';

// Fetch sales
$sales_sql = "SELECT s.id, s.sale_date, p.name AS product_name, s.quantity, s.price, s.total_amount 
              FROM sales s 
              JOIN products p ON s.product_id = p.id 
              ORDER BY s.sale_date DESC";
$sales_result = $conn->query($sales_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <!-- Sidebar -->
    <?php include 'admin_panel.php'; ?>

    <!-- Main Content -->
    <div id="mainContent" class="lg:ml-64 p-6">
        <h1 class="text-3xl font-bold text-green-700 mb-6">Sales</h1>
        <table class="w-full bg-white rounded-lg shadow-lg">
            <thead>
                <tr class="bg-green-600 text-white">
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2">Price</th>
                    <th class="px-4 py-2">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $sales_result->fetch_assoc()): ?>
                    <tr class="border-b text-center">
                        <td class="px-4 py-2"><?php echo $row['id']; ?></td>
                        <td class="px-4 py-2"><?php echo date('M d, Y', strtotime($row['sale_date'])); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-4 py-2"><?php echo $row['quantity']; ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($row['price'], 2); ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
