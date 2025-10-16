<?php
require_once 'includes/db.php';

// Protect this page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $donation_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Check if the book is available and not already requested by the user
    $stmt = $conn->prepare("SELECT * FROM donations WHERE donation_id = ? AND status = 'approved' AND is_borrowed = 0");
    $stmt->bind_param("i", $donation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Check if the user has already made a borrow request
        $check = $conn->prepare("SELECT * FROM borrow_requests WHERE user_id = ? AND donation_id = ?");
        $check->bind_param("ii", $user_id, $donation_id);
        $check->execute();
        $existing = $check->get_result();

        if ($existing->num_rows === 0) {
            // Insert new borrow request with 'pending' status
            $insert = $conn->prepare("INSERT INTO borrow_requests (user_id, donation_id, status) VALUES (?, ?, 'pending')");
            $insert->bind_param("ii", $user_id, $donation_id);

            if ($insert->execute()) {
                $_SESSION['message'] = ['text' => 'Your borrow request has been sent for admin approval.', 'type' => 'success'];
            } else {
                $_SESSION['message'] = ['text' => 'Error submitting request. Please try again.', 'type' => 'error'];
            }

            $insert->close();
        } else {
            $_SESSION['message'] = ['text' => 'You have already requested this book.', 'type' => 'error'];
        }

        $check->close();
    } else {
        $_SESSION['message'] = ['text' => 'This book is not available for borrowing.', 'type' => 'error'];
    }

    $stmt->close();
} else {
    $_SESSION['message'] = ['text' => 'Invalid book ID.', 'type' => 'error'];
}

header("Location: my_account.php"); // Redirect to a page showing statuses
exit();
