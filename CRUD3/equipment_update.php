<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is authorized (Admin or Research Assistant)
if (!isset($_SESSION['session_userid']) || !in_array($_SESSION['session_role'], ['Admin', 'Research Assistant'])) {
    header('Location: login.php');
    exit();
}

// Ensure CSRF token exists before validating
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = $success_message = "";

// Fetch the equipment details
if (isset($_GET['id'])) {
    $equipment_id = (int)$_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->bind_param('i', $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment = $result->fetch_assoc();

    if (!$equipment) {
        $_SESSION['error_message'] = "Equipment not found!";
        header("Location: equipment.php");
        exit();
    }
    $stmt->close();
}

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid CSRF token. Update not allowed.";
        header("Location: equipment.php");
        exit();
    }

    // Regenerate CSRF token after validation
    unset($_SESSION['csrf_token']);  // Remove old token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Sanitize and validate inputs
    $equipment_id = (int)$_POST['id'];
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $usage_status = htmlspecialchars(trim($_POST['usage_status']), ENT_QUOTES, 'UTF-8');
    $availability = isset($_POST['availability']) ? (int)$_POST['availability'] : 0;

    // Enhanced validation logic
    if (!preg_match('/^[a-zA-Z0-9 ]+$/', $name)) {
        $_SESSION['error_message'] = "Error: Equipment name must not contain special characters!!";
    } elseif (empty($name) || empty($usage_status)) {
        $_SESSION['error_message'] = "Please fill in all required fields!";
    } elseif (ctype_digit($name)) {
        $_SESSION['error_message'] = "Equipment name cannot be a number!";
    } elseif (strlen($name) < 2) {
        $_SESSION['error_message'] = "Equipment name must be at least 2 characters long.";
    } elseif (!in_array($usage_status, ['Available', 'Under Maintenance'])) {
        $_SESSION['error_message'] = "Invalid usage status.";
    } elseif ($availability < 0) {
        $_SESSION['error_message'] = "Availability cannot be negative.";
    } elseif ($usage_status === 'Available' && $availability <= 0) {
        $_SESSION['error_message'] = "For 'Available' equipment, availability must be greater than 0.";
    } else {
        // Set availability to 0 automatically if the usage status is not "Available"
        if ($usage_status !== 'Available') {
            $availability = 0;
        }

        // Update the equipment details
        $stmt = $conn->prepare("UPDATE equipment SET name = ?, usage_status = ?, availability = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('ssii', $name, $usage_status, $availability, $equipment_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Equipment updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating equipment: " . htmlspecialchars($stmt->error);
        }

        $stmt->close();
        $conn->close();
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
    <title>Update Equipment</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css">
    <script>
        function toggleQuantityField() {
            const usageStatus = document.getElementById('usage_status').value;
            const quantityField = document.getElementById('availability-field');
            if (usageStatus === 'Available') {
                quantityField.style.display = 'block';
                document.getElementById('availability').required = true;
            } else {
                quantityField.style.display = 'none';
                document.getElementById('availability').required = false;
                document.getElementById('availability').value = '';
            }
        }
        window.onload = function () { toggleQuantityField(); };
    </script>
</head>
<body>
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <span class="main-title">AMC Research System</span>
        </div>
        <a href="equipment.php" class="home-btn">Cancel</a>
    </div>

    <div class="equipment-container">
        <?php if (!empty($error_message)) : ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)) : ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="create-container">
            <h2>Update Equipment</h2>
            <form method="POST" class="add-equipment-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($equipment['id']); ?>">

                <div class="form-group">
                    <label for="name">Equipment Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($equipment['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="usage_status">Usage Status:</label>
                    <select id="usage_status" name="usage_status" onchange="toggleQuantityField()" required>
                        <option value="Available" <?php echo $equipment['usage_status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Under Maintenance" <?php echo $equipment['usage_status'] === 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    </select>
                </div>
                <div class="form-group" id="availability-field" style="display: none;">
                    <label for="availability">Quantity (Availability):</label>
                    <input type="number" id="availability" name="availability" min="0" value="<?php echo htmlspecialchars($equipment['availability']); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="submit-button">Update Equipment</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
