<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($request_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM borrow_requests WHERE request_id = ? AND user_id = ? AND status = 'approved'");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $update = $conn->prepare("UPDATE borrow_requests SET status = 'return_requested' WHERE request_id = ?");
        $update->bind_param("i", $request_id);
        if ($update->execute()) {
            $_SESSION['message'] = ['text' => 'Your return request has been sent to admin.', 'type' => 'success'];
        } else {
            $_SESSION['message'] = ['text' => 'Error sending return request.', 'type' => 'error'];
        }
    } else {
        $_SESSION['message'] = ['text' => 'Invalid return request.', 'type' => 'error'];
    }
} else {
    $_SESSION['message'] = ['text' => 'Invalid action.', 'type' => 'error'];
}

header("Location: my_account.php");
exit();
