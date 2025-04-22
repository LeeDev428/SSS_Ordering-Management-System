<?php
session_start();
include '../middleware.php'; // Include the middleware
userOnly(); // Restrict access to user-only pages
include '../database/db_connection.php'; // Include the database connection

// Get the product ID from the query string
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0; // Ensure the ID is retrieved and validated
if ($productId <= 0) {
    header("Location: ../dashboard.php"); // Redirect to dashboard if ID is invalid
    exit();
}

// Fetch the product details from the database
$query = "SELECT * FROM items WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// If product not found, redirect to dashboard
if (!$product) {
    header("Location: ../dashboard.php");
    exit();
}

// Handle null values for optional fields
$product['description'] = $product['description'] ?? 'No description available.';

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Toggle dropdown visibility
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            dropdown.classList.toggle('hidden');
        }
    </script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <!-- Navbar -->
    <header class="bg-green-600 text-white py-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">User Dashboard</h1>
            <div class="relative">
                <button onclick="toggleDropdown()" class="flex items-center space-x-2 px-4 py-2 bg-green-700 text-white rounded-lg hover:bg-green-800 transition">
                    <span class="text-lg font-semibold"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div id="dropdownMenu" class="absolute right-0 mt-2 w-48 bg-white text-gray-700 rounded-lg shadow-lg hidden">
                    <a href="cart.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Cart</a>
                    <a href="dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Dashboard</a>
                    <a href="../logout.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto mt-12">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-3xl font-bold text-green-700 mb-4"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <img src="../<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Product Image" class="w-full h-64 object-cover rounded-lg mb-4">
            <p class="text-gray-700 mb-2">Price: $<?php echo number_format($product['price'], 2); ?></p>
            <p class="text-gray-700 mb-2">Available Quantity: <?php echo $product['quantity']; ?></p>
            <p class="text-gray-700 mb-2">Category: <?php echo htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="text-gray-700 mb-4">Description: <?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            <form method="post" action="place_order.php" class="mt-6">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <div>
                    <label class="block text-gray-700 mb-2">Your Money</label>
                    <input type="number" name="money" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                </div>
                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition mt-4">Place Order</button>
            </form>
        </div>
    </div>
</body>
</html>
