<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_GET['donation_id']) || !is_numeric($_GET['donation_id'])) {
    echo "<p class='text-red-600'>Invalid book ID.</p>";
    require_once 'includes/footer.php';
    exit();
}

$donation_id = (int)$_GET['donation_id'];

// Fetch book details + donor username
$stmt = $conn->prepare("
    SELECT d.*, u.username 
    FROM donations d 
    JOIN users u ON d.user_id = u.user_id
    WHERE d.donation_id = ? AND d.status = 'approved'
");
$stmt->bind_param('i', $donation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='text-red-600'>Book not found or not approved yet.</p>";
    require_once 'includes/footer.php';
    exit();
}

$book = $result->fetch_assoc();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md my-8">
    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-shrink-0">
            <?php 
            $imagePath = !empty($book['cover_image']) ? 'uploads/' . $book['cover_image'] : 'assets/images/default_book.png'; 
            ?>
            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($book['book_title']) ?>" class="rounded-lg max-w-xs mx-auto md:max-w-sm" 
                onerror="this.onerror=null;this.src='assets/images/default_book.png';">
        </div>

        <div class="flex-1">
            <h1 class="text-3xl font-bold text-brand-dark mb-2"><?= htmlspecialchars($book['book_title']) ?></h1>
            <h2 class="text-xl text-gray-700 mb-4">By <?= htmlspecialchars($book['author']) ?></h2>
            
            <p class="mb-2"><strong>Category:</strong> <?= htmlspecialchars($book['category']) ?></p>
            <p class="mb-2"><strong>Condition:</strong> <?= htmlspecialchars($book['book_condition']) ?></p>
            <?php if (!empty($book['isbn'])): ?>
                <p class="mb-2"><strong>ISBN:</strong> <?= htmlspecialchars($book['isbn']) ?></p>
            <?php endif; ?>

            <p class="mb-4 text-gray-600"><?= nl2br(htmlspecialchars($book['description'])) ?></p>

            <p class="mb-2"><strong>Status:</strong> 
                <span class="<?= $book['is_borrowed'] ? 'text-red-600' : 'text-green-600' ?>">
                    <?= $book['is_borrowed'] ? 'Not Available' : 'Available' ?>
                </span>
            </p>

            <p class="mb-2"><strong>Donated by:</strong> <?= htmlspecialchars($book['username']) ?></p>

            <?php if ($is_logged_in): ?>
                <p class="mb-2"><strong>Pickup Address:</strong> <br><?= nl2br(htmlspecialchars($book['address'])) ?></p>
                <p class="mb-4"><strong>Contact:</strong> <?= htmlspecialchars($book['contact']) ?></p>

                <?php if (!$book['is_borrowed']): ?>
                    <a href="borrow.php?donation_id=<?= $book['donation_id'] ?>" 
                       class="inline-block bg-brand-orange text-white px-6 py-3 rounded hover:bg-orange-700 transition">
                        Borrow this Book
                    </a>
                <?php else: ?>
                    <button disabled class="inline-block bg-gray-400 text-white px-6 py-3 rounded cursor-not-allowed">
                        Currently Unavailable
                    </button>
                <?php endif; ?>

            <?php else: ?>
                <p class="mt-6 text-center">
                    <a href="login.php" class="text-brand-orange font-semibold hover:underline">
                        Login to Borrow this book
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
