<?php
require 'stripe_config.php';
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: authenticate.php");
    exit();
}

$sql = "SELECT user_role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_role = $user['user_role'] ?? 'participant';

$tournament_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tournament_format = isset($_GET['format']) ? htmlspecialchars($_GET['format']) : 'single_elimination';

$sql = "SELECT name FROM tournaments WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$result = $stmt->get_result();
$tournament = $result->fetch_assoc();
$tournament_name = $tournament['name'] ?? 'Tournament';

$sql = "SELECT p.user_id, u.username 
        FROM participants p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.tournament_id = ?
        ORDER BY p.slot";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$result = $stmt->get_result();

$participants = [];
$participants_ids = [];
while ($row = $result->fetch_assoc()) {
    $participants[] = htmlspecialchars($row['username']);
    $participants_ids[] = intval($row['user_id']);
}

if (count($participants) % 2 != 0) {
    $participants[] = 'Bye';
    $participants_ids[] = null;
}

$sql = "SELECT COUNT(*) as match_count FROM matches WHERE tournament_id = ? AND round = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$match_count = $row['match_count'] ?? 0;

function insertMatches($conn, $tournament_id, $round, $matches) {
    foreach ($matches as $match) {
        $sql = "INSERT INTO matches (tournament_id, round, match_number, participant1_id, participant2_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiii", $tournament_id, $round, $match['match_number'], $match['participant1_id'], $match['participant2_id']);
        $stmt->execute();
    }
}

if ($match_count == 0) {
    $numParticipants = count($participants_ids);
    $round = 0;

    $initialMatches = [];
    for ($i = 0; $i < $numParticipants; $i += 2) {
        $initialMatches[] = [
            'match_number' => $i / 2,
            'participant1_id' => $participants_ids[$i],
            'participant2_id' => $participants_ids[$i + 1]
        ];
    }
    insertMatches($conn, $tournament_id, $round, $initialMatches);

    while ($numParticipants > 1) {
        $round++;
        $numParticipants = ceil($numParticipants / 2);
        if ($numParticipants > 1) {
            $subsequentMatches = [];
            for ($i = 0; $i < $numParticipants; $i += 2) {
                $subsequentMatches[] = [
                    'match_number' => $i / 2,
                    'participant1_id' => null,
                    'participant2_id' => null
                ];
            }
            insertMatches($conn, $tournament_id, $round, $subsequentMatches);
        }
    }
}

$matchData = [];
$sql = "SELECT * FROM matches WHERE tournament_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $matchData[$row['round']][$row['match_number']] = $row;
}

function generateSingleEliminationBracket($conn, $matchData, $tournament_id) {
    $bracket = [];
    $numRounds = count($matchData);

    for ($roundIndex = 0; $roundIndex < $numRounds; $roundIndex++) {
        $round = [];
        foreach ($matchData[$roundIndex] as $match) {
            $matchNumber = $match['match_number'];
            $participant1_id = $match['participant1_id'];
            $participant2_id = $match['participant2_id'];

            if ($roundIndex == 0) {
                $participant1_name = $participant1_id ? getUsernameById($conn, $participant1_id) : 'BYE';
                $participant2_name = $participant2_id ? getUsernameById($conn, $participant2_id) : 'BYE';
            } else {
                $participant1_name = $participant1_id ? getUsernameById($conn, $participant1_id) : 'Winner of...';
                $participant2_name = $participant2_id ? getUsernameById($conn, $participant2_id) : 'Winner of...';
                
                if ($match['winner_id'] !== null) {
                    if ($match['participant1_id'] === $match['winner_id']) {
                        $participant1_name = getUsernameById($conn, $match['winner_id']);
                    }
                    if ($match['participant2_id'] === $match['winner_id']) {
                        $participant2_name = getUsernameById($conn, $match['winner_id']);
                    }
                }
            }

            $matchDetails = [
                'match' => [$participant1_name, $participant2_name],
                'match_ids' => [$participant1_id, $participant2_id],
                'score1' => $match['participant1_score'],
                'score2' => $match['participant2_score'],
                'winner' => $match['winner_id']
            ];
            $round[] = $matchDetails;
        }
        $bracket[] = $round;
    }

    return $bracket;
}

function getUsernameById($conn, $user_id) {
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    return $user ? htmlspecialchars($user['username']) : 'Winner of...';
}

function generateRankings($conn, $tournament_id) {
    $rankings = [];
    $ranked_ids = [];

    $sql = "SELECT m.winner_id, u.username, m.round 
            FROM matches m 
            JOIN users u ON m.winner_id = u.id 
            WHERE m.tournament_id = ?
            ORDER BY m.round DESC, m.match_number ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (!in_array($row['winner_id'], $ranked_ids)) {
            $rankings[] = ['username' => htmlspecialchars($row['username']), 'user_id' => $row['winner_id']];
            $ranked_ids[] = $row['winner_id'];
        }
    }

    $sql = "SELECT u.id, u.username 
            FROM participants p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.tournament_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (!in_array($row['id'], $ranked_ids)) {
            $rankings[] = ['username' => htmlspecialchars($row['username']), 'user_id' => $row['id']];
        }
    }

    return $rankings;
}

function saveRankings($conn, $tournament_id, $rankings) {
    $sql = "DELETE FROM rankings WHERE tournament_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();

    $rank = 1;
    foreach ($rankings as $ranking) {
        $sql = "INSERT INTO rankings (tournament_id, user_id, rank) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $tournament_id, $ranking['user_id'], $rank);
        $stmt->execute();
        $rank++;
    }
}

function getPrizeAmount($conn, $participant_id, $tournament_id) {
    $sql = "SELECT SUM(amount) as total_prize FROM payments WHERE reference_id = ? AND tournament_id = ? AND payment_type = 'prize'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $participant_id, $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total_prize'] ?? 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['amount']) && isset($_POST['description'])) {
    $recipient_user_id = $_POST['user_id'];
    $amount = floatval($_POST['amount']) * 100; // Stripe amount in cents
    $currency = 'myr';
    $description = htmlspecialchars($_POST['description']);

    // Create a new Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => $description,
                ],
                'unit_amount' => $amount,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost/esports_santai/php/bracket.php?session_id={CHECKOUT_SESSION_ID}&tournament_id=' . $tournament_id,
        'cancel_url' => 'http://localhost/esports_santai/php/bracket.php?id=' . $tournament_id,
        'metadata' => [
            'user_id' => $recipient_user_id,
            'description' => $description,
            'tournament_id' => $tournament_id
        ],
    ]);

    // Redirect to Stripe Checkout
    header("Location: " . $session->url);
    exit();
}

if (isset($_GET['session_id'])) {
    $session_id = $_GET['session_id'];
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    $payment_intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);

    $user_id = $_SESSION['user_id']; // Organizer ID
    $amount_total = $payment_intent->amount_received / 100; // Amount in the payment table
    $currency = $payment_intent->currency;
    $payment_status = $payment_intent->status;
    $payment_type = 'prize';
    $stripe_payment_id = $payment_intent->id;
    $reference_id = $session->metadata->user_id; // Participant ID
    $tournament_id = $session->metadata->tournament_id; // Tournament ID
    $created_at = date('Y-m-d H:i:s');

    $sql_insert_payment = "INSERT INTO payments (user_id, amount, currency, payment_status, payment_type, reference_id, stripe_payment_id, tournament_id, created_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_payment = $conn->prepare($sql_insert_payment);
    $stmt_insert_payment->bind_param("idsssissi", $user_id, $amount_total, $currency, $payment_status, $payment_type, $reference_id, $stripe_payment_id, $tournament_id, $created_at);
    if ($stmt_insert_payment->execute()) {
        echo "<script>alert('Payment recorded successfully!');</script>";
    } else {
        echo "<script>alert('Failed to record payment.');</script>";
    }
    $stmt_insert_payment->close();
    header("Location: bracket.php?id=" . $_GET['tournament_id']);
    exit();
}

$bracket = generateSingleEliminationBracket($conn, $matchData, $tournament_id);

$rankings = generateRankings($conn, $tournament_id);

saveRankings($conn, $tournament_id, $rankings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <title><?php echo htmlspecialchars($tournament_name); ?></title>
    <link rel="stylesheet" href="../css/bracket.css">
    <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
    <style>
        .advance-btn {
            margin-top: 20px;
        }
        .disabled {
            pointer-events: none;
            opacity: 0.5;
        }
        /* Rankings Section */
        .rankings {
            margin-top: 20px;
            padding: 20px;
            background-color: #2c2c2c; /* Dark background */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .rankings h3 {
            color: #ffffff; /* White text color */
            margin-bottom: 10px;
            text-align: center;
            font-size: 24px;
        }

        .rankings ol {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .rankings ol li {
            background-color: #3d3d3d; /* Slightly lighter background */
            color: #ffffff; /* White text color */
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 4px;
            font-size: 18px;
            display: flex;
            justify-content: space-between; /* Space between text and button */
            align-items: center;
        }

        .rankings ol li:nth-child(odd) {
            background-color: #4a4a4a; /* Alternate row color */
        }

        .rankings ol li:before {
            content: "🏆"; /* Trophy emoji for each rank */
            margin-right: 10px;
            font-size: 18px;
        }

        .rankings form input[type="number"] {
            width: 80px;
            margin-right: 10px;
            padding: 5px;
            border: none;
            border-radius: 4px;
        }

        .rankings form button {
            padding: 5px 10px;
            background-color: #00b894;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .rankings form button:hover {
            background-color: #019474;
        }
    </style>
    <script>
        function goBack(userRole) {
            if (userRole === 'participant') {
                window.location.href = 'tournament_history.php';
            } else if (userRole === 'organizer') {
                window.location.href = 'manage_tournament.php';
            }
        }
    </script>
</head>
<body>
    <main>
        <div class="header">
            <button class="back-button" onclick="goBack('<?php echo $user_role; ?>')">
                <span class="iconify" data-icon="mdi-arrow-left" data-inline="false"></span> Back
            </button>
            <h2><?php echo htmlspecialchars($tournament_name); ?></h2>
        </div>

        <div class="theme theme-dark">
            <div class="bracket disable-image">
                <?php foreach ($bracket as $roundIndex => $round) { ?>
                    <div class="column">
                        <?php foreach ($round as $matchIndex => $match) { ?>
                            <div class="match winner-<?php echo $matchIndex % 2 == 0 ? 'top' : 'bottom'; ?> <?php echo ($user_role === 'participant') ? 'disabled' : ''; ?>" id="match-<?php echo $roundIndex . '-' . $matchIndex; ?>" <?php if ($user_role === 'organizer') echo 'onclick="location.href=\'match.php?tournament_id=' . $tournament_id . '&round=' . $roundIndex . '&match_number=' . $matchIndex . '\'"'; ?>>
                                <div class="match-top team">
                                    <span class="image"></span>
                                    <span class="seed"><?php echo $matchIndex * 2 + 1; ?></span>
                                    <span class="name"><?php echo htmlspecialchars($match['match'][0]); ?></span>
                                    <span class="score"><?php echo $match['score1']; ?></span>
                                </div>
                                <div class="match-bottom team">
                                    <span class="image"></span>
                                    <span class="seed"><?php echo $matchIndex * 2 + 2; ?></span>
                                    <span class="name"><?php echo htmlspecialchars($match['match'][1]); ?></span>
                                    <span class="score"><?php echo $match['score2']; ?></span>
                                </div>
                                <div class="match-lines">
                                    <div class="line one"></div>
                                    <div class="line two"></div>
                                </div>
                                <div class="match-lines alt">
                                    <div class="line one"></div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <div class="rankings">
                <h3>Rankings</h3>
                <ol>
                    <?php 
                    $titles = ['Champion', 'Runner-Up', '3rd Place', '4th Place'];
                    foreach ($rankings as $index => $ranking) { 
                        $title = $titles[$index] ?? ($index + 1) . 'th Place';
                        $prize_amount = getPrizeAmount($conn, $ranking['user_id'], $tournament_id);
                    ?>
                        <li>
                            <span><?php echo $title . ': ' . $ranking['username']; ?></span>
                            <?php if ($user_role === 'organizer') { ?>
                                <?php if ($prize_amount > 0) { ?>
                                    <span>RM<?php echo number_format($prize_amount, 2); ?></span>
                                <?php } else { ?>
                                    <form method="post" action="">
                                        <input type="hidden" name="user_id" value="<?php echo $ranking['user_id']; ?>">
                                        <input type="number" name="amount" step="0.01" min="0" required>
                                        <input type="hidden" name="description" value="Prize for <?php echo $title; ?>">
                                        <button type="submit">Pay</button>
                                    </form>
                                <?php } ?>
                            <?php } else { ?>
                                <span>RM<?php echo number_format($prize_amount, 2); ?></span>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ol>
            </div>
        </div>
    </main>
</body>
</html>
