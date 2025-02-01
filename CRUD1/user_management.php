<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Check if the user is logged in and is an Admin
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role']) || $_SESSION['session_role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <span class="main-title">AMC Research System</span>
        </div>
        <a href="/group/admin_dashboard.php" class="home-btn">Home</a>
    </div>

    <!-- Main Content -->
    <div class="dashboard-content">
        <h1 class="dashboard-title">User Management</h1>

        
        
        <!-- User Management Container -->
        <div class="read-container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact Information</th>
                        <th>Area of Expertise</th>
                        <th>Email</th>
                        <th>Age</th>
                        <th>Role</th>
                        <th colspan="2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Prepare and execute the query securely
                    $stmt = $conn->prepare("SELECT id, name, contact_information, area_of_expertise, email, age, role FROM user");
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td>" . htmlspecialchars($row['contact_information'], ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td>" . htmlspecialchars($row['area_of_expertise'], ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td>" . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td>" . htmlspecialchars($row['age'], ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td>" . htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td><a href='update_user.php?id=" . urlencode($row['id']) . "'>Update</a></td>";
                        echo "<td><a href='delete_user.php?id=" . urlencode($row['id']) . "' onclick='return confirm(\"Are you sure you want to delete this user?\");'>Delete</a></td>";
                        echo "</tr>";
                    }

                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
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
