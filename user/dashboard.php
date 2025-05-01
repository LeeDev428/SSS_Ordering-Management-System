<?php
session_start();
include '../middleware.php';
userOnly();
include '../database/db_connection.php';

// Make sure the user_id is available
$userId = $_SESSION['user_id'];

// Handle add to cart functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $productId = intval($_POST['product_id']);
    
    // Check if the cart table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'cart'");
    if ($check_table->num_rows === 0) {
        // Create the cart table if it doesn't exist
        $conn->query("CREATE TABLE cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Check if this product is already in the cart
    $checkQuery = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $userId, $productId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // If already in cart, increase quantity
        $cartItem = $checkResult->fetch_assoc();
        $newQuantity = $cartItem['quantity'] + 1;
        $updateQuery = "UPDATE cart SET quantity = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ii", $newQuantity, $cartItem['id']);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // If not in cart, add it
        $insertQuery = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("ii", $userId, $productId);
        $insertStmt->execute();
        $insertStmt->close();
    }
    
    $checkStmt->close();
    header("Location: dashboard.php?added_to_cart=1");
    exit();
}

// Handle pagination
$itemsPerPage = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get products
$query = "SELECT * FROM products LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $itemsPerPage);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);

// Update the count query as well
$countQuery = "SELECT COUNT(*) as total FROM products";
$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Do not close the connection here to ensure navbar.php can use it
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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

    <main class="container mx-auto mt-12">
        <!-- Success message for adding to cart -->
        <?php if (isset($_GET['added_to_cart'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p>Product added to your cart!</p>
            </div>
        <?php endif; ?>
        
        <!-- Product Cards Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
            <?php foreach ($items as $item): ?>
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <!-- Show the product image if available, otherwise show placeholder -->
                    <?php if (!empty($item['image_path'])): ?>
                        <img src="../<?php echo htmlspecialchars($item['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-48 object-cover rounded-lg mb-4">
                    <?php else: ?>
                        <div class="w-full h-48 bg-gray-200 rounded-lg mb-4 flex items-center justify-center">
                            <span class="text-gray-500">No Image</span>
                        </div>
                    <?php endif; ?>
                    <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="text-green-700 font-bold mb-2">₱<?php echo number_format($item['price'], 2); ?></p>
                    <!-- Show description instead of category -->
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($item['description'] ?? 'No description available', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-sm text-gray-500 mb-2">Available Quantity: <?php echo $item['quantity']; ?></p>
                    <!-- Product Actions -->
                    <div class="flex space-x-2">
                        <a href="product_details.php?id=<?php echo $item['id']; ?>" class="flex-1 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition text-center">Order Now</a>
                        <form method="post" class="flex-shrink-0">
                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="add_to_cart" class="bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-center space-x-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-green-700 hover:text-white transition">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </main>
</body>
</html>
