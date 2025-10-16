<?php
// Start session at the very beginning
session_start();

require_once 'includes/db.php';

// --- Configuration ---
define('MIN_PASSWORD_LENGTH', 8);

// --- Initialization ---
$errors = [];
$success_message = '';
$submitted_data = []; // To repopulate form on error

// --- CSRF Protection: Generate a token to prevent cross-site request forgery ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];


// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors['form'] = "Invalid request. Please refresh and try again.";
    } else {
        // 2. Sanitize and store submitted data for repopulation
        $submitted_data = [
            'username'   => trim($_POST['username'] ?? ''),
            'email'      => trim($_POST['email'] ?? ''),
            'contact'    => trim($_POST['contact'] ?? ''),
            'address'    => trim($_POST['address'] ?? ''),
            'profession' => trim($_POST['profession'] ?? ''),
        ];
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // 3. Perform Detailed Validation
        // Username
        if (empty($submitted_data['username'])) {
            $errors['username'] = "Username is required.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,25}$/', $submitted_data['username'])) {
            $errors['username'] = "Username must be 3-25 characters long and contain only letters, numbers, and underscores.";
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $submitted_data['username']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['username'] = "This username is already taken.";
            }
            $stmt->close();
        }

        // Email
        if (empty($submitted_data['email'])) {
            $errors['email'] = "Email is required.";
        } elseif (!filter_var($submitted_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Please enter a valid email address.";
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $submitted_data['email']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['email'] = "This email address is already registered.";
            }
            $stmt->close();
        }

        // Contact, Address, Profession
        if (empty($submitted_data['contact'])) $errors['contact'] = "Contact number is required.";
        if (empty($submitted_data['address'])) $errors['address'] = "Address is required.";
        if (empty($submitted_data['profession'])) $errors['profession'] = "Profession is required.";

        // Password
        if (empty($password)) {
            $errors['password'] = "Password is required.";
        } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors['password'] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long.";
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
            $errors['password'] = "Password needs at least one uppercase letter, one lowercase, one number, and one special character.";
        } elseif ($password !== $password_confirm) {
            $errors['password_confirm'] = "Passwords do not match.";
        }
        
        // 4. If no errors, proceed with registration
        if (empty($errors)) {
            // Use a stronger hashing algorithm
            $password_hash = password_hash($password, PASSWORD_ARGON2ID);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, address, profession, contact, name) VALUES (?, ?, ?, ?, ?, ?, ?)");
            // Assuming 'name' can be the same as 'username' initially
            $stmt->bind_param("sssssss", $submitted_data['username'], $submitted_data['email'], $password_hash, $submitted_data['address'], $submitted_data['profession'], $submitted_data['contact'], $submitted_data['username']);

            if ($stmt->execute()) {
                $success_message = "Registration successful! You can now <a href='login.php' class='text-brand-orange font-semibold underline hover:text-orange-700'>login</a>.";
                // Clear form data on success
                $submitted_data = []; 
                // Regenerate CSRF token after successful submission
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                // This is a fallback error in case the database fails for other reasons (e.g., connection issue)
                $errors['form'] = "An unexpected error occurred. Please try again later.";
            }
            $stmt->close();
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full space-y-8 bg-white p-10 rounded-xl shadow-lg">
        <div>
            <h1 class="mt-6 text-center text-3xl font-extrabold text-brand-dark">
                Create Your Account
            </h1>
            <p class="mt-2 text-center text-sm text-gray-600">
                And start your journey with us.
            </p>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Success!</p>
                <p><?= $success_message ?></p>
            </div>
            <p class="mt-4 text-center text-sm text-gray-600">
                Return to the <a href="login.php" class="text-brand-orange font-semibold hover:underline">Login page</a>.
            </p>
        <?php else: ?>
            <!-- Display a general form error if one exists -->
            <?php if (isset($errors['form'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($errors['form']) ?></span>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" action="register.php" method="POST" novalidate>
                <!-- Add the CSRF token to the form -->
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <!-- Username -->
                    <div>
                        <label for="username" class="sr-only">Username</label>
                        <input id="username" name="username" type="text" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border <?= isset($errors['username']) ? 'border-red-500' : 'border-gray-300' ?> placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-brand-orange focus:border-brand-orange focus:z-10 sm:text-sm" 
                               placeholder="Username" value="<?= htmlspecialchars($submitted_data['username'] ?? '') ?>">
                        <?php if (isset($errors['username'])): ?>
                            <p class="text-red-500 text-xs mt-1 px-1"><?= htmlspecialchars($errors['username']) ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- Email -->
                    <div class="pt-4">
                        <label for="email" class="sr-only">Email Address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?> placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-brand-orange focus:border-brand-orange focus:z-10 sm:text-sm" 
                               placeholder="Email address" value="<?= htmlspecialchars($submitted_data['email'] ?? '') ?>">
                        <?php if (isset($errors['email'])): ?>
                            <p class="text-red-500 text-xs mt-1 px-1"><?= htmlspecialchars($errors['email']) ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- Contact -->
                    <div class="pt-4">
                        <label for="contact" class="sr-only">Contact Number</label>
                        <input id="contact" name="contact" type="tel" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border <?= isset($errors['contact']) ? 'border-red-500' : 'border-gray-300' ?> placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-brand-orange focus:border-brand-orange focus:z-10 sm:text-sm" 
                               placeholder="Contact Number" value="<?= htmlspecialchars($submitted_data['contact'] ?? '') ?>">
                        <?php if (isset($errors['contact'])): ?>
                            <p class="text-red-500 text-xs mt-1 px-1"><?= htmlspecialchars($errors['contact']) ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- Address -->
                    <div class="pt-4">
                        <label for="address" class="sr-only">Address</label>
                        <textarea id="address" name="address" rows="3" required
                                  class="appearance-none rounded-none relative block w-full px-3 py-2 border <?= isset($errors['address']) ? 'border-red-500' : 'border-gray-300' ?> placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-brand-orange focus:border-brand-orange focus:z-10 sm:text-sm" 
                                  placeholder="Address"><?= htmlspecialchars($submitted_data['address'] ?? '') ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <p class="text-red-500 text-xs mt-1 px-1"><?= htmlspecialchars($errors['address']) ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- Profession -->
                    <div class="pt-4">
                        <label for="profession" class="sr-only">Profession</label>
                        <input id="profession" name="profession" type="text" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border <?= isset($errors['profession']) ? 'border-red-500' : 'border-gray-300' ?> placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-brand-orange focus:border-brand-orange focus:z-10 sm:text-sm" 
                               placeholder="Profession" value="<?= htmlspecialchars($submitted_data['profession'] ?? '') ?>">
                        <?php if (isset($errors['profession'])): ?>
                            <p class="text-red-500 text-xs mt-1 px-1"><?= htmlspecialchars($errors['profession']) ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- Password -->
                    <div class="pt-4 relative">
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border <?= isset($errors['password']) ? 'border-red-500' : 'border-gray-300' ?> placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-brand-orange focus:border-brand-orange focus:z-10 sm:text-sm" 
                               placeholder="Password">
                        <button type="button" class="absolute inset-y-0 right-0 top-4 pr-3 flex items-center text-sm leading-5" onclick="togglePasswordVisibility('password')">
                            <!-- SVG icon for password visibility toggle -->
                            <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                     <?php if (isset($errors['password'])): ?>
                        <p class="text-red-500 text-xs mt-1 px-1"><?= htmlspecialchars($errors['password']) ?></p>
                    <?php endif; ?>
                    <!-- Confirm Password -->
                    <div class="pt-4 relative">
                        <label for="password_confirm" class="sr-only">Confirm Password</label>
                        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border <?= isset($errors['password_confirm']) ? 'border-red-500' : 'border-gray-300' ?> placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-brand-orange focus:border-brand-orange focus:z-10 sm:text-sm" 
                               placeholder="Confirm Password">
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <p class="text-red-500 text-xs mt-1 px-1"><?= htmlspecialchars($errors['password_confirm']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-brand-orange hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-orange">
                        Register
                    </button>
                </div>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600">
                Already have an account?
                <a href="login.php" class="font-medium text-brand-orange hover:text-orange-700">
                    Login here
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>

<script>
// Simple script to toggle password visibility
function togglePasswordVisibility(id) {
    const passwordInput = document.getElementById(id);
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);

    // Optional: toggle the icon (requires more complex SVG handling or two SVGs)
    const icon = passwordInput.nextElementSibling.querySelector('svg');
    // This is a simple example; a real implementation might swap the SVG path 'd' attribute
    icon.classList.toggle('text-brand-orange');
}
</script>

<?php require_once 'includes/footer.php'; ?>
