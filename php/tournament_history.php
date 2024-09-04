<?php include 'header.php'; ?>
<?php include 'db.php'; ?>

<?php
// Check if a session is already started, if not, start a session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null; // Use null coalescing operator to handle undefined session variable

if (!$user_id) {
    // If user ID is not set, redirect to login or show an error
    header("Location: authenticate.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament History - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/tournament_history.css">
    <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
</head>

<body>
    <div class="main-content">
        <main>
            <section id="history-section" class="section">
                <h2><span class="iconify" data-icon="mdi:history" data-inline="false"></span> Tournament History</h2>
                <div class="search-container">
                    <input type="text" id="searchInput" onkeyup="filterTournaments()" placeholder="Search for tournament names..">
                    <select id="gameFilter" onchange="filterTournaments()">
                        <option value="">All Games</option>
                        <option value="tekken8">Tekken 8</option>
                        <option value="eafc24">EA FC 24</option>
                        <!-- Add more options here if needed -->
                    </select>
                </div>
                <div class="history-container">
                    <?php
                    // Fetch tournament history for the specific user from the database
                    $sql = "SELECT t.id, t.name, t.date, t.game, t.venue, r.rank, t.poster
                            FROM tournaments t
                            JOIN participants p ON t.id = p.tournament_id
                            LEFT JOIN rankings r ON t.id = r.tournament_id AND r.user_id = p.user_id
                            WHERE p.user_id = ?
                            ORDER BY t.date DESC";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 0) {
                        echo '<p>No tournaments joined yet.</p>';
                    } else {
                        echo '<table id="tournamentTable">';
                        echo '<thead><tr><th>Poster</th><th>Tournament Name</th><th>Date</th><th>Game</th><th>Venue</th><th>Result</th><th>Certificate</th></thead>';
                        echo '<tbody>';
                        while ($row = $result->fetch_assoc()) {
                            $resultText = 'Participant'; // Default result text
                            switch ($row['rank']) {
                                case 1:
                                    $resultText = 'Champion';
                                    break;
                                case 2:
                                    $resultText = 'Runner-up';
                                    break;
                                case 3:
                                    $resultText = '3rd Place';
                                    break;
                                case 4:
                                    $resultText = '4th Place';
                                    break;
                                default:
                                    if ($row['rank'] > 4) {
                                        $resultText = $row['rank'] . 'th Place';
                                    }
                                    break;
                            }

                            echo '
                            <tr class="clickable-row" data-name="' . strtolower($row['name']) . '" data-game="' . strtolower($row['game']) . '" data-id="' . $row['id'] . '">
                                <td><img src="' . $row['poster'] . '" alt="' . $row['name'] . ' Poster" class="poster"></td>
                                <td>' . htmlspecialchars($row['name']) . '</td>
                                <td>' . htmlspecialchars($row['date']) . '</td>
                                <td>' . htmlspecialchars($row['game']) . '</td>
                                <td>' . htmlspecialchars($row['venue']) . '</td>
                                <td>' . $resultText . '</td>
                                <td><a href="generate_certificate.php?tournament_id=' . $row['id'] . '" class="btn">Download</a></td>
                            </tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                    }

                    $stmt->close();
                    $conn->close();
                    ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = document.querySelectorAll('.clickable-row');
            rows.forEach(row => {
                row.addEventListener('click', () => {
                    const id = row.getAttribute('data-id');
                    window.location.href = `bracket.php?id=${id}`;
                });
            });
        });

        function filterTournaments() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const gameFilter = document.getElementById('gameFilter').value.toLowerCase();
            const rows = document.querySelectorAll('.clickable-row');

            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const game = row.getAttribute('data-game');

                if ((name.includes(searchInput) || !searchInput) && (game.includes(gameFilter) || !gameFilter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>
