<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure user is logged in as Admin
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Validate delete request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['csrf_token'])) {
    $project_id = intval($_GET['id']);

    // Validate CSRF token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        die('Invalid CSRF token. Delete not allowed.');
    }

    // Check if project exists
    $stmt = $conn->prepare("SELECT title FROM research_projects WHERE id = ?");
    $stmt->bind_param('i', $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();

    if (!$project) {
        $_SESSION['error_message'] = "Project not found!";
        header("Location: view_projects.php");
        exit();
    }
    $stmt->close();

    // Delete project
    $deleteStmt = $conn->prepare("DELETE FROM research_projects WHERE id = ? AND status != 'Completed'");
    $deleteStmt->bind_param('i', $project_id);

    if ($deleteStmt->execute()) {
        // ✅ Regenerate CSRF token after successful deletion
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['delete_success_message'] = "Project '{$project['title']}' deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting project.";
    }

    $deleteStmt->close();
    header("Location: view_projects.php");
    exit();
} else {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: view_projects.php");
    exit();
}
?>
