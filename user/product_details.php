<?php
session_start();
include '../middleware.php';
userOnly();
include '../database/db_connection.php';

// Get the product ID from the query string
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

// If product not found, redirect to dashboard
if (!$product) {
    header("Location: dashboard.php");
    exit();
}

// Handle order confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $userId = $_SESSION['user_id'];
    $quantity = intval($_POST['quantity']);
    $totalAmount = $product['price'] * $quantity;

    // Insert into sales table
    $insertQuery = "INSERT INTO sales (product_id, user_id, quantity, price, total_amount, sale_date, status) 
                   VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("iiidd", $productId, $userId, $quantity, $product['price'], $totalAmount);
    $insertStmt->execute();
    $insertStmt->close();

    // Redirect with success or error message
    header("Location: product_details.php?id=$productId&status=success");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
        });
    </script>
    <style>
        #statusMessage {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background-color: #f0fdf4;
            border: 1px solid #34d399;
            color: #065f46;
            padding: 16px;
            border-radius: 8px;
            display: none;
        }
        #statusMessage button {
            background: none;
            border: none;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            color: #065f46;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <!-- Include shared navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Status Message -->
    <div id="statusMessage">
        <span id="statusText"></span>
        <button onclick="closeStatusMessage()">x</button>
    </div>

    <div class="container mx-auto mt-12">
        <div class="bg-white p-8 rounded-lg shadow-lg flex flex-col md:flex-row">
            <!-- Product Image -->
            <div class="md:w-1/4">
                <img src="../<?php echo htmlspecialchars($product['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Product Image" class="w-full h-auto object-cover rounded-lg">
            </div>
            <!-- Product Details and Form -->
            <div class="md:w-1/2 md:pl-8">
                <h1 class="text-3xl font-bold text-green-700 mb-4"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="text-gray-700 mb-2">Price: ₱<?php echo number_format($product['price'], 2); ?></p>
                <p class="text-gray-700 mb-2">Available Quantity: <?php echo $product['quantity']; ?></p>
                <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></p>

                <!-- Order Confirmation Form -->
                <form method="post">
                    <label for="quantity" class="block text-gray-700 mb-2">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" max="<?php echo $product['quantity']; ?>" required class="w-full px-4 py-2 border rounded-lg mb-4">
                    <button type="submit" name="confirm_order" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition">Confirm Order</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const params = new URLSearchParams(window.location.search);
            if (params.has('status')) {
                const statusMessage = document.getElementById('statusMessage');
                const statusText = document.getElementById('statusText');
                statusText.textContent = params.get('status') === 'success' ? 'Order placed successfully!' : 'Failed to place order.';
                statusMessage.style.display = 'block';

                // Hide after 5 seconds
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 5000);
            }
        });

        function closeStatusMessage() {
            document.getElementById('statusMessage').style.display = 'none';
        }
    </script>
</body>
</html>
