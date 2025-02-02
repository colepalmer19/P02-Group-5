<?php
// Secure session settings (MUST be set BEFORE session_start())
ini_set('session.cookie_secure', '1');  // Enforce HTTPS for session cookies
ini_set('session.cookie_httponly', '1'); // Prevent JavaScript from accessing session cookie
ini_set('session.use_strict_mode', '1'); // Prevent session hijacking
ini_set('session.use_only_cookies', '1'); // Ensure session only uses cookies (no URL-based sessions)
ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF attacks by enforcing strict cookie policy
ini_set('session.gc_maxlifetime', '1800'); // 30 minutes session timeout

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,  // Session expires when browser closes
    'path' => '/',
    'domain' => '',  // Use current domain
    'secure' => true, // Ensure cookies are sent only over HTTPS
    'httponly' => true, // Prevent JavaScript access
    'samesite' => 'Strict' // Prevent CSRF attacks
]);

// Start session (ONLY if it hasn't already started)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Function to manage session security:
 * - Handles session timeout (default 30 minutes)
 * - Regenerates session ID every 60 seconds to prevent fixation attacks
 * - Binds session to IP & User-Agent to prevent hijacking
 */
function manageSession($timeout_duration = 1800, $regenerate_interval = 60) {
    // Check if session expired due to inactivity
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > $timeout_duration) {
            session_unset();
            session_destroy();
            header("Location: logout.php?session_expired=1");
            exit();
        }
    }
    $_SESSION['last_activity'] = time(); // Update last activity timestamp

    // Regenerate session ID every X seconds (default: 60s) to prevent fixation attacks
    if (!isset($_SESSION['LAST_REGEN']) || time() - $_SESSION['LAST_REGEN'] > $regenerate_interval) {
        session_regenerate_id(true);
        $_SESSION['LAST_REGEN'] = time();
    }

    // Bind session to IP & User-Agent to prevent session hijacking
    if (!isset($_SESSION['user_ip'])) {
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    } else {
        if ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_unset();
            session_destroy();
            header('Location: login.php');
            exit();
        }
    }
}

// Call the function to manage session security
manageSession();
?>
