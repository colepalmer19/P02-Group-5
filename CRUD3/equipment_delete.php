<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Check if the user is authorized (Admin only)
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['csrf_token'])) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        die('Invalid CSRF token. Delete not allowed.');
    }

    // ✅ Regenerate CSRF token after validation
    unset($_SESSION['csrf_token']);  // Remove old token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Check if an ID is passed in the URL
    if (isset($_GET['id'])) {
        $equipment_id = (int)$_GET['id'];

        // Prepare and execute the delete query
        $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->bind_param('i', $equipment_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Equipment deleted successfully!"; // Store message in session
            header("Location: equipment.php"); // Redirect to main equipment page
            exit();
        } else {
            $_SESSION['error_message'] = "Error deleting equipment: " . htmlspecialchars($stmt->error);
            header("Location: equipment.php"); // Redirect even on failure
            exit();
        }

        $stmt->close();
        $conn->close();
    } else {
        echo "<p class='error-message'>No equipment ID provided. Unable to delete equipment.</p>";
    }
} else {
    die('CSRF token missing. Delete not allowed.');
}
?>
