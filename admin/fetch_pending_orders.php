<?php
session_start();
include '../middleware.php'; // Ensure admin-only access
adminOnly();
include '../database/db_connection.php'; // Include the database connection

// Fetch the count of pending orders from the sales table
$query = "SELECT COUNT(*) AS count FROM sales WHERE status = 'pending'";
$result = $conn->query($query);
$count = $result->fetch_assoc()['count'] ?? 0;

// Return the count as JSON
header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?>
