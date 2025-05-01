<?php
session_start();
include '../middleware.php'; // Include the middleware
adminOnly(); // Restrict access to admin-only users
include '../database/db_connection.php'; // Include the database connection

// Ensure uploads directory exists
$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    // Create an index.html file to prevent directory listing
    file_put_contents($upload_dir . 'index.html', 'Access Denied');
}

// Deduct product quantity for accepted orders
$query = "SELECT sales.product_id, sales.quantity 
          FROM sales 
          WHERE sales.status = 'accepted' AND sales.is_processed = 0";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $productId = $row['product_id'];
    $orderedQuantity = $row['quantity'];

    // Deduct the quantity from the products table
    $updateQuery = "UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("iii", $orderedQuantity, $productId, $orderedQuantity);
    $updateStmt->execute();
    $updateStmt->close();
}

// Mark the processed orders as completed
$markProcessedQuery = "UPDATE sales SET is_processed = 1 WHERE status = 'accepted' AND is_processed = 0";
$conn->query($markProcessedQuery);

// Handle form submission for adding or editing an item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'create') {
        $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8');
        
        // Set supplier_id to null (removed from form)
        $supplier_id = null;
        
        // Handle image upload
        $image_path = null;
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../uploads/';
            
            // Create directory if it doesn't exist
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if(in_array($_FILES['image']['type'], $allowed_types) && move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/' . $file_name; // Save relative path
            }
        }
        
        // Modified to include image field
        $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, description, supplier_id, image_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdisss", $name, $price, $quantity, $description, $supplier_id, $image_path);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        // Changed from items to products
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'edit' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8');
        
        // Set supplier_id to null (removed from form)
        $supplier_id = null;
        
        // Handle image upload for edit
        $image_path = null;
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../uploads/';
            
            // Create directory if it doesn't exist
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if(in_array($_FILES['image']['type'], $allowed_types) && move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/' . $file_name; // Save relative path
                
                // Update with new image
                $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, description = ?, supplier_id = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("sdisssi", $name, $price, $quantity, $description, $supplier_id, $image_path, $id);
            }
        } else {
            // Update without changing image
            $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, description = ?, supplier_id = ? WHERE id = ?");
            $stmt->bind_param("sdisii", $name, $price, $quantity, $description, $supplier_id, $id);
        }
        
        $stmt->execute();
        $stmt->close();
    }
}

// Handle form submission for updating an item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = intval($_POST['id']);
    $name = !empty($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : null;
    $price = !empty($_POST['price']) ? floatval($_POST['price']) : null;
    $quantity = !empty($_POST['quantity']) ? intval($_POST['quantity']) : null;
    $description = !empty($_POST['description']) ? htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8') : null;
    
    // Set supplier_id to null (removed from form)
    $supplier_id = null;

    // Build the SQL query dynamically to update only modified fields
    $fields = [];
    $params = [];
    $types = '';

    if ($name !== null) {
        $fields[] = 'name = ?';
        $params[] = $name;
        $types .= 's';
    }
    if ($price !== null) {
        $fields[] = 'price = ?';
        $params[] = $price;
        $types .= 'd';
    }
    if ($quantity !== null) {
        $fields[] = 'quantity = ?';
        $params[] = $quantity;
        $types .= 'i';
    }
    if ($description !== null) {
        $fields[] = 'description = ?';
        $params[] = $description;
        $types .= 's';
    }
    if ($supplier_id !== null) {
        $fields[] = 'supplier_id = ?';
        $params[] = $supplier_id;
        $types .= 'i';
    }

    $params[] = $id;
    $types .= 'i';

    // Changed from items to products
    $query = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
}

// Handle search functionality
$searchQuery = isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '';

// Handle pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Changed from items to products
$query = "SELECT * FROM products WHERE name LIKE ? LIMIT ?, ?";
$stmt = $conn->prepare($query);
$searchTerm = "%$searchQuery%";
$stmt->bind_param("sii", $searchTerm, $offset, $itemsPerPage);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM products WHERE name LIKE ?";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param("s", $searchTerm);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

$stmt->close();
$countStmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        // Initialize lucide icons after the DOM is fully loaded
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
        });

        // Preview image in the form
        function previewImage(input, previewId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById(previewId).src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Populate the update modal with item data
        function populateUpdateModal(item) {
            document.getElementById('updateModal').classList.remove('hidden');
            document.getElementById('updateItemId').value = item.id;
            document.getElementById('updateName').value = item.name;
            document.getElementById('updatePrice').value = item.price;
            document.getElementById('updateQuantity').value = item.quantity;
            document.getElementById('updateDescription').value = item.description || '';
        }

        // Close the update modal
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.add('hidden');
        }

        // Show delete confirmation modal
        function showDeleteModal(itemId) {
            const modal = document.getElementById('deleteModal');
            const confirmButton = document.getElementById('confirmDeleteButton');
            modal.classList.remove('hidden');
            confirmButton.setAttribute('data-id', itemId);
        }

        // Close delete confirmation modal
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
        }

        // Confirm delete action
        function confirmDelete() {
            const itemId = document.getElementById('confirmDeleteButton').getAttribute('data-id');
            const form = document.getElementById('deleteForm');
            form.querySelector('input[name="id"]').value = itemId;
            form.submit();
        }

        // Toggle dropdown visibility
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            dropdown.classList.toggle('hidden');
        }

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
    </script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen flex overflow-x-auto">
    <!-- Sidebar -->
    <?php include 'admin_panel.php'; ?>

    <!-- Main Content -->
    <div id="mainContent" class="flex-1 lg:ml-64 transition-all duration-300">
        <header class="bg-green-600 text-white py-4 shadow-lg">
            <div class="container mx-auto flex justify-center items-center">
                <h1 class="text-2xl font-bold">Item Management</h1>
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
            <h2 class="text-3xl font-bold text-green-700 mb-6 text-center">Add Items</h2>

            <!-- Add Item Form - Removed supplier_id field -->
            <form method="post" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-lg mb-8">
                <input type="hidden" name="action" value="create">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Price</label>
                        <input type="number" step="0.01" name="price" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Quantity</label>
                        <input type="number" name="quantity" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Description</label>
                        <textarea name="description" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Product Image</label>
                        <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                </div>
                <button type="submit" class="mt-4 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Add Item</button>
            </form>
            <br>
            <h2 class="text-3xl font-bold text-green-700 mb-6 text-center">Items</h2>
            <!-- Search Bar -->
            <form method="get" class="mb-6">
                <input type="text" name="search" value="<?php echo $searchQuery; ?>" placeholder="Search by name..." class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
               
               &nbsp;  &nbsp;  &nbsp; <button type="submit" class="mt-2 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Search</button>
            </form>

            <!-- Update Modal - Removed supplier_id field -->
            <div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-3/4 max-h-[80vh] overflow-y-auto">
                    <h3 class="text-2xl font-bold text-green-700 mb-4">Update Item</h3>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="updateItemId">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Name</label>
                                <input type="text" name="name" id="updateName" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Price</label>
                                <input type="number" step="0.01" name="price" id="updatePrice" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Quantity</label>
                                <input type="number" name="quantity" id="updateQuantity" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Description</label>
                                <textarea name="description" id="updateDescription" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Product Image</label>
                                <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            </div>
                        </div>
                        <div class="flex justify-end space-x-4 mt-4">
                            <button type="button" onclick="closeUpdateModal()" class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Update</button>
                        </div>
                    </form>
                </div>
            </div>

            
            <!-- Items Table -->
            <table class="w-full bg-white rounded-lg shadow-lg">
                <thead>
                    <tr class="bg-green-600 text-white">
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Price</th>
                        <th class="px-4 py-2">Quantity</th>
                        <th class="px-4 py-2">
                            <?php echo isset($item['updated_at']) && $item['updated_at'] !== $item['created_at'] ? 'Updated At' : 'Created At'; ?>
                        </th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr class="border-b text-center">
                            <td class="px-4 py-2"><?php echo $item['id']; ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-2">₱<?php echo number_format($item['price'], 2); ?></td>
                            <td class="px-4 py-2"><?php echo $item['quantity']; ?></td>
                            <td class="px-4 py-2">
                                <?php echo isset($item['updated_at']) && $item['updated_at'] !== $item['created_at'] 
                                    ? htmlspecialchars($item['updated_at'], ENT_QUOTES, 'UTF-8') 
                                    : htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="px-4 py-2">
                                <?php
                                if ($item['quantity'] <= 10) {
                                    $status = 'Low Stock';
                                    $statusClass = 'bg-red-600';
                                } elseif ($item['quantity'] <= 20) {
                                    $status = 'Medium Stock';
                                    $statusClass = 'bg-yellow-500';
                                } else {
                                    $status = 'High Stock';
                                    $statusClass = 'bg-green-600';
                                }
                                ?>
                                <span class="px-2 py-1 rounded-lg text-white <?php echo $statusClass; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 flex justify-center space-x-2">
                                <!-- Update Button -->
                                <button onclick="populateUpdateModal(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-600 hover:text-blue-800">
                                    <i data-lucide="notebook-pen"></i>
                                </button>
                                <!-- Delete Button -->
                                <button onclick="showDeleteModal(<?php echo $item['id']; ?>)" class="text-red-600 hover:text-red-800">
                                    <i data-lucide="trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center space-x-2">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>" class="px-4 py-2 rounded-lg <?php echo $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-green-700 hover:text-white transition">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Confirm Deletion</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this item? This action cannot be undone.</p>
            <div class="flex justify-end space-x-4">
                <button onclick="closeDeleteModal()" class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition">Cancel</button>
                <button id="confirmDeleteButton" onclick="confirmDelete()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">Delete</button>
            </div>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteForm" method="post" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id">
    </form>
</body>
</html>