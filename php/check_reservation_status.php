<?php
require 'db.php';
require 'stripe_config.php'; // Include Stripe configuration
session_start(); // Start the session

// Fetch current user's ID from the session
$user_id = $_SESSION['user_id'];

// Handle successful payment
if (isset($_GET['session_id'])) {
    $session_id = $_GET['session_id'];

    try {
        // Retrieve the session from Stripe
        $session = \Stripe\Checkout\Session::retrieve($session_id);

        // Handle the successful payment here
        $reservation_id = $session->metadata->reservation_id;
        $user_id = $session->metadata->user_id;
        $payment_status = 'completed';
        $stripe_payment_id = $session->payment_intent;
        $amount_total = $session->amount_total / 100; // Convert amount from cents to currency unit
        $currency = $session->currency;
        $created_at = date('Y-m-d H:i:s');

        // Check if payment already exists
        $sql_check_payment = "SELECT COUNT(*) as payment_count FROM payments WHERE stripe_payment_id = ?";
        $stmt_check_payment = $conn->prepare($sql_check_payment);
        $stmt_check_payment->bind_param("s", $stripe_payment_id);
        $stmt_check_payment->execute();
        $result_check_payment = $stmt_check_payment->get_result();
        $payment_check = $result_check_payment->fetch_assoc();

        if ($payment_check['payment_count'] == 0) {
            // Insert payment information into your database
            $sql_insert_payment = "INSERT INTO payments (user_id, amount, currency, payment_status, payment_type, reference_id, stripe_payment_id, created_at)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_payment = $conn->prepare($sql_insert_payment);
            $payment_type = 'reservation';
            $stmt_insert_payment->bind_param("idsssiss", $user_id, $amount_total, $currency, $payment_status, $payment_type, $reservation_id, $stripe_payment_id, $created_at);
            $stmt_insert_payment->execute();

            echo "<script>alert('Payment successful! Your reservation has been updated.');</script>";
        } else {
            echo "<script>alert('Payment has already been processed.');</script>";
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reservation_id']) && isset($_POST['total_price'])) {
    $reservation_id = $_POST['reservation_id'];
    $amount = $_POST['total_price'] * 100; // Stripe amount in cents
    $currency = 'myr';

    // Create a new Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => 'Reservation Payment',
                ],
                'unit_amount' => $amount,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost/esports_santai/php/check_reservation_status.php?session_id={CHECKOUT_SESSION_ID}', // Replace with your actual success URL
        'cancel_url' => 'http://localhost/esports_santai/php/check_reservation_status.php', // Replace with your actual cancel URL
        'metadata' => [
            'reservation_id' => $reservation_id,
            'user_id' => $user_id,
        ],
    ]);

    // Redirect to Stripe Checkout
    header("Location: " . $session->url);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Status - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/reservation_studio.css">
</head>

<body>
    <?php require 'header.php'; ?>

    <div class="main-content">
        <main>
            <section id="reservation-status-section" class="section">
                <h2>Your Reservations</h2>

                <!-- Buttons to navigate between reservation form and reservation status -->
                <div class="button-container">
                    <a href="reservation_studio.php"><button id="show-reservation-form">Reservation Form</button></a>
                    <a href="check_reservation_status.php"><button id="show-reservation-status">Check Reservation Status</button></a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Tournament</th>
                            <th>Reservation Date</th>
                            <th>Start Time</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Admin Comments</th>
                            <th>Total Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch reservations for the current user
                        $sql_reservations = "SELECT r.id as reservation_id, t.name as tournament_name, r.reservation_date, r.start_time, r.duration, r.status, r.admin_comments, r.total_price
                                            FROM reservations r
                                            JOIN tournaments t ON r.tournament_id = t.id
                                            WHERE r.user_id = ?";
                        $stmt_reservations = $conn->prepare($sql_reservations);
                        $stmt_reservations->bind_param("i", $user_id);
                        $stmt_reservations->execute();
                        $result_reservations = $stmt_reservations->get_result();

                        if ($result_reservations->num_rows > 0) {
                            while ($reservation = $result_reservations->fetch_assoc()) {
                                $duration = $reservation['duration'];
                                $duration_text = $duration . ' ' . ($duration == 1 ? 'hour' : 'hours');

                                // Check if payment is already made for this reservation
                                $sql_check_payment = "SELECT COUNT(*) as payment_count FROM payments WHERE user_id = ? AND reference_id = ? AND payment_type = 'reservation'";
                                $stmt_check_payment = $conn->prepare($sql_check_payment);
                                $stmt_check_payment->bind_param("ii", $user_id, $reservation['reservation_id']);
                                $stmt_check_payment->execute();
                                $result_check_payment = $stmt_check_payment->get_result();
                                $payment_check = $result_check_payment->fetch_assoc();

                                echo "<tr>
                                        <td>{$reservation['tournament_name']}</td>
                                        <td>{$reservation['reservation_date']}</td>
                                        <td>{$reservation['start_time']}</td>
                                        <td>{$duration_text}</td>
                                        <td>{$reservation['status']}</td>
                                        <td>{$reservation['admin_comments']}</td>
                                        <td>RM " . number_format($reservation['total_price'], 2) . "</td>";
                                
                                if ($reservation['status'] === 'approved') {
                                    if ($payment_check['payment_count'] > 0) {
                                        echo "<td><button type='button' disabled>Paid</button></td>";
                                    } else {
                                        echo "<td>
                                                <form method='POST' action=''>
                                                    <input type='hidden' name='reservation_id' value='{$reservation['reservation_id']}'>
                                                    <input type='hidden' name='total_price' value='{$reservation['total_price']}'>
                                                    <button type='submit'>Payment</button>
                                                </form>
                                              </td>";
                                    }
                                } else {
                                    echo "<td></td>";
                                }
                                
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8'>No reservations found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

</body>

</html>
