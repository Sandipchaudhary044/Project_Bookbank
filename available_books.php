<?php require_once 'includes/header.php'; ?>

<h1 class="text-3xl font-bold text-brand-dark mb-6">Available Books</h1>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php
    $sql = "SELECT d.*, u.username AS donor_name FROM donations d JOIN users u ON d.user_id = u.user_id WHERE d.status = 'approved' AND d.is_borrowed = 0 ORDER BY d.created_at DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($book = $result->fetch_assoc()) { ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden flex flex-col">
                <img src="uploads/<?= htmlspecialchars($book['cover_image'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($book['book_title']) ?>" class="w-full h-56 object-cover">
                
                <div class="p-4 flex flex-col flex-grow">
                    <h2 class="text-xl font-bold text-brand-dark"><?= htmlspecialchars($book['book_title']) ?></h2>
                    <p class="text-gray-600 mb-2">by <?= htmlspecialchars($book['author']) ?></p>
                    <p class="text-gray-700 text-sm flex-grow"><?= htmlspecialchars(substr($book['description'], 0, 100)) ?>...</p>
                    <p class="text-sm text-gray-500 mt-2">Donated by: <?= htmlspecialchars($book['donor_name']) ?></p>
                   <?php if (isset($_SESSION['user_id'])): ?>
                <a href="request_borrow.php?id=<?= $book['donation_id'] ?>"
                class="mt-4 w-full bg-brand-orange text-white text-center font-bold py-2 px-4 rounded hover:bg-orange-700 transition duration-300">
                    Request to Borrow
                </a>
                <?php else: ?>
                <a href="book_details.php?donation_id=<?= $book['donation_id']; ?>" class="mt-auto w-full text-center bg-brand-orange text-white font-semibold py-2 px-4 rounded-lg hover:bg-brand-orange-dark transition duration-300">
                                    See Details
                                </a>
                <?php endif; ?>

                    
                </div>
            </div>
    <?php }
    } else {
        echo "<p class='text-gray-600 col-span-full'>No books are available at the moment. Check back soon!</p>";
    }
    ?>
</div>

<?php require_once 'includes/footer.php'; ?>