<?php
require_once 'session_handler.php';
require_once 'dbconnect.php'; // Include the database connection file

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {

    // Sanitize inputs
    $form_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $form_password = $_POST['password'];

    // Check for empty fields
    if (empty($form_email) || empty($form_password)) {
        $_SESSION['error_message'] = "Invalid email or password.";
        header('Location: login.php');
        exit();
    }

    try {
        // Prepare the SQL query
        $stmt = $conn->prepare("SELECT id, password, role, name FROM user WHERE email = ?");
        $stmt->bind_param('s', $form_email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the user exists
        if ($row = $result->fetch_assoc()) {
            // Verify the password
            if (password_verify($form_password, $row['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['session_userid'] = $row['id'];
                $_SESSION['session_name'] = $row['name'];
                $_SESSION['session_role'] = $row['role'];

                // Redirect based on role
                switch ($row['role']) {
                    case 'Admin':
                        header('Location: admin_dashboard.php');
                        break;
                    case 'Research Assistant':
                        header('Location: assistant_dashboard.php');
                        break;
                    case 'Researcher':
                        header('Location: researcher_dashboard.php');
                        break;
                    default:
                        $_SESSION['error_message'] = "Invalid role assigned. Contact the system administrator.";
                        header('Location: login.php');
                }
                exit();
            }
        }

        // Generic error message for failed login attempts
        $_SESSION['error_message'] = "Invalid email or password.";
        header('Location: login.php');
        exit();
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error_message'] = "An unexpected error occurred. Please try again later.";
        header('Location: login.php');
        exit();
    } finally {
        // Close the connection
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style/style.css?v=<?php echo time(); ?>"> <!-- External CSS -->
</head>
<body class="login-page" style="margin: 0; padding: 0; height: 90vh; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 40px;">

    <!-- Top Bar -->
    <div class="top-bar" style="width: 100%; padding: 10px; background: #2C3E50; text-align: center; position: fixed; top: 0; left: 0; color: white;">
        <div class="logo-title-container">
            <img src="images/research12.png" alt="Logo" class="logo"> <!-- Add your logo here -->
            <h1 class="main-title" style="margin: 0; font-size: 22px;">AMC Research System</h1>
        </div>
    </div>

    <!-- Centered Login Wrapper -->
    <div class="login-wrapper">
        <div class="form-container">
            <h1>Login</h1>
            <?php
            if (isset($_SESSION['error_message'])) {
                echo "<div class='message error'>{$_SESSION['error_message']}</div>";
                unset($_SESSION['error_message']);
            }
            ?>
            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
            </form>

            <a href="password_reset_request.php" class="link">Forgot Password?</a> <!-- Forgot Password Link -->
        </div>
    </div>
    <?php include 'footer.php'; ?> 

</body>
</html>
