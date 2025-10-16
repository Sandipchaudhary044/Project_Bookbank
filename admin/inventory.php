<?php
session_start();

// Include DB connection and admin header
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

// Protect this page and check for admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: " . $base_url . "/login.php");
    exit();
}

// Fetch inventory data
// This query groups donations by book_title and calculates:
// 1. total_approved_donations: The total number of individual books (donation entries) for that title that are approved.
// 2. currently_borrowed_count: The number of those approved books that are currently marked as borrowed (is_borrowed = 1).
$sql = "
    SELECT
        book_title,
        COUNT(donation_id) AS total_approved_donations,
        SUM(CASE WHEN is_borrowed = 1 THEN 1 ELSE 0 END) AS currently_borrowed_count
    FROM
        donations
    WHERE
        status = 'approved'
    GROUP BY
        book_title
    ORDER BY
        book_title ASC
";

$stmt = $conn->prepare($sql);
$inventory_data = [];

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $inventory_data[] = $row;
    }
    $stmt->close();
} else {
    // Log error if statement preparation fails
    error_log("Failed to prepare inventory query: " . $conn->error);
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Error loading inventory data.'];
}

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-4xl font-extrabold text-brand-dark mb-10 text-center">Book Inventory</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="p-4 mb-6 text-sm <?= $_SESSION['message']['type'] === 'success' ? 'text-green-700 bg-green-100 border-l-4 border-green-500' : 'text-red-700 bg-red-100 border-l-4 border-red-500' ?> rounded-lg shadow-sm" role="alert">
            <p class="font-semibold"><?= $_SESSION['message']['type'] === 'success' ? 'Success!' : 'Error!' ?></p>
            <p><?= htmlspecialchars($_SESSION['message']['text']) ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-lg shadow-xl border border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-brand-dark">Available Books Overview</h2>
            <!-- Could add a search bar or filter options here later -->
        </div>

        <?php if (!empty($inventory_data)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Quantity</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Currently Borrowed</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($inventory_data as $book): ?>
                            <?php
                                $available_quantity = $book['total_approved_donations'] - $book['currently_borrowed_count'];
                                $available_class = '';
                                if ($available_quantity <= 0) {
                                    $available_class = 'text-red-600 font-semibold';
                                } elseif ($available_quantity <= 5) {
                                    $available_class = 'text-yellow-600 font-semibold';
                                } else {
                                    $available_class = 'text-green-600 font-semibold';
                                }
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($book['book_title']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">
                                    <?= htmlspecialchars($book['total_approved_donations']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">
                                    <?= htmlspecialchars($book['currently_borrowed_count']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm <?= $available_class ?>">
                                    <?= htmlspecialchars($available_quantity) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-gray-50 p-6 text-center rounded-lg border border-dashed border-gray-300">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <p class="mt-2 text-sm text-gray-600">No approved books found in the inventory.</p>
                <p class="mt-1 text-sm text-gray-500">Encourage users to donate books to build up your inventory!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>