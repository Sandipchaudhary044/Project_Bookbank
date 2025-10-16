<?php 
require_once '../includes/admin_header.php'; 
require_once '../includes/db.php'; 

// Only admin access
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: /bookbank/login.php");
    exit();
}

// Fetch all borrow requests with user details
// Also fetch the return_requested flag
$sql = "SELECT 
            br.*, 
            u.username, u.email, u.contact, u.address, u.profession, 
            d.book_title 
        FROM borrow_requests br
        JOIN users u ON br.user_id = u.user_id
        JOIN donations d ON br.donation_id = d.donation_id
        ORDER BY br.request_date DESC";

$result = $conn->query($sql);
?>

<h1 class="text-3xl font-bold text-brand-dark mb-6">Manage Borrow Requests</h1>

<?php if ($result->num_rows > 0): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg shadow-md">
            <thead class="bg-gray-100">
                <tr class="text-left text-gray-700 font-semibold">
                    <th class="py-3 px-4">User</th>
                    <th class="py-3 px-4">Email</th>
                    <th class="py-3 px-4">Contact</th>
                    <th class="py-3 px-4">Address</th>
                    <th class="py-3 px-4">Profession</th>
                    <th class="py-3 px-4">Book</th>
                    <th class="py-3 px-4">Request Date</th>
                    <th class="py-3 px-4">Return Date</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    // Determine status display and action
                    $status_display = $row['status'];
                    $return_requested = (bool)$row['return_requested']; // assuming 0 or 1

                    // If approved and user requested return, show return_requested status
                    if ($row['status'] === 'approved' && $return_requested) {
                        $status_display = 'return_requested';
                    }
                    ?>
                    <tr class="border-t">
                        <td class="py-3 px-4 font-semibold"><?= htmlspecialchars($row['username']) ?></td>
                        <td class="py-3 px-4">
                            <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="text-blue-600 hover:underline"><?= htmlspecialchars($row['email']) ?></a>
                        </td>
                        <td class="py-3 px-4">
                            <a href="tel:<?= htmlspecialchars($row['contact']) ?>" class="text-blue-600 hover:underline"><?= htmlspecialchars($row['contact']) ?></a>
                        </td>
                        <td class="py-3 px-4 text-sm" title="<?= htmlspecialchars($row['address']) ?>"><?= nl2br(htmlspecialchars($row['address'])) ?></td>
                        <td class="py-3 px-4"><?= htmlspecialchars($row['profession']) ?></td>
                        <td class="py-3 px-4"><?= htmlspecialchars($row['book_title']) ?></td>
                        <td class="py-3 px-4"><?= date('Y-m-d', strtotime($row['request_date'])) ?></td>
                        <td class="py-3 px-4">
                            <?= !empty($row['return_date']) ? date('Y-m-d', strtotime($row['return_date'])) : '-' ?>
                        </td>
                        <td class="py-3 px-4 capitalize">
                            <span class="font-semibold 
                                <?= match($status_display) {
                                    'pending' => 'text-yellow-500',
                                    'approved' => 'text-green-600',
                                    'rejected' => 'text-red-600',
                                    'returned' => 'text-blue-600',
                                    'return_requested' => 'text-indigo-600',
                                    default => 'text-gray-600'
                                } ?>">
                                <?= htmlspecialchars($status_display) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 space-x-2">
                            <?php if ($status_display === 'pending'): ?>
                                <a href="process_borrow.php?id=<?= $row['request_id'] ?>&action=approve" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Approve</a>
                                <a href="process_borrow.php?id=<?= $row['request_id'] ?>&action=reject" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Reject</a>

                            <?php elseif ($status_display === 'approved'): ?>
                                <span class="text-gray-500">Borrowed</span>

                            <?php elseif ($status_display === 'return_requested'): ?>
                                <a href="process_borrow.php?id=<?= $row['request_id'] ?>&action=returned" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Confirm Return</a>

                            <?php else: ?>
                                <span class="text-gray-500">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-gray-600">No borrow requests found.</p>
<?php endif; ?>

<?php require_once '../includes/admin_footer.php'; ?>
