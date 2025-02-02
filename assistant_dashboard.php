<?php

// Include required files
require_once 'session_handler.php';
require_once 'dbconnect.php';

// Authentication & Authorization: Ensure user is a Research Assistant
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Research Assistant') {
    header('Location: login.php');
    exit();
}

// Sanitize session variables for safe output
$sessionName = htmlspecialchars($_SESSION['session_name'], ENT_QUOTES, 'UTF-8');
$sessionRole = htmlspecialchars($_SESSION['session_role'], ENT_QUOTES, 'UTF-8');


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Assistant Dashboard</title>
    <link rel="stylesheet" href="style/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <!-- Top Bar -->
    <div class="top-bar" style="width: 100%; padding: 10px; background: #2C3E50; text-align: center; position: fixed; top: 0; left: 0; color: white;">
        <div class="logo-title-container">
            <img src="images/research12.png" alt="Logo" class="logo">
            <h1 class="main-title" style="margin: 0; font-size: 22px;">AMC Research System</h1>
        </div>
        
        <div class="account-dropdown" style="margin-right: 30px;">
            <button class="account-btn">
                My Account ▼
            </button>
            <div class="dropdown-content">
                <a href="my_account_assistant.php">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>

    </div> <!-- Fixed extra closing div -->

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <div class="header">
            <h2>Welcome, <?php echo $sessionName; ?></h2>
            <p>You are logged in as a <strong><?php echo $sessionRole; ?></strong>.</p>
        </div>

        <!-- Updated Dashboard Grid -->
        <div class="dashboard-grid">
            <a href="crud2/assistant_projects.php" class="dashboard-card">
                <img src="images/records.png" alt="Assigned Projects">
                <span>Assigned Projects</span>
            </a>
            <a href="crud3/research_assistant_equipment.php" class="dashboard-card">
                <img src="images/equipment.png" alt="Equipment Management">
                <span>Equipment Management</span>
            </a>

            <a href="crud4/research_assistant_reports.php" class="dashboard-card">
                <img src="images/management.png" alt="Project Reports">
                <span>Project Reports</span>
            </a>
        </div>
    </div>
    <?php include 'footer.php'; ?> 
</body>
</html>