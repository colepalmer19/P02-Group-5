<?php
require 'vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include required files
require_once 'session_handler.php';
require_once 'dbconnect.php';

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email']);

    // Check if the email exists
    $query = "SELECT * FROM user WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $token = bin2hex(random_bytes(50)); // Generate token
        $expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes")); // Set expiration time

        // Save token with expiry time
        $query = "INSERT INTO password_reset (email, token, expires_at) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $email, $token, $expires_at);
        $stmt->execute();
        $stmt->close();

        // Save token to password_reset table
        $query = "INSERT INTO password_reset (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $email, $token, $expires_at);
        $stmt->execute();


        // Send reset email
        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'amcresearchsystem@gmail.com';
            $mail->Password = 'oxvc vbaf zsrr xmgz'; // App password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Email content
            $mail->setFrom('amcresearchsystem@gmail.com', 'AMC Research System');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Click <a href='http://localhost/group/password_change.php?token=$token'>here</a> to reset your password.";

            $mail->send();
            $successMessage = "Password reset email has been sent!";
        } catch (Exception $e) {
            $errorMessage = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $errorMessage = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <link rel="stylesheet" type="text/css" href="style/style.css?v=<?php echo time(); ?>">
</head>

<body style="margin: 0; padding: 0; font-family: 'Poppins', sans-serif; overflow: hidden;">

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="images/research12.png" alt="Logo" class="logo">
            <h1 class="main-title">AMC Research System</h1>
        </div>
        <a href="login.php" class="home-btn">Login</a>
    </div>

    <!-- Centered Form -->
    <div class="login-wrapper" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 40px);">
        <div class="form-container" style="margin-top: 20px;"> <!-- Added margin-top -->
            <h1>Password Reset</h1>

            <!-- Success or Error Message -->
            <?php if (!empty($successMessage)) : ?>
                <p class="message success"><?php echo $successMessage; ?></p>
            <?php elseif (!empty($errorMessage)) : ?>
                <p class="message error"><?php echo $errorMessage; ?></p>
            <?php endif; ?>

            <form method="POST" action="password_reset_request.php">
                <div class="form-group">
                    <label for="email">Enter your email address:</label>
                    <input type="text" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <button type="submit" class="button">Send Password Reset Email</button>
            </form>
        </div>
    </div>

</body>

</html>
