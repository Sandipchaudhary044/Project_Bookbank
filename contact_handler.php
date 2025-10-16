<?php
session_start();
require_once 'includes/db.php'; // your DB connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Simple server-side validation
    if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['contact_error'] = "Please fill all fields correctly.";
        header("Location: contact.php");
        exit();
    }

    // Prepare and insert into DB
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);

    if ($stmt->execute()) {
        $_SESSION['contact_success'] = "Thank you for your message! We'll get back to you soon.";
    } else {
        $_SESSION['contact_error'] = "Something went wrong. Please try again later.";
    }
    $stmt->close();
    header("Location: contact.php");
    exit();
} else {
    header("Location: contact.php");
    exit();
}
