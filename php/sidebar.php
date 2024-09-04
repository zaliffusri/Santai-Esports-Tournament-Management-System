<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php'; // Adjust the path to your db.php file

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header('Location: authenticate.php'); // Adjust the path to your authenticate.php file
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/sidebar.css"> <!-- Link to the sidebar-specific CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> <!-- Font Awesome for icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"> <!-- Professional font -->
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../image/Santai Esports Logo.png" alt="Santai Esports Logo"> <!-- Adjust the path to your logo file -->
            <h3>Santai Esports</h3>
        </div>

        <a href="admin_dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <a href="manage_reservation.php">
            <i class="fas fa-calendar-check"></i> Manage Reservation
        </a>

        <a href="manage_news.php">
            <i class="fas fa-edit"></i> Manage News
        </a>

        <button class="dropdown-btn" onclick="toggleDropdown('reports-menu')">
            <i class="fas fa-file-alt"></i> Reports <i class="fas fa-caret-down dropdown-icon"></i>
        </button>
        <div class="dropdown-container" id="reports-menu">
            <a href="reservation_report.php">Reservation Report</a>
            <a href="tournament_report.php">Tournament Report</a>
        </div>

        <a href="logout.php" onclick="return confirmLogout()">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <script>
        function toggleDropdown(menuId) {
            var dropdown = document.getElementById(menuId);
            var icon = document.querySelector(`button[onclick="toggleDropdown('${menuId}')"] .dropdown-icon`);

            dropdown.classList.toggle('active');
            icon.classList.toggle('fa-caret-up');
            icon.classList.toggle('fa-caret-down');
        }

        function confirmLogout() {
            return confirm('Are you sure you want to log out?');
        }
    </script>
</body>
</html>
