<?php
require_once 'includes/db.php';

// Protect this page and check for admin role
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: /bookbank/login.php");
    exit();
}

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? '';

if ($id > 0 && !empty($action)) {
    if ($type === 'donation' && in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE donations SET status = ? WHERE donation_id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        header("Location: /bookbank/admin/donations.php");
        exit();
    }
    
    if ($type === 'borrow' && in_array($action, ['approve', 'reject', 'return'])) {
        if ($action === 'approve') {
            // Set request to approved and mark book as borrowed
            $conn->begin_transaction();
            try {
                $stmt1 = $conn->prepare("UPDATE borrow_requests SET status = 'approved' WHERE request_id = ?");
                $stmt1->bind_param("i", $id);
                $stmt1->execute();

                // Get donation_id from request
                $stmt_get_did = $conn->prepare("SELECT donation_id FROM borrow_requests WHERE request_id = ?");
                $stmt_get_did->bind_param("i", $id);
                $stmt_get_did->execute();
                $donation_id = $stmt_get_did->get_result()->fetch_assoc()['donation_id'];

                $stmt2 = $conn->prepare("UPDATE donations SET is_borrowed = 1 WHERE donation_id = ?");
                $stmt2->bind_param("i", $donation_id);
                $stmt2->execute();

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
            }

        } elseif ($action === 'return') {
            // Set request to returned and mark book as available
            $conn->begin_transaction();
             try {
                $stmt1 = $conn->prepare("UPDATE borrow_requests SET status = 'returned', return_date = NOW() WHERE request_id = ?");
                $stmt1->bind_param("i", $id);
                $stmt1->execute();

                $stmt_get_did = $conn->prepare("SELECT donation_id FROM borrow_requests WHERE request_id = ?");
                $stmt_get_did->bind_param("i", $id);
                $stmt_get_did->execute();
                $donation_id = $stmt_get_did->get_result()->fetch_assoc()['donation_id'];
                
                $stmt2 = $conn->prepare("UPDATE donations SET is_borrowed = 0 WHERE donation_id = ?");
                $stmt2->bind_param("i", $donation_id);
                $stmt2->execute();
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
            }
        }
        else { // Reject
            $stmt = $conn->prepare("UPDATE borrow_requests SET status = 'rejected' WHERE request_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: /bookbank/admin/borrows.php");
        exit();
    }
}

// Fallback redirect
header("Location: /bookbank/admin/dashboard.php");
exit();
?>