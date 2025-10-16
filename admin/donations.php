<?php 
require_once '../includes/admin_header.php'; 
require_once '../includes/db.php'; 

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: /bookbank/login.php");
    exit();
}

// Fetch all donations with donor info
$sql = "SELECT 
            d.*, 
            u.username, 
            u.email 
        FROM donations d
        JOIN users u ON d.user_id = u.user_id
        ORDER BY d.created_at DESC";

        

$result = $conn->query($sql);
?>

<h1 class="text-3xl font-bold text-brand-dark mb-6">📚 Manage All Book Donations</h1>

<?php if ($result && $result->num_rows > 0): ?>
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded-lg shadow-md">
        <thead class="bg-gray-100">
            <tr class="text-left text-gray-700 font-semibold">
                <th class="py-3 px-4">Book</th>
                <th class="py-3 px-4">Author</th>
                <th class="py-3 px-4">Donor</th>
                <th class="py-3 px-4">Contact</th>
                <th class="py-3 px-4">Address</th>
                <th class="py-3 px-4">Category</th>
                <th class="py-3 px-4">Condition</th>
                <th class="py-3 px-4">ISBN</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="border-t text-sm">
                    <td class="py-3 px-4 font-semibold"><?= htmlspecialchars($row['book_title']) ?></td>
                    <td class="py-3 px-4"><?= htmlspecialchars($row['author']) ?></td>
                    <td class="py-3 px-4">
                        <?= htmlspecialchars($row['username']) ?><br>
                        <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="text-blue-600 text-xs underline"><?= htmlspecialchars($row['email']) ?></a>
                    </td>
                    <td class="py-3 px-4">
    <?php if (!empty($row['contact'])): ?>
        <a href="tel:<?= htmlspecialchars($row['contact']) ?>" class="text-blue-600 underline">
            <?= htmlspecialchars($row['contact']) ?>
        </a>
    <?php else: ?>
        <span class="text-gray-500 italic">Not Provided</span>
    <?php endif; ?>
</td>

<td class="py-3 px-4 text-xs">
    <?= !empty($row['address']) ? nl2br(htmlspecialchars($row['address'])) : '<span class="text-gray-500 italic">Not Provided</span>' ?>
</td>

                    <td class="py-3 px-4"><?= htmlspecialchars($row['category']) ?></td>
                    <td class="py-3 px-4"><?= htmlspecialchars($row['book_condition']) ?></td>
                    <td class="py-3 px-4"><?= htmlspecialchars($row['isbn']) ?: '-' ?></td>
                    <td class="py-3 px-4 capitalize">
                        <span class="font-bold 
                            <?= match($row['status']) {
                                'pending' => 'text-yellow-600',
                                'approved' => 'text-green-600',
                                'rejected' => 'text-red-600',
                                default => 'text-gray-600'
                            } ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 space-x-2">
                        <?php if ($row['status'] === 'pending'): ?>
                            <a href="process_donation_action.php?id=<?= $row['donation_id'] ?>&action=approve" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs">Approve</a>
                            <a href="process_donation_action.php?id=<?= $row['donation_id'] ?>&action=reject" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs">Reject</a>
                        <?php endif; ?>
                        <a href="process_donation_action.php?id=<?= $row['donation_id'] ?>&action=delete" class="bg-gray-500 text-white px-2 py-1 rounded hover:bg-gray-700 text-xs" onclick="return confirm('Are you sure you want to delete this donation?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p class="text-gray-600">No donation records found.</p>
<?php endif; ?>

<?php require_once '../includes/admin_footer.php'; ?>
