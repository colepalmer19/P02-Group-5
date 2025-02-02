<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is logged in as Research Assistant
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Research Assistant') {
    header('Location: login.php');
    exit();
}

// Fetch the equipment details
if (isset($_GET['id'])) {
    $equipment_id = (int)$_GET['id'];

    // Ensure the equipment belongs to the logged-in Research Assistant
    $stmt = $conn->prepare("SELECT * FROM equipment WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param('ii', $equipment_id, $_SESSION['session_userid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment = $result->fetch_assoc();

    if (!$equipment) {
        $_SESSION['error_message'] = "Equipment not found or not assigned to you!";
        header("Location: research_assistant_equipment.php");
        exit();
    }
    $stmt->close();
}

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_equipment'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid CSRF token. Update not allowed.";
        header("Location: research_assistant_equipment.php");
        exit();
    }

    // Sanitize and validate inputs
    $equipment_id = (int)$_POST['id'];
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $usage_status = htmlspecialchars(trim($_POST['usage_status']), ENT_QUOTES, 'UTF-8');
    $availability = isset($_POST['availability']) ? (int)$_POST['availability'] : 0;

    // If usage_status is not "Available," set availability to 0 automatically
    if ($usage_status !== 'Available') {
        $availability = 0;
    }

    // Input Validation
    if (!preg_match('/^[a-zA-Z0-9 ]+$/', $name)) {
        $_SESSION['error_message'] = "Error: Equipment name must not contain special characters!";
    } elseif (empty($name) || empty($usage_status)) {
        $_SESSION['error_message'] = "Please fill in all required fields!";
    } elseif (ctype_digit($name)) {
        $_SESSION['error_message'] = "Equipment name cannot be a number!";
    } elseif (strlen($name) < 2) {
        $_SESSION['error_message'] = "Equipment name must be at least 2 characters long.";
    } else {
        // Update the equipment details
        $stmt = $conn->prepare("UPDATE equipment SET name = ?, usage_status = ?, availability = ?, updated_at = NOW() WHERE id = ? AND assigned_to = ?");
        $stmt->bind_param('ssiii', $name, $usage_status, $availability, $equipment_id, $_SESSION['session_userid']);

        if ($stmt->execute()) {
            // Regenerate CSRF token after successful operation
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Store success message in session
            $_SESSION['success_message'] = "Equipment updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating equipment: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }

    // Always redirect back to research_assistant_equipment.php
    header("Location: research_assistant_equipment.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Equipment</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css?v=<?php echo time(); ?>">
    <script>
        // Dynamically toggle the availability input field based on usage status
        function toggleAvailabilityInput() {
            const usageStatus = document.getElementById('usage_status').value;
            const availabilityGroup = document.getElementById('availability-group');
            const availabilityInput = document.getElementById('availability');

            if (usageStatus === 'Under Maintenance') {
                availabilityInput.value = 0;
                availabilityInput.disabled = true;
                availabilityGroup.style.display = 'none'; // Hide the field
            } else {
                availabilityInput.disabled = false;
                availabilityGroup.style.display = 'block'; // Show the field
            }
        }

        // Initialize the availability input on page load
        window.onload = toggleAvailabilityInput;
    </script>
</head>
<body>
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <h1 class="main-title">AMC Research System</h1>
        </div>
        <a href="research_assistant_equipment.php" class="home-btn">Cancel</a>
    </div>

    <div class="equipment-container">
        <!-- Display success/error messages -->
        <?php if (!empty($success_message)) : ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)) : ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Update Section -->
        <div class="create-container">
            <h2>Update Equipment</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($equipment['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="form-group">
                    <label for="name">Equipment Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($equipment['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="usage_status">Usage Status:</label>
                    <select id="usage_status" name="usage_status" onchange="toggleAvailabilityInput()" required>
                        <option value="Available" <?php echo ($equipment['usage_status'] ?? '') === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Under Maintenance" <?php echo ($equipment['usage_status'] ?? '') === 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    </select>
                </div>
                <div class="form-group" id="availability-group">
                    <label for="availability">Availability (Quantity):</label>
                    <input type="number" id="availability" name="availability" min="0" value="<?php echo htmlspecialchars($equipment['availability'] ?? 0, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="update_equipment" class="submit-button">Update Equipment</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
