<?php
// Include required files
require_once 'session_handler.php';
require_once 'dbconnect.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid or missing token.");
}

$token = $_GET['token'];

// Check if the token exists and is still valid
$stmt = $conn->prepare("SELECT email, expires_at FROM password_reset WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->bind_result($email, $expires_at);
if (!$stmt->fetch() || strtotime($expires_at) < time()) {
    die("This password reset link has expired.");
}
$stmt->close();

// Check if the token is expired
$current_time = date("Y-m-d H:i:s");
if ($current_time > $expires_at) {
    die("This password reset link has expired.");
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Error and Success Messages
$errorMessage = "";
$successMessage = "";

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errorMessage = "CSRF token validation failed.";
    } else {
        // Sanitize user inputs
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validate inputs
        if (empty($new_password) || empty($confirm_password)) {
            $errorMessage = "All fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $errorMessage = "Passwords do not match."; // Password mismatch error
        } else {
            // Hash the new password
            $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Update the user's password in the `user` table
            $stmt = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $new_hashed_password, $email);
            if ($stmt->execute()) {
                $successMessage = "Password updated successfully!";

                // ✅ Delete the token after successful password reset
                $deleteStmt = $conn->prepare("DELETE FROM password_reset WHERE token = ?");
                $deleteStmt->bind_param("s", $token);
                $deleteStmt->execute();
                $deleteStmt->close();
            } else {
                $errorMessage = "Error updating password. Please try again.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" type="text/css" href="style/style.css?v=<?php echo time(); ?>">
</head>
<body style="margin: 0; padding: 0; font-family: 'Poppins', sans-serif; overflow: hidden; display: flex; flex-direction: column; min-height: 100vh;">

    <!-- Top Bar -->
    <div class="top-bar" style="flex-shrink: 0;">
        <div class="logo-title-container" style="display: flex; align-items: center;">
            <img src="images/research12.png" alt="Logo" class="logo" style="width: 36px; height: auto; margin-right: 10px;">
            <h1 class="main-title" style="color: #FFFFFF; font-size: 24px; margin: 0;">AMC Research System</h1>
        </div>
        <a href="login.php" class="home-btn" style="background: #007bff; color: #FFFFFF; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 14px; font-weight: bold;">Login</a>
    </div>

    <!-- Centered Form -->
    <div class="login-wrapper" style="display: flex; justify-content: center; align-items: center; flex-grow: 1; padding: 20px;">
        <div class="form-container" style="width: 100%; max-width: 400px; padding: 20px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); text-align: center;">
            <h1 style="font-size: 24px; color: #2C3E50; margin-bottom: 20px;">Change Password</h1>
            
            <!-- Display Error or Success Messages -->
            <?php if (!empty($successMessage)) : ?>
                <p class="message success" style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 8px 10px; border-radius: 4px; font-size: 14px; margin-bottom: 15px;">
                    <?php echo $successMessage; ?>
                </p>
            <?php elseif (!empty($errorMessage)) : ?>
                <p class="message error" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 8px 10px; border-radius: 4px; font-size: 14px; margin-bottom: 15px;">
                    <?php echo $errorMessage; ?>
                </p>
            <?php endif; ?>

            <form action="password_change.php?token=<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>" method="POST" style="text-align: left;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="form-group" style="margin-bottom: 10px;">
                    <label for="new_password" style="display: block; font-size: 14px; color: #333; margin-bottom: 3px;">New Password</label>
                    <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 10px;">
                    <label for="confirm_password" style="display: block; font-size: 14px; color: #333; margin-bottom: 3px;">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                </div>
                
                <button type="submit" class="button" style="width: 100%; padding: 10px; background-color: #2C3E50; color: white; border: none; border-radius: 5px; font-size: 14px; font-weight: bold; cursor: pointer; transition: background-color 0.3s;">Change Password</button>
            </form>

        </div>
    </div>

</body>
</html>
