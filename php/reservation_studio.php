<?php
require 'db.php';
session_start(); // Start the session

// Fetch current user's ID from the session
$user_id = $_SESSION['user_id'];

// Fetch user details
$sql_user = "SELECT fullname, email, phone FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

// Fetch tournaments for the form dropdown created by the current user
$tournaments = [];
$sql_tournaments = "SELECT id, name, date, game FROM tournaments WHERE user_id = ?";
$stmt_tournaments = $conn->prepare($sql_tournaments);
$stmt_tournaments->bind_param("i", $user_id);
$stmt_tournaments->execute();
$result_tournaments = $stmt_tournaments->get_result();

if ($result_tournaments->num_rows > 0) {
    while ($row = $result_tournaments->fetch_assoc()) {
        $tournaments[] = $row;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['fullName'];
    $email = $_POST['email'];
    $phone_number = $_POST['phoneNumber'];
    $ps5_count = $_POST['ps5Count'];
    $reservation_date = $_POST['reservationDate'];
    $start_time = $_POST['startTime'];
    $duration = $_POST['duration'];
    $end_time = $_POST['endTime'];
    $tournament_id = $_POST['tournament'];
    $total_price = $_POST['price'];

    // Check for existing reservations on the same date
    $sql_check = "SELECT COUNT(*) as count FROM reservations WHERE reservation_date = ? AND status IN ('pending', 'approved')";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $reservation_date);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row = $result_check->fetch_assoc();

    if ($row['count'] > 0) {
        $message = "Reservation date is already taken. Please choose another date.";
    } else {
        $sql = "INSERT INTO reservations (full_name, email, phone_number, ps5_count, reservation_date, start_time, duration, end_time, tournament_id, total_price, user_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssississii", $full_name, $email, $phone_number, $ps5_count, $reservation_date, $start_time, $duration, $end_time, $tournament_id, $total_price, $user_id);

        if ($stmt->execute()) {
            $message = "Reservation successful!";
        } else {
            $message = "Error: " . $stmt->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <title>Reservation Studio PS5 - Santai Esports</title>
    <link rel="stylesheet" href="../css/reservation_studio.css">
</head>

<body>
    <?php require 'header.php'; ?>

    <div class="main-content">
        <main>
<section id="reservation-section" class="section">
    <h2>Reserve Your PS5 Studio</h2>
    <p>Book your slot to enjoy gaming on PS5 at our studio.</p>

    <!-- Buttons to switch between the reservation form and the reservation status list -->
    <div class="button-container">
        <a href="reservation_studio.php"><button id="show-reservation-form">Reservation Form</button></a>
        <a href="check_reservation_status.php"><button id="show-reservation-status">Check Reservation Status</button></a>
    </div>

    <div id="reservation-form-container" class="reservation-form-container">
        <form id="reservationForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="fullName">Full Name:</label>
                <input type="text" id="fullName" name="fullName" required value="<?php echo htmlspecialchars($user['fullname']); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div class="form-group">
                <label for="phoneNumber">Phone Number:</label>
                <input type="text" id="phoneNumber" name="phoneNumber" required value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            <div class="form-group">
                <label for="ps5Count">Number of PS5s (max 8):</label>
                <select id="ps5Count" name="ps5Count" required onchange="calculatePrice()">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                </select>
            </div>
            <div class="form-group">
                <label for="tournament">Tournament:</label>
                <select id="tournament" name="tournament" required onchange="setReservationDate()">
                    <option value="">Select a Tournament</option>
                    <?php foreach ($tournaments as $tournament) : ?>
                        <option value="<?php echo $tournament['id']; ?>" data-date="<?php echo $tournament['date']; ?>">
                            <?php echo $tournament['name']; ?> (<?php echo $tournament['game']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="reservationDate">Reservation Date:</label>
                <input type="date" id="reservationDate" name="reservationDate" required readonly>
            </div>
            <div class="form-group">
                <label for="startTime">Start Time:</label>
                <select id="startTime" name="startTime" required onchange="updateDurationOptions()">
                    <option value="08:00">08:00 AM</option>
                    <option value="09:00">09:00 AM</option>
                    <option value="10:00">10:00 AM</option>
                    <option value="11:00">11:00 AM</option>
                    <option value="12:00">12:00 PM</option>
                    <option value="13:00">01:00 PM</option>
                    <option value="14:00">02:00 PM</option>
                    <option value="15:00">03:00 PM</option>
                    <option value="16:00">04:00 PM</option>
                    <option value="17:00">05:00 PM</option>
                    <option value="18:00">06:00 PM</option>
                    <option value="19:00">07:00 PM</option>
                    <option value="20:00">08:00 PM</option>
                    <option value="21:00">09:00 PM</option>
                    <option value="22:00">10:00 PM</option>
                    <option value="23:00">11:00 PM</option>
                    <option value="00:00">12:00 AM</option>
                </select>
            </div>
            <div class="form-group">
                <label for="duration">Duration (hours):</label>
                <select id="duration" name="duration" required onchange="calculateEndTime()">
                    <!-- Options will be dynamically added here based on start time -->
                </select>
            </div>
            <div class="form-group">
                <label for="endTime">End Time:</label>
                <input type="time" id="endTime" name="endTime" readonly>
            </div>
            <div class="form-group">
                <label for="price">Total Price (RM):</label>
                <input type="text" id="price" name="price" readonly>
            </div>
            <button type="submit">Reserve Now</button>
        </form>
    </div>
</section>

        </main>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <p id="modalMessage"></p>
        </div>
    </div>

    <script>
        function setReservationDate() {
            const tournamentSelect = document.getElementById('tournament');
            const reservationDateInput = document.getElementById('reservationDate');
            const selectedOption = tournamentSelect.options[tournamentSelect.selectedIndex];
            const tournamentDate = selectedOption.getAttribute('data-date');
            reservationDateInput.value = tournamentDate;
        }

        function updateDurationOptions() {
            const startTime = document.getElementById('startTime').value;
            const durationSelect = document.getElementById('duration');
            const [startHour, startMinute] = startTime.split(':').map(Number);
            const maxDuration = 27 - startHour; // Calculate the maximum duration to not exceed 3:00 AM

            durationSelect.innerHTML = ''; // Clear existing options

            for (let i = 1; i <= maxDuration; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `${i} hour${i > 1 ? 's' : ''}`;
                durationSelect.appendChild(option);
            }

            calculateEndTime();
        }

        function calculateEndTime() {
            const startTime = document.getElementById('startTime').value;
            const duration = parseInt(document.getElementById('duration').value);

            if (startTime && duration) {
                const [startHour, startMinute] = startTime.split(':').map(Number);
                let endHour = startHour + duration;
                let endMinute = startMinute;

                // Adjust for times past midnight
                if (endHour >= 24) {
                    endHour -= 24;
                }

                const endTime = `${String(endHour).padStart(2, '0')}:${String(endMinute).padStart(2, '0')}`;
                document.getElementById('endTime').value = endTime;

                calculatePrice();
            }
        }

        function calculatePrice() {
            const ps5Count = parseInt(document.getElementById('ps5Count').value);
            const duration = parseInt(document.getElementById('duration').value);
            const pricePerHour = 3;

            if (ps5Count && duration) {
                const totalPrice = ps5Count * duration * pricePerHour;
                document.getElementById('price').value = totalPrice.toFixed(2);
            }
        }

        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        function showForm() {
            document.getElementById('reservation-form-container').style.display = 'block';
            document.getElementById('reservation-status-container').style.display = 'none';
        }

        // Add event listeners for dynamic price calculation
        document.getElementById('ps5Count').addEventListener('change', calculatePrice);
        document.getElementById('startTime').addEventListener('change', updateDurationOptions);
        document.getElementById('duration').addEventListener('change', calculatePrice);

        // Initialize duration options and minimum reservation date on page load
        window.onload = function() {
            updateDurationOptions();

            <?php if ($message) : ?>
                document.getElementById('modalMessage').textContent = "<?php echo $message; ?>";
                document.getElementById('messageModal').style.display = 'block';
            <?php endif; ?>
        };
    </script>
</body>

</html>
