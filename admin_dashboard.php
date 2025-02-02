<?php
require_once 'session_handler.php';
require_once 'dbconnect.php'; // Include the database connection file

// Authentication and Authorization: Verify the user is logged in and is an Admin
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role']) || $_SESSION['session_role'] !== 'Admin') {
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
    <title>AMC Research System</title>
    <link rel="stylesheet" type="text/css" href="style/style.css?v=<?php echo time(); ?>">
    
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
                <a href="my_account.php">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <div class="header">
            <h2>Welcome, <?php echo $sessionName; ?></h2>
            <p>You are logged in as an <strong><?php echo $sessionRole; ?></strong>.</p>
        </div>

        <div class="dashboard-grid">
            <a href="CRUD2/view_projects.php" class="dashboard-card">
                <img src="images/records.png" alt="Project Records">
                <span>Project Records</span>
            </a>
            <a href="CRUD3/equipment.php" class="dashboard-card">
                <img src="images/equipment.png" alt="Equipment Management">
                <span>Equipment Management</span>
            </a>
            <a href="CRUD1/create_user.php" class="dashboard-card">
                <img src="images/user_creation.png" alt="User Creation">
                <span>User Creation</span>
            </a>
            <a href="CRUD1/user_management.php" class="dashboard-card">
                <img src="images/management.png" alt="User Management">
                <span>User Management</span>
            </a>

            <a href="CRUD4/admin_reports.php" class="dashboard-card">
                <img src="images/report.png" alt="Generate Report">
                <span>Generate Report</span>
            </a>
        
            <a href="crud3/accept_equipment.php" class="dashboard-card">
                <img src="images/yesno.png" alt="Accept Equipment">
                <span>Accept Equipment</span>
            </a>
            
        </div>
    </div>
    <?php include 'footer.php'; ?> 
</body>

</html>
