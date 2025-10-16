<?php
session_start();

// Include your DB connection first to have $conn available
require_once '../includes/db.php';

// Protect this page and check for admin role early
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: /bookbank/login.php");
    exit();
}

// Then include admin header for session/admin checks and HTML start
require_once '../includes/admin_header.php';

// Fetch stats
// It's good practice to handle potential query errors, though for simple counts it's less critical.
$total_users = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'] ?? 0;
$total_donations = $conn->query("SELECT COUNT(*) AS count FROM donations")->fetch_assoc()['count'] ?? 0;
$pending_donations = $conn->query("SELECT COUNT(*) AS count FROM donations WHERE status = 'pending'")->fetch_assoc()['count'] ?? 0;
$borrowed_books = $conn->query("SELECT COUNT(*) AS count FROM borrow_requests WHERE status = 'approved'")->fetch_assoc()['count'] ?? 0; // Assuming 'approved' means currently borrowed. If 'is_borrowed' is still on donations, use that.
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-4xl font-extrabold text-brand-dark mb-10 text-center">Admin Dashboard Overview</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

        <div class="bg-white p-6 rounded-lg shadow-xl border border-gray-200 flex flex-col items-center justify-center text-center transition-transform transform hover:scale-105 duration-300">
            <div class="p-3 bg-brand-orange rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
            </div>
            <h2 class="text-5xl font-extrabold text-brand-dark mb-2"><?= $total_users ?></h2>
            <p class="text-lg text-gray-600 font-semibold mb-4">Total Users</p>
            <a href="users.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                View All Users
                <svg class="ml-2 -mr-0.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-xl border border-gray-200 flex flex-col items-center justify-center text-center transition-transform transform hover:scale-105 duration-300">
            <div class="p-3 bg-green-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 .75.75 0 001.5 0A2 2 0 0110 3a.75.75 0 001.5 0 2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm10-1a.75.75 0 00-1.5 0A2 2 0 0110 3a.75.75 0 00-1.5 0 2 2 0 01-2-2 .75.75 0 00-1.5 0A2 2 0 016 3a.75.75 0 00-1.5 0A2 2 0 014 5v6a2 2 0 012 2h4a2 2 0 012-2V5a.75.75 0 00-1.5 0zM4.75 15a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3zM13.25 15a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3z" clip-rule="evenodd"></path></svg>
            </div>
            <h2 class="text-5xl font-extrabold text-brand-dark mb-2"><?= $total_donations ?></h2>
            <p class="text-lg text-gray-600 font-semibold mb-4">Total Donations</p>
            <a href="donations.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                View All Donations
                <svg class="ml-2 -mr-0.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-xl border border-gray-200 flex flex-col items-center justify-center text-center transition-transform transform hover:scale-105 duration-300">
            <div class="p-3 bg-yellow-500 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l3 3a1 1 0 001.414-1.414L11 9.586V6z" clip-rule="evenodd"></path></svg>
            </div>
            <h2 class="text-5xl font-extrabold text-brand-dark mb-2"><?= $pending_donations ?></h2>
            <p class="text-lg text-gray-600 font-semibold mb-4">Pending Donations</p>
            <a href="donations.php?status=pending" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Review Pending
                <svg class="ml-2 -mr-0.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-xl border border-gray-200 flex flex-col items-center justify-center text-center transition-transform transform hover:scale-105 duration-300">
            <div class="p-3 bg-blue-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 12H7V8h2v4zm0 2H7v-2h2v2zm6-6h-2V8h2v2zm0 2h-2v-2h2v2zm-2 4H9v-2h2v2z"></path><path fill-rule="evenodd" d="M16 2a2 2 0 00-2-2H6a2 2 0 00-2 2v16a2 2 0 002 2h8a2 2 0 002-2V2zm-2 16H6V2h8v16z" clip-rule="evenodd"></path></svg>
            </div>
            <h2 class="text-5xl font-extrabold text-brand-dark mb-2"><?= $borrowed_books ?></h2>
            <p class="text-lg text-gray-600 font-semibold mb-4">Currently Borrowed</p>
            <a href="borrows.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Manage Borrows
                <svg class="ml-2 -mr-0.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>