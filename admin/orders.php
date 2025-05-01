<?php
session_start();
include '../middleware.php'; // Include the middleware
adminOnly(); // Restrict access to admin-only users
include '../database/db_connection.php'; // Include the database connection

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Handle Accept and Decline actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saleId = intval($_POST['sale_id']);
    $action = $_POST['action'];
    $checkedAt = date('Y-m-d H:i:s'); // Current timestamp in Asia/Manila timezone

    if ($action === 'accept') {
        // Update status to 'accepted' and set checked_at
        $stmt = $conn->prepare("UPDATE sales SET status = 'accepted', checked_at = ? WHERE id = ?");
        $stmt->bind_param("si", $checkedAt, $saleId);
        $stmt->execute();
        $stmt->close();

        // Deduct the product quantity
        $query = "SELECT product_id, quantity FROM sales WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $saleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $sale = $result->fetch_assoc();
        $stmt->close();

        if ($sale) {
            $productId = $sale['product_id'];
            $saleQuantity = $sale['quantity'];

            $updateQuery = "UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("iii", $saleQuantity, $productId, $saleQuantity);
            $updateStmt->execute();
            $updateStmt->close();
        }
    } elseif ($action === 'decline') {
        // Update status to 'declined' and set checked_at
        $stmt = $conn->prepare("UPDATE sales SET status = 'declined', checked_at = ? WHERE id = ?");
        $stmt->bind_param("si", $checkedAt, $saleId);
        $stmt->execute();
        $stmt->close();
        
        // For decline action, restore the product quantity
        
        // Get the sale details to restore product quantity
        $query = "SELECT product_id, quantity FROM sales WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $saleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $sale = $result->fetch_assoc();
        $stmt->close();
        
        if ($sale) {
            // Restore the product quantity
            $productId = $sale['product_id'];
            $saleQuantity = $sale['quantity'];
            
            $updateQuery = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $saleQuantity, $productId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
}

// Handle pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Handle date range filtering and sorting
$filterDate = isset($_GET['filter_date']) ? $_GET['filter_date'] : null;
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'most_recent';

// Define valid sort options
$sortOptions = [
    'most_recent' => 's.sale_date DESC',
    'oldest' => 's.sale_date ASC',
    'highest_total' => 's.total_amount DESC',
    'lowest_total' => 's.total_amount ASC',
    'highest_price' => 's.price DESC',
    'lowest_price' => 's.price ASC',
    'highest_quantity' => 's.quantity DESC',
    'lowest_quantity' => 's.quantity ASC',
];

// Validate the sortBy value
$orderBy = isset($sortOptions[$sortBy]) ? $sortOptions[$sortBy] : $sortOptions['most_recent'];

// Modify pending orders query
$query = "SELECT s.*, p.name AS product_name, u.username 
          FROM sales s 
          JOIN products p ON s.product_id = p.id 
          JOIN users u ON s.user_id = u.id
          WHERE s.status = 'pending'
          AND (? IS NULL OR DATE(s.sale_date) = ?)
          ORDER BY $orderBy
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssii", $filterDate, $filterDate, $itemsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$sales = $result->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM sales WHERE status = 'pending'";
$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Pagination for accepted orders
$acceptedItemsPerPage = 10;
$acceptedPage = isset($_GET['accepted_page']) ? intval($_GET['accepted_page']) : 1;
$acceptedOffset = ($acceptedPage - 1) * $acceptedItemsPerPage;

// Modify accepted orders query to include date range filtering
$acceptedQuery = "SELECT s.*, p.name AS product_name, u.username 
                  FROM sales s 
                  JOIN products p ON s.product_id = p.id 
                  JOIN users u ON s.user_id = u.id
                  WHERE s.status = 'accepted'
                  AND (? IS NULL OR DATE(s.checked_at) = ?)
                  ORDER BY $orderBy
                  LIMIT ? OFFSET ?";
$acceptedStmt = $conn->prepare($acceptedQuery);
$acceptedStmt->bind_param("ssii", $filterDate, $filterDate, $acceptedItemsPerPage, $acceptedOffset);
$acceptedStmt->execute();
$acceptedResult = $acceptedStmt->get_result();

$acceptedCountQuery = "SELECT COUNT(*) as total FROM sales WHERE status = 'accepted'";
$acceptedCountResult = $conn->query($acceptedCountQuery);
$acceptedTotalItems = $acceptedCountResult->fetch_assoc()['total'];
$acceptedTotalPages = ceil($acceptedTotalItems / $acceptedItemsPerPage);

// Pagination for declined orders
$declinedItemsPerPage = 10;
$declinedPage = isset($_GET['declined_page']) ? intval($_GET['declined_page']) : 1;
$declinedOffset = ($declinedPage - 1) * $declinedItemsPerPage;

// Modify declined orders query to include date range filtering
$declinedQuery = "SELECT s.*, p.name AS product_name, u.username 
                  FROM sales s 
                  JOIN products p ON s.product_id = p.id 
                  JOIN users u ON s.user_id = u.id
                  WHERE s.status = 'declined'
                  AND (? IS NULL OR DATE(s.checked_at) = ?)
                  ORDER BY $orderBy
                  LIMIT ? OFFSET ?";
$declinedStmt = $conn->prepare($declinedQuery);
$declinedStmt->bind_param("ssii", $filterDate, $filterDate, $declinedItemsPerPage, $declinedOffset);
$declinedStmt->execute();
$declinedResult = $declinedStmt->get_result();

$declinedCountQuery = "SELECT COUNT(*) as total FROM sales WHERE status = 'declined'";
$declinedCountResult = $conn->query($declinedCountQuery);
$declinedTotalItems = $declinedCountResult->fetch_assoc()['total'];
$declinedTotalPages = ceil($declinedTotalItems / $declinedItemsPerPage);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Modal styles for zooming the image */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
        }
        .modal:target {
            display: block;
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 20px;
            color: white;
            font-size: 30px;
            font-weight: bold;
            text-decoration: none;
        }
        .close-modal:hover {
            color: red;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <!-- Sidebar -->
    <?php include 'admin_panel.php'; ?>

    <!-- Main Content -->
    <div id="mainContent" class="lg:ml-64 p-6">
        <h1 class="text-3xl font-bold text-green-700 mb-6 text-center">Orders</h1>

        <!-- Date and Sort Filters -->
        <form method="get" class="mb-6 flex flex-wrap gap-4">
            <label for="filter_date" class="block text-gray-700">Filter by Date:</label>
            <input type="date" id="filter_date" name="filter_date" value="<?php echo $filterDate; ?>" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">

            <label for="sort_by" class="block text-gray-700">Sort By:</label>
            <select id="sort_by" name="sort_by" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                <option value="most_recent" <?php echo $sortBy === 'most_recent' ? 'selected' : ''; ?>>Most Recent</option>
                <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                <option value="highest_total" <?php echo $sortBy === 'highest_total' ? 'selected' : ''; ?>>Highest Total Amount</option>
                <option value="lowest_total" <?php echo $sortBy === 'lowest_total' ? 'selected' : ''; ?>>Lowest Total Amount</option>
                <option value="highest_price" <?php echo $sortBy === 'highest_price' ? 'selected' : ''; ?>>Highest Price</option>
                <option value="lowest_price" <?php echo $sortBy === 'lowest_price' ? 'selected' : ''; ?>>Lowest Price</option>
                <option value="highest_quantity" <?php echo $sortBy === 'highest_quantity' ? 'selected' : ''; ?>>Highest Quantity</option>
                <option value="lowest_quantity" <?php echo $sortBy === 'lowest_quantity' ? 'selected' : ''; ?>>Lowest Quantity</option>
            </select>

            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Apply</button>
            <a href="orders.php" class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition">Clear</a>
        </form>

        <!-- Pending Orders -->
        <h2 class="text-2xl font-bold text-green-700 mb-4">Pending Orders</h2>
        <table class="w-full bg-white rounded-lg shadow-lg">
            <thead>
                <tr class="bg-green-600 text-white">
                    <th class="px-4 py-2">Actions</th>
                    <th class="px-4 py-2">Sale ID</th>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2">Price</th>
                    <th class="px-4 py-2">Total Amount</th>
                    <th class="px-4 py-2">Sale Date</th>
                    <th class="px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr class="border-b text-center">
                        <td class="px-4 py-2">
                            <form method="post" class="inline">
                                <input type="hidden" name="sale_id" value="<?php echo $sale['id']; ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Accept</button>
                            </form>
                            <form method="post" class="inline">
                                <input type="hidden" name="sale_id" value="<?php echo $sale['id']; ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">Decline</button>
                            </form>
                        </td>
                        <td class="px-4 py-2"><?php echo $sale['id']; ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($sale['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($sale['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-4 py-2"><?php echo $sale['quantity']; ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($sale['price'], 2); ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                        <td class="px-4 py-2"><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                        <td class="px-4 py-2"><?php echo ucfirst($sale['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-2 text-center">No pending orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination Controls -->
        <div class="mt-6 flex justify-center space-x-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-green-700 hover:text-white transition">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>

        <!-- Accepted Orders -->
        <h2 class="text-2xl font-bold text-green-700 mb-4 mt-8">Accepted Orders</h2>
        <table class="w-full bg-white rounded-lg shadow-lg">
            <thead>
                <tr class="bg-green-600 text-white">
                    <th class="px-4 py-2">Sale ID</th>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2">Price</th>
                    <th class="px-4 py-2">Total Amount</th>
                    <th class="px-4 py-2">Accepted Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sale = $acceptedResult->fetch_assoc()): ?>
                    <tr class="border-b text-center">
                        <td class="px-4 py-2"><?php echo $sale['id']; ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($sale['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($sale['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-4 py-2"><?php echo $sale['quantity']; ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($sale['price'], 2); ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                        <td class="px-4 py-2"><?php echo $sale['checked_at'] ? date('M d, Y h:i A', strtotime($sale['checked_at'])) : 'N/A'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="mt-6 flex justify-center space-x-2">
            <?php for ($i = 1; $i <= $acceptedTotalPages; $i++): ?>
                <a href="?accepted_page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i == $acceptedPage ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-green-700 hover:text-white transition">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>

        <!-- Declined Orders -->
        <h2 class="text-2xl font-bold text-red-700 mb-4 mt-8">Declined Orders</h2>
        <table class="w-full bg-white rounded-lg shadow-lg">
            <thead>
                <tr class="bg-red-600 text-white">
                    <th class="px-4 py-2">Sale ID</th>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2">Price</th>
                    <th class="px-4 py-2">Total Amount</th>
                    <th class="px-4 py-2">Declined Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sale = $declinedResult->fetch_assoc()): ?>
                    <tr class="border-b text-center">
                        <td class="px-4 py-2"><?php echo $sale['id']; ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($sale['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($sale['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-4 py-2"><?php echo $sale['quantity']; ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($sale['price'], 2); ?></td>
                        <td class="px-4 py-2">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                        <td class="px-4 py-2"><?php echo $sale['checked_at'] ? date('M d, Y h:i A', strtotime($sale['checked_at'])) : 'N/A'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="mt-6 flex justify-center space-x-2">
            <?php for ($i = 1; $i <= $declinedTotalPages; $i++): ?>
                <a href="?declined_page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i == $declinedPage ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-red-700 hover:text-white transition">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>
