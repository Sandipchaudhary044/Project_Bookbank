<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$searchQuery = trim($_GET['q'] ?? '');
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Search Results for: "<?= htmlspecialchars($searchQuery) ?>"</h1>

    <?php
    if ($searchQuery !== '') {
        $query = '%' . strtolower($searchQuery) . '%';
        $stmt = $conn->prepare("SELECT * FROM donations 
                                WHERE status = 'approved' 
                                AND (LOWER(book_title) LIKE ? 
                                     OR LOWER(author) LIKE ? 
                                     OR LOWER(category) LIKE ?)");
        $stmt->bind_param("sss", $query, $query, $query);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($book = $result->fetch_assoc()):
                    $cover = !empty($book['cover_image']) ? 'uploads/' . $book['cover_image'] : 'assets/images/default_book.png';
                ?>
                    <div class="bg-white rounded shadow p-4 flex flex-col h-full relative">
                        <!-- Image with badge -->
                        <div class="relative">
                            <img src="<?= htmlspecialchars($cover) ?>" 
                                 alt="<?= htmlspecialchars($book['book_title']) ?>" 
                                 class="h-48 w-full object-cover rounded mb-2"
                                 onerror="this.onerror=null;this.src='assets/images/default_book.png';">

                            <!-- Availability Badge -->
                            <?php if ($book['is_borrowed'] == 1): ?>
                                <span class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">Not Available</span>
                            <?php else: ?>
                                <span class="absolute top-2 left-2 bg-green-600 text-white text-xs font-bold px-2 py-1 rounded">Available</span>
                            <?php endif; ?>
                        </div>

                        <h3 class="font-semibold text-lg"><?= htmlspecialchars($book['book_title']) ?></h3>
                        <p class="text-sm text-gray-600">Author: <?= htmlspecialchars($book['author']) ?></p>
                        <p class="text-sm text-gray-600">Category: <?= htmlspecialchars($book['category']) ?></p>

                        <?php if (!empty($book['isbn'])): ?>
                            <p class="text-sm text-gray-600">ISBN: <?= htmlspecialchars($book['isbn']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($book['description'])): ?>
                            <p class="text-sm mt-1 text-gray-700"><?= nl2br(htmlspecialchars($book['description'])) ?></p>
                        <?php endif; ?>

                        <div class="mt-auto pt-3">
                            <?php if ($book['is_borrowed'] == 1): ?>
                                <button disabled class="block w-full text-center bg-gray-400 text-white font-semibold py-2 px-4 rounded cursor-not-allowed">
                                    Currently Unavailable
                                </button>
                            <?php elseif (isset($_SESSION['user_id'])): ?>
                                <a href="request_borrow.php?id=<?= $book['donation_id'] ?>" 
                                   class="block text-center bg-brand-orange text-white font-semibold py-2 px-4 rounded hover:bg-green-700 transition duration-300">
                                    Request to Borrow
                                </a>
                            <?php else: ?>
                                <a href="book_details.php?donation_id=<?= $book['donation_id'] ?>" 
                                   class="block text-center bg-brand-orange text-white font-semibold py-2 px-4 rounded hover:bg-orange-700 transition duration-300">
                                    See Details
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-red-600 mt-4">No books found matching your search.</p>
        <?php endif;
        $stmt->close();
    } else {
        echo "<p class='text-gray-500'>Please enter a search keyword.</p>";
    }
    ?>
</div>

<?php require_once 'includes/footer.php'; ?>
