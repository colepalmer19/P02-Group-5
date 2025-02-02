<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is logged in as Admin
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));  // Generate a new CSRF token
}

// Fetch the report ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_reports.php');
    exit();
}

$report_id = $_GET['id'];

// Fetch the report details to pre-fill the form
$stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin_reports.php');
    exit();
}

$report = $result->fetch_assoc();
$stmt->close();

// Fetch all valid projects
$projects = [];
$project_sql = "SELECT id, title FROM research_projects";
$project_result = $conn->query($project_sql);
if ($project_result->num_rows > 0) {
    while ($row = $project_result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Fetch all valid users
$users = [];
$user_sql = "SELECT id, name FROM user";
$user_result = $conn->query($user_sql);
if ($user_result->num_rows > 0) {
    while ($row = $user_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Handle the form submission
$success_message = $error_message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_report'])) {
    // Validate CSRF token using hash_equals for security
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token.";
    } else {
        // Regenerate CSRF token after form submission
        unset($_SESSION['csrf_token']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $project = $_POST['project'];
        $assigned_to = $_POST['assigned_to'];
        $description = $_POST['description'];
        $equipment_percentage_used = $_POST['equipment_percentage_used'];
        $funding = $_POST['funding'];
        $progress = $_POST['progress'];

        // Validate inputs
        if (empty($description)) {
            $error_message = "Description cannot be empty.";
        } else {
            // Update the report in the database
            $stmt = $conn->prepare("UPDATE reports SET 
                project_id = ?, 
                assigned_to = ?, 
                description = ?, 
                equipment_percentage_used = ?, 
                funding = ?, 
                progress = ? 
                WHERE id = ?");
            $stmt->bind_param("iisidsi", $project, $assigned_to, $description, $equipment_percentage_used, $funding, $progress, $report_id);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Report updated successfully!";
                header("Location: admin_reports.php");
                exit();
            } else {
                $error_message = "Error updating report: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Report</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <span class="main-title">AMC Research System</span>
        </div>
        <a href="admin_reports.php" class="home-btn">Cancel</a>
    </div>

    <!-- Main Content -->
    <div style="display: flex; justify-content: center; align-items: center; flex-direction: column; min-height: 100vh;">
        <div style="width: 100%; max-width: 600px; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 8px; background-color: white;">
            <h1 style="text-align: center; font-size: 24px; margin-bottom: 20px; color: #2C3E50;">Update Report</h1>

            <?php if (!empty($success_message)) echo "<p style='color:green; text-align: center;'>$success_message</p>"; ?>
            <?php if (!empty($error_message)) echo "<p style='color:red; text-align: center;'>$error_message</p>"; ?>

            <form action="report_update.php?id=<?php echo $report_id; ?>" method="POST" style="display: flex; flex-direction: column; gap: 15px; max-width: 100%;">
                <!-- CSRF token field -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <label for="project" style="font-size: 14px; font-weight: bold;">Project:</label>
                <select id="project" name="project" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>" <?php echo $project['id'] == $report['project_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($project['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="assigned_to" style="font-size: 14px; font-weight: bold;">Assigned To (User):</label>
                <select id="assigned_to" name="assigned_to" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $report['assigned_to'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="description" style="font-size: 14px; font-weight: bold;">Description:</label>
                <textarea id="description" name="description" rows="4" required style="resize: none; width: 97%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"><?php echo htmlspecialchars($report['description']); ?></textarea>

                <label for="equipment_percentage_used" style="font-size: 14px; font-weight: bold;">Equipment Percentage Used:</label>
                <input type="number" id="equipment_percentage_used" name="equipment_percentage_used" value="<?php echo htmlspecialchars($report['equipment_percentage_used']); ?>" required style="width: 97%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">

                <label for="funding" style="font-size: 14px; font-weight: bold;">Funding:</label>
                <input type="number" id="funding" name="funding" step="0.01" value="<?php echo htmlspecialchars($report['funding']); ?>" required style="width: 97%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">

                <label for="progress" style="font-size: 14px; font-weight: bold;">Progress:</label>
                <textarea id="progress" name="progress" rows="4" required style="resize: none; width: 97%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"><?php echo htmlspecialchars($report['progress']); ?></textarea>

                <button type="submit" name="update_report" style="width: 100%; padding: 12px; background-color: #2C3E50; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer;">Update Report</button>
            </form>
        </div>
    </div>
</body>
</html>
