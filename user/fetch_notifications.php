<!-- <?php
session_start();
include '../middleware.php';
userOnly();
include '../database/db_connection.php';

// Fetch pending count
$pendingQuery = "SELECT COUNT(*) AS pendingCount FROM sales WHERE user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pendingQuery);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pendingResult = $stmt->get_result();
$pendingCount = $pendingResult->fetch_assoc()['pendingCount'] ?? 0;
$stmt->close();

// Fetch notifications (accepted and declined)
$notificationsQuery = "SELECT sales.status, sales.checked_at, products.name AS product_name 
                       FROM sales 
                       JOIN products ON sales.product_id = products.id 
                       WHERE sales.user_id = ? AND sales.is_read = 0 AND sales.status IN ('accepted', 'declined')";
$stmt = $conn->prepare($notificationsQuery);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'pendingCount' => $pendingCount,
    'notifications' => $notifications,
]);
?> -->
