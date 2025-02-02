<?php
require_once 'session_handler.php';
require_once 'dbconnect.php'; // Ensure $conn is initialized

// Ensure the user is logged in and is a Research Assistant
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Research Assistant') {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if it doesn't already exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize messages
$success_message = '';
$error_message = '';

// Fetch user details from the database
$stmt = $conn->prepare("SELECT name, email, contact_information, password FROM user WHERE id = ?");
$stmt->bind_param("i", $_SESSION['session_userid']);
$stmt->execute();
$stmt->bind_result($current_name, $current_email, $current_contact, $hashed_password);
$stmt->fetch();
$stmt->close();

// Debugging: Check session values (remove after testing)
error_log("Session UserID: " . $_SESSION['session_userid']);
error_log("Session Role: " . $_SESSION['session_role']);

// Handle profile update (name, email, and contact)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_info'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    // Regenerate CSRF token only on successful update
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Sanitize inputs
    $new_name = htmlspecialchars(trim($_POST['new_name']), ENT_QUOTES, 'UTF-8');
    $new_email = filter_var(trim($_POST['new_email']), FILTER_SANITIZE_EMAIL);
    $new_contact = htmlspecialchars(trim($_POST['new_contact']), ENT_QUOTES, 'UTF-8');

    // Validate inputs
    if (empty($new_name) || empty($new_email) || empty($new_contact)) {
        $error_message = "All fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE user SET name = ?, email = ?, contact_information = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_name, $new_email, $new_contact, $_SESSION['session_userid']);

        if ($stmt->execute()) {
            $success_message = "Information updated successfully!";
            $current_name = $new_name;
            $current_email = $new_email;
            $current_contact = $new_contact;
        } else {
            $error_message = "Error updating information: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    // Regenerate CSRF token after successful password change
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate password inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required for password change.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New password and confirmation do not match.";
    } elseif (!password_verify($current_password, $hashed_password)) {
        $error_message = "Current password is incorrect.";
    } else {
        // Hash the new password
        $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update the password in the database
        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hashed_password, $_SESSION['session_userid']);
        if ($stmt->execute()) {
            $success_message = "Password updated successfully!";
        } else {
            $error_message = "Error updating password: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="stylesheet" type="text/css" href="style/style.css?v=<?php echo time(); ?>">
</head>
<body>
<!-- Top Bar -->
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <span class="main-title">AMC Research System</span>
        </div>
        <a href="/group/assistant_dashboard.php" class="home-btn">Home</a>
    </div>
    
    <div class="container create-container">
        <h2>Update Your Information</h2>

        <!-- Display Messages -->
        <?php 
        if (!empty($success_message)) echo "<p class='message success'>$success_message</p>"; 
        if (!empty($error_message)) echo "<p class='message error'>$error_message</p>"; 
        ?>

        <!-- Update Name, Email & Contact Form -->
        <form action="my_account_assistant.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <label for="new_name">Name:</label>
            <input type="text" name="new_name" id="new_name" value="<?php echo htmlspecialchars($current_name, ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="new_email">Email:</label>
            <input type="email" name="new_email" id="new_email" value="<?php echo htmlspecialchars($current_email, ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="new_contact">Contact Information:</label>
            <input type="text" name="new_contact" id="new_contact" value="<?php echo htmlspecialchars($current_contact, ENT_QUOTES, 'UTF-8'); ?>" required>

            <button type="submit" name="update_info">Update</button>
        </form>

        <!-- Change Password Form -->
        <h2>Change Password</h2>
        <form action="my_account_assistant.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" id="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit" name="change_password">Change Password</button>
        </form>
    </div>

    <script>
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
