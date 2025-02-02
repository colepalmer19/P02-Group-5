<?php
$servername = "localhost"; // Use your database server (default is localhost for XAMPP)
$username = "root";        // Default XAMPP username
$password = "";            // Default XAMPP password (empty for default setup)
$dbname = "amc_system";    // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
