<?php
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tournament_id = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : 0;

if ($tournament_id <= 0) {
    echo "Invalid tournament ID.";
    exit();
}

// Check if the bracket is already generated
$sql_check_bracket = "SELECT bracket_generated FROM tournaments WHERE id = ?";
$stmt_check_bracket = $conn->prepare($sql_check_bracket);
$stmt_check_bracket->bind_param("i", $tournament_id);
$stmt_check_bracket->execute();
$result_check_bracket = $stmt_check_bracket->get_result();
$row_check_bracket = $result_check_bracket->fetch_assoc();
$bracket_generated = $row_check_bracket['bracket_generated'];

// Handle saving the shuffled slots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_slots') {
    $participants = json_decode($_POST['participants'], true);

    if (!isset($participants) || !is_array($participants)) {
        echo "Invalid data.";
        exit();
    }

    foreach ($participants as $participant) {
        $participant_id = intval($participant['participant_id']);
        $slot = intval($participant['slot']);

        if ($participant_id > 0 && $slot > 0) {
            $sql = "UPDATE participants SET slot = ? WHERE id = ? AND tournament_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log('MySQL prepare() failed: ' . $conn->error);
                echo "Error preparing statement.";
                exit();
            }
            $stmt->bind_param("iii", $slot, $participant_id, $tournament_id);
            if (!$stmt->execute()) {
                error_log('MySQL execute() failed: ' . $stmt->error);
                echo "Error executing statement.";
                exit();
            }
        }
    }

    echo "success";
    exit();
}

// Fetch participants for the tournament
$participants = [];
$sql = "SELECT p.slot, u.username, p.id as participant_id FROM participants p JOIN users u ON p.user_id = u.id WHERE p.tournament_id = ? ORDER BY p.slot ASC";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log('MySQL prepare() failed: ' . $conn->error);
    echo "Error fetching participants.";
    exit();
}
$stmt->bind_param("i", $tournament_id);
if (!$stmt->execute()) {
    error_log('MySQL execute() failed: ' . $stmt->error);
    echo "Error fetching participants.";
    exit();
}
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $participants[] = $row;
}

require 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <title>Participants - Santai Esports</title>
    <link rel="stylesheet" href="../css/manage_tournament.css">
    <style>
        #successModal {
            display: none;
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        #successModal p {
            margin: 0 0 20px 0;
        }
        #modalBackdrop {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <main>
            <section id="participants-section" class="section">
                <h2>Participants for Tournament ID: <?php echo htmlspecialchars($tournament_id); ?></h2>
                <ul id="participants-list">
                    <?php
                    if (count($participants) > 0) {
                        foreach ($participants as $participant) {
                            echo "<li data-participant-id=\"{$participant['participant_id']}\" data-slot=\"{$participant['slot']}\">Slot: {$participant['slot']}, Username: {$participant['username']}</li>";
                        }
                    } else {
                        echo "<li>No participants found.</li>";
                    }
                    ?>
                </ul>
                <button onclick="location.href='manage_tournament.php'">Back</button>
                <button id="shuffleButton" onclick="shuffleSlots()" <?php if ($bracket_generated == 1) echo 'disabled'; ?>>Shuffle</button>
                <?php if ($bracket_generated == 1) : ?>
                    <p>The bracket is already generated.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Modal -->
    <div id="modalBackdrop"></div>
    <div id="successModal">
        <p>Slots updated successfully.</p>
        <button onclick="closeModal()">Close</button>
    </div>

    <script>
        function shuffleSlots() {
            const list = document.getElementById('participants-list');
            const items = Array.from(list.children);
            for (let i = items.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                list.appendChild(items[j]);
                items.splice(j, 1);
            }
            updateSlotNumbers();
            saveSlots();
        }

        function updateSlotNumbers() {
            const list = document.getElementById('participants-list');
            Array.from(list.children).forEach((item, index) => {
                item.dataset.slot = index + 1;
                item.innerHTML = `Slot: ${index + 1}, ${item.innerHTML.split(', ')[1]}`;
            });
        }

        function saveSlots() {
            const participants = Array.from(document.getElementById('participants-list').children).map(item => ({
                participant_id: item.dataset.participantId,
                slot: item.dataset.slot
            }));

            fetch(location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' // Indicate AJAX request
                },
                body: new URLSearchParams({
                    action: 'save_slots',
                    participants: JSON.stringify(participants)
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(text) });
                }
                return response.text();
            })
            .then(responseText => {
                if (responseText === 'success') {
                    showModal();
                } else {
                    alert('Error saving slots: ' + responseText);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving slots: ' + error.message);
            });
        }

        function showModal() {
            document.getElementById('modalBackdrop').style.display = 'block';
            document.getElementById('successModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modalBackdrop').style.display = 'none';
            document.getElementById('successModal').style.display = 'none';
        }
    </script>
</body>
</html>
