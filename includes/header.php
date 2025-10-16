<?php
require_once 'db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$userName = 'User';
$userImage = '/bookbank/assets/images/default_avatar.png';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Always fetch latest user info from database
    $stmt = $conn->prepare("SELECT name, avatar FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($dbName, $dbAvatar);
    if ($stmt->fetch()) {
        $userName = $dbName ?? 'User';
        if (!empty($dbAvatar)) {
            $userImage = '/bookbank/uploads/avatars/' . $dbAvatar;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BookBank - Sharing Knowledge for Free</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              orange: '#D97706',
              dark: '#1F2937',
            }
          }
        }
      }
    }
  </script>
  <style>
    .dropdown-menu {
      display: none;
      position: absolute;
      background-color: white;
      min-width: 160px;
      box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
      z-index: 10;
      right: 0;
      top: 100%;
      border-radius: 0.375rem;
    }
    .dropdown-menu a {
      color: black;
      padding: 12px 16px;
      display: block;
      text-decoration: none;
    }
    .dropdown-menu a:hover {
      background-color: #f1f1f1;
    }
    .dropdown:hover .dropdown-menu {
      display: block;
    }
  </style>
</head>
<body class="bg-gray-100 font-sans">

<nav class="bg-white shadow-md">
  <div class="container mx-auto px-6 py-3">
    <div class="flex justify-between items-center">
      <a href="index.php">
        <img src="/bookbank/assets/images/logo.png" alt="BookBank Logo" class="h-20 w-auto">
      </a>

      <button id="navToggle" class="text-black md:hidden">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>

      <div id="navMenu" class="hidden md:flex flex-col md:flex-row md:items-center md:space-x-6 w-full md:w-auto mt-4 md:mt-0">
        <div class="flex flex-col md:flex-row md:items-center md:space-x-4 text-black">
          <a href="index.php" class="hover:text-brand-orange">Home</a>
          <a href="about.php" class="hover:text-brand-orange">About Us</a>
          <a href="contact.php" class="hover:text-brand-orange">Contact Us</a>
          <a href="available_books.php" class="hover:text-brand-orange">Browse Books</a>
        </div>

        <form action="search_results.php" method="GET" onsubmit="this.q.value = this.q.value.toLowerCase().trim();" class="flex flex-col md:flex-row items-stretch md:items-center gap-2 mt-3 md:mt-0">
          <input type="text" name="q" placeholder="Search books..." class="px-3 py-2 rounded-md border w-full md:w-64 text-black" />
          <button type="submit" class="bg-brand-orange text-white px-4 py-2 rounded hover:bg-orange-700">Search</button>
        </form>

        <div class="flex flex-col md:flex-row md:items-center md:space-x-4 mt-3 md:mt-0 text-black">
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="donate.php" class="hover:text-brand-orange">Donate</a>
            
            <?php if (!empty($_SESSION['is_admin'])): ?>
              <a href="/bookbank/admin/dashboard.php" class="bg-brand-orange text-white px-3 py-1 rounded hover:bg-orange-700">Admin Panel</a>
            <?php endif; ?>

            <div class="relative dropdown">
              <button class="flex items-center space-x-2 focus:outline-none">
                <img src="<?php echo htmlspecialchars($userImage); ?>" alt="User Avatar" class="h-8 w-8 rounded-full object-cover border border-gray-300">
                <span class="text-black hover:text-brand-orange font-medium"><?php echo htmlspecialchars($userName); ?></span>
                <svg class="w-4 h-4 ml-1 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div class="dropdown-menu">
                <a href="my_account.php">My Account</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')">Logout</a>
              </div>
            </div>

          <?php else: ?>
            <a href="login.php" class="hover:text-brand-orange">Login</a>
            <a href="register.php" class="bg-brand-orange px-3 py-1 rounded hover:bg-orange-700 text-white">Register</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</nav>

<main class="container mx-auto p-6">
<script>
  document.getElementById('navToggle').addEventListener('click', function () {
    document.getElementById('navMenu').classList.toggle('hidden');
  });
</script>
