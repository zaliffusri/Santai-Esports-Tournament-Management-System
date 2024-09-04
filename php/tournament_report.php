<?php
session_start();
require 'db.php'; // Adjust the path to your db.php file

// Check if the user is logged in and fetch user details
if (!isset($_SESSION['user_id'])) {
    header('Location: authenticate.php'); // Adjust the path to your authenticate.php file
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'participant'; // Default to 'participant' if not set

// Function to get tournament data for the last 7 days, monthly, and yearly
function getTournamentData($conn, $period, $user_id = null, $user_role = 'participant') {
    $query = "";
    $whereClause = "";
    
    if ($user_role === 'organizer') {
        $whereClause = "AND t.user_id = $user_id";
    }

    switch($period) {
        case '7days':
            $query = "SELECT t.*, COALESCE(COUNT(p.id), 0) AS participant_count
                      FROM tournaments t
                      LEFT JOIN participants p ON t.id = p.tournament_id
                      WHERE t.date >= CURDATE() - INTERVAL 7 DAY $whereClause
                      GROUP BY t.id";
            break;
        case 'monthly':
            $query = "SELECT t.*, COALESCE(COUNT(p.id), 0) AS participant_count
                      FROM tournaments t
                      LEFT JOIN participants p ON t.id = p.tournament_id
                      WHERE MONTH(t.date) = MONTH(CURDATE()) AND YEAR(t.date) = YEAR(CURDATE()) $whereClause
                      GROUP BY t.id";
            break;
        case 'yearly':
            $query = "SELECT t.*, COALESCE(COUNT(p.id), 0) AS participant_count
                      FROM tournaments t
                      LEFT JOIN participants p ON t.id = p.tournament_id
                      WHERE YEAR(t.date) = YEAR(CURDATE()) $whereClause
                      GROUP BY t.id";
            break;
    }

    $result = $conn->query($query);
    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $fee = floatval($row['fee']);
            $participant_count = intval($row['participant_count']);
            $row['total_price'] = $fee * $participant_count;
            $data[] = $row;
        }
    }
    return $data;
}

$period = isset($_GET['period']) ? $_GET['period'] : '7days';
$tournamentData = getTournamentData($conn, $period, $user_id, $user_role);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Report - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/tournament_report.css"> <!-- Adjust the path as necessary -->
    <link rel="stylesheet" href="../css/sidebar.css"> <!-- Link to the sidebar-specific CSS file -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script> <!-- Font Awesome for icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"> <!-- Professional font -->
    <script src="https://code.iconify.design/2/2.0.3/iconify.min.js"></script> <!-- Include Iconify for icons -->
</head>
<body>
    <div class="main-container">
        <?php if ($user_role == 'organizer') : ?>
            <?php include 'header.php'; ?>
        <?php else : ?>
            <div class="sidebar-container">
                <?php include 'sidebar.php'; ?>
            </div>
        <?php endif; ?>
        <div class="content-container">
            <div class="main-content">
                <h2>Tournament Report</h2>
                <div class="filter-buttons">
                    <button onclick="window.location.href='?period=7days'">Last 7 Days</button>
                    <button onclick="window.location.href='?period=monthly'">Monthly</button>
                    <button onclick="window.location.href='?period=yearly'">Yearly</button>
                </div>
                <div id="report-content">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Game</th>
                                <th>Venue</th>
                                <th>Participants</th>
                                <th>Fee (RM)</th>
                                <th>Total Price (RM)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tournamentData as $tournament) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tournament['name']); ?></td>
                                    <td><?php echo htmlspecialchars($tournament['date']); ?></td>
                                    <td><?php echo htmlspecialchars($tournament['game']); ?></td>
                                    <td><?php echo htmlspecialchars($tournament['venue']); ?></td>
                                    <td><?php echo htmlspecialchars($tournament['participant_count']); ?></td>
                                    <td><?php echo htmlspecialchars($tournament['fee']); ?></td>
                                    <td><?php echo htmlspecialchars($tournament['total_price']); ?></td>
                                    <td>
                                        <button onclick="printTournament(<?php echo htmlspecialchars($tournament['id']); ?>)" class="print-btn">
                                            <span class="iconify" data-icon="mdi:printer"></span> Print
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const tournaments = <?php echo json_encode($tournamentData); ?>;

        function printTournament(id) {
            const tournament = tournaments.find(t => t.id == id);
            if (!tournament) return;

            const printContent = `
                <div class="print-area">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="../image/Santai Esports Logo.png" alt="Company Logo" style="max-width: 150px; margin-bottom: 10px;">
                        <h1>Santai Esports</h1>
                        <p>No. 7 Tingkat, 1, Jalan Universiti 1, 86400 Parit Raja, Johor</p>
                        <p>Email: thesantaiesports@gmail.com | Phone: 0127735054</p>
                        <h2>Tournament Report</h2>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Name</th><td style="padding: 8px; border: 1px solid #ddd;">${tournament.name}</td></tr>
                        <tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Date</th><td style="padding: 8px; border: 1px solid #ddd;">${tournament.date}</td></tr>
                        <tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Game</th><td style="padding: 8px; border: 1px solid #ddd;">${tournament.game}</td></tr>
                        <tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Venue</th><td style="padding: 8px; border: 1px solid #ddd;">${tournament.venue}</td></tr>
                        <tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Participants</th><td style="padding: 8px; border: 1px solid #ddd;">${tournament.participant_count}</td></tr>
                        <tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Fee (RM)</th><td style="padding: 8px; border: 1px solid #ddd;">${tournament.fee}</td></tr>
                        <tr><th style="text-align: left; padding: 8px; background-color: #f2f2f2;">Total Price (RM)</th><td style="padding: 8px; border: 1px solid #ddd;">${tournament.total_price}</td></tr>
                    </table>
                </div>
            `;

            const originalContents = document.body.innerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload(); // Reload the page to restore the original content
        }
    </script>
</body>
</html>
