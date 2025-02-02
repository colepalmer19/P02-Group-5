<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Check if the user is authorized (Researcher)
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Researcher') {
    header('Location: login.php');
    exit();
}

// Fetch the project details
if (isset($_GET['id'])) {
    $project_id = (int)$_GET['id'];

    // Fetch project details
    $stmt = $conn->prepare("SELECT * FROM research_projects WHERE id = ?");
    $stmt->bind_param('i', $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();

    if (!$project) {
        echo "Project not found!";
        exit();
    }
    $stmt->close();

    // Fetch assigned team members
    $assigned_team_stmt = $conn->prepare("SELECT user_id FROM project_team WHERE project_id = ?");
    $assigned_team_stmt->bind_param('i', $project_id);
    $assigned_team_stmt->execute();
    $assigned_team_result = $assigned_team_stmt->get_result();
    $assigned_team = [];
    while ($row = $assigned_team_result->fetch_assoc()) {
        $assigned_team[] = $row['user_id'];
    }
    $assigned_team_stmt->close();
}

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = (int)$_POST['id'];
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $funding = floatval($_POST['funding']);
    $status = htmlspecialchars($_POST['status']);
    $assigned_to = $_POST['assigned_to']; // Array of user IDs

    // Update the project details
    $stmt = $conn->prepare("UPDATE research_projects SET title = ?, description = ?, funding = ?, status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ssdsi', $title, $description, $funding, $status, $project_id);

    if ($stmt->execute()) {
        // Update project-team assignments
        $conn->query("DELETE FROM project_team WHERE project_id = $project_id");
        $assignment_stmt = $conn->prepare("INSERT INTO project_team (project_id, user_id) VALUES (?, ?)");
        foreach ($assigned_to as $user_id) {
            $user_id_value = intval($user_id);
            $assignment_stmt->bind_param('ii', $project_id, $user_id_value);
            $assignment_stmt->execute();
        }
        $assignment_stmt->close();

        $_SESSION['success_message'] = "Project updated successfully!";
        header("Location: researcher_projects.php"); // Redirect to project listing page
        exit();
    } else {
        $error_message = "Error updating project: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Research Project</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <span class="main-title">AMC Research System</span>
        </div>
        <a href="researcher_projects.php" class="home-btn">Cancel</a>
    </div>

    <!-- Main Content -->
    <div class="project-container">
        <!-- Display Success/Error Messages -->
        <?php if (!empty($error_message)) : ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Update Section -->
        <div class="create-container">
            <h2>Update Research Project</h2>
            <form method="POST" class="add-project-form">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($project['id']); ?>">

                <div class="form-group">
                    <label for="title">Project Title:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Project Description:</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="10" 
                        required 
                        style="resize: none; display: block; width: 100%; max-width: 1160px; height: 100px; margin-bottom: 20px;"><?php echo htmlspecialchars($project['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="funding">Funding (USD):</label>
                    <input type="number" id="funding" name="funding" value="<?php echo htmlspecialchars($project['funding']); ?>" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Completed" <?php echo $project['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="In Progress" <?php echo $project['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="assigned_to">Assign to Team Members:</label>
                    <select id="assigned_to" name="assigned_to[]" multiple required style="width: 100%; max-width: 1160px;">
                        <?php
                        $users = $conn->query("SELECT id, name FROM user WHERE role != 'Admin'");
                        while ($user = $users->fetch_assoc()) {
                            $selected = in_array($user['id'], $assigned_team) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($user['id']) . "' $selected>" . htmlspecialchars($user['name']) . "</option>";
                        }
                        ?>
                    </select>
                    <small>Hold CTRL (or CMD on Mac) to select multiple team members.</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="submit-button">Update Project</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
