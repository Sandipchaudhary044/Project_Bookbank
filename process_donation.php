<?php
session_start();
require_once 'includes/db.php'; // DB connection

$errors = [];
$success_message = '';

if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-red-600'>You must be logged in to donate.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];
$book_title = trim($_POST['book_title'] ?? '');
$author = trim($_POST['author'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$condition = trim($_POST['book_condition'] ?? '');
isbn = trim($_POST['isbn'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$address = trim($_POST['address'] ?? '');

if (empty($book_title) || empty($author) || empty($category) || empty($condition) || empty($contact) || empty($address)) {
    $errors[] = "Please fill in all required fields marked with *.";
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir);
$filename = '';

if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
    $file = $_FILES['cover_image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024;

    if (!in_array($file['type'], $allowedTypes)) {
        $errors[] = "Only JPG, PNG, and WEBP formats are allowed.";
    }

    if ($file['size'] > $maxSize) {
        $errors[] = "Image must be under 2MB.";
    }

    if (empty($errors)) {
        $filename = uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors[] = "Image upload failed.";
        }
    }
} else {
    $errors[] = "Book cover image is required.";
}

if (empty($errors)) {
    $stmt = $conn->prepare("INSERT INTO donations 
        (user_id, book_title, author, description, image_path, cover_image, category, book_condition, isbn, contact, address, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

    $stmt->bind_param("issssssssss", 
        $user_id, 
        $book_title, 
        $author, 
        $description, 
        $filename,     // image_path
        $filename,     // cover_image
        $category,
        $condition,
        $isbn,
        $contact,
        $address
    );

    if ($stmt->execute()) {
        echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded'>Thank you! Your donation has been submitted.</div>";
    } else {
        echo "<div class='text-red-600'>Failed to submit donation.</div>";
    }
    $stmt->close();
} else {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>";
    foreach ($errors as $e) echo "<p>$e</p>";
    echo "</div>";
}
?>
