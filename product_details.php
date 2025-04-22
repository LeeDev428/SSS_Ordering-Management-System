<?php
session_start();
include 'middleware.php'; // Include the middleware
userOnly(); // Restrict access to user-only pages
include 'database/product_seeder.php'; // Include the product seeder

// Get the product ID from the query string
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Find the product by ID
$product = null;
foreach ($products as $item) {
    if ($item['id'] === $productId) {
        $product = $item;
        break;
    }
}

// If product not found, redirect to dashboard
if (!$product) {
    header("Location: dashboard.php");
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
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <div class="container mx-auto mt-12">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-3xl font-bold text-green-700 mb-4"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="text-gray-700 mb-2">Price: $<?php echo number_format($product['price'], 2); ?></p>
            <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="dashboard.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
