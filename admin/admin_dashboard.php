<?php
session_start();
include '../middleware.php';
adminOnly();
include '../database/db_connection.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Fetch monthly sales for the line graph - Filter by status='accepted'
$monthlySales = [];
for ($month = 1; $month <= 12; $month++) {
    // Use the sales table instead of orders and filter by accepted status
    $query = "SELECT SUM(total_amount) AS total FROM sales WHERE MONTH(sale_date) = ? AND YEAR(sale_date) = YEAR(CURDATE()) AND status = 'accepted'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $monthlySales[] = $row['total'] ? (float)$row['total'] : 0; // Default to 0 if no sales
    $stmt->close();
}

// Fetch sales per product for the donut graph - Filter by status='accepted'
$productSales = [];
$productLabels = [];
// Use the sales table and products table instead of orders and items
$query = "SELECT p.name AS product_name, SUM(s.total_amount) AS total_sales 
          FROM sales s
          JOIN products p ON s.product_id = p.id 
          WHERE s.status = 'accepted'
          GROUP BY s.product_id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $productLabels[] = $row['product_name'];
    $productSales[] = (float)$row['total_sales'];
}

// Handle search functionality
$searchQuery = isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '';

// Handle date range filtering
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Set default filter date to today if not provided
$filterDate = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

// Handle sorting
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'most_recent';

// Define valid sort options
$sortOptions = [
    'most_recent' => 's.sale_date DESC',
    'oldest' => 's.sale_date ASC',
    'highest_total' => 's.total_amount DESC',
    'lowest_total' => 's.total_amount ASC',
    'highest_price' => 's.price DESC',
    'lowest_price' => 's.price ASC',
    'highest_quantity' => 's.quantity DESC',
    'lowest_quantity' => 's.quantity ASC',
];

// Validate the sortBy value
$orderBy = isset($sortOptions[$sortBy]) ? $sortOptions[$sortBy] : $sortOptions['most_recent'];

// Dynamically build the WHERE clause and parameters
$whereClauses = ["s.status = 'accepted'"];
$params = [];
$types = "";

// Add filter_date condition
if (!empty($filterDate)) {
    $whereClauses[] = "DATE(s.sale_date) = ?";
    $params[] = $filterDate;
    $types .= "s";
}

// Add search condition
if (!empty($searchQuery)) {
    $whereClauses[] = "(p.name LIKE ? OR s.id LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $types .= "ss";
}

// Add start_date condition
if (!empty($startDate)) {
    $whereClauses[] = "s.sale_date >= ?";
    $params[] = $startDate;
    $types .= "s";
}

// Add end_date condition
if (!empty($endDate)) {
    $whereClauses[] = "s.sale_date <= ?";
    $params[] = $endDate;
    $types .= "s";
}

// Combine the WHERE clauses
$whereSql = implode(" AND ", $whereClauses);

// Pagination for sales table
$itemsPerPage = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Add pagination parameters
$params[] = $itemsPerPage;
$params[] = $offset;
$types .= "ii";

// Fetch sales data for the table with pagination, date filtering, and sorting
$sales_sql = "SELECT s.id, s.sale_date, s.checked_at, p.name AS product_name, s.quantity, s.price, s.total_amount 
              FROM sales s 
              JOIN products p ON s.product_id = p.id 
              WHERE $whereSql
              ORDER BY $orderBy
              LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sales_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$sales_result = $stmt->get_result();

// Get total count for pagination with date range filtering
$count_sql = "SELECT COUNT(*) as total FROM sales s 
              JOIN products p ON s.product_id = p.id 
              WHERE $whereSql";
$count_stmt = $conn->prepare($count_sql);

// Exclude LIMIT and OFFSET parameters from the count query
$count_params = array_slice($params, 0, count($params) - 2);
$count_types = substr($types, 0, strlen($types) - 2); // Remove the last two type definitions for LIMIT and OFFSET

if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_sales = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_sales / $itemsPerPage);

// Handle AJAX request for filtering and sorting
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    $stmt = $conn->prepare($sales_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $sales_result = $stmt->get_result();

    $sales_data = [];
    while ($row = $sales_result->fetch_assoc()) {
        $sales_data[] = $row;
    }

    echo json_encode([
        'sales' => $sales_data,
        'pagination' => $total_pages,
    ]);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        // Initialize lucide icons after the DOM is fully loaded
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
        });

        // Toggle sidebar visibility
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }

        // Minimize sidebar
        function minimizeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-16');
            mainContent.classList.toggle('lg:ml-64');
            mainContent.classList.toggle('lg:ml-16');
            const sidebarText = document.querySelectorAll('.sidebar-text');
            sidebarText.forEach(text => text.classList.toggle('hidden'));
        }

        // Toggle dropdown visibility
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            dropdown.classList.toggle('hidden');
        }

        document.addEventListener("DOMContentLoaded", () => {
            const applyButton = document.getElementById("applyButton");
            const clearButton = document.getElementById("clearButton");
            const salesTableBody = document.getElementById("salesTableBody");
            const paginationContainer = document.getElementById("paginationContainer");

            function fetchSalesData(params) {
                fetch(`admin_dashboard.php?ajax=true&${params}`)
                    .then(response => response.json())
                    .then(data => {
                        // Update sales table
                        salesTableBody.innerHTML = data.sales.map(sale => `
                            <tr class="border-b text-center">
                                <td class="px-4 py-2">${sale.id}</td>
                                <td class="px-4 py-2">${sale.sale_date ? new Date(sale.sale_date).toLocaleString() : 'N/A'}</td>
                                <td class="px-4 py-2">${sale.checked_at ? new Date(sale.checked_at).toLocaleString() : 'N/A'}</td>
                                <td class="px-4 py-2">${sale.product_name}</td>
                                <td class="px-4 py-2">${sale.quantity}</td>
                                <td class="px-4 py-2">₱${parseFloat(sale.price).toFixed(2)}</td>
                                <td class="px-4 py-2">₱${parseFloat(sale.total_amount).toFixed(2)}</td>
                            </tr>
                        `).join("");

                        // Update pagination
                        paginationContainer.innerHTML = Array.from({ length: data.pagination }, (_, i) => `
                            <a href="#" data-page="${i + 1}" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-green-700 hover:text-white transition">${i + 1}</a>
                        `).join("");

                        // Add event listeners to pagination links
                        document.querySelectorAll("#paginationContainer a").forEach(link => {
                            link.addEventListener("click", (e) => {
                                e.preventDefault();
                                const page = e.target.dataset.page;
                                const params = new URLSearchParams(new FormData(document.getElementById("filterForm")));
                                params.set("page", page);
                                fetchSalesData(params.toString());
                            });
                        });
                    });
            }

            applyButton.addEventListener("click", (e) => {
                e.preventDefault();
                const params = new URLSearchParams(new FormData(document.getElementById("filterForm")));
                fetchSalesData(params.toString());
            });

            clearButton.addEventListener("click", (e) => {
                e.preventDefault();
                document.getElementById("filterForm").reset();
                fetchSalesData("");
            });
        });
    </script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen flex overflow-x-auto">
    <!-- Sidebar -->
    <?php include 'admin_panel.php'; ?>

    <!-- Main Content -->
    <div id="mainContent" class="flex-1 lg:ml-64 transition-all duration-300">
        <header class="bg-green-600 text-white py-4 shadow-lg">
            <div class="container mx-auto flex justify-center items-center">
                <h1 class="text-2xl font-bold">Admin Dashboard</h1>
            </div>
            <div class="absolute top-4 right-4">
                <button onclick="toggleDropdown()" class="flex items-center space-x-2 px-4 py-2 bg-green-700 text-white rounded-lg hover:bg-green-800 transition">
                    <span class="text-lg font-semibold"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <i data-lucide="chevron-down"></i>
                </button>
                <div id="dropdownMenu" class="absolute right-0 mt-2 w-48 bg-white text-gray-700 rounded-lg shadow-lg hidden">
                    <a href="../logout.php" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Logout</a>
                </div>
            </div>
        </header>

        <main class="container mx-auto mt-12">
            <!-- Graphs Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Line Graph -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-bold text-gray-700 mb-4">Sales Monthly</h2>
                    <div id="lineChart"></div>
                </div>

                <!-- Donut Graph -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-bold text-gray-700 mb-4">Sales per Product</h2>
                    <div id="donutChart"></div>
                </div>
            </div>

            <h1 class="text-3xl font-bold text-green-700 mb-6 ml-3 mt-5">Sales</h1>

            <!-- Search, Date, and Sort Filters -->
            <form id="filterForm" class="mb-6 flex flex-wrap gap-4">
                <input type="text" name="search" value="<?php echo $searchQuery; ?>" placeholder="Search by Sale ID or Product Name..." class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                <label for="filter_date" class="block text-gray-700">Filter by Date:</label>
                <input type="date" id="filter_date" name="filter_date" value="<?php echo $filterDate; ?>" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">

                <label for="sort_by" class="block text-gray-700">Sort By:</label>
                <select id="sort_by" name="sort_by" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="most_recent" <?php echo $sortBy === 'most_recent' ? 'selected' : ''; ?>>Most Recent</option>
                    <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                    <option value="highest_total" <?php echo $sortBy === 'highest_total' ? 'selected' : ''; ?>>Highest Total Amount</option>
                    <option value="lowest_total" <?php echo $sortBy === 'lowest_total' ? 'selected' : ''; ?>>Lowest Total Amount</option>
                    <option value="highest_price" <?php echo $sortBy === 'highest_price' ? 'selected' : ''; ?>>Highest Price</option>
                    <option value="lowest_price" <?php echo $sortBy === 'lowest_price' ? 'selected' : ''; ?>>Lowest Price</option>
                    <option value="highest_quantity" <?php echo $sortBy === 'highest_quantity' ? 'selected' : ''; ?>>Highest Quantity</option>
                    <option value="lowest_quantity" <?php echo $sortBy === 'lowest_quantity' ? 'selected' : ''; ?>>Lowest Quantity</option>
                </select>

                <button id="applyButton" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Apply</button>
                <button id="clearButton" class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition">Clear</button>
            </form>

            <table class="w-full bg-white rounded-lg shadow-lg">
                <thead>
                    <tr class="bg-green-600 text-white">
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Accepted Date</th>
                        <th class="px-4 py-2">Product</th>
                        <th class="px-4 py-2">Quantity</th>
                        <th class="px-4 py-2">Price</th>
                        <th class="px-4 py-2">Total</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    <?php while ($row = $sales_result->fetch_assoc()): ?>
                        <tr class="border-b text-center">
                            <td class="px-4 py-2"><?php echo $row['id']; ?></td>
                            <td class="px-4 py-2"><?php echo $row['sale_date'] ? date('M d, Y h:i A', strtotime($row['sale_date'])) : 'N/A'; ?></td>
                            <td class="px-4 py-2"><?php echo $row['checked_at'] ? date('M d, Y h:i A', strtotime($row['checked_at'])) : 'N/A'; ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-2"><?php echo $row['quantity']; ?></td>
                            <td class="px-4 py-2">₱<?php echo number_format($row['price'], 2); ?></td>
                            <td class="px-4 py-2">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <div id="paginationContainer" class="mt-6 flex justify-center space-x-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="#" data-page="<?php echo $i; ?>" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-green-700 hover:text-white transition"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </main>
    </div>

    <script>
        // Line Graph: Sales Monthly
        var lineOptions = {
            chart: {
                type: 'line',
                height: 350
            },
            series: [{
                name: 'Sales',
                data: <?php echo json_encode($monthlySales); ?>
            }],
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            title: {
                text: 'Monthly Sales',
                align: 'center'
            }
        };

        var lineChart = new ApexCharts(document.querySelector("#lineChart"), lineOptions);
        lineChart.render();

        // Donut Graph: Sales per Product
        var donutOptions = {
            chart: {
                type: 'donut',
                height: 350
            },
            series: <?php echo json_encode($productSales); ?>,
            labels: <?php echo json_encode($productLabels); ?>,
            title: {
                text: 'Sales per Product',
                align: 'center'
            }
        };

        var donutChart = new ApexCharts(document.querySelector("#donutChart"), donutOptions);
        donutChart.render();
    </script>
</body>
</html>
