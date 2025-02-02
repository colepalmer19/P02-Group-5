<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is logged in as Research Assistant
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Research Assistant') {
    header('Location: login.php');
    exit();
}

$success_message = $error_message = "";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Projects</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css">
</head>
<body class="projects-management">
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <h1 class="main-title">AMC Research System</h1>
        </div>
        <a href="/group/assistant_dashboard.php" class="home-btn">Home</a>
    </div>

    <div class="projects-container">
        <!-- Display success/error messages -->
        <?php if (!empty($success_message)) : ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)) : ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- All Research Projects Assigned to Research Assistant -->
        <div class="read-container">
            <h2>Your Research Projects</h2>
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Funding</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $assistant_id = $_SESSION['session_userid'];
                    $projects = $conn->query(
                        "SELECT rp.*, GROUP_CONCAT(u.name SEPARATOR ', ') AS assigned_to_names
                         FROM research_projects rp
                         LEFT JOIN project_team pt ON rp.id = pt.project_id
                         LEFT JOIN user u ON pt.user_id = u.id
                         WHERE pt.user_id = $assistant_id
                         GROUP BY rp.id"
                    );

                    while ($row = $projects->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                        echo "<td>" . htmlspecialchars(number_format($row['funding'], 2)) . "</td>";
                        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['assigned_to_names'] ?: 'Unassigned') . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        // Hide success/error messages after 3 seconds
        setTimeout(function() {
            const message = document.querySelector('.message');
            if (message) {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            }
        }, 3000);
    </script>
</body>
</html>
