<?php
// --- SETUP AND INITIALIZATION ---

// Sessions are required for login state, CSRF, and rate limiting.
// Best practice: start session at the very top.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php'; // Your database connection

// --- CONFIGURATION ---
define('LOGIN_ATTEMPT_LIMIT', 5); // Max login attempts
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minutes in seconds

// Define base_url for redirects
$base_url = '/bookbank'; // Adjust this if your base URL is different

// --- INITIALIZE VARIABLES ---
$errors = [];
$submitted_email = '';


// --- CSRF PROTECTION ---
// Generate a CSRF token if one doesn't exist in the session.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- EARLY REDIRECT FOR ALREADY LOGGED-IN USERS ---
// This prevents showing the login form if the user is already authenticated.
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header("Location: " . $base_url . "/admin/dashboard.php");
    } else {
        header("Location: " . $base_url . "/index.php"); // Or my_account.php if you have one
    }
    exit(); // IMPORTANT: Stop script execution after redirect
}


// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. VERIFY CSRF TOKEN
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        // 2. RATE LIMITING CHECK
        $time = time();
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= LOGIN_ATTEMPT_LIMIT && $time < $_SESSION['lockout_time']) {
            $remaining_time = ceil(($_SESSION['lockout_time'] - $time) / 60);
            $errors[] = "Too many failed login attempts. Please wait for {$remaining_time} minute(s).";
        } else {
            // 3. PROCESS LOGIN DATA
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            // Removed: $remember_me = isset($_POST['remember_me']);
            $submitted_email = $email; // For repopulating the form

            if (empty($email) || empty($password)) {
                $errors[] = "Email and password are required.";
            } else {
                // 4. FETCH USER FROM DATABASE
                // Assuming 'email' is the login identifier
                $stmt = $conn->prepare("SELECT user_id, username, password_hash, is_admin, avatar FROM users WHERE email = ?");

                
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    // 5. VERIFY PASSWORD
                    if (password_verify($password, $user['password_hash'])) {
                        // SUCCESS! Reset rate limiting on success.
                        unset($_SESSION['login_attempts'], $_SESSION['lockout_time']);

                        // Regenerate session ID to prevent session fixation attacks.
                        session_regenerate_id(true);

                        // Set session variables.
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_name'] = $user['username'];
                        $_SESSION['is_admin'] = (bool)$user['is_admin']; // Ensure boolean type
                        $_SESSION['avatar'] = $user['avatar'] ?? null;


                        // Check if password needs to be rehashed to a stronger algorithm.
                        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                            $new_hash = password_hash($password, PASSWORD_DEFAULT);
                            $rehash_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                            $rehash_stmt->bind_param("si", $new_hash, $user['user_id']);
                            $rehash_stmt->execute();
                            $rehash_stmt->close();
                        }
                        
                        // Removed: "Remember Me" handling as it's no longer needed

                        // 6. REDIRECT USER after successful login
                        $redirect_path = $_SESSION['is_admin'] ? $base_url . '/admin/dashboard.php' : $base_url . '/index.php'; // Or my_account.php
                        header("Location: " . $redirect_path);
                        exit(); // IMPORTANT: Stop script execution after redirect
                        
                    }
                }
                
                // FAILURE! Invalid credentials.
                // Increment rate limiting counter.
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                if ($_SESSION['login_attempts'] >= LOGIN_ATTEMPT_LIMIT) {
                    $_SESSION['lockout_time'] = $time + LOGIN_LOCKOUT_TIME;
                }
                // Generic error to prevent user enumeration.
                $errors[] = "Invalid email or password.";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<?php require_once 'includes/header.php'; ?>

<div class="min-h-[70vh] flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg">
        <div>
            <h1 class="text-center text-3xl font-extrabold text-brand-dark">
                Login to Your Account
            </h1>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
            <p class="font-bold">Login Failed</p>
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="login.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="rounded-md shadow-sm -space-y-px">
                <div class="mb-4">
                    <label for="email" class="sr-only">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           class="appearance-none rounded-md relative block w-full px-3 py-3 border <?= !empty($errors) ? 'border-red-500' : 'border-gray-300' ?> placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-brand-primary focus:border-brand-primary focus:z-10 sm:text-sm" 
                           placeholder="Email address" value="<?= htmlspecialchars($submitted_email) ?>">
                </div>
                <div class="relative">
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required 
                           class="appearance-none rounded-md relative block w-full px-3 py-3 border <?= !empty($errors) ? 'border-red-500' : 'border-gray-300' ?> placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-brand-primary focus:border-brand-primary focus:z-10 sm:text-sm" 
                           placeholder="Password">
                    <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500" onclick="togglePasswordVisibility('password')">
                        <svg id="eye-icon" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                           <svg id="eye-off-icon" class="h-5 w-5 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.367zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-end"> <div class="text-sm">
                    <a href="forgot_password.php" class="font-medium text-brand-primary hover:text-amber-600">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-brand-orange hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                    Login
                </button>
            </div>
        </form>

        <div class="mt-6 text-center text-sm">
            <p class="text-gray-600">
                Don't have an account?
                <a href="register.php" class="font-semibold text-brand-orange hover:underline">Register here</a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(id) {
    const passwordInput = document.getElementById(id);
    const eyeIcon = document.getElementById('eye-icon');
    const eyeOffIcon = document.getElementById('eye-off-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeOffIcon.classList.remove('hidden');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeOffIcon.classList.add('hidden');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>