<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php'; // Ensures the database connection is established

if (!isset($_SESSION['user_id'])) {
    header('Location: authenticate.php'); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'participant'; // Default to 'participant' if not set

// Fetch user information
$query = $conn->prepare("SELECT username, email, fullname, phone, user_role FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$user_role = $user['user_role']; // Fetch the latest role

// Handle role change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_role'])) {
    $new_role = $_POST['new_role'];
    $updateRoleQuery = $conn->prepare("UPDATE users SET user_role = ? WHERE id = ?");
    $updateRoleQuery->bind_param("si", $new_role, $user_id);
    if ($updateRoleQuery->execute()) {
        $_SESSION['user_role'] = $new_role; // Update the session to reflect the new role
        header("Location: home.php"); // Redirect to home.php after role change
        exit();
    } else {
        echo "Failed to update role: " . $conn->error;
    }
}

// Check if the request is an AJAX request
$is_ajax_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax_request) {
    return; // Stop further execution if it's an AJAX request
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/header.css"> <!-- Include the CSS file here -->
    <title>User Dashboard - Santai Esports</title>
    <!-- Include Iconify for icons -->
    <script src="https://code.iconify.design/2/2.0.3/iconify.min.js"></script>
</head>

<body>
<header>
    <nav>
        <ul>
            <li><a href="home.php"><span class="iconify" data-icon="mdi:home"></span>Home</a></li>
            <?php if ($user_role == 'organizer') : ?>
                <li><a href="manage_tournament.php"><span class="iconify" data-icon="mdi:gamepad-variant"></span>Manage Tournaments</a></li>
                <li><a href="reservation_studio.php"><span class="iconify" data-icon="mdi:calendar"></span>Reservation Studio</a></li>
                <li><a href="tournament_report.php"><span class="iconify" data-icon="mdi:chart-bar"></span>Tournament Report</a></li> <!-- Added Tournament Report link for organizer -->
            <?php else : ?>
                <li><a href="join_tournament.php"><span class="iconify" data-icon="mdi:gamepad-variant"></span>Join Tournament</a></li>
                <li><a href="tournament_history.php"><span class="iconify" data-icon="mdi:history"></span>Tournament History</a></li>
            <?php endif; ?>
            <li><a href="profile.php"><span class="iconify" data-icon="mdi:account"></span>Profile</a></li>
            <li>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="roleChangeForm">
                    <select name="new_role" id="roleSelect" onchange="document.getElementById('roleChangeForm').submit();" aria-expanded="false">
                        <option value="participant" <?php echo $user_role === 'participant' ? 'selected' : ''; ?>>Participant</option>
                        <option value="organizer" <?php echo $user_role === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                    </select>
                </form>
            </li>
            <li><a href="logout.php" onclick="return confirmLogout()"><span class="iconify" data-icon="mdi:logout"></span>Logout</a></li>
        </ul>
    </nav>
</header>

<script>
    function confirmLogout() {
        return confirm('Are you sure you want to log out?');
    }

    const roleDropdown = document.getElementById('roleSelect');
    const roleForm = document.getElementById('roleChangeForm');

    roleDropdown.addEventListener('click', function () {
        const expanded = roleForm.getAttribute('aria-expanded') === 'true';
        roleForm.setAttribute('aria-expanded', !expanded);
    });
</script>
</body>

</html>
