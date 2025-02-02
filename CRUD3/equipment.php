<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is logged in as Admin
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not set (but don't regenerate on every request)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success_message = $error_message = "";

// Handle adding new equipment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_equipment'])) {
    // Validate CSRF token before regenerating
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: equipment.php");
        exit();
    }

    // Regenerate CSRF token immediately after validation
    unset($_SESSION['csrf_token']);  // Remove old token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Sanitize inputs
    $equipment_name = htmlspecialchars(trim($_POST['equipment_name']), ENT_QUOTES, 'UTF-8');
    $usage_status = htmlspecialchars(trim($_POST['usage_status']), ENT_QUOTES, 'UTF-8');
    $availability = isset($_POST['availability']) ? (int)$_POST['availability'] : 0;
    $assigned_to = $_SESSION['session_userid'];

    // Validation rules
    if (!preg_match('/^[a-zA-Z0-9 ]+$/', $equipment_name)) {
        $_SESSION['error_message'] = "Error: Equipment name must not contain special characters!";
    } elseif (empty($equipment_name) || empty($usage_status)) {
        $_SESSION['error_message'] = "Please fill in all required fields!";
    } elseif (ctype_digit($equipment_name)) {
        $_SESSION['error_message'] = "Equipment name cannot be a number!";
    } elseif (strlen($equipment_name) < 2) {
        $_SESSION['error_message'] = "Equipment name must be at least 2 characters long.";
    } elseif ($availability < 0) {
        $_SESSION['error_message'] = "Availability cannot be negative.";
    } elseif ($usage_status === 'Available' && $availability <= 0) {
        $_SESSION['error_message'] = "For 'Available' equipment, availability must be greater than 0.";
    }

    // Proceed only if there are no errors
    if (!isset($_SESSION['error_message'])) {
        // Insert new equipment
        $stmt = $conn->prepare(
            "INSERT INTO equipment (name, usage_status, availability, assigned_to, created_at, updated_at) 
             VALUES (?, ?, ?, ?, NOW(), NOW())"
        );

        if (!$stmt) {
            error_log("SQL Error: " . $conn->error);
            $_SESSION['error_message'] = "An unexpected error occurred. Please try again later.";
        } else {
            $stmt->bind_param('ssii', $equipment_name, $usage_status, $availability, $assigned_to);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Equipment added successfully!";
            } else {
                error_log("Database Insert Error: " . $stmt->error);
                $_SESSION['error_message'] = "An unexpected error occurred. Please try again later.";
            }
            $stmt->close();
        }
    }

    // Always redirect back to equipment.php
    header("Location: equipment.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Management</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css?v=<?php echo time(); ?>">
</head>
<body class="equipment-management">
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <h1 class="main-title">AMC Research System</h1>
        </div>
        <a href="/group/admin_dashboard.php" class="home-btn">Home</a>
    </div>

    <div class="equipment-container">
        <!-- Create Section -->
        <div class="create-container">
            <h2>Add Equipment</h2>

    <!-- Display success/error messages -->
    <?php 
    if (!empty($success_message)) {
        echo "<div class='message success'>$success_message</div>";
    }
    if (!empty($error_message)) {
        echo "<div class='message error'>$error_message</div>";
    }
    // Check and display session success/error messages from delete
    if (isset($_SESSION['success_message'])) {
        echo "<div class='message success'>" . $_SESSION['success_message'] . "</div>";
        unset($_SESSION['success_message']); // Remove message after displaying
    }
    if (isset($_SESSION['error_message'])) {
        echo "<div class='message error'>" . $_SESSION['error_message'] . "</div>";
        unset($_SESSION['error_message']); // Remove error message
    }
    ?>

            <form action="equipment.php" method="POST" class="add-equipment-form">
                <!-- Add CSRF token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <input type="text" name="equipment_name" placeholder="Equipment Name" required>
                </div>
                <div class="form-group">
                    <select id="usage_status" name="usage_status" required>
                        <option value="Available">Available</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                    </select>
                </div>
                <div class="form-group" id="availability-group">
                    <label for="availability">Availability (Quantity):</label>
                    <input type="number" id="availability" name="availability" min="0" placeholder="Enter quantity" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="add_equipment" class="submit-button">Add Equipment</button>
                </div>
            </form>
        </div>

        <!-- Admin Equipment Inventory -->
        <div class="read-container">
            <h2>Admin Equipment Inventory</h2>
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Usage Status</th>
                        <th>Availability</th>
                        <th>Assigned To</th>
                        <th colspan="2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch and display admin equipment
                    $adminResult = $conn->query(
                        "SELECT equipment.*, user.name AS assigned_to_name, user.role AS assigned_to_role 
                         FROM equipment 
                         LEFT JOIN user ON equipment.assigned_to = user.id
                         WHERE user.role = 'Admin'"
                    );

                    while ($row = $adminResult->fetch_assoc()) {
                        $assigned_to = $row['assigned_to_name']
                            ? htmlspecialchars($row['assigned_to_name']) . ' (' . htmlspecialchars($row['assigned_to_role']) . ')'
                            : 'Not Assigned';
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['usage_status']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['availability']) . "</td>";
                        echo "<td>" . $assigned_to . "</td>";
                        echo "<td><a href='equipment_update.php?id=" . htmlspecialchars($row['id']) . "'>Update</a></td>";
                        echo "<td><a href='equipment_delete.php?id=" . htmlspecialchars($row['id']) . "&csrf_token=" . $_SESSION['csrf_token'] . "' onclick='return confirm(\"Are you sure you want to delete this equipment?\");'>Delete</a></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Research Assistant Equipment Inventory -->
        <div class="read-container">
            <h2>Research Assistant Equipment Inventory</h2>
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Usage Status</th>
                        <th>Availability</th>
                        <th>Assigned To</th>
                        <th colspan="2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch and display research assistant equipment
                    $assistantResult = $conn->query(
                        "SELECT equipment.*, user.name AS assigned_to_name, user.role AS assigned_to_role 
                         FROM equipment 
                         LEFT JOIN user ON equipment.assigned_to = user.id
                         WHERE user.role = 'Research Assistant'"
                    );

                    while ($row = $assistantResult->fetch_assoc()) {
                        $assigned_to = $row['assigned_to_name']
                            ? htmlspecialchars($row['assigned_to_name']) . ' (' . htmlspecialchars($row['assigned_to_role']) . ')'
                            : 'Not Assigned';
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['usage_status']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['availability']) . "</td>";
                        echo "<td>" . $assigned_to . "</td>";
                        echo "<td><a href='equipment_update.php?id=" . htmlspecialchars($row['id']) . "'>Update</a></td>";
                        echo "<td><a href='equipment_delete.php?id=" . htmlspecialchars($row['id']) . "&csrf_token=" . $_SESSION['csrf_token'] . "' onclick='return confirm(\"Are you sure you want to delete this equipment?\");'>Delete</a></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Dynamically toggle the availability input field
        const usageStatus = document.querySelector('#usage_status');
        const availabilityGroup = document.querySelector('#availability-group');
        const availabilityInput = document.querySelector('#availability');

        const toggleAvailabilityInput = () => {
            if (usageStatus.value === 'Under Maintenance') {
                availabilityInput.value = 0;
                availabilityInput.disabled = true;
                availabilityGroup.style.display = 'none'; // Hide the quantity field
            } else {
                availabilityInput.disabled = false;
                availabilityGroup.style.display = 'block'; // Show the quantity field
                availabilityInput.value = ''; // Clear the field when enabled
            }
        };

        usageStatus.addEventListener('change', toggleAvailabilityInput);
        window.addEventListener('DOMContentLoaded', toggleAvailabilityInput);

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