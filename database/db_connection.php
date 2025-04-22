<?php
$servername = "localhost";
$username = "root";  // Default user for MySQL in Laragon
$password = "";      // Default password for Laragon MySQL
$dbname = "orderease_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
