<?php
// admin_header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include your DB connection first to have $conn available
require_once __DIR__ . '/../includes/db.php'; // Using __DIR__ for robust path

// Protect this page and check for admin role early
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: /bookbank/login.php");
    exit();
}

$base_url = '/bookbank';
$current_page = basename($_SERVER['PHP_SELF']);

// --- Fetch Admin Profile Information (Minimalistic) ---
$admin_name = $_SESSION['user_name'] ?? 'Admin'; // Fallback name
$admin_avatar = ''; // Default empty

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Prepare a statement to get the user's name and avatar
    $stmt = $conn->prepare("SELECT name, avatar FROM users WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $admin_name = htmlspecialchars($user_data['name'] ?: $_SESSION['user_name'] ?? 'Admin');
            $admin_avatar = $user_data['avatar'];
        }
        $stmt->close();
    }
}

// Determine the avatar source URL
$defaultAvatarPath = $base_url . '/assets/images/default_avatar.png'; // Make sure this path is correct
$adminAvatarSrc = $defaultAvatarPath;

if (!empty($admin_avatar)) {
    // Check if the uploaded avatar file actually exists on the server
    $uploadedAvatarFilePath = __DIR__ . '/../uploads/avatars/' . $admin_avatar;
    if (file_exists($uploadedAvatarFilePath)) {
        $adminAvatarSrc = $base_url . '/uploads/avatars/' . $admin_avatar;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookBank Admin | <?= ucwords(str_replace(['_', '.php'], [' ', ''], $current_page)); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            orange: '#D97706', dark: '#111827', light: '#F9FAFB', primary: '#F59E0B'
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
</head>
<body class="bg-brand-light font-sans">
    <div x-data="{ menuOpen: false, profileOpen: false }">
        <aside
            x-show="menuOpen"
            x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 bg-brand-dark shadow-lg w-64 z-30"
            style="display: none;">

            <div class="flex items-center justify-between h-20 px-6 border-b border-gray-700">
                <h1 class="text-white text-2xl font-bold">Admin Menu</h1>
                <button @click="menuOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2">
                <?php
                // Helper function to create nav links - restored to original styling
                function nav_link($url, $page_name, $current, $icon_svg, $label) {
                    $is_active = ($page_name === $current);
                    $active_class = $is_active ? 'bg-brand-orange text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white';
                    echo "<a href='$url' class='flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200 $active_class'>";
                    echo $icon_svg;
                    echo "<span class='mx-4 font-medium'>{$label}</span>";
                    echo "</a>";
                }

                // --- Icon Definitions ---
                // Keeping your original icons for consistency
                $dashboard_icon = '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>';
                $users_icon = '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197m0 0A5.975 5.975 0 0112 13a5.975 5.975 0 013 1.803M15 21a9 9 0 00-9-9m9 9a9 9 0 01-9-9"></path></svg>';
                $messages_icon = '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>';
                $donations_icon = '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>';
                $borrows_icon = '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h4a2 2 0 002-2V7a2 2 0 00-2-2h-4a2 2 0 00-2 2z"></path></svg>';
                $site_icon = '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>';
                $inventory_icon = '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m-8-4V7m8 4V4l-8 4-8-4m8 4l-8 4m0 0l-8-4"></path></svg>';
                $profile_icon = '<svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>';
                $logout_icon = '<svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>';


                // --- Navigation Links ---
                nav_link("$base_url/admin/dashboard.php", "dashboard.php", $current_page, $dashboard_icon, "Dashboard");
                nav_link("$base_url/admin/users.php", "users.php", $current_page, $users_icon, "Users");
                nav_link("$base_url/admin/messages.php", "messages.php", $current_page, $messages_icon, "Messages");
                nav_link("$base_url/admin/donations.php", "donations.php", $current_page, $donations_icon, "Donations");
                nav_link("$base_url/admin/borrows.php", "borrows.php", $current_page, $borrows_icon, "Borrows");
                nav_link("$base_url/admin/inventory.php", "inventory.php", $current_page, $inventory_icon, "Inventory"); 
                ?>
                <hr class="border-t border-gray-700 my-4">
                <a href="<?php echo $base_url; ?>/index.php" class="flex items-center px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200">
                    <?php echo $site_icon; ?>
                    <span class="mx-4 font-medium">Go to Site</span>
                </a>
            </nav>
        </aside>
        <div x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 bg-black opacity-50 z-20" style="display:none;"></div>

        <div class="flex-1 flex flex-col">
            <header class="bg-white shadow-md z-10">
                <div class="container mx-auto flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <button @click="menuOpen = !menuOpen" class="text-gray-600 hover:text-gray-800 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="flex items-center space-x-2">
                             <img src="/bookbank/assets/images/logo.png" alt="BookBank Logo" class="h-20 w-auto">
                        </a>
                    </div>

                    <div @click.away="profileOpen = false" class="relative" x-data="{ profileOpen: false }">
                        <button @click="profileOpen = !profileOpen" class="flex items-center space-x-2 text-gray-700 hover:text-brand-orange focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-orange rounded-full p-1">
                            <img src="<?= htmlspecialchars($adminAvatarSrc); ?>" alt="Admin Avatar" class="w-9 h-9 rounded-full object-cover border border-gray-300">
                            <span class="font-semibold hidden sm:inline text-gray-700"><?= $admin_name; ?></span>
                            <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="profileOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 py-2 w-48 bg-white rounded-md shadow-xl z-20 ring-1 ring-black ring-opacity-5" style="display: none;">
                            <a>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="<?php echo $base_url; ?>/logout.php" class="flex items-center w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white">
                                <?= $logout_icon; ?>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            <main class="flex-1 p-6 overflow-y-auto">