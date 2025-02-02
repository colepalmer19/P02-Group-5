<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Researcher-only access check
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Researcher') {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user details securely
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT name, email, contact_information, area_of_expertise, role FROM user WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $email, $contact_information, $area_of_expertise, $role);
    $stmt->fetch();
    $stmt->close();

    if (!$name) {
        $_SESSION['error_message'] = "User not found!";
        header("Location: researcher_user_management.php");
        exit();
    }

    // Prevent Researchers from Updating Admins
    if ($role === 'Admin') {
        $_SESSION['error_message'] = "You are not allowed to update an Admin user!";
        header("Location: researcher_user_management.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Invalid user ID!";
    header("Location: researcher_user_management.php");
    exit();
}

// Handle update form submission securely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: researcher_user_management.php");
        exit();
    }

    // Regenerate CSRF token after validation
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Sanitize and validate inputs
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $contact_information = htmlspecialchars(trim($_POST['contact_information']), ENT_QUOTES, 'UTF-8');
    $area_of_expertise = htmlspecialchars(trim($_POST['area_of_expertise']), ENT_QUOTES, 'UTF-8');

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        header("Location: researcher_user_management.php");
        exit();
    }

    // Check if the new email already exists (excluding current user)
    $email_check_stmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
    $email_check_stmt->bind_param('si', $email, $user_id);
    $email_check_stmt->execute();
    $email_check_stmt->store_result();

    if ($email_check_stmt->num_rows > 0) {
        $_SESSION['error_message'] = "Error: Email already exists. Please use a different email.";
        $email_check_stmt->close();
        header("Location: researcher_user_management.php");
        exit();
    }
    $email_check_stmt->close();

    // Update user details
    $stmt = $conn->prepare("UPDATE user SET name = ?, email = ?, contact_information = ?, area_of_expertise = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $email, $contact_information, $area_of_expertise, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User updated successfully!";
        header("Location: researcher_user_management.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating user: " . htmlspecialchars($stmt->error);
        header("Location: researcher_user_management.php");
        exit();
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <span class="main-title">AMC Research System</span>
        </div>
        <a href="researcher_user_management.php" class="home-btn">Cancel</a>
    </div>

    <!-- Main Content -->
    <div class="equipment-container">
        <!-- Display Success/Error Messages -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <!-- Update Section -->
        <div class="create-container">
            <h2>Update User</h2>
            <form method="POST" class="add-equipment-form">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="contact_information">Contact Information:</label>
                    <input type="text" id="contact_information" name="contact_information" value="<?php echo htmlspecialchars($contact_information); ?>" required>
                </div>
                <div class="form-group">
                    <label for="area_of_expertise">Area of Expertise:</label>
                    <select id="area_of_expertise" name="area_of_expertise" required>
                        <option value="Data Analysis" <?php echo $area_of_expertise === 'Data Analysis' ? 'selected' : ''; ?>>Data Analysis</option>
                        <option value="Software Development" <?php echo $area_of_expertise === 'Software Development' ? 'selected' : ''; ?>>Software Development</option>
                        <option value="Biology" <?php echo $area_of_expertise === 'Biology' ? 'selected' : ''; ?>>Biology</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="submit-button">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Hide success/error messages after 3 seconds
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
