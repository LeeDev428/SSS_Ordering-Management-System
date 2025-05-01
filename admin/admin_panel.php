<aside id="sidebar" class="bg-green-600 text-white w-64 min-h-screen fixed transform -translate-x-full lg:translate-x-0 transition-transform duration-300 border-r border-green-800">
    <div class="p-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold sidebar-text">Admin Panel</h2>
        <button onclick="minimizeSidebar()" class="text-white hover:text-gray-300">
            <i data-lucide="chevron-left"></i>
        </button>
    </div>
    <nav class="mt-6">
        <a href="admin_dashboard.php" class="block px-6 py-3 hover:bg-green-700 transition sidebar-text">Dashboard</a>
        <a href="add_item.php" class="block px-6 py-3 hover:bg-green-700 transition sidebar-text">Item Inventory</a>
        <a href="orders.php" class="block px-6 py-3 hover:bg-green-700 transition sidebar-text relative">
            Pending Orders
            <span id="pendingOrdersCount" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center hidden"></span>
        </a>
        <a href="reports.php" class="block px-6 py-3 hover:bg-green-700 transition sidebar-text">Reports</a>
        <a href="suppliers.php" class="block px-6 py-3 hover:bg-green-700 transition sidebar-text">Suppliers</a>
        <a href="users.php" class="block px-6 py-3 hover:bg-green-700 transition sidebar-text">Users</a>
        <a href="settings.php" class="block px-6 py-3 hover:bg-green-700 transition sidebar-text">Settings</a>
    </nav>
</aside>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        fetchPendingOrdersCount();

        // Fetch pending orders count every 10 seconds
        setInterval(fetchPendingOrdersCount, 10000);
    });

    function fetchPendingOrdersCount() {
        fetch('fetch_pending_orders.php')
            .then(response => response.json())
            .then(data => {
                const countElement = document.getElementById('pendingOrdersCount');
                if (data.count > 0) {
                    countElement.textContent = data.count;
                    countElement.classList.remove('hidden');
                } else {
                    countElement.classList.add('hidden');
                }
            })
            .catch(error => console.error('Error fetching pending orders count:', error));
    }
</script>
