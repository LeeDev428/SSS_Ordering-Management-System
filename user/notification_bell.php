<?php
if (!isset($conn) || !$conn instanceof mysqli) {
    include '../database/db_connection.php'; // Ensure the database connection is available
}

// Fetch unread notifications with product name and checked_at, excluding 'pending' status
$query = "SELECT sales.status, sales.checked_at, products.name AS product_name 
          FROM sales 
          JOIN products ON sales.product_id = products.id 
          WHERE sales.user_id = ? AND sales.is_read = 0 AND sales.status IN ('accepted', 'declined')";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!-- Notification Bell -->
<div class="relative">
    <button onclick="toggleNotifications()" class="relative flex items-center px-4 py-2 bg-green-700 text-white rounded-lg hover:bg-green-800 transition">
        <i data-lucide="bell"></i>
        <span id="notificationCount" class="absolute top-0 right-0 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
            <?php echo count($notifications); ?>
        </span>
    </button>
    <!-- Notification Dropdown -->
    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white text-gray-700 rounded-lg shadow-lg max-h-64 overflow-y-auto">
        <ul>
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                    <li class="px-4 py-2 border-b">
                        <p class="font-bold"><?php echo htmlspecialchars($notification['product_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p>Status: <span class="text-<?php echo $notification['status'] === 'accepted' ? 'green' : 'red'; ?>-500">
                            <?php echo ucfirst($notification['status']); ?>
                        </span></p>
                        <p>Confirmed At: <span class="text-gray-700">
                            <?php echo $notification['checked_at'] ? date('M d, Y h:i A', strtotime($notification['checked_at'])) : 'N/A'; ?>
                        </span></p>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="px-4 py-2 text-center text-gray-500">No notifications</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<script>
    function toggleNotifications() {
        const notificationDropdown = document.getElementById('notificationDropdown');
        notificationDropdown.classList.toggle('hidden');

        // Mark notifications as read when the dropdown is opened
        if (!notificationDropdown.classList.contains('hidden')) {
            markNotificationsAsRead();
        }
    }

    function markNotificationsAsRead() {
        fetch('mark_notifications_read.php', { method: 'POST' })
            .then(() => {
                // Optionally, refresh the notification count after marking as read
                const notificationCount = document.getElementById('notificationCount');
                notificationCount.textContent = '';
                notificationCount.classList.add('hidden');
            })
            .catch(error => console.error('Error marking notifications as read:', error));
    }
</script>
