<?php
session_start();
require 'db.php'; // Adjust the path to your db.php file

// Check if the user is logged in and is an administrator
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header('Location: authenticate.php'); // Adjust the path to your authenticate.php file
    exit();
}

// Fetch reservation data for the last 7 days, monthly, and yearly
function getReservationData($conn, $period) {
    $query = "";
    switch($period) {
        case '7days':
            $query = "SELECT r.*, t.name AS tournament_name 
                      FROM reservations r
                      JOIN tournaments t ON r.tournament_id = t.id
                      WHERE r.reservation_date >= CURDATE() - INTERVAL 7 DAY";
            break;
        case 'monthly':
            $query = "SELECT r.*, t.name AS tournament_name 
                      FROM reservations r
                      JOIN tournaments t ON r.tournament_id = t.id
                      WHERE MONTH(r.reservation_date) = MONTH(CURDATE()) AND YEAR(r.reservation_date) = YEAR(CURDATE())";
            break;
        case 'yearly':
            $query = "SELECT r.*, t.name AS tournament_name 
                      FROM reservations r
                      JOIN tournaments t ON r.tournament_id = t.id
                      WHERE YEAR(r.reservation_date) = YEAR(CURDATE())";
            break;
    }

    $result = $conn->query($query);
    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

$period = isset($_GET['period']) ? $_GET['period'] : '7days';
$reservationData = getReservationData($conn, $period);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Report - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/report.css"> <!-- Adjust the path as necessary -->
    <link rel="stylesheet" href="../css/sidebar.css"> <!-- Link to the sidebar-specific CSS file -->
    <link rel="stylesheet" href="../css/reservation_report.css"> <!-- Link to the new reservation report-specific CSS file -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script> <!-- Font Awesome for icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"> <!-- Professional font -->
    <script src="https://code.iconify.design/2/2.0.3/iconify.min.js"></script> <!-- Include Iconify for icons -->
</head>
<body>
    <div class="container">
        <div class="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="main-content-container">
            <div class="main-content">
                <h2>Reservation Report</h2>
                <div class="filter-buttons">
                    <button onclick="window.location.href='?period=7days'">Last 7 Days</button>
                    <button onclick="window.location.href='?period=monthly'">Monthly</button>
                    <button onclick="window.location.href='?period=yearly'">Yearly</button>
                </div>
                <div id="report-content">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Tournament Name</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>PS5 (pcs)</th>
                                <th>Reservation Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Duration (hours)</th>
                                <th>Total Price (RM)</th>
                                <th>Status</th>
                                <th>Admin Comments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservationData as $reservation) : ?>
                                <tr>
                                    <td><?php echo $reservation['tournament_name']; ?></td>
                                    <td><?php echo $reservation['full_name']; ?></td>
                                    <td><?php echo $reservation['email']; ?></td>
                                    <td><?php echo $reservation['phone_number']; ?></td>
                                    <td><?php echo $reservation['ps5_count']; ?> pcs</td>
                                    <td><?php echo $reservation['reservation_date']; ?></td>
                                    <td><?php echo $reservation['start_time']; ?></td>
                                    <td><?php echo $reservation['end_time']; ?></td>
                                    <td><?php echo $reservation['duration']; ?> hour<?php echo $reservation['duration'] > 1 ? 's' : ''; ?></td>
                                    <td>RM <?php echo $reservation['total_price']; ?></td>
                                    <td><?php echo $reservation['status']; ?></td>
                                    <td><?php echo $reservation['admin_comments']; ?></td>
                                    <td>
                                        <button onclick="printReservation(<?php echo $reservation['id']; ?>)" class="print-btn">
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
        const reservations = <?php echo json_encode($reservationData); ?>;

        function printReservation(id) {
            const reservation = reservations.find(r => r.id == id);
            if (!reservation) return;

            const printContent = `
                <div class="print-area">
                    <div class="print-header">
                        <img src="../image/Santai Esports Logo.png" alt="Company Logo" class="company-logo">
                        <h1>Santai Esports</h1>
                        <p>No. 7 Tingkat, 1, Jalan Universiti 1, 86400 Parit Raja, Johor</p>
                        <p>Email: thesantaiesports@gmail.com | Phone: 0127735054</p>
                        <h2>Reservation Report</h2>
                    </div>
                    <table class="print-table">
                        <tr><th>Tournament Name</th><td>${reservation.tournament_name}</td></tr>
                        <tr><th>Full Name</th><td>${reservation.full_name}</td></tr>
                        <tr><th>Email</th><td>${reservation.email}</td></tr>
                        <tr><th>Phone Number</th><td>${reservation.phone_number}</td></tr>
                        <tr><th>PS5</th><td>${reservation.ps5_count} pcs</td></tr>
                        <tr><th>Reservation Date</th><td>${reservation.reservation_date}</td></tr>
                        <tr>
                            <th>Time</th>
                            <td>${reservation.start_time} - ${reservation.end_time}</td>
                        </tr>
                        <tr><th>Duration</th><td>${reservation.duration} hour${reservation.duration > 1 ? 's' : ''}</td></tr>
                        <tr><th>Total Price</th><td>RM ${reservation.total_price}</td></tr>
                        <tr><th>Status</th><td>${reservation.status}</td></tr>
                        <tr><th>Admin Comments</th><td>${reservation.admin_comments}</td></tr>
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
