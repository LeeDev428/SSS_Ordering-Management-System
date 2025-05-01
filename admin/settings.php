<?php
session_start();
include '../middleware.php';
adminOnly();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
    <!-- Sidebar -->
    <?php include 'admin_panel.php'; ?>

    <!-- Main Content -->
    <div id="mainContent" class="lg:ml-64 p-6">
        <h1 class="text-3xl font-bold text-green-700 mb-6">Settings</h1>
        <p class="text-gray-700">This is the settings page. Add your settings management functionality here.</p>
    </div>
</body>
</html>
