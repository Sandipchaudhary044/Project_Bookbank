<?php
require_once 'includes/db.php';
session_start();

// Make sure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate required parameters
if (!isset($_GET['action'], $_GET['id'])) {
    $_SESSION['message'] = ['text' => 'Missing parameters.', 'type' => 'error'];
    header("Location: my_account.php");
    exit();
}

$action = $_GET['action'];
$request_id = intval($_GET['id']);

// Allowable actions
$allowed_actions = ['cancel', 'return', 'request_return'];

if (!in_array($action, $allowed_actions)) {
    $_SESSION['message'] = ['text' => 'Invalid action.', 'type' => 'error'];
    header("Location: my_account.php");
    exit();
}

// Fetch the borrow request and verify user ownership
$stmt = $conn->prepare("SELECT status, return_requested FROM borrow_requests WHERE request_id = ? AND user_id = ?");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['message'] = ['text' => 'Borrow request not found or not authorized.', 'type' => 'error'];
    header("Location: my_account.php");
    exit();
}

$request = $result->fetch_assoc();
$stmt->close();

// Handle Cancel (only for pending requests)
if ($action === 'cancel') {
    if ($request['status'] !== 'pending') {
        $_SESSION['message'] = ['text' => 'Only pending requests can be canceled.', 'type' => 'error'];
    } else {
        $stmt = $conn->prepare("DELETE FROM borrow_requests WHERE request_id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = ['text' => 'Borrow request canceled successfully.', 'type' => 'success'];
    }

// Handle Return Request (approved only)
} elseif ($action === 'return' || $action === 'request_return') {
    if ($request['status'] !== 'approved') {
        $_SESSION['message'] = ['text' => 'Only approved books can be requested for return.', 'type' => 'error'];
    } elseif ($request['return_requested'] == 1) {
        $_SESSION['message'] = ['text' => 'Return request already sent.', 'type' => 'info'];
    } else {
        // Update return_requested flag
        $stmt = $conn->prepare("UPDATE borrow_requests SET return_requested = 1 WHERE request_id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = ['text' => 'Return request sent. Please wait for admin approval.', 'type' => 'success'];
    }
}

header("Location: my_account.php");
exit();
