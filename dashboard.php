<?php
session_start();
include 'middleware.php'; // Include the middleware
userOnly(); // Restrict access to user-only pages
include 'database/db_connection.php'; // Include the database connection

// Handle pagination
$itemsPerPage = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Fetch items from the 'items' table in the 'orderease_db' database with pagination
$query = "SELECT * FROM items LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $itemsPerPage);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM items";
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
    <title>Dashboard</title>
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
                    <a href="logout.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto mt-12">
        <!-- Product Cards Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($items as $item): ?>
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Item Image" class="w-full h-48 object-cover rounded-lg mb-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="text-green-700 font-bold mb-2">$<?php echo number_format($item['price'], 2); ?></p>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-sm text-gray-500 mb-2">Available Quantity: <?php echo $item['quantity']; ?></p>
                    <p class="text-sm text-gray-500 mb-4">No Ratings/Stars</p>
                    <a href="product_details.php?id=<?php echo $item['id']; ?>" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition block text-center">Order Now</a>
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
