<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is logged in as Admin
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Success/Error Message Variables
$success_message = "";
$error_message = "";

// Handle Adding a New Research Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }

    // ✅ Regenerate CSRF token after successful validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Get form values and sanitize
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $funding = floatval($_POST['funding']);
    $status = htmlspecialchars($_POST['status']);
    
    // ✅ Ensure assigned_to exists, otherwise set it to an empty array
    $assigned_to = isset($_POST['assigned_to']) ? $_POST['assigned_to'] : [];

    if (empty($title) || empty($description) || empty($status)) {
        $error_message = "Please fill in all required fields!";
    } else {
        // Insert the new project into the database
        $stmt = $conn->prepare(
            "INSERT INTO research_projects (title, description, funding, status, created_at, updated_at) 
             VALUES (?, ?, ?, ?, NOW(), NOW())"
        );

        if (!$stmt) {
            $error_message = "Error in query preparation: " . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param('ssds', $title, $description, $funding, $status);

            if ($stmt->execute()) {
                $project_id = $stmt->insert_id; // Get the newly inserted project ID

                // ✅ If users are assigned, insert them into project_team
                if (!empty($assigned_to)) {
                    $assignment_stmt = $conn->prepare("INSERT INTO project_team (project_id, user_id) VALUES (?, ?)");
                    foreach ($assigned_to as $user_id) {
                        $user_id_value = intval($user_id);
                        $assignment_stmt->bind_param('ii', $project_id, $user_id_value);
                        $assignment_stmt->execute();
                    }
                    $assignment_stmt->close();
                }

                $_SESSION['success_message'] = "Project added successfully!";
            } else {
                $_SESSION['error_message'] = "Error adding project: " . htmlspecialchars($stmt->error);
            }

            $stmt->close();
        }
    }
}



// Fetch Ongoing (In Progress) Projects
$ongoingProjectsQuery = $conn->query(
    "SELECT rp.*, IFNULL(GROUP_CONCAT(DISTINCT u.name SEPARATOR ', '), 'Unassigned') AS assigned_to_names
     FROM research_projects rp
     LEFT JOIN project_team pt ON rp.id = pt.project_id
     LEFT JOIN user u ON pt.user_id = u.id
     WHERE rp.status = 'In Progress'
     GROUP BY rp.id"
);

// Fetch Completed Projects
$completedProjectsQuery = $conn->query(
    "SELECT rp.*, IFNULL(GROUP_CONCAT(DISTINCT u.name SEPARATOR ', '), 'Unassigned') AS assigned_to_names
     FROM research_projects rp
     LEFT JOIN project_team pt ON rp.id = pt.project_id
     LEFT JOIN user u ON pt.user_id = u.id
     WHERE rp.status = 'Completed'
     GROUP BY rp.id"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Projects</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css?v=<?php echo time(); ?>">
</head>
<body class="projects-management">
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <h1 class="main-title">AMC Research System</h1>
        </div>
        <a href="/group/admin_dashboard.php" class="home-btn">Home</a>
    </div>

    <div class="projects-container">
        <!-- Create or Assign Section -->
        <div class="create-container">
            <h2>Add or Assign Research Project</h2>

            <!-- ✅ SHOW SUCCESS/ERROR MESSAGES HERE ✅ -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['delete_success_message'])): ?>
                <div class="message success"><?php echo $_SESSION['delete_success_message']; unset($_SESSION['delete_success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>


            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="add-project-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <input type="text" name="title" placeholder="Project Title" required>
                </div>
                <div class="form-group">
                    <textarea name="description" rows="10" required style="resize: none; width: 100%;" placeholder="Project Description"></textarea>
                </div>

                <div class="form-group">
                    <input type="number" step="0.01" name="funding" placeholder="Funding Amount (e.g., 5000.00)" required>
                </div>
                <div class="form-group">
                    <select name="status" required>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assigned_to">Assign to Team Members:</label>
                    <select name="assigned_to[]" id="assigned_to" multiple required>
                        <option value="" disabled selected>Select Team Members</option>
                        <?php
                        $users = $conn->query("SELECT id, name FROM user WHERE role != 'Admin'");
                        while ($user = $users->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($user['id']) . "'>" . htmlspecialchars($user['name']) . "</option>";
                        }
                        ?>
                    </select>
                    <small>Hold CTRL (or CMD on Mac) to select multiple team members.</small>
                </div>
                <div class="form-group">
                    <button type="submit" name="add_project" class="submit-button">Add/Assign Project</button>
                </div>
            </form>
        </div>

        <!-- Ongoing Research Projects Section -->
        <div class="read-container">
            <h2>Ongoing Research Projects</h2>
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Funding</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $ongoingProjectsQuery->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($row['funding'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo !empty($row['assigned_to_names']) ? htmlspecialchars($row['assigned_to_names']) : 'Unassigned'; ?></td>
                            <td>
                                <a href='project_update.php?id=<?php echo htmlspecialchars($row['id']); ?>'>Update</a> | 
                                <a href="project_delete.php?id=<?php echo htmlspecialchars($row['id']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                    class="delete-link"
                                    onclick="return confirm('Are you sure you want to delete this project?');">
                                    Delete
                                </a>

                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Completed Research Projects Section -->
        <div class="read-container">
            <h2>Completed Research Projects</h2>
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
                    <?php while ($row = $completedProjectsQuery->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($row['funding'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo !empty($row['assigned_to_names']) ? htmlspecialchars($row['assigned_to_names']) : 'Unassigned'; ?></td>
                        </tr>
                    <?php endwhile; ?>
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
