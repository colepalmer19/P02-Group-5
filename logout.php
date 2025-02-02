<?php
session_start();

// Security: Destroy session
session_unset();
session_destroy();

// Remove session cookie for security
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Determine logout reason
$logout_message = "You have successfully logged out.";
if (isset($_GET['session_expired'])) {
    $logout_message = "You were logged out due to inactivity.";
}

// Secure redirect after 3 seconds
header("Refresh: 3; url=login.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <link rel="stylesheet" href="style/style.css?v=<?php echo time(); ?>">
</head>

<body style="margin: 0; padding: 0; height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; background: #f4f4f4; font-family: 'Poppins', sans-serif; color: #333; overflow: hidden;">

    <!-- Top Bar -->
    <div class="top-bar" style="width: 100%; padding: 15px; background: #2C3E50; text-align: center; position: fixed; top: 0; left: 0; color: white;">
        <div class="logo-title-container" style="display: flex; align-items: center; justify-content: center;">
            <img src="images/research12.png" alt="Logo" class="logo" style="height: 40px; margin-right: 10px;">
            <h1 class="main-title" style="margin: 0; font-size: 22px;">AMC Research System</h1>
        </div>
    </div>

    <!-- Logout Message Container -->
    <div class="message-container" 
        style="width: 350px; height: 350px; background: white; display: flex; flex-direction: column; justify-content: center; align-items: center; 
               text-align: center; border-radius: 10px; box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1); border: 2px solid #2C3E50;">
        <h1 style="margin-bottom: 15px; font-size: 26px;">👋 Goodbye, User!</h1>
        <p style="margin-bottom: 10px; font-size: 16px;"><?php echo htmlspecialchars($logout_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <p style="font-size: 14px;">Redirecting to login page in <span style="font-weight: bold;">3 seconds...</span></p>
    </div>

    <?php include 'footer.php'; ?> 

    <script>
        // Redirect to login after 3 seconds
        setTimeout(() => {
            window.location.href = "login.php";
        }, 3000);
    </script>
</body>
</html>
