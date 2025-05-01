<?php
session_start();
include '../middleware.php'; // Include the middleware
userOnly(); // Restrict access to user-only pages
include '../database/db_connection.php'; // Include the database connection

// Check if the cart table exists, if not, create it
$check_table_query = "SHOW TABLES LIKE 'cart'";
$table_result = $conn->query($check_table_query);
if ($table_result->num_rows == 0) {
    // Create the cart table
    $create_table_query = "CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($create_table_query);
}

// Fetch cart items for the logged-in user - Include the quantity from cart table
$userId = $_SESSION['user_id'];
$query = "SELECT cart.id AS cart_id, cart.quantity AS cart_quantity, products.* FROM cart 
          JOIN products ON cart.product_id = products.id WHERE cart.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$cartProducts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle product removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cart_id'])) {
    $cartId = intval($_POST['remove_cart_id']);
    $deleteQuery = "DELETE FROM cart WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $cartId);
    $deleteStmt->execute();
    $deleteStmt->close();
    header("Location: cart.php");
    exit();
}

// Handle AJAX request to reset notification count
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_notifications'])) {
    $query = "UPDATE sales SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    exit(); // End the script after handling the AJAX request
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
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
        <h1 class="text-3xl font-bold text-green-700 mb-6">Your Cart</h1>
        <?php if (empty($cartProducts)): ?>
            <p class="text-gray-700">Your cart is empty.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($cartProducts as $product): ?>
                    <div class="bg-white p-6 rounded-lg shadow-lg relative">
                        <form method="post" class="absolute top-2 right-2">
                            <input type="hidden" name="remove_cart_id" value="<?php echo $product['cart_id']; ?>">
                            <button type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 hover:text-red-800 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 0m0 0l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13m-14 0h14M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                                </svg>
                            </button>
                        </form>
                        
                        <!-- Display product image if it exists, otherwise show placeholder -->
                        <?php if (!empty($product['image_path'])): ?>
                            <img src="../<?php echo htmlspecialchars($product['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-48 object-cover rounded-lg mb-4">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gray-200 rounded-lg mb-4 flex items-center justify-center">
                                <span class="text-gray-500">No Image</span>
                            </div>
                        <?php endif; ?>
                        
                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="text-green-700 font-bold mb-2">₱<?php echo number_format($product['price'], 2); ?></p>
                        <p class="text-gray-600 mb-2">Quantity: <?php echo $product['cart_quantity']; ?></p>
                        <p class="text-gray-600 mb-4">
                            <?php echo htmlspecialchars($product['description'] ?? 'No description available.', ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
