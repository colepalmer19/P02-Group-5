<?php
require_once '../session_handler.php';
require_once '../dbconnect.php';

// Ensure the user is logged in as Research Assistant
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] !== 'Research Assistant') {
    header('Location: login.php');
    exit();
}

$assistant_id = $_SESSION['session_userid']; // Get logged-in assistant's ID

// Fetch reports assigned to this Research Assistant
$sql = "SELECT r.*, rp.title AS project_title, u.name AS assigned_by
        FROM reports r
        JOIN research_projects rp ON r.project_id = rp.id
        JOIN user u ON r.created_by = u.id
        WHERE r.assigned_to = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assistant_id);
$stmt->execute();
$result = $stmt->get_result();
$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assigned Reports</title>
    <link rel="stylesheet" type="text/css" href="/group/style/style.css">
</head>
<body>
    <div class="top-bar" style="width: 100%; height: 60px; background: #2C3E50; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">
        <div class="logo-title-container" style="display: flex; align-items: center; gap: 10px;">
            <img src="/group/images/research12.png" alt="Logo" class="logo" style="width: 36px; height: auto; margin-left: 30px;">
            <h1 class="main-title" style="color: #FFFFFF; font-size: 30px; font-weight: bold; margin: 0;">AMC Research System</h1>
        </div>
        <a href="/group/assistant_dashboard.php" class="home-btn" style="background: #007bff; color: #FFFFFF; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 16px; font-weight: bold; transition: background 0.3s ease;">Home</a>
    </div>

    <div class="projects-container" style="width: 80%; max-width: 900px; margin: 40px auto; padding: 20px; text-align: center; background-color: #f9f9f9; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);">
        <h2 style="font-size: 24px; color: #2C3E50;">Reports Assigned to You</h2>

        <?php if (empty($reports)): ?>
            <p style="color: #777;">No reports assigned to you yet.</p>
        <?php else: ?>
            <div class="reports-grid" style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
                <?php foreach ($reports as $report): ?>
                    <div class="report-card" style="width: 100%; max-width: 750px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); text-align: left; border-left: 5px solid #2C3E50;">
                        <h3 style="color: #2C3E50;">Project: <?php echo htmlspecialchars($report['project_title']); ?></h3>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                        <p><strong>Assigned By:</strong> <?php echo htmlspecialchars($report['assigned_by']); ?></p>
                        <p><strong>Equipment Used:</strong> <?php echo htmlspecialchars($report['equipment_percentage_used']); ?>%</p>
                        <p><strong>Funding:</strong> $<?php echo htmlspecialchars($report['funding']); ?></p>
                        <p><strong>Progress:</strong> <?php echo nl2br(htmlspecialchars($report['progress'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
