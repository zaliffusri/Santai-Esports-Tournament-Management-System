<?php
session_start();
require 'db.php'; // Adjust the path to your db.php file

// Check if the user is logged in and is an administrator
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header('Location: authenticate.php'); // Adjust the path to your authenticate.php file
    exit();
}

// Process form submission for approving or rejecting a reservation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reservation_id = $_POST['reservation_id'];
    $comments = $_POST['comments'];
    $action = $_POST['action'];

    // Update the reservation status and comments
    if ($action == 'approve') {
        $status = 'approved';
    } elseif ($action == 'reject') {
        $status = 'rejected';
    } else {
        header('Location: manage_reservation.php'); // Redirect back if action is invalid
        exit();
    }

    $sql = "UPDATE reservations SET status = ?, admin_comments = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $status, $comments, $reservation_id);

    if ($stmt->execute()) {
        $message = "Reservation $action successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch reservations from the database
$reservations = [];
$sql_reservations = "SELECT * FROM reservations WHERE status = 'pending'";
$result_reservations = $conn->query($sql_reservations);

if ($result_reservations->num_rows > 0) {
    while ($row = $result_reservations->fetch_assoc()) {
        $reservations[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/manage_reservation.css"> <!-- Adjust the path as necessary -->
    <link rel="stylesheet" href="../css/sidebar.css"> <!-- Link to the sidebar-specific CSS file -->
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
                <h2>Manage Reservations</h2>

                <?php if (isset($message)): ?>
                    <div class="message">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="reservation-list">
                    <table>
                        <thead>
                            <tr>
                                <th><span class="iconify" data-icon="mdi:account"></span> Name</th>
                                <th><span class="iconify" data-icon="mdi:email"></span> Email</th>
                                <th><span class="iconify" data-icon="mdi:phone"></span> Phone</th>
                                <th><span class="iconify" data-icon="mdi:calendar"></span> Date</th>
                                <th><span class="iconify" data-icon="mdi:clock"></span> Start Time</th>
                                <th><span class="iconify" data-icon="mdi:cog"></span> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reservation['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['email']); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['phone_number']); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['reservation_date']); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['start_time']); ?></td>
                                    <td>
                                        <button class="view-details-btn" onclick="showDetails(<?php echo htmlspecialchars(json_encode($reservation)); ?>)">
                                            <span class="iconify" data-icon="mdi:eye"></span> View Details
                                        </button>
                                        <button class="approve-btn" onclick="showApproveForm(<?php echo $reservation['id']; ?>)">
                                            <span class="iconify" data-icon="mdi:check"></span> Approve
                                        </button>
                                        <button class="reject-btn" onclick="showRejectForm(<?php echo $reservation['id']; ?>)">
                                            <span class="iconify" data-icon="mdi:close"></span> Reject
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

    <!-- Modal Structure for Details -->
    <div id="details-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Reservation Details</h3>
            <p><strong>Name:</strong> <span id="modal-name"></span></p>
            <p><strong>Email:</strong> <span id="modal-email"></span></p>
            <p><strong>Phone:</strong> <span id="modal-phone"></span></p>
            <p><strong>Reservation Date:</strong> <span id="modal-date"></span></p>
            <p><strong>Start Time:</strong> <span id="modal-start-time"></span></p>
            <p><strong>Duration:</strong> <span id="modal-duration"></span></p>
            <p><strong>End Time:</strong> <span id="modal-end-time"></span></p>
            <p><strong>Tournament ID:</strong> <span id="modal-tournament-id"></span></p>
            <p><strong>Total Price:</strong> <span id="modal-total-price"></span></p>
            <p><strong>Status:</strong> <span id="modal-status"></span></p>
            <p><strong>Admin Comments:</strong> <span id="modal-comments"></span></p>
        </div>
    </div>

    <!-- Modal Structure for Approve Form -->
    <div id="approve-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Approve Reservation</h3>
            <form id="approve-form" method="POST" action="">
                <input type="hidden" name="reservation_id" id="approve-reservation-id">
                <label for="approve-comments">Comments:</label>
                <textarea id="approve-comments" name="comments" rows="4" cols="50"></textarea>
                <input type="hidden" name="action" value="approve">
                <button type="submit">Approve</button>
            </form>
        </div>
    </div>

    <!-- Modal Structure for Reject Form -->
    <div id="reject-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Reject Reservation</h3>
            <form id="reject-form" method="POST" action="">
                <input type="hidden" name="reservation_id" id="reject-reservation-id">
                <label for="reject-comments">Comments:</label>
                <textarea id="reject-comments" name="comments" rows="4" cols="50"></textarea>
                <input type="hidden" name="action" value="reject">
                <button type="submit">Reject</button>
            </form>
        </div>
    </div>

    <script>
        // Function to show the modal with reservation details
        function showDetails(reservation) {
            document.getElementById('modal-name').innerText = reservation.full_name;
            document.getElementById('modal-email').innerText = reservation.email;
            document.getElementById('modal-phone').innerText = reservation.phone_number;
            document.getElementById('modal-date').innerText = reservation.reservation_date;
            document.getElementById('modal-start-time').innerText = reservation.start_time;
            document.getElementById('modal-duration').innerText = reservation.duration + " hours";
            document.getElementById('modal-end-time').innerText = reservation.end_time;
            document.getElementById('modal-tournament-id').innerText = reservation.tournament_id;
            document.getElementById('modal-total-price').innerText = "RM " + reservation.total_price;
            document.getElementById('modal-status').innerText = reservation.status;
            document.getElementById('modal-comments').innerText = reservation.admin_comments;

            document.getElementById('details-modal').style.display = 'block';
        }

        // Function to show the approve form
        function showApproveForm(reservationId) {
            document.getElementById('approve-reservation-id').value = reservationId;
            document.getElementById('approve-modal').style.display = 'block';
        }

        // Function to show the reject form
        function showRejectForm(reservationId) {
            document.getElementById('reject-reservation-id').value = reservationId;
            document.getElementById('reject-modal').style.display = 'block';
        }

        // Close the modal when the close button is clicked
        document.querySelectorAll('.close-button').forEach(button => {
            button.onclick = function () {
                document.getElementById('details-modal').style.display = 'none';
                document.getElementById('approve-modal').style.display = 'none';
                document.getElementById('reject-modal').style.display = 'none';
            };
        });

        // Close the modal when clicking outside of the modal content
        window.onclick = function (event) {
            if (event.target == document.getElementById('details-modal') ||
                event.target == document.getElementById('approve-modal') ||
                event.target == document.getElementById('reject-modal')) {
                document.getElementById('details-modal').style.display = 'none';
                document.getElementById('approve-modal').style.display = 'none';
                document.getElementById('reject-modal').style.display = 'none';
            }
        };
    </script>
</body>
</html>
