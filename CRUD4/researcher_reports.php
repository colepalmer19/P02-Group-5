<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is logged in as a Researcher
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Researcher') {
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

// Fetch all valid projects from research_projects
$projects = [];
$project_sql = "SELECT id, title FROM research_projects"; // Use the correct column name 'title'
$project_result = $conn->query($project_sql);
if ($project_result->num_rows > 0) {
    while ($row = $project_result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Fetch all valid users from the `user` table
$users = [];
$user_sql = "SELECT id, name FROM user"; // Ensure 'name' is the correct column in your database
$user_result = $conn->query($user_sql);
if ($user_result->num_rows > 0) {
    while ($row = $user_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Handling form submission to create a report
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_report'])) {

    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    // Regenerate CSRF token after form submission
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Retrieve and sanitize user inputs
    $project = $_POST['project'];
    $assigned_to = $_POST['assigned_to'];
    $description = $_POST['description'];
    $equipment_percentage_used = $_POST['equipment_percentage_used'];
    $funding = $_POST['funding'];
    $progress = $_POST['progress'];
    $created_by = $_SESSION['session_userid']; // Use logged-in user's ID as created_by

    // Validate inputs
    if (empty($description)) {
        $error_message = "Description cannot be empty.";
    } else {
        // Check if the project ID exists in the research_projects table
        $stmt = $conn->prepare("SELECT COUNT(*) FROM research_projects WHERE id = ?");
        $stmt->bind_param("i", $project);
        $stmt->execute();
        $stmt->bind_result($project_exists);
        $stmt->fetch();
        $stmt->close();

        if ($project_exists == 0) {
            $error_message = "Invalid Project ID. Please select a valid project.";
        } else {
            // Insert the report into the database
            $stmt = $conn->prepare("INSERT INTO reports 
                (project_id, assigned_to, description, equipment_percentage_used, funding, progress, created_by) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisidsi", $project, $assigned_to, $description, $equipment_percentage_used, $funding, $progress, $created_by);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Report created successfully!";
                header("Location: researcher_reports.php");
                exit();
            } else {
                $error_message = "Error creating report: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all reports to display (including the user who created it)
$sql = "SELECT r.*, u.name AS assigned_user 
        FROM reports r 
        JOIN user u ON r.assigned_to = u.id";
$result = $conn->query($sql);
$reports = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="/group/style/style.css">
    <title>Project Report</title>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <span class="main-title">AMC Research System</span>
        </div>
        <a href="/group/researcher_dashboard.php" class="home-btn">Home</a>
    </div>

    <!-- Main Content -->
    <div class="report-container">
        
        <!-- Form to create a new report -->
        <div class="create-container">
            <h2>Create New Report</h2>
            <?php 
            if (!empty($_SESSION['success_message'])) {
                echo "<div class='message success' style='margin-top: 20px; padding: 10px; background-color: #d4edda; 
                        color: #155724; border: 1px solid #c3e6cb; border-radius: 5px;'>
                        " . $_SESSION['success_message'] . "
                    </div>";
                unset($_SESSION['success_message']); // Clear the message after displaying
            }

            if (!empty($error_message)) {
                echo "<div class='message error' style='margin-top: 20px; padding: 10px; background-color: #f8d7da; 
                        color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'>
                        $error_message
                    </div>";
            }
            ?>
            <form action="researcher_reports.php" method="POST">
                <!-- Include CSRF token in the form -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <label for="project">Project:</label>
                <select id="project" name="project" required>
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>">
                            <?php echo htmlspecialchars($project['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="assigned_to">Assigned To (User):</label>
                <select id="assigned_to" name="assigned_to" required>
                    <option value="">Select User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required style="resize: none; display: block; width: 100%; max-width: 1170px; margin-bottom: 20px;"></textarea>

                <label for="equipment_percentage_used">Equipment Percentage Used:</label>
                <input type="number" id="equipment_percentage_used" name="equipment_percentage_used" required>

                <label for="funding">Funding:</label>
                <input type="number" id="funding" name="funding" step="0.01" required>

                <label for="progress">Progress:</label>
                <textarea id="progress" name="progress" rows="4" required style="resize: none; display: block; width: 100%; max-width: 1170px; margin-bottom: 20px;"></textarea>
                        
                <button type="submit" name="create_report" style="display: block; margin-top: 20px; width: 100%; max-width: 200px;">Create Report</button>
            </form>
        </div>

        <div class="read-container">
            <h2>Existing Reports</h2>
            <div class="reports-grid">
                <?php foreach ($reports as $report): ?>
                    <div class="report-card">
                        <h3>Project ID: <?php echo htmlspecialchars($report['project_id']); ?></h3>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                        <p><strong>Assigned To:</strong> <?php echo htmlspecialchars($report['assigned_user']); ?></p>
                        <p><strong>Equipment Used:</strong> <?php echo htmlspecialchars($report['equipment_percentage_used']); ?>%</p>
                        <p><strong>Funding:</strong> $<?php echo htmlspecialchars($report['funding']); ?></p>
                        <p><strong>Progress:</strong> <?php echo nl2br(htmlspecialchars($report['progress'])); ?></p>
                    </div>
                <?php endforeach; ?>
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
    </div>
</body>
</html>
