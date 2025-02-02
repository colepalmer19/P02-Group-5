<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Admin') {
    header('Location: login.php'); // Redirect to login page if not authorized
    exit();
}

// Check if the user ID is passed via the URL
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Delete the user
    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param('i', $user_id);

    if ($stmt->execute()) {
        // Redirect back to the user management page after successful deletion
        $_SESSION['success_message'] = "User deleted successfully!";
        header('Location: user_management.php');
        exit();
    } else {
        // Redirect back to the user management page with an error message
        $_SESSION['error_message'] = "Error deleting user. Please try again.";
        header('Location: user_management.php');
        exit();
    }
} else {
    // If no user ID is provided, redirect back with an error message
    $_SESSION['error_message'] = "No user ID provided.";
    header('Location: user_management.php');
    exit();
}

$stmt->close();
$conn->close();
?>
