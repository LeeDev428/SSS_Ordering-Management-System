<?php
session_start();
include '../middleware.php'; // Include the middleware
userOnly(); // Restrict access to user-only pages

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get the product ID from the POST request
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

// Add the product to the cart
if ($productId > 0) {
    $_SESSION['cart'][] = $productId;
}

// Redirect back to the dashboard
header("Location: dashboard.php");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add to Cart</title>
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
</body>
</html>
