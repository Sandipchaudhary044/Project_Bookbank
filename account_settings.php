<?php
require_once 'includes/header.php';
require_once 'includes/db.php'; // Assuming $conn is available from db.php

// Start the session if it's not already started (important for header.php and this file)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = "";

// Fetch current user data
// Use prepared statements for fetching to prevent SQL injection
$stmt = $conn->prepare("SELECT username, name, email, contact, address, profession, avatar, password_hash FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// If user somehow doesn't exist (shouldn't happen if logged in), redirect
if (!$user) {
    header("Location: logout.php"); // Or appropriate error page
    exit();
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $name = trim($_POST['name']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL); // Validate email format
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $profession = trim($_POST['profession']);

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Server-Side Validation ---
    if (empty($name)) {
        $errors[] = "Full Name is required.";
    }
    if (!$email) {
        $errors[] = "A valid Email address is required.";
    } else {
        // Check if email already exists for another user (if updating email)
        $stmt_check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt_check_email->bind_param("si", $email, $user_id);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $errors[] = "This email is already registered to another account.";
        }
        $stmt_check_email->close();
    }

    // Profile image handling
    $avatar = $user['avatar']; // Keep current avatar by default
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_file_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, and WEBP images are allowed for profile pictures.";
        } elseif ($_FILES['avatar']['size'] > $max_file_size) {
            $errors[] = "Profile image size must be less than 2MB.";
        } else {
            $upload_dir = 'uploads/avatars/'; // Dedicated directory for avatars
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('avatar_') . '.' . $extension; // Unique filename
            $target = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                // Delete old avatar if it's not the default one
                if (!empty($user['avatar']) && $user['avatar'] !== 'default_avatar.png' && file_exists($upload_dir . $user['avatar'])) {
                    unlink($upload_dir . $user['avatar']);
                }
                $avatar = $filename;
            } else {
                $errors[] = "Failed to upload profile image.";
            }
        }
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "An error occurred during file upload: " . $_FILES['avatar']['error'];
    }


    // Password update validation
    $update_password = false;
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (!password_verify($current_password, $user['password_hash'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($new_password) < 8 || !preg_match("#[0-9]+#", $new_password) || !preg_match("#[A-Z]+#", $new_password) || !preg_match("#[a-z]+#", $new_password)) {
            $errors[] = "New password must be at least 8 characters long and include at least one number, one uppercase, and one lowercase letter.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation password do not match.";
        } else {
            $update_password = true;
        }
    }

    // If no validation errors, proceed with update
    if (empty($errors)) {
        $query_parts = [];
        $params = [];
        $types = "";

        // Dynamically build the update query
        if ($name !== $user['name']) {
            $query_parts[] = "name = ?";
            $params[] = $name;
            $types .= "s";
        }
        if ($email !== $user['email']) {
            $query_parts[] = "email = ?";
            $params[] = $email;
            $types .= "s";
        }
        if ($contact !== $user['contact']) {
            $query_parts[] = "contact = ?";
            $params[] = $contact;
            $types .= "s";
        }
        if ($address !== $user['address']) {
            $query_parts[] = "address = ?";
            $params[] = $address;
            $types .= "s";
        }
        if ($profession !== $user['profession']) {
            $query_parts[] = "profession = ?";
            $params[] = $profession;
            $types .= "s";
        }
        if ($avatar !== $user['avatar']) { // Only update if avatar changed
            $query_parts[] = "avatar = ?";
            $params[] = $avatar;
            $types .= "s";
        }
        if ($update_password) {
            $query_parts[] = "password_hash = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            $types .= "s";
        }

        if (empty($query_parts)) {
            $success = "No changes were submitted.";
        } else {
            $query = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE user_id = ?";
            $params[] = $user_id;
            $types .= "i"; // Add type for user_id

            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                $errors[] = "Database prepare error: " . $conn->error;
            } else {
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $success = "Your profile has been updated successfully!";
                    // Update session variables if changed
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_image'] = 'uploads/avatars/' . $avatar; // Store full path for header
                    // Re-fetch user data to display latest changes on the form
                    $stmt_re_fetch = $conn->prepare("SELECT username, name, email, contact, address, profession, avatar, password_hash FROM users WHERE user_id = ?");
                    $stmt_re_fetch->bind_param("i", $user_id);
                    $stmt_re_fetch->execute();
                    $result_re_fetch = $stmt_re_fetch->get_result();
                    $user = $result_re_fetch->fetch_assoc();
                    $stmt_re_fetch->close();
                } else {
                    $errors[] = "Failed to update profile: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
}
?>

<div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-xl my-8">
    <h2 class="text-3xl font-extrabold mb-8 text-brand-dark text-center">Account Settings</h2>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6" role="alert">
            <p class="font-bold">Success!</p>
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6" role="alert">
            <p class="font-bold">Errors:</p>
            <ul class="list-disc list-inside mt-2">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6" id="accountSettingsForm">

        <div class="border-b border-gray-200 pb-6">
            <h3 class="text-xl font-semibold mb-4 text-brand-dark">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-brand-orange focus:border-brand-orange sm:text-sm" required>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-brand-orange focus:border-brand-orange sm:text-sm" required>
                </div>

                <div>
                    <label for="contact" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($user['contact']) ?>" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-brand-orange focus:border-brand-orange sm:text-sm">
                </div>

                <div>
                    <label for="profession" class="block text-sm font-medium text-gray-700 mb-1">Profession</label>
                    <input type="text" id="profession" name="profession" value="<?= htmlspecialchars($user['profession']) ?>" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-brand-orange focus:border-brand-orange sm:text-sm">
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea id="address" name="address" rows="3" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-brand-orange focus:border-brand-orange sm:text-sm resize-y"><?= htmlspecialchars($user['address']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="border-b border-gray-200 pb-6">
            <h3 class="text-xl font-semibold mb-4 text-brand-dark">Profile Image</h3>
            <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
                <div class="flex-shrink-0">
                    <img src="<?= !empty($user['avatar']) ? 'uploads/avatars/' . htmlspecialchars($user['avatar']) : '/bookbank/assets/images/default_avatar.png' ?>" alt="Current Profile" class="w-24 h-24 rounded-full object-cover shadow-lg border-2 border-brand-orange">
                </div>
                <div class="flex-grow">
                    <label for="avatar" class="block text-sm font-medium text-gray-700 mb-1">Upload New Profile Image (JPG, PNG, WEBP - max 2MB)</label>
                    <input type="file" id="avatar" name="avatar" accept="image/jpeg, image/png, image/webp" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-brand-orange file:text-white
                        hover:file:bg-orange-700
                    ">
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xl font-semibold mb-4 text-brand-dark">Change Password</h3>
            <p class="text-sm text-gray-600 mb-4">Leave these fields blank if you do not wish to change your password.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-brand-orange focus:border-brand-orange sm:text-sm">
                </div>

                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-brand-orange focus:border-brand-orange sm:text-sm">
                    <p id="password-strength" class="text-xs mt-1 text-gray-500"></p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-brand-orange focus:border-brand-orange sm:text-sm">
                    <p id="password-match" class="text-xs mt-1 text-gray-500"></p>
                </div>
            </div>
        </div>

        <div class="pt-6 border-t border-gray-200 flex justify-end">
            <button type="submit" class="inline-flex justify-center py-3 px-8 border border-transparent shadow-sm text-lg font-medium rounded-md text-white bg-brand-orange hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-orange transition duration-150 ease-in-out">
                Update Settings
            </button>
        </div>
    </form>
</div>

<script>
    // Client-side password validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordMatchMsg = document.getElementById('password-match');
    const passwordStrengthMsg = document.getElementById('password-strength');

    function validatePassword() {
        if (newPassword.value === '' && confirmPassword.value === '') {
            passwordMatchMsg.textContent = '';
            passwordStrengthMsg.textContent = '';
            return true;
        }

        // Password strength check
        let strength = 0;
        let msg = [];
        if (newPassword.value.length >= 8) {
            strength++;
        } else {
            msg.push('at least 8 characters');
        }
        if (/[A-Z]/.test(newPassword.value)) strength++; else msg.push('an uppercase letter');
        if (/[a-z]/.test(newPassword.value)) strength++; else msg.push('a lowercase letter');
        if (/[0-9]/.test(newPassword.value)) strength++; else msg.push('a number');
        if (/[^A-Za-z0-9]/.test(newPassword.value)) strength++; else msg.push('a special character');

        if (strength < 3) {
            passwordStrengthMsg.textContent = 'Weak: needs ' + msg.join(', ') + '.';
            passwordStrengthMsg.className = 'text-xs mt-1 text-red-600';
        } else if (strength < 5) {
            passwordStrengthMsg.textContent = 'Medium strength.';
            passwordStrengthMsg.className = 'text-xs mt-1 text-yellow-600';
        } else {
            passwordStrengthMsg.textContent = 'Strong password!';
            passwordStrengthMsg.className = 'text-xs mt-1 text-green-600';
        }

        // Password match check
        if (newPassword.value !== confirmPassword.value) {
            passwordMatchMsg.textContent = 'Passwords do not match.';
            passwordMatchMsg.className = 'text-xs mt-1 text-red-600';
            return false;
        } else if (newPassword.value !== '' && confirmPassword.value !== '') {
            passwordMatchMsg.textContent = 'Passwords match.';
            passwordMatchMsg.className = 'text-xs mt-1 text-green-600';
            return true;
        } else {
            passwordMatchMsg.textContent = '';
            return true;
        }
    }

    newPassword.addEventListener('keyup', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);

    // Form submission client-side validation for required fields
    document.getElementById('accountSettingsForm').addEventListener('submit', function(event) {
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');

        if (nameInput.value.trim() === '') {
            alert('Full Name is required.');
            nameInput.focus();
            event.preventDefault();
            return false;
        }

        if (emailInput.value.trim() === '') {
            alert('Email Address is required.');
            emailInput.focus();
            event.preventDefault();
            return false;
        }

        if (newPassword.value !== '' || confirmPassword.value !== '') {
            if (!validatePassword()) {
                alert('Please correct your new password fields.');
                event.preventDefault();
                return false;
            }
        }
    });

</script>

<?php require_once 'includes/footer.php'; ?>