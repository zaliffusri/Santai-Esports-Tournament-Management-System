<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header('Location: authenticate.php'); // Adjust the path to your authenticate.php file
    exit();
}

require 'db.php'; // Include the database connection

// Function to get user statistics
function getUserStatistics($conn, $period) {
    $query = "";
    switch($period) {
        case 'week':
            $query = "SELECT DATE(created_at) AS date, COUNT(*) AS user_count FROM users WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC";
            break;
        case 'month':
            $query = "SELECT DATE(created_at) AS date, COUNT(*) AS user_count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC";
            break;
        case 'year':
            $query = "SELECT MONTH(created_at) AS month, COUNT(*) AS user_count FROM users WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at) ORDER BY MONTH(created_at)";
            break;
    }

    $result = $conn->query($query);
    $data = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function getAllUsers($conn) {
    $query = "SELECT username, email, fullname, phone, user_role FROM users ORDER BY id ASC";
    $result = $conn->query($query);
    $data = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Function to get reservation statistics
function getReservationStatistics($conn, $period) {
    $query = "";
    switch($period) {
        case 'week':
            $query = "SELECT DATE(reservation_date) AS date, SUM(ps5_count) AS ps5_count FROM reservations WHERE reservation_date >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(reservation_date) ORDER BY DATE(reservation_date) ASC";
            break;
        case 'month':
            $query = "SELECT DATE(reservation_date) AS date, SUM(ps5_count) AS ps5_count FROM reservations WHERE MONTH(reservation_date) = MONTH(CURDATE()) AND YEAR(reservation_date) = YEAR(CURDATE()) GROUP BY DATE(reservation_date) ORDER BY DATE(reservation_date) ASC";
            break;
    }

    $result = $conn->query($query);
    $data = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Function to get tournament statistics
function getTournamentStatistics($conn, $period) {
    $query = "";
    switch($period) {
        case 'week':
            $query = "SELECT game, COUNT(*) AS tournament_count FROM tournaments WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY game";
            break;
        case 'month':
            $query = "SELECT game, COUNT(*) AS tournament_count FROM tournaments WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) GROUP BY game";
            break;
        case 'year':
            $query = "SELECT game, COUNT(*) AS tournament_count FROM tournaments WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY game";
            break;
    }

    $result = $conn->query($query);
    $data = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Function to get a full list of dates for the current month
function getCurrentMonthDates() {
    $dates = [];
    $now = new DateTime();
    $currentMonth = $now->format('m');
    $currentYear = $now->format('Y');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
    
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $date = new DateTime("$currentYear-$currentMonth-$i");
        $dates[] = $date->format('Y-m-d');
    }
    
    return $dates;
}

$data_week = getUserStatistics($conn, 'week');
$data_month = getUserStatistics($conn, 'month');
$data_year = getUserStatistics($conn, 'year');
$all_users = getAllUsers($conn);
$reservation_statistics_month = getReservationStatistics($conn, 'month');
$reservation_statistics_week = getReservationStatistics($conn, 'week');
$tournament_statistics_week = getTournamentStatistics($conn, 'week');
$tournament_statistics_month = getTournamentStatistics($conn, 'month');
$tournament_statistics_year = getTournamentStatistics($conn, 'year');
$month_dates = getCurrentMonthDates();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/admin.css"> <!-- Adjust the path as necessary -->
    <link rel="stylesheet" href="../css/sidebar.css"> <!-- Link to the sidebar-specific CSS file -->
    <link rel="stylesheet" href="../css/admin_dashboard.css"> <!-- Link to the new admin dashboard-specific CSS file -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script> <!-- Font Awesome for icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"> <!-- Professional font -->
</head>
<body>
    <div class="container">
        <div class="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="main-content-container">
            <div class="main-content">
                <h2>Dashboard Overview</h2>
                <div class="chart-section">
                    <h3>User Statistics</h3>
                    <div class="filter-buttons">
                        <button onclick="updateChart(userChart, 'week')">Last 7 Days</button>
                        <button onclick="updateChart(userChart, 'month')">Current Month</button>
                        <button onclick="updateChart(userChart, 'year')">Current Year</button>
                        <button onclick="showUserTable()">View Users</button>
                    </div>
                    <div id="chartContainer">
                        <canvas id="userChart"></canvas>
                    </div>
                    <div id="userTable" style="display:none;">
                        <h3>User Table</h3>
                        <div class="filter-buttons">
                            <label for="roleFilter">Filter by User Role:</label>
                            <select id="roleFilter" onchange="filterUsersByRole()">
                                <option value="all">All</option>
                                <option value="administrator">Administrator</option>
                                <option value="organizer">Organizer</option>
                                <option value="participant">Participant</option>
                            </select>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Fullname</th>
                                    <th>Phone No</th>
                                    <th>User Role</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                                <?php foreach ($all_users as $user) : ?>
                                    <tr>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td><?php echo $user['fullname']; ?></td>
                                        <td><?php echo $user['phone']; ?></td>
                                        <td><?php echo $user['user_role']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="chart-section">
                    <h3>Reservation Statistics</h3>
                    <div class="filter-buttons">
                        <button onclick="updateReservationChart('week')">Last 7 Days</button>
                        <button onclick="updateReservationChart('month')">Current Month</button>
                    </div>
                    <div id="reservationChartContainer">
                        <canvas id="reservationChart"></canvas>
                    </div>
                </div>

                <div class="chart-section">
                    <h3>Tournament Statistics</h3>
                    <div class="filter-buttons">
                        <button onclick="updateTournamentChart('week')">Last 7 Days</button>
                        <button onclick="updateTournamentChart('month')">Current Month</button>
                        <button onclick="updateTournamentChart('year')">Current Year</button>
                    </div>
                    <div id="tournamentChartContainer">
                        <canvas id="tournamentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const data_week = <?php echo json_encode($data_week); ?>;
        const data_month = <?php echo json_encode($data_month); ?>;
        const data_year = <?php echo json_encode($data_year); ?>;
        const allUsers = <?php echo json_encode($all_users); ?>;
        const reservationStatisticsWeek = <?php echo json_encode($reservation_statistics_week); ?>;
        const reservationStatisticsMonth = <?php echo json_encode($reservation_statistics_month); ?>;
        const tournamentStatisticsWeek = <?php echo json_encode($tournament_statistics_week); ?>;
        const tournamentStatisticsMonth = <?php echo json_encode($tournament_statistics_month); ?>;
        const tournamentStatisticsYear = <?php echo json_encode($tournament_statistics_year); ?>;
        const monthDates = <?php echo json_encode($month_dates); ?>;

        function transformData(data, period) {
            let labels = [];
            let values = [];
            const now = new Date();

            if (period === 'year') {
                labels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                values = new Array(12).fill(0);
                data.forEach(item => {
                    values[item.month - 1] = item.user_count;
                });
            } else if (period === 'month') {
                const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
                labels = Array.from({ length: daysInMonth }, (_, i) => {
                    const date = new Date(now.getFullYear(), now.getMonth(), i + 1);
                    return date.toLocaleDateString('en-GB').replace(/\//g, '-');
                });
                values = new Array(daysInMonth).fill(0);
                data.forEach(item => {
                    const day = new Date(item.date).getDate();
                    values[day - 1] = item.user_count;
                });
            } else if (period === 'week') {
                const daysOfWeek = 7;
                labels = Array.from({ length: daysOfWeek }, (_, i) => {
                    const date = new Date();
                    date.setDate(now.getDate() - (daysOfWeek - 1) + i);
                    return date.toLocaleDateString('en-GB').replace(/\//g, '-');
                });
                values = new Array(daysOfWeek).fill(0);
                data.forEach(item => {
                    const index = labels.indexOf(item.date.split('-').reverse().join('-'));
                    if (index !== -1) {
                        values[index] = item.user_count;
                    }
                });
            }

            return { labels, values };
        }

        function updateChart(chart, period) {
            let data = [];
            if (period === 'week') {
                data = transformData(data_week, period);
            } else if (period === 'month') {
                data = transformData(data_month, period);
            } else if (period === 'year') {
                data = transformData(data_year, period);
            }

            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.update();
            document.getElementById('chartContainer').style.display = 'block';
            document.getElementById('userTable').style.display = 'none';
        }

        function showUserTable() {
            document.getElementById('chartContainer').style.display = 'none';
            document.getElementById('userTable').style.display = 'block';
        }

        function filterUsersByRole() {
            const roleFilter = document.getElementById('roleFilter').value;
            const userTableBody = document.getElementById('userTableBody');
            userTableBody.innerHTML = '';

            const filteredUsers = roleFilter === 'all' ? allUsers : allUsers.filter(user => user.user_role === roleFilter);

            filteredUsers.forEach(user => {
                const row = `<tr>
                                <td>${user.username}</td>
                                <td>${user.email}</td>
                                <td>${user.fullname}</td>
                                <td>${user.phone}</td>
                                <td>${user.user_role}</td>
                            </tr>`;
                userTableBody.innerHTML += row;
            });
        }

        // Chart.js configuration for User Statistics
        const ctxUser = document.getElementById('userChart').getContext('2d');
        const userChart = new Chart(ctxUser, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Users',
                    data: [],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1 // Ensure the y-axis increments by whole numbers
                        }
                    }
                }
            }
        });

        // Process reservation statistics
        function getReservationData(period) {
            let dates, reservationData;

            if (period === 'week') {
                dates = reservationStatisticsWeek.map(stat => stat.date);
                reservationData = reservationStatisticsWeek.map(stat => stat.ps5_count);
            } else if (period === 'month') {
                dates = monthDates;
                reservationData = monthDates.map(date => {
                    const stat = reservationStatisticsMonth.find(stat => stat.date === date);
                    return stat ? stat.ps5_count : 0;
                });
            }

            return { dates, reservationData };
        }

        function updateReservationChart(period) {
            const { dates, reservationData } = getReservationData(period);
            reservationChart.data.labels = dates.map(date => new Date(date).toLocaleDateString('en-GB').replace(/\//g, '-'));
            reservationChart.data.datasets[0].data = reservationData;
            reservationChart.update();
        }

        // Chart.js configuration for Reservation Statistics
        const ctxReservation = document.getElementById('reservationChart').getContext('2d');
        const reservationChart = new Chart(ctxReservation, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Reservations',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1 // Ensure the y-axis increments by whole numbers
                        }
                    }
                }
            }
        });

        // Chart.js configuration for Tournament Statistics
        function getTournamentData(period) {
            let data;
            if (period === 'week') {
                data = tournamentStatisticsWeek;
            } else if (period === 'month') {
                data = tournamentStatisticsMonth;
            } else if (period === 'year') {
                data = tournamentStatisticsYear;
            }

            return data;
        }

        function updateTournamentChart(period) {
            const data = getTournamentData(period);
            tournamentChart.data.labels = data.map(stat => stat.game);
            tournamentChart.data.datasets[0].data = data.map(stat => stat.tournament_count);
            tournamentChart.update();
        }

        const ctxTournament = document.getElementById('tournamentChart').getContext('2d');
        const tournamentChart = new Chart(ctxTournament, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    label: 'Tournaments',
                    data: [],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Initialize charts with default data
        updateChart(userChart, 'week');
        updateReservationChart('month');
        updateTournamentChart('week');
    </script>
</body>
</html>
