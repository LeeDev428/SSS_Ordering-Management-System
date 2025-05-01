<?php
session_start();
include '../middleware.php';
userOnly();
include '../database/db_connection.php';

// Mark notifications as read
$userId = $_SESSION['user_id'];
$query = "UPDATE sales SET is_read = 1 WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();
?>
