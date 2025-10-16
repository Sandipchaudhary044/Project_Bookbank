<?php
// It's good practice to start the session at the very top.
session_start(); 
require_once '../includes/admin_header.php';
require_once '../includes/db.php';

// --- Configuration ---
define('UPLOAD_DIR', '../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

// --- Initialization ---
// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = "";

// --- CSRF Protection: Generate a token to prevent cross-site request forgery ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- Helper function to fetch user data ---
function fetch_user_data($conn, $user_id) {
    $stmt = $conn->prepare("SELECT name, email, avatar, password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

$user = fetch_user_data($conn, $user_id);
if (!$user) {
    die("Error: User not found.");
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors['form'] = "Invalid request. Please try again.";
    } else {
        // 2. Sanitize and Validate Inputs
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($name)) $errors['name'] = "Name is required.";

        if (empty($email)) {
            $errors['email'] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format.";
        } else {
            // Check if email is already taken by ANOTHER user
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['email'] = "This email is already in use.";
            }
            $stmt->close();
        }

        // 3. Handle Avatar Upload
        $new_avatar_filename = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            if ($file['size'] > MAX_FILE_SIZE) {
                $errors['profile_image'] = "File is too large. Max size is 5MB.";
            } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed_mime_types)) {
                $errors['profile_image'] = "Invalid file type. Only JPG, PNG, WEBP, or GIF are allowed.";
            } else {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_avatar_filename = 'avatar_' . $user_id . '_' . uniqid() . '.' . $extension;
                $target_path = UPLOAD_DIR . $new_avatar_filename;
                
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);

                if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                    $errors['profile_image'] = "Failed to upload the image.";
                    $new_avatar_filename = null;
                }
            }
        }
        
        // 4. Handle Password Change
        $update_password = false;
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors['current_password'] = "Enter your current password to change it.";
            } elseif (!password_verify($current_password, $user['password_hash'])) {
                $errors['current_password'] = "Current password is incorrect.";
            } elseif (strlen($new_password) < 8) {
                $errors['new_password'] = "New password must be at least 8 characters long.";
            } elseif ($new_password !== $confirm_password) {
                $errors['confirm_password'] = "The new passwords do not match.";
            } else {
                $update_password = true;
            }
        }

        // 5. Update Database if there are no validation errors
        if (empty($errors)) {
            $query_parts = ["name = ?", "email = ?"];
            $params = [$name, $email];
            $types = "ss";

            if ($new_avatar_filename) {
                $query_parts[] = "avatar = ?";
                $params[] = $new_avatar_filename;
                $types .= "s";
                
                // Delete the old avatar file if it exists
                if (!empty($user['avatar']) && file_exists(UPLOAD_DIR . $user['avatar'])) {
                    @unlink(UPLOAD_DIR . $user['avatar']);
                }
            }

            if ($update_password) {
                $query_parts[] = "password_hash = ?";
                $params[] = password_hash($new_password, PASSWORD_ARGON2ID); // Using stronger hash
                $types .= "s";
            }
            
            $query = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE user_id = ?";
            $params[] = $user_id;
            $types .= "i";

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $success_message = "Profile updated successfully! ✨";
                $_SESSION['user_name'] = $name; // Update session
                $user = fetch_user_data($conn, $user_id); // Refresh user data on page
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerate CSRF token
                $csrf_token = $_SESSION['csrf_token'];
            } else {
                $errors['form'] = "Database error: Could not update profile.";
            }
            $stmt->close();
        }
    }
}
?>

<div class="max-w-4xl mx-auto p-6 md:p-8 bg-white rounded-lg shadow-md">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Settings</h1>
    <p class="text-gray-600 mb-6">Manage your profile information and password.</p>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-6" role="alert">
            <p><?= $success_message ?></p>
        </div>
    <?php endif; ?>
    <?php if (isset($errors['form'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md mb-6" role="alert">
            <p><?= htmlspecialchars($errors['form']) ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <div class="space-y-8">
            <fieldset class="p-6 border rounded-lg">
                <legend class="text-xl font-semibold text-gray-700 px-2">Profile Information</legend>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    <div class="md:col-span-1 flex flex-col items-center text-center">
                        <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                        <img src="<?= !empty($user['avatar']) && file_exists(UPLOAD_DIR . $user['avatar']) ? htmlspecialchars(UPLOAD_DIR . $user['avatar']) : 'https://via.placeholder.com/150' ?>" 
                             alt="Current Avatar" 
                             class="h-32 w-32 rounded-full object-cover shadow-md mb-4"
                             id="avatar-preview">
                        <input type="file" name="profile_image" id="profile_image" accept=".jpg, .jpeg, .png, .webp, .gif" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-brand-orange hover:file:bg-orange-100 cursor-pointer w-full">
                        <?php if (isset($errors['profile_image'])): ?>
                            <p class="text-red-500 text-xs mt-2"><?= htmlspecialchars($errors['profile_image']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="md:col-span-2 space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="mt-1 block w-full px-3 py-2 border <?= isset($errors['name']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md shadow-sm focus:outline-none focus:ring-brand-orange focus:border-brand-orange">
                            <?php if (isset($errors['name'])): ?>
                                <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['name']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="mt-1 block w-full px-3 py-2 border <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md shadow-sm focus:outline-none focus:ring-brand-orange focus:border-brand-orange">
                            <?php if (isset($errors['email'])): ?>
                                <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['email']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset class="p-6 border rounded-lg">
                <legend class="text-xl font-semibold text-gray-700 px-2">Change Password</legend>
                <p class="text-sm text-gray-500 mt-1 mb-4">Leave these fields blank to keep your current password.</p>
                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="mt-1 block w-full px-3 py-2 border <?= isset($errors['current_password']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md shadow-sm focus:outline-none focus:ring-brand-orange focus:border-brand-orange">
                         <?php if (isset($errors['current_password'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['current_password']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password (min. 8 characters)</label>
                        <input type="password" id="new_password" name="new_password" class="mt-1 block w-full px-3 py-2 border <?= isset($errors['new_password']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md shadow-sm focus:outline-none focus:ring-brand-orange focus:border-brand-orange">
                         <?php if (isset($errors['new_password'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['new_password']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="mt-1 block w-full px-3 py-2 border <?= isset($errors['confirm_password']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md shadow-sm focus:outline-none focus:ring-brand-orange focus:border-brand-orange">
                         <?php if (isset($errors['confirm_password'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-brand-orange text-white font-bold py-2 px-6 rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-orange transition duration-150 ease-in-out">
                Update Settings
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('profile_image').addEventListener('change', function(event) {
    const [file] = event.target.files;
    if (file) {
        const preview = document.getElementById('avatar-preview');
        preview.src = URL.createObjectURL(file);
        preview.onload = () => URL.revokeObjectURL(preview.src); // free up memory after load
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>