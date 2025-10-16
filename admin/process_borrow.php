<?php
require_once '../includes/db.php';
session_start();

// Ensure admin access
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: /bookbank/login.php");
    exit();
}

// Validate GET parameters
if (!isset($_GET['id'], $_GET['action'])) {
    $_SESSION['message'] = ['text' => "Missing parameters.", 'type' => 'error'];
    header("Location: borrows.php");
    exit();
}

$request_id = intval($_GET['id']);
$action = $_GET['action'];

// Map admin action to borrow status
$status = match ($action) {
    'approve' => 'approved',
    'reject' => 'rejected',
    'returned' => 'returned',
    default => null
};

if (!$status) {
    $_SESSION['message'] = ['text' => "Invalid action.", 'type' => 'error'];
    header("Location: borrows.php");
    exit();
}

// Fetch borrow request and donation_id and current status and return_requested flag
$stmt = $conn->prepare("SELECT donation_id, status, return_requested FROM borrow_requests WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['message'] = ['text' => "Request not found.", 'type' => 'error'];
    header("Location: borrows.php");
    exit();
}

$row = $result->fetch_assoc();
$donation_id = $row['donation_id'];
$current_status = $row['status'];
$return_requested = (bool)$row['return_requested'];
$stmt->close();

// Ensure proper status transitions
if ($status === 'returned' && !$return_requested) {
    $_SESSION['message'] = ['text' => "Cannot mark as returned unless return is requested.", 'type' => 'error'];
    header("Location: borrows.php");
    exit();
}

// Update borrow_requests table depending on action
if ($status === 'returned') {
    // Mark as returned, reset return_requested flag
    $stmt = $conn->prepare("UPDATE borrow_requests SET status = ?, return_date = NOW(), return_requested = 0 WHERE request_id = ?");
    $stmt->bind_param("si", $status, $request_id);
} else {
    // For approve or reject: update status and clear return_requested flag
    $stmt = $conn->prepare("UPDATE borrow_requests SET status = ?, return_date = NULL, return_requested = 0 WHERE request_id = ?");
    $stmt->bind_param("si", $status, $request_id);
}
$stmt->execute();
$stmt->close();

// Update donation's is_borrowed flag accordingly
if ($status === 'approved') {
    $stmt = $conn->prepare("UPDATE donations SET is_borrowed = 1 WHERE donation_id = ?");
    $stmt->bind_param("i", $donation_id);
    $stmt->execute();
    $stmt->close();
} elseif ($status === 'returned') {
    $stmt = $conn->prepare("UPDATE donations SET is_borrowed = 0 WHERE donation_id = ?");
    $stmt->bind_param("i", $donation_id);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['message'] = ['text' => "Request marked as '$status' successfully.", 'type' => 'success'];
header("Location: borrows.php");
exit();
