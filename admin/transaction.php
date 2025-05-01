<?php
session_start();
include '../middleware.php'; // Include the middleware
adminOnly(); // Restrict access to admin-only pages
include '../database/db_connection.php'; // Include the database connection

// Handle pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Fetch all sales with status='accepted' - modified to match your schema
$query = "SELECT s.*, p.name AS product_name 
          FROM sales s 
          JOIN products p ON s.product_id = p.id 
          WHERE s.status = 'accepted'
          ORDER BY s.sale_date DESC
          LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $itemsPerPage);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM sales WHERE status = 'accepted'";
$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <!-- Sidebar -->
    <?php include 'admin_panel.php'; ?>

    <!-- Main Content -->
    <div id="mainContent" class="lg:ml-64 p-6">
        <h1 class="text-3xl font-bold text-green-700 mb-6">Completed Transactions</h1>
        <table class="w-full bg-white rounded-lg shadow-lg">
            <thead>
                <tr class="bg-green-600 text-white">
                    <th class="px-4 py-2">Sale ID</th>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2">Price</th>
                    <th class="px-4 py-2">Total Amount</th>
                    <th class="px-4 py-2">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr class="border-b text-center">
                        <td class="px-4 py-2"><?php echo $transaction['id']; ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($transaction['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-4 py-2"><?php echo $transaction['quantity']; ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($transaction['price'], 2); ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                        <td class="px-4 py-2"><?php echo date('M d, Y', strtotime($transaction['sale_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-2 text-center">No completed transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="mt-6 flex justify-center space-x-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-green-700 hover:text-white transition">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>
