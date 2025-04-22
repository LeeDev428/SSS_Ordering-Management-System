<?php
session_start();
include 'database/db_connection.php'; // Adjusted path for the database connection

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Login process
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8'); // Prevent XSS
    $password = $_POST['password'];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Successful login, start session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['is_admin'] = $row['is_admin'];

            // Redirect based on is_admin value
            if ($row['is_admin'] == 1) {
                header("Location: admin/admin_dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit();
        } else {
            echo "Incorrect password";
        }
    } else {
        echo "No user found with this email";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-3xl font-extrabold mb-6 text-center text-green-700">Login to Your Account</h2>
        <form method="post" action="login.php" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div>
                <label class="block text-gray-700 mb-2">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>
            <div>
                <label class="block text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition shadow-lg">Login</button>
        </form>
        <p class="mt-6 text-center text-gray-600">Don't have an account? <a href="register.php" class="text-green-600 hover:underline">Register here</a></p>
    </div>
</body>
</html>
