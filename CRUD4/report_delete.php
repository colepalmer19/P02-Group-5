<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is logged in as Admin
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Check if an ID is passed in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $report_id = (int)$_GET['id'];  // Get report ID from URL

    try {
        // Check if the report exists before trying to delete
        $stmt = $conn->prepare("SELECT COUNT(*) FROM reports WHERE id = ?");
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            // Proceed with deletion if the report exists
            $delete_stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
            $delete_stmt->bind_param("i", $report_id);

            if ($delete_stmt->execute()) {
                $_SESSION['success_message'] = " Report deleted successfully!";
                $delete_stmt->close();
                header("Location: admin_reports.php");
                exit();
            } else {
                $_SESSION['error_message'] = " Failed to delete the report. Please try again.";
            }
        } else {
            $_SESSION['error_message'] = " Report not found.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = " Error: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = " No report ID provided. Unable to delete the report.";
}

// Redirect back to admin reports page
header("Location: admin_reports.php");
exit();
?>
