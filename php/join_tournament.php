<?php
include 'header.php';
include 'db.php';
require 'stripe_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: authenticate.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle join tournament form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_id = intval($_POST['tournament_id']);

    // Check if the user is already registered for this tournament
    $check_sql = "SELECT COUNT(*) AS count FROM participants WHERE tournament_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $tournament_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();

    if ($check_row['count'] > 0) {
        $message = "You are already registered for this tournament.";
    } else {
        // Fetch tournament details
        $tournament_sql = "SELECT * FROM tournaments WHERE id = ?";
        $tournament_stmt = $conn->prepare($tournament_sql);
        $tournament_stmt->bind_param("i", $tournament_id);
        $tournament_stmt->execute();
        $tournament_result = $tournament_stmt->get_result();
        $tournament = $tournament_result->fetch_assoc();

        // If the tournament has a fee, process payment
if ($tournament['fee'] === 'paid') {
    $price = intval($tournament['price']) * 100; // Convert to cents
    $YOUR_DOMAIN = 'http://localhost/esports_santai/php';

    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => $tournament['name'],
                    ],
                    'unit_amount' => $price,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/join_tournament.php?success=true&tournament_id=' . $tournament_id . '&user_id=' . $user_id . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $YOUR_DOMAIN . '/join_tournament.php?success=false',
        ]);

        header("Location: " . $session->url);
        exit();
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $message = "Error creating payment session: " . $e->getMessage();
    }
} else {
    // No fee, proceed with joining the tournament
    joinTournament($conn, $tournament_id, $user_id);
}

    }
}

// Handle success and failure after Stripe payment
if (isset($_GET['success']) && isset($_GET['tournament_id']) && isset($_GET['user_id'])) {
    $success = $_GET['success'];
    $tournament_id = intval($_GET['tournament_id']);
    $user_id = intval($_GET['user_id']);

    // Fetch tournament details again
    $tournament_sql = "SELECT * FROM tournaments WHERE id = ?";
    $tournament_stmt = $conn->prepare($tournament_sql);
    $tournament_stmt->bind_param("i", $tournament_id);
    $tournament_stmt->execute();
    $tournament_result = $tournament_stmt->get_result();
    $tournament = $tournament_result->fetch_assoc();

    if ($success === 'true' && isset($_GET['session_id'])) {
        $stripe_payment_id = $_GET['session_id'];

        // Check if payment record already exists
        $payment_check_sql = "SELECT COUNT(*) AS count FROM payments WHERE stripe_payment_id = ?";
        $payment_check_stmt = $conn->prepare($payment_check_sql);
        $payment_check_stmt->bind_param("s", $stripe_payment_id);
        $payment_check_stmt->execute();
        $payment_check_result = $payment_check_stmt->get_result();
        $payment_check_row = $payment_check_result->fetch_assoc();

        if ($payment_check_row['count'] == 0) {
            // Save payment record
            $payment_sql = "INSERT INTO payments (user_id, amount, currency, payment_status, payment_type, reference_id, stripe_payment_id) 
                            VALUES (?, ?, ?, ?, 'tournament', ?, ?)";
            $payment_stmt = $conn->prepare($payment_sql);
            $amount = $tournament['price'];
            $currency = 'myr';
            $status = 'completed';
            $payment_stmt->bind_param("idssis", $user_id, $amount, $currency, $status, $tournament_id, $stripe_payment_id);
            $payment_stmt->execute();

            // Successful payment
            joinTournament($conn, $tournament_id, $user_id);
            $message = "You have successfully joined the tournament.";
        } else {
            $message = "You have already completed the payment for this tournament.";
        }
    } else {
        $message = "Payment failed or was cancelled.";
    }
}

function joinTournament($conn, $tournament_id, $user_id) {
    global $message;

    $slot_sql = "SELECT IFNULL(MAX(slot), 0) + 1 AS next_slot FROM participants WHERE tournament_id = ?";
    $slot_stmt = $conn->prepare($slot_sql);
    $slot_stmt->bind_param("i", $tournament_id);
    $slot_stmt->execute();
    $slot_result = $slot_stmt->get_result();
    $slot_row = $slot_result->fetch_assoc();
    $next_slot = $slot_row['next_slot'];

    $insert_sql = "INSERT INTO participants (tournament_id, user_id, slot) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iii", $tournament_id, $user_id, $next_slot);

    if (!$insert_stmt->execute()) {
        $message = "An error occurred while joining the tournament. Please try again.";
    }
}

$search = '';
$filter_game = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter_game = isset($_GET['filter_game']) ? trim($_GET['filter_game']) : '';
}

$sql = "SELECT t.id, t.name, t.date, t.game, t.venue, t.format, t.participants AS total_participants, t.poster, 
               t.fee, t.price,
               (SELECT COUNT(*) FROM participants p WHERE p.tournament_id = t.id) AS registered_participants 
        FROM tournaments t WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (t.name LIKE ? OR t.venue LIKE ?)";
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

if (!empty($filter_game)) {
    $sql .= " AND t.game = ?";
    $params[] = $filter_game;
    $types .= 's';
}

$sql .= " ORDER BY t.date ASC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$tournaments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tournaments[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Tournament - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/join_tournament.css">
</head>

<body>
    <main>
<div class="container">
    <h2>Join a Tournament</h2>
    <?php if (!empty($message)): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="get" action="join_tournament.php" class="search-form">
        <input type="text" name="search" placeholder="Search tournaments..." value="<?php echo htmlspecialchars($search); ?>" oninput="this.form.submit()">
        <select name="filter_game" onchange="this.form.submit()">
            <option value="">Filter by game</option>
            <option value="eafc24" <?php echo $filter_game == 'eafc24' ? 'selected' : ''; ?>>EA FC 24</option>
            <option value="tekken8" <?php echo $filter_game == 'tekken8' ? 'selected' : ''; ?>>Tekken 8</option>
            <option value="efootball" <?php echo $filter_game == 'efootball' ? 'selected' : ''; ?>>eFootball</option>
        </select>
    </form>
    <div class="tournament-cards">
        <?php foreach ($tournaments as $tournament): ?>
            <div class="tournament-card">
                <img src="<?php echo htmlspecialchars($tournament['poster']); ?>" alt="Poster" class="tournament-poster">
                <div class="tournament-details">
                    <span class="tournament-date"><?php echo date('F j, Y', strtotime($tournament['date'])); ?></span>
                    <h3><?php echo htmlspecialchars($tournament['name']); ?></h3>
                    <p><?php echo htmlspecialchars($tournament['game']); ?></p>
                    <p><?php echo htmlspecialchars($tournament['venue']); ?></p>
                    <p>Format: <?php echo htmlspecialchars($tournament['format']); ?></p>
                    <p>Slots: <?php echo htmlspecialchars($tournament['registered_participants']) . '/' . htmlspecialchars($tournament['total_participants']); ?></p>
                    <?php if ($tournament['fee'] === 'paid'): ?>
                        <p>Fee: RM <?php echo htmlspecialchars(number_format($tournament['price'], 2)); ?></p>
                    <?php endif; ?>
                    <?php
                    $current_date = date('Y-m-d');
                    $tournament_date = date('Y-m-d', strtotime($tournament['date']));
                    if ($tournament_date >= $current_date && $tournament['registered_participants'] < $tournament['total_participants']): ?>
                        <form action="join_tournament.php" method="post">
                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                            <button type="submit">Join Tournament</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

    </main>
</body>

</html>
