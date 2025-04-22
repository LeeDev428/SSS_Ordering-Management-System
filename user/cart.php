<?php
session_start();
include '../middleware.php'; // Include the middleware
userOnly(); // Restrict access to user-only pages
include '../database/product_seeder.php'; // Include the product seeder

// Get the products in the cart
$cartProducts = [];
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $productId) {
        foreach ($products as $product) {
            if ($product['id'] === $productId) {
                $cartProducts[] = $product;
                break;
            }
        }
    }
}

// Handle product removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product_id'])) {
    $removeProductId = intval($_POST['remove_product_id']);
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function ($id) use ($removeProductId) {
        return $id !== $removeProductId;
    });
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
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
        <h1 class="text-3xl font-bold text-green-700 mb-6">Your Cart</h1>
        <?php if (empty($cartProducts)): ?>
            <p class="text-gray-700">Your cart is empty.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($cartProducts as $product): ?>
                    <div class="bg-white p-6 rounded-lg shadow-lg relative">
                        <form method="post" class="absolute top-2 right-2">
                            <input type="hidden" name="remove_product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 hover:text-red-800 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 0m0 0l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13m-14 0h14M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                                </svg>
                            </button>
                        </form>
                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="text-green-700 font-bold mb-2">$<?php echo number_format($product['price'], 2); ?></p>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
