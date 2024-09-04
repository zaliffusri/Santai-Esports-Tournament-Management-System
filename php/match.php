<?php
// Include database connection
require 'db.php';

// Fetch tournament ID, round, and match number from the query parameters
$tournament_id = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : 0;
$round = isset($_GET['round']) ? intval($_GET['round']) : 0;
$match_number = isset($_GET['match_number']) ? intval($_GET['match_number']) : 0;

// Fetch match data from the database
$sql = "SELECT m.*, u1.username as participant1_name, u2.username as participant2_name
        FROM matches m
        LEFT JOIN users u1 ON m.participant1_id = u1.id
        LEFT JOIN users u2 ON m.participant2_id = u2.id
        WHERE m.tournament_id = ? AND m.round = ? AND m.match_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $tournament_id, $round, $match_number);
$stmt->execute();
$result = $stmt->get_result();
$match = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score1 = isset($_POST['score1']) ? intval($_POST['score1']) : 0;
    $score2 = isset($_POST['score2']) ? intval($_POST['score2']) : 0;

    // Update match scores and determine the winner
    $sql = "UPDATE matches SET participant1_score = ?, participant2_score = ? WHERE tournament_id = ? AND round = ? AND match_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $score1, $score2, $tournament_id, $round, $match_number);
    $stmt->execute();

    $winner_id = null;
    if ($score1 > $score2) {
        $winner_id = $match['participant1_id'];
    } elseif ($score2 > $score1) {
        $winner_id = $match['participant2_id'];
    }

    $sql = "UPDATE matches SET winner_id = ? WHERE tournament_id = ? AND round = ? AND match_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $winner_id, $tournament_id, $round, $match_number);
    $stmt->execute();

    // Place winner in the next round match
    $next_round = $round + 1;
    $next_match_number = intdiv($match_number, 2);
    $next_match_position = $match_number % 2 == 0 ? 'participant1_id' : 'participant2_id';

    $sql = "UPDATE matches SET $next_match_position = ? WHERE tournament_id = ? AND round = ? AND match_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $winner_id, $tournament_id, $next_round, $next_match_number);
    $stmt->execute();

    // Redirect back to bracket.php
    header("Location: bracket.php?id=$tournament_id&format=single_elimination");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <title>Match Details - Tournament <?php echo $tournament_id; ?></title>
    <link rel="stylesheet" href="../css/match.css">
</head>
<body>
    <main>
        <div class="header">
            <button class="back-button" onclick="window.location.href='bracket.php?id=<?php echo $tournament_id; ?>'">
                <span class="iconify" data-icon="mdi-arrow-left" data-inline="false"></span> Back to Bracket
            </button>
            <h2>Match Details - Tournament <?php echo $tournament_id; ?></h2>
        </div>

        <div class="match-details">
            <h3>Round: <?php echo $round; ?> | Match Number: <?php echo $match_number; ?></h3>
            <form method="POST">
                <div class="participant">
                    <h4>Participant 1: <?php echo htmlspecialchars($match['participant1_name']); ?></h4>
                    <label for="score1">Score:</label>
                    <input type="number" id="score1" name="score1" value="<?php echo $match['participant1_score']; ?>">
                </div>
                <div class="participant">
                    <h4>Participant 2: <?php echo htmlspecialchars($match['participant2_name']); ?></h4>
                    <label for="score2">Score:</label>
                    <input type="number" id="score2" name="score2" value="<?php echo $match['participant2_score']; ?>">
                </div>
                <button type="submit">Update Scores</button>
            </form>
            <div class="match-result">
                <h4>Winner: <?php echo $match['winner_id'] ? htmlspecialchars($match['winner_id'] == $match['participant1_id'] ? $match['participant1_name'] : $match['participant2_name']) : 'TBD'; ?></h4>
            </div>
        </div>
    </main>
</body>
</html>
