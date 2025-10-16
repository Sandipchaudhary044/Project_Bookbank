<?php 
require_once '../includes/admin_header.php'; 
require_once '../includes/db.php'; 

// Only admin access
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: /bookbank/login.php");
    exit();
}

// Fetch users with donation/borrow counts and details
$sql = "SELECT 
            u.user_id, u.username, u.email, u.contact, u.address, u.is_admin, u.created_at,
            (SELECT COUNT(*) FROM donations d WHERE d.user_id = u.user_id) AS total_donations,
            (SELECT COUNT(*) FROM borrow_requests br WHERE br.user_id = u.user_id) AS total_borrowed,
            (SELECT COUNT(*) FROM borrow_requests br WHERE br.user_id = u.user_id AND br.status = 'returned') AS total_returned
        FROM users u
        ORDER BY u.created_at DESC";

$result = $conn->query($sql);
?>

<h1 class="text-3xl font-bold text-brand-dark mb-6">Registered Users</h1>

<?php if ($result->num_rows > 0): ?>
<div class="overflow-x-auto">
    <table class="min-w-full bg-white shadow-md rounded-lg">
        <thead class="bg-gray-100 text-gray-700 font-semibold">
            <tr>
                <th class="py-3 px-4">Username</th>
                <th class="py-3 px-4">Email</th>
                <th class="py-3 px-4">Contact</th>
                <th class="py-3 px-4">Address</th>
                <th class="py-3 px-4">Role</th>
                <th class="py-3 px-4">Registered On</th>
                <th class="py-3 px-4">Books Donated</th>
                <th class="py-3 px-4">Books Borrowed</th>
                <th class="py-3 px-4">Books Returned</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $result->fetch_assoc()): ?>
                <tr class="border-t text-sm">
                    <td class="py-3 px-4 font-medium"><?= htmlspecialchars($user['username']) ?></td>
                    <td class="py-3 px-4"><?= htmlspecialchars($user['email']) ?></td>
                    <td class="py-3 px-4">
                        <a href="tel:<?= htmlspecialchars($user['contact']) ?>" class="text-blue-600 underline"><?= htmlspecialchars($user['contact']) ?></a>
                    </td>
                    <td class="py-3 px-4 text-xs"><?= nl2br(htmlspecialchars($user['address'])) ?></td>
                    <td class="py-3 px-4">
                        <?= $user['is_admin'] ? '<span class="text-green-600 font-semibold">Admin</span>' : 'User' ?>
                    </td>
                    <td class="py-3 px-4"><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                    <td class="py-3 px-4 text-center"><?= $user['total_donations'] ?></td>
                    <td class="py-3 px-4 text-center"><?= $user['total_borrowed'] ?></td>
                    <td class="py-3 px-4 text-center"><?= $user['total_returned'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="text-gray-600">No users found.</p>
<?php endif; ?>

<?php require_once '../includes/admin_footer.php'; ?>
