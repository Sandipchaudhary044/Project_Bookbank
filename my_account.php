<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

// Ensure session is started before accessing $_SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = null; // Initialize $stmt for safety
$stmt = $conn->prepare("SELECT username, email, contact, address, profession, avatar, name FROM users WHERE user_id = ?");
if ($stmt === false) {
    // Handle prepare error, e.g., log it and display a generic error
    error_log("Failed to prepare user fetch statement: " . $conn->error);
    // Optionally redirect to an error page or show a user-friendly message
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not load user data. Please try again later.'];
    header("Location: index.php"); // Redirect to homepage or dashboard
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

// If user somehow doesn't exist despite being logged in (should be rare)
if (!$user) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'User not found. Please log in again.'];
    header("Location: logout.php");
    exit();
}

// Default avatar path if the user has none or if the file doesn't exist
$defaultAvatarPath = '/bookbank/assets/images/default_avatar.png';
$userAvatarSrc = $defaultAvatarPath; // Default to this

if (!empty($user['avatar'])) {
    $avatarFilePath = 'uploads/avatars/' . $user['avatar']; // Consistent with account_settings.php
    if (file_exists($avatarFilePath)) {
        $userAvatarSrc = $avatarFilePath;
    }
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-4xl font-extrabold text-brand-dark mb-10 text-center">My Account Overview</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="p-4 mb-6 text-sm <?= $_SESSION['message']['type'] === 'success' ? 'text-green-700 bg-green-100 border-l-4 border-green-500' : 'text-red-700 bg-red-100 border-l-4 border-red-500' ?> rounded-lg shadow-sm" role="alert">
            <p class="font-semibold"><?= $_SESSION['message']['type'] === 'success' ? 'Success!' : 'Error!' ?></p>
            <p><?= htmlspecialchars($_SESSION['message']['text']) ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="bg-white p-8 rounded-lg shadow-xl lg:col-span-1 border border-gray-200">
            <h2 class="text-2xl font-bold text-brand-dark mb-6 flex items-center">
                <svg class="w-7 h-7 mr-2 text-brand-orange" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                My Details
            </h2>

            <div class="flex flex-col items-center mb-6">
                <img src="<?= htmlspecialchars($userAvatarSrc) ?>" alt="Profile Image" class="w-32 h-32 rounded-full mb-4 object-cover border-4 border-brand-orange shadow-md">
                <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($user['name'] ?: $user['username']) ?></p>
            </div>

            <div class="space-y-3 text-gray-700">
                <p><strong>Username:</strong> <span class="text-gray-900"><?= htmlspecialchars($user['username']) ?></span></p>
                <p><strong>Email:</strong> <span class="text-gray-900"><?= htmlspecialchars($user['email']) ?></span></p>
                <p><strong>Contact:</strong> <span class="text-gray-900"><?= htmlspecialchars($user['contact'] ?: 'Not Provided') ?></span></p>
                <p><strong>Profession:</strong> <span class="text-gray-900"><?= htmlspecialchars($user['profession'] ?: 'Not Provided') ?></span></p>
                <div>
                    <strong>Address:</strong><br>
                    <span class="text-gray-900"><?= nl2br(htmlspecialchars($user['address'] ?: 'Not Provided')) ?></span>
                </div>
            </div>

            <div class="mt-8 text-center">
                <a href="account_settings.php" class="inline-flex items-center justify-center text-md font-semibold text-white bg-brand-orange hover:bg-orange-700 px-6 py-3 rounded-lg shadow-md transition duration-200 ease-in-out">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.3-.874-2.886.67-2.01 1.934a1.532 1.532 0 01-.098 2.292c-.932 1.258-.29 2.76.988 3.194a1.532 1.532 0 01.769 2.0A1.532 1.532 0 019.26 17.65c1.264.47 2.025 1.807 1.49 3.17-.38 1.56-2.6 1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.3-.874-2.886.67-2.01 1.934a1.532 1.532 0 01-.098 2.292c-.932 1.258-.29 2.76.988 3.194a1.532 1.532 0 01.769 2.0A1.532 1.532 0 019.26 17.65c1.264.47 2.025 1.807 1.49 3.17z" clip-rule="evenodd"></path><path fill-rule="evenodd" d="M10 10a1 1 0 110-2 1 1 0 010 2zm1-7a1 1 0 10-2 0v2a1 1 0 102 0V3zm0 14a1 1 0 10-2 0v2a1 1 0 102 0v-2zm-7-2a1 1 0 100 2h2a1 1 0 100-2H4zM15 4a1 1 0 10-2 0h2a1 1 0 100 2h-2zm-2 13a1 1 0 102 0v-2a1 1 0 10-2 0v2zm6-4a1 1 0 100 2h-2a1 1 0 100-2h2zm-3-3a1 1 0 10-2 0v2a1 1 0 102 0v-2z" clip-rule="evenodd"></path></svg>
                    Edit Profile
                </a>
            </div>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-xl lg:col-span-2 border border-gray-200">
            <h2 class="text-2xl font-bold text-brand-dark mb-6 flex items-center">
                <svg class="w-7 h-7 mr-2 text-brand-orange" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 .75.75 0 001.5 0A2 2 0 0110 3a.75.75 0 001.5 0 2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm10-1a.75.75 0 00-1.5 0A2 2 0 0110 3a.75.75 0 00-1.5 0 2 2 0 01-2-2 .75.75 0 00-1.5 0A2 2 0 016 3a.75.75 0 00-1.5 0A2 2 0 014 5v6a2 2 0 012 2h4a2 2 0 012-2V5a.75.75 0 00-1.5 0zM4.75 15a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3zM13.25 15a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-3z" clip-rule="evenodd"></path></svg>
                My Donations
            </h2>
            <?php
            $sql = "SELECT book_title, category, book_condition, status, created_at
                    FROM donations
                    WHERE user_id = ?
                    ORDER BY created_at DESC";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("Failed to prepare donations statement: " . $conn->error);
                echo '<p class="text-red-500">Error loading donations.</p>';
            } else {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                ?>
                <?php if ($result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($d = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($d['book_title']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($d['category']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($d['book_condition']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize
                                            <?php
                                            echo match($d['status']) {
                                                'approved' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>">
                                            <?= htmlspecialchars($d['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('Y-m-d', strtotime($d['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 p-6 text-center rounded-lg border border-dashed border-gray-300">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        <p class="mt-2 text-sm text-gray-600">You haven't donated any books yet.</p>
                        <p class="mt-1 text-sm text-gray-500">Share knowledge with others by donating a book!</p>
                        <div class="mt-4">
                            <a href="donate.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-orange hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-orange">
                                Donate a Book
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php $stmt->close();
            } // end if ($stmt === false)
            ?>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-xl lg:col-span-3 border border-gray-200">
            <h2 class="text-2xl font-bold text-brand-dark mb-6 flex items-center">
                <svg class="w-7 h-7 mr-2 text-brand-orange" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0113 3.414L16.586 7A2 2 0 0118 8.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 10a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1-4a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                My Borrow Requests
            </h2>
            <?php
            $sql = "SELECT br.request_id, br.status, br.request_date, d.book_title
                    FROM borrow_requests br
                    JOIN donations d ON br.donation_id = d.donation_id
                    WHERE br.user_id = ?
                    ORDER BY br.request_date DESC";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("Failed to prepare borrow requests statement: " . $conn->error);
                echo '<p class="text-red-500">Error loading borrow requests.</p>';
            } else {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                ?>
                <?php if ($result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($r = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($r['book_title']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize
                                            <?php
                                            echo match($r['status']) {
                                                'approved' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                'returned' => 'bg-blue-100 text-blue-800',
                                                'return_requested' => 'bg-indigo-100 text-indigo-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>">
                                            <?= htmlspecialchars($r['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= date('Y-m-d', strtotime($r['request_date'])) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <a href="borrow_action.php?action=cancel&id=<?= $r['request_id'] ?>"
                                               class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                               onclick="return confirm('Are you sure you want to cancel this request?')">
                                                Cancel
                                            </a>
                                        <?php elseif ($r['status'] === 'approved'): ?>
                                            <a href="borrow_action.php?action=request_return&id=<?= $r['request_id'] ?>"
                                               class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200"
                                               onclick="return confirm('Send a return request for this book?')">
                                                Request Return
                                            </a>
                                        <?php elseif ($r['status'] === 'return_requested'): ?>
                                            <span class="text-indigo-600 opacity-75 cursor-not-allowed">Return Requested</span>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 p-6 text-center rounded-lg border border-dashed border-gray-300">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                        <p class="mt-2 text-sm text-gray-600">You haven't made any borrow requests yet.</p>
                        <p class="mt-1 text-sm text-gray-500">Explore available books and make your first request!</p>
                        <div class="mt-4">
                            <a href="available_books.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-orange hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-orange">
                                Browse Books
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php $stmt->close();
            } // end if ($stmt === false)
            ?>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>