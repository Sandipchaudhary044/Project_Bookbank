<?php
// Set timezone to UTC to sync with database
date_default_timezone_set('UTC');

require_once 'includes/db.php';

// Set MySQL timezone to UTC as well
$conn->query("SET time_zone = '+00:00'");

// Get token from GET parameter safely
if (isset($_GET['token'])) {
    $token = $_GET['token'];
} else {
    $token = '';
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $token = $_POST['token'];

    // Prepare statement to get email and expires_at from token
    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            // Check if token is expired
            if ($row['expires_at'] < date('Y-m-d H:i:s')) {
                $error = "Token expired.";
            } else {
                $email = $row['email'];
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Update user's password
                $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                $updateStmt->bind_param("ss", $hash, $email);
                $updateStmt->execute();

                // Delete token after use
                $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $deleteStmt->bind_param("s", $email);
                $deleteStmt->execute();

                $success = "Password has been reset. <a href='login.php' class='text-blue-500 underline'>Login here</a>";
            }
        }
    } else {
        $error = "Invalid or expired token.";
    }
}
?>

<?php require_once 'includes/header.php'; ?>
<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-xl font-bold mb-4 text-center">Reset Password</h2>

    <?php if ($error): ?>
        <p class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($success): ?>
        <p class="bg-green-100 text-green-700 p-2 rounded mb-4"><?= $success ?></p>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label class="block mb-2 font-medium text-gray-700">New Password</label>
        <input type="password" name="password" class="w-full border rounded px-4 py-2 mb-4" required>
        <label class="block mb-2 font-medium text-gray-700">Confirm Password</label>
        <input type="password" name="confirm" class="w-full border rounded px-4 py-2 mb-4" required>
        <button type="submit" class="w-full bg-brand-orange text-white py-2 rounded hover:bg-orange-700">Reset Password</button>
    </form>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
