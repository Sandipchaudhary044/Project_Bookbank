<?php
// =================================================================
//  STEP 1: PHP LOGIC & DATABASE OPERATIONS
// =================================================================

// All PHP logic must come before including the header.
require_once '../includes/db.php';

// Fetch all messages from the database
// CHANGE #1: Rename $result to $messages_result
$messages_result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

// We'll check for query errors
if (!$messages_result) {
    die("Database query failed: " . $conn->error);
}

// CHANGE #2: Use the new variable name here
$message_count = $messages_result->num_rows;

// =================================================================
//  STEP 2: INCLUDE THE ADMIN HEADER
// =================================================================
require_once '../includes/admin_header.php';
?>

<div class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
    
    <div class="flex justify-between items-center mb-6 pb-4 border-b">
        <h1 class="text-3xl font-bold text-brand-dark">Contact Messages</h1>
        <span class="px-3 py-1 text-sm font-semibold bg-brand-primary text-white rounded-full">
            <?= $message_count ?> Total
        </span>
    </div>

    <?php if ($message_count > 0): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto text-left">
            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-sm">
                    <th class="px-6 py-3 font-semibold">#</th>
                    <th class="px-6 py-3 font-semibold">Sender</th>
                    <th class="px-6 py-3 font-semibold">Message</th>
                    <th class="px-6 py-3 font-semibold">Received</th>
                    <th class="px-6 py-3 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                
                <?php $sn = 1; while ($row = $messages_result->fetch_assoc()): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium"><?= $sn++ ?></td>
                        <td class="px-6 py-4">
                            <div class="font-semibold"><?= htmlspecialchars($row['name']) ?></div>
                            <div class="text-sm text-gray-500"><?= htmlspecialchars($row['email']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-pre-line text-sm max-w-md"><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?= date("M d, Y, g:i A", strtotime($row['created_at'])) ?></td>
                        <td class="px-6 py-4 text-center">
                            <a href="delete_message.php?id=<?= $row['id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete this message?');"
                               class="bg-red-500 text-white px-3 py-1 rounded-md text-sm font-semibold hover:bg-red-600 transition-colors">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-16">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-semibold text-gray-900">No messages</h3>
        <p class="mt-1 text-sm text-gray-500">There are currently no messages from the contact form.</p>
    </div>
    <?php endif; ?>
</div>

<?php
// =================================================================
//  STEP 4: INCLUDE THE ADMIN FOOTER
// =================================================================
require_once '../includes/admin_footer.php';
?>