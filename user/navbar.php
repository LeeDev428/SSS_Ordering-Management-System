<?php
if (!isset($conn) || !$conn instanceof mysqli) {
    include '../database/db_connection.php'; // Ensure the database connection is available
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
?>

<header class="bg-green-600 text-white py-4 shadow-lg">
    <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-2xl font-bold">Sari-Sari Store</h1>
        <div class="relative flex items-center space-x-4">
            <!-- Include notification bell -->
            <?php include 'notification_bell.php'; ?>

            <!-- User Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown()" class="flex items-center space-x-2 px-4 py-2 bg-green-700 text-white rounded-lg hover:bg-green-800 transition">
                    <span class="text-lg font-semibold"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <i data-lucide="chevron-down"></i>
                </button>
                <div id="dropdownMenu" class="absolute right-0 mt-2 w-48 bg-white text-gray-700 rounded-lg shadow-lg hidden">
                    <a href="dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Dashboard</a>
                    <a href="cart.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Cart</a>
                    <a href="my_orders.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">My Orders</a>
                    <a href="transaction_history.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Transaction History</a>
                    <a href="user_profile.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Profile</a>
                    <a href="../logout.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleDropdown() {
        const dropdown = document.getElementById('dropdownMenu');
        dropdown.classList.toggle('hidden');
    }
</script>
