<?php
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_title    = trim($_POST['book_title']);
    $author        = trim($_POST['author']);
    $category      = trim($_POST['category']);
    $condition     = trim($_POST['condition']);
    $quantity      = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1; // Get quantity, default to 1 if not set
    $isbn          = trim($_POST['isbn'] ?? '');
    $contact       = trim($_POST['contact']);
    $address       = trim($_POST['address']);
    $description   = trim($_POST['description']);
    $user_id       = $_SESSION['user_id'];

    // Required fields validation
    if (empty($book_title) || empty($author) || empty($category) || empty($condition) || empty($contact) || empty($address) || empty($quantity)) {
        $errors[] = "All fields marked with * are required.";
    }

    // Quantity specific validation
    if ($quantity < 1) {
        $errors[] = "Quantity must be at least 1.";
    }

    // Proceed only if no errors
    if (empty($errors)) {
        // Upload cover image
        $cover_image = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($_FILES['cover_image']['type'], $allowed)) {
                $errors[] = "Only JPG, PNG, and WEBP formats are allowed.";
            } else {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $filename = uniqid() . '_' . basename($_FILES['cover_image']['name']);
                $target = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target)) {
                    $cover_image = $filename;
                } else {
                    $errors[] = "Failed to upload image.";
                }
            }
        } else {
            $errors[] = "Book cover image is required.";
        }
    }

    // Final DB insert
    if (empty($errors)) {
        // IMPORTANT: Ensure your 'donations' table has a 'quantity' column.
        // If not, this INSERT statement will cause an error.
        $stmt = $conn->prepare("INSERT INTO donations (user_id, book_title, author, category, book_condition, quantity, isbn, contact, address, description, cover_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        // Note: 'i' for integer for quantity
        $stmt->bind_param("issssisssss", $user_id, $book_title, $author, $category, $condition, $quantity, $isbn, $contact, $address, $description, $cover_image);

        if ($stmt->execute()) {
            $success_message = "Thank you for your donation! It has been submitted for admin review.";
            // Clear form fields after successful submission
            $_POST = array(); // Clears all post data to empty the form
        } else {
            $errors[] = "There was an error submitting your donation. Please try again. " . $stmt->error; // Added $stmt->error for debugging
        }

        $stmt->close();
    }
}
?>

<div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center text-brand-dark mb-6">Donate a Book</h1>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <p><?= $success_message ?></p>
        </div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form id="donationForm" method="POST" enctype="multipart/form-data">

            <div class="mb-4">
                <label for="book_title" class="block text-gray-700 font-semibold">Book Title <span class="text-red-600">*</span></label>
                <input type="text" name="book_title" id="book_title" class="w-full px-3 py-2 border rounded-lg" value="<?= htmlspecialchars($_POST['book_title'] ?? '') ?>" required>
            </div>

            <div class="mb-4">
                <label for="author" class="block text-gray-700 font-semibold">Author <span class="text-red-600">*</span></label>
                <input type="text" name="author" id="author" class="w-full px-3 py-2 border rounded-lg" value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" required>
            </div>

            <div class="mb-4">
                <label for="category" class="block text-gray-700 font-semibold">Category <span class="text-red-600">*</span></label>
                <select name="category" id="category" class="w-full px-3 py-2 border rounded-lg" required>
                    <option value="" disabled <?= !isset($_POST['category']) ? 'selected' : '' ?>>Select category</option>
                    <option value="SEE" <?= ($_POST['category'] ?? '') === 'SEE' ? 'selected' : '' ?>>SEE</option>
                    <option value="+2" <?= ($_POST['category'] ?? '') === '+2' ? 'selected' : '' ?>>+2</option>
                    <option value="Academic" <?= ($_POST['category'] ?? '') === 'Academic' ? 'selected' : '' ?>>Academic</option>
                    <option value="Non-academic" <?= ($_POST['category'] ?? '') === 'Non-academic' ? 'selected' : '' ?>>Non-academic</option>
                    <option value="Fiction" <?= ($_POST['category'] ?? '') === 'Fiction' ? 'selected' : '' ?>>Fiction</option>
                    <option value="Other" <?= ($_POST['category'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="condition" class="block text-gray-700 font-semibold">Book Condition <span class="text-red-600">*</span></label>
                <select name="condition" id="condition" class="w-full px-3 py-2 border rounded-lg" required>
                    <option value="" disabled <?= !isset($_POST['condition']) ? 'selected' : '' ?>>Select condition</option>
                    <option value="New" <?= ($_POST['condition'] ?? '') === 'New' ? 'selected' : '' ?>>New</option>
                    <option value="Good" <?= ($_POST['condition'] ?? '') === 'Good' ? 'selected' : '' ?>>Good</option>
                    <option value="Used" <?= ($_POST['condition'] ?? '') === 'Used' ? 'selected' : '' ?>>Used</option>
                    <option value="Worn" <?= ($_POST['condition'] ?? '') === 'Worn' ? 'selected' : '' ?>>Worn</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="quantity" class="block text-gray-700 font-semibold">Quantity <span class="text-red-600">*</span></label>
                <input type="number" name="quantity" id="quantity" class="w-full px-3 py-2 border rounded-lg" min="1" value="<?= htmlspecialchars($_POST['quantity'] ?? '1') ?>" required>
                <p class="text-gray-500 text-sm mt-1">Number of copies you are donating.</p>
            </div>

            <div class="mb-4">
                <label for="isbn" class="block text-gray-700 font-semibold">ISBN (Optional)</label>
                <input type="text" name="isbn" id="isbn" class="w-full px-3 py-2 border rounded-lg" value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>">
            </div>

            <div class="mb-4">
                <label for="contact" class="block text-gray-700 font-semibold">Contact Number <span class="text-red-600">*</span></label>
                <input type="text" name="contact" id="contact" class="w-full px-3 py-2 border rounded-lg" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" required pattern="[0-9]{10}" title="Enter a valid 10-digit number">
            </div>

            <div class="mb-4">
                <label for="address" class="block text-gray-700 font-semibold">Pickup Address <span class="text-red-600">*</span></label>
                <textarea name="address" id="address" rows="2" class="w-full px-3 py-2 border rounded-lg" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>

            <div class="mb-4">
                <label for="cover_image" class="block text-gray-700 font-semibold">Book Cover Image <span class="text-red-600">*</span></label>
                <input type="file" name="cover_image" id="cover_image" accept="image/*" class="w-full px-3 py-2 border rounded-lg" required>
            </div>

            <div class="mb-6">
                <label for="description" class="block text-gray-700 font-semibold">Description (Optional)</label>
                <textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border rounded-lg"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="w-full bg-brand-orange text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-700">
                Submit Donation
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>