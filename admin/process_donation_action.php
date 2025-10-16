<?php
require_once '../includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Security check: Only admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: /bookbank/login.php");
    exit();
}

if (isset($_GET['id'], $_GET['action'])) {
    $donation_id = intval($_GET['id']);
    $action = $_GET['action'];

    if (!in_array($action, ['approve', 'reject', 'delete'])) {
        header("Location: donations.php?error=InvalidAction");
        exit();
    }

    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        $stmt = $conn->prepare("UPDATE donations SET status = ? WHERE donation_id = ?");
        $stmt->bind_param("si", $status, $donation_id);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: donations.php?success={$action}");
            exit();
        } else {
            $stmt->close();
            header("Location: donations.php?error=UpdateFailed");
            exit();
        }
    }

    if ($action === 'delete') {
        // Optionally: delete book cover image from filesystem
        $imageQuery = $conn->prepare("SELECT cover_image FROM donations WHERE donation_id = ?");
        $imageQuery->bind_param("i", $donation_id);
        $imageQuery->execute();
        $imageQuery->bind_result($cover_image);
        $imageQuery->fetch();
        $imageQuery->close();

        if (!empty($cover_image) && file_exists("../uploads/" . $cover_image)) {
            unlink("../uploads/" . $cover_image);
        }

        $stmt = $conn->prepare("DELETE FROM donations WHERE donation_id = ?");
        $stmt->bind_param("i", $donation_id);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: donations.php?success=deleted");
            exit();
        } else {
            $stmt->close();
            header("Location: donations.php?error=DeleteFailed");
            exit();
        }
    }
} else {
    // Missing parameters
    header("Location: donations.php?error=MissingParams");
    exit();
}
