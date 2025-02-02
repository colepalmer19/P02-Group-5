<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure only researchers access this page
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Researcher') {
    header('Location: login.php');
    exit();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) { // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$researcher_id = $_SESSION['session_userid'];

// Fetch "My Requests"
$myRequestsQuery = $conn->prepare(
    "SELECT er.*, e.name AS equipment_name, e.usage_status 
    FROM equipment_requests er 
    JOIN equipment e ON er.equipment_id = e.id 
    WHERE er.researcher_id = ?"
);
$myRequestsQuery->bind_param('i', $researcher_id);
$myRequestsQuery->execute();
$myRequestsResult = $myRequestsQuery->get_result();

// Fetch Available Equipment (exclude already requested or accepted by this researcher)
$availableEquipmentQuery = $conn->prepare(
    "SELECT e.* 
    FROM equipment e 
    WHERE e.availability > 0 
    AND e.id NOT IN (
        SELECT equipment_id 
        FROM equipment_requests 
        WHERE researcher_id = ? AND (status = 'Pending' OR status = 'Accepted')
    )"
);
$availableEquipmentQuery->bind_param('i', $researcher_id);
$availableEquipmentQuery->execute();
$availableEquipmentResult = $availableEquipmentQuery->get_result();

// Handle Equipment Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_equipment'])) {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $equipment_id = (int)$_POST['equipment_id'];
    if (!filter_var($equipment_id, FILTER_VALIDATE_INT)) {
        $_SESSION['error_message'] = "Invalid equipment ID.";
        header('Location: request_equipment.php');
        exit();
    }

    // Insert new request into `equipment_requests` table
    $requestStmt = $conn->prepare(
        "INSERT INTO equipment_requests (researcher_id, equipment_id, request_date, status, updated_at) 
        VALUES (?, ?, NOW(), 'Pending', NOW())"
    );
    $requestStmt->bind_param('ii', $researcher_id, $equipment_id);

    if ($requestStmt->execute()) {
        $_SESSION['success_message'] = "Request submitted successfully!";
    } else {
        $_SESSION['error_message'] = "An error occurred while processing your request. Please try again.";
    }
    $requestStmt->close();
    header('Location: request_equipment.php');
    exit();
}

// Handle Request Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $request_id = (int)$_POST['request_id'];
    if (!filter_var($request_id, FILTER_VALIDATE_INT)) {
        $_SESSION['error_message'] = "Invalid request ID.";
        header('Location: request_equipment.php');
        exit();
    }

    // Ensure the request is still pending before allowing cancellation
    $checkStmt = $conn->prepare(
        "SELECT status FROM equipment_requests WHERE id = ? AND researcher_id = ?"
    );
    $checkStmt->bind_param('ii', $request_id, $researcher_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $request = $result->fetch_assoc();
    $checkStmt->close();

    if ($request && $request['status'] === 'Pending') {
        $cancelStmt = $conn->prepare(
            "DELETE FROM equipment_requests WHERE id = ? AND researcher_id = ?"
        );
        $cancelStmt->bind_param('ii', $request_id, $researcher_id);

        if ($cancelStmt->execute()) {
            $_SESSION['success_message'] = "Request canceled successfully!";
        } else {
            $_SESSION['error_message'] = "An error occurred while processing your request. Please try again.";
        }
        $cancelStmt->close();
    } else {
        $_SESSION['error_message'] = "You cannot cancel an accepted or completed request.";
    }

    header('Location: request_equipment.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Equipment</title>
    <link rel="stylesheet" href="/group/style/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <h1 class="main-title">AMC Research System</h1>
        </div>
        <a href="/group/researcher_dashboard.php" class="home-btn">Home</a>
    </div>

    <div class="equipment-container">
        <!-- My Requests Section -->
        <div class="read-container">
            <h2>My Requests and Equipment</h2>
            <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])) : ?>
            <div class="message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Equipment Name</th>
                        <th>Usage Status</th>
                        <th>Request Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $myRequestsResult->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['usage_status']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'Pending') : ?>
                                    <form method="POST" class="action-form" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="cancel_request" class="link-button">Cancel</button>
                                    </form>
                                <?php else : ?>
                                    <span class="disabled-link">Cannot Cancel</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Available Equipment Section -->
        <div class="read-container">
            <h2>Available Equipment</h2>
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Equipment Name</th>
                        <th>Usage Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $availableEquipmentResult->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['usage_status']); ?></td>
                            <td>
                                <form method="POST" class="action-form" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="equipment_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="request_equipment" class="link-button">Request</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Fading away success/error messages after 3 seconds
        setTimeout(function () {
            const message = document.querySelector('.message');
            if (message) {
                message.style.transition = "opacity 0.5s ease";
                message.style.opacity = "0";
                setTimeout(() => message.remove(), 500);
            }
        }, 3000);
    </script>
</body>
</html>
