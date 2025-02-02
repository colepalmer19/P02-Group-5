<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Check if the user is logged in and is a Researcher
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Researcher') {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch ongoing projects (status = 'In Progress')
$ongoing_projects = [];
$query = "SELECT id, title FROM research_projects WHERE status = 'In Progress'";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ongoing_projects[] = $row;
    }
}

// Handle form submission for creating a user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid CSRF token!";
        header("Location: create_user_researcher.php");
        exit();
    }

    // Regenerate CSRF token after validation
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Sanitize and validate inputs
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $contact_information = htmlspecialchars(trim($_POST['contact_information']), ENT_QUOTES, 'UTF-8');
    $area_of_expertise = htmlspecialchars(trim($_POST['area_of_expertise']), ENT_QUOTES, 'UTF-8');
    $age = (int)$_POST['age'];
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $role = htmlspecialchars($_POST['role'], ENT_QUOTES, 'UTF-8');
    $assigned_projects = isset($_POST['assigned_projects']) ? $_POST['assigned_projects'] : [];
    $password = $_POST['password'];

    // Ensure Admins cannot be created
    if ($role === "Admin") {
        $_SESSION['error_message'] = "You cannot create an Admin user.";
        header("Location: create_user_researcher.php");
        exit();
    }

    // Check if the email already exists
    $email_check_stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $email_check_stmt->bind_param('s', $email);
    $email_check_stmt->execute();
    $email_check_stmt->store_result();

    if ($email_check_stmt->num_rows > 0) {
        $_SESSION['error_message'] = "Error: Email already exists. Please use a different email.";
        $email_check_stmt->close();
        header("Location: create_user_researcher.php");
        exit();
    }

    $email_check_stmt->close();

    // Securely Hash the Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the `user` table
    $stmt = $conn->prepare("INSERT INTO user (name, contact_information, area_of_expertise, age, email, role, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssisss', $name, $contact_information, $area_of_expertise, $age, $email, $role, $hashed_password);

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id; // Get the newly created user ID

        // Assign projects if any were selected
        if (!empty($assigned_projects)) {
            $assign_stmt = $conn->prepare("INSERT INTO project_team (project_id, user_id) VALUES (?, ?)");
            foreach ($assigned_projects as $project_id) {
                $assign_stmt->bind_param('ii', $project_id, $new_user_id);
                $assign_stmt->execute();
            }
            $assign_stmt->close();
        }

        $_SESSION['success_message'] = "User created successfully!";
    } else {
        $_SESSION['error_message'] = "Error: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    header("Location: create_user_researcher.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User (Researcher)</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css">
</head>
<body>
    <div class="top-bar">
        <div class="logo-title-container">
            <img src="/group/images/research12.png" alt="Logo" class="logo">
            <span class="main-title">AMC Research System</span>
        </div>
        <a href="/group/researcher_dashboard.php" class="home-btn">Home</a>
    </div>

    <div class="dashboard-content">
        <div class="create-container">
            <h1 class="dashboard-title">Create New User</h1> 

            <?php
            if (isset($_SESSION['success_message'])) {
                echo "<div class='message success'>{$_SESSION['success_message']}</div>";
                unset($_SESSION['success_message']);
            }

            if (isset($_SESSION['error_message'])) {
                echo "<div class='message error'>{$_SESSION['error_message']}</div>";
                unset($_SESSION['error_message']);
            }
            ?>

            <form method="POST" action="create_user_researcher.php" class="add-equipment-form">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <label for="contact_information">Contact Information:</label>
                    <input type="text" id="contact_information" name="contact_information" placeholder="Phone Number" required>
                </div>
                <div class="form-group">
                    <label for="area_of_expertise">Area of Expertise:</label>
                    <select id="area_of_expertise" name="area_of_expertise" required>
                        <option value="" disabled selected>Select an Area of Expertise</option>
                        <option value="Data Analysis">Data Analysis</option>
                        <option value="Software Development">Software Development</option>
                        <option value="Biology">Biology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" placeholder="Age" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="Researcher">Researcher</option>
                        <option value="Research Assistant">Research Assistant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assigned_projects">Assign Research Projects:</label>
                    <select id="assigned_projects" name="assigned_projects[]" multiple>
                        <option value="" disabled selected>Select Ongoing Projects</option>
                        <?php foreach ($ongoing_projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Hold CTRL (or CMD on Mac) to select multiple projects.</small>
                </div>

                <button type="submit" class="submit-button">Create User</button>
            </form>
        </div>
    </div>
    <script>
        // Hide success/error messages after 3 seconds
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
