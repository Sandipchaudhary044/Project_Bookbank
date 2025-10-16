<?php
// Set PHP timezone to UTC
date_default_timezone_set('UTC');

require_once 'includes/db.php';

// Set MySQL timezone to UTC
$conn->query("SET time_zone = '+00:00'");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists in users table
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // Generate token and expiration time
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Log times for debugging (optional)
        error_log("Token expires at (PHP UTC): $expires");
        error_log("Current PHP time (UTC): " . date('Y-m-d H:i:s'));

        // Delete any previous tokens for this email
        $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();

        // Insert new reset token with expiration
        $insertStmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $email, $token, $expires);
        $insertStmt->execute();

        // Prepare reset link (in production, send this by email)
        $reset_link = "http://localhost/bookbank/reset_password.php?token=$token";

        $success = "Reset link (valid for 1 hour): <a href='$reset_link' class='text-blue-500 underline'>$reset_link</a>";
    } else {
        $error = "Email not found.";
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-xl font-bold mb-4 text-center">Forgot Password</h2>

    <?php if ($error): ?>
        <p class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($success): ?>
        <p class="bg-green-100 text-green-700 p-2 rounded mb-4"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label class="block mb-2 font-medium text-gray-700" for="email">Enter your email</label>
        <input id="email" type="email" name="email" class="w-full border rounded px-4 py-2 mb-4" required>
        <button type="submit" class="w-full bg-brand-orange text-white py-2 rounded hover:bg-orange-700">Send Reset Link</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
