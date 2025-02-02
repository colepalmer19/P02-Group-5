<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is logged in as Admin
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch all pending equipment requests
$pendingRequestsQuery = $conn->query(
    "SELECT er.id AS request_id, er.request_date, er.status, 
            e.name AS equipment_name, e.availability, 
            u.name AS researcher_name
     FROM equipment_requests er
     JOIN equipment e ON er.equipment_id = e.id
     JOIN user u ON er.researcher_id = u.id
     WHERE er.status = 'Pending'"
);

// Handle Accept/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }

    // Fetch equipment ID and availability
    $fetchRequestQuery = $conn->prepare(
        "SELECT er.equipment_id, e.availability 
         FROM equipment_requests er
         JOIN equipment e ON er.equipment_id = e.id
         WHERE er.id = ?"
    );
    $fetchRequestQuery->bind_param('i', $request_id);
    $fetchRequestQuery->execute();
    $fetchRequestQuery->bind_result($equipment_id, $availability);
    $fetchRequestQuery->fetch();
    $fetchRequestQuery->close();

    if ($action === 'Accept') {
        if ($availability > 0) {
            $conn->begin_transaction();
            try {
                // Ensure the equipment still has availability before updating
                $checkAvailabilityQuery = $conn->prepare(
                    "SELECT availability FROM equipment WHERE id = ? FOR UPDATE"
                );
                $checkAvailabilityQuery->bind_param('i', $equipment_id);
                $checkAvailabilityQuery->execute();
                $result = $checkAvailabilityQuery->get_result();
                $row = $result->fetch_assoc();
                $current_availability = $row['availability'];
                $checkAvailabilityQuery->close();

                if ($current_availability > 0) {
                    // Reduce equipment availability
                    $updateEquipmentQuery = $conn->prepare(
                        "UPDATE equipment SET availability = availability - 1 WHERE id = ?"
                    );
                    $updateEquipmentQuery->bind_param('i', $equipment_id);
                    if (!$updateEquipmentQuery->execute()) {
                        throw new Exception("Error updating equipment availability: " . $conn->error);
                    }
                    $updateEquipmentQuery->close();

                    // Update the request status to Accepted
                    $updateRequestQuery = $conn->prepare(
                        "UPDATE equipment_requests SET status = 'Accepted', updated_at = NOW() WHERE id = ?"
                    );
                    $updateRequestQuery->bind_param('i', $request_id);
                    if (!$updateRequestQuery->execute()) {
                        throw new Exception("Error updating request status: " . $conn->error);
                    }
                    $updateRequestQuery->close();

                    $conn->commit();
                    $_SESSION['success_message'] = "Request accepted successfully! Equipment availability updated.";
                } else {
                    throw new Exception("Equipment is no longer available.");
                }
            } catch (Exception $e) {
                error_log("Transaction failed: " . $e->getMessage());
                $conn->rollback();
                $_SESSION['error_message'] = "Error accepting request: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Equipment is no longer available.";
        }
    } elseif ($action === 'Reject') {
        // Update request status to Rejected
        $updateRequestQuery = $conn->prepare(
            "UPDATE equipment_requests SET status = 'Rejected', updated_at = NOW() WHERE id = ?"
        );
        $updateRequestQuery->bind_param('i', $request_id);
        if ($updateRequestQuery->execute()) {
            $_SESSION['success_message'] = "Request rejected successfully!";
        } else {
            $_SESSION['error_message'] = "Error rejecting request.";
        }
        $updateRequestQuery->close();
    }
    header('Location: accept_equipment.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Equipment Requests</title>
    <link rel="stylesheet" href="/group/style/style.css">
</head>
<body>
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <h1 class="main-title">AMC Research System</h1>
        </div>
        <a href="/group/admin_dashboard.php" class="home-btn">Home</a>
    </div>

    <div class="equipment-container">
        <div class="read-container">
            <h2>Pending Equipment Requests</h2>

            <!-- Success/Error Messages Inside Container -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Researcher Name</th>
                        <th>Equipment Name</th>
                        <th>Availability</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $pendingRequestsQuery->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['researcher_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['availability']); ?></td>
                            <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" name="action" value="Accept" class="link-button">Accept</button>
                                </form>
                                |
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" name="action" value="Reject" class="link-button">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Fading out messages after 3 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.transition = "opacity 0.5s ease";
                message.style.opacity = "0";
                setTimeout(() => message.remove(), 500);
            });
        }, 3000);
    </script>
</body>
</html>
