<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome | My Ordering System</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-100 via-yellow-100 to-white min-h-screen">
  <header class="bg-green-600 text-white py-6 shadow-lg">
    <div class="container mx-auto text-center">
      <h1 class="text-4xl font-extrabold tracking-wide">Online Ordering System</h1>
    </div>
  </header>
  <main class="container mx-auto mt-12 flex flex-col lg:flex-row items-center">
    <div class="lg:w-1/2 text-center lg:text-left px-6">
      <h2 class="text-5xl font-extrabold text-green-700 mb-6 leading-tight">Order Your Favorite Food Online</h2>
      <p class="text-lg text-gray-700 mb-8">Fast, reliable, and convenient. Enjoy delicious meals delivered to your doorstep!</p>
      <div class="flex justify-center lg:justify-start space-x-6">
        <a href="login.php" class="px-8 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition shadow-lg">Login</a>
        <a href="register.php" class="px-8 py-3 bg-yellow-400 text-green-800 rounded-lg font-semibold hover:bg-yellow-500 transition shadow-lg">Register</a>
      </div>
    </div>
    <div class="lg:w-1/2 mt-10 lg:mt-0">
      <img src="assets/images/ordering-system-hero.png" alt="Ordering System" class="w-full rounded-lg shadow-xl">
    </div>
  </main>
  <br>
  <br>
  <br>
  <footer class="mt-12 text-center text-gray-600">
    <p>&copy; 2025 Online Ordering System. All rights reserved.</p>
  </footer>
</body>
</html>
