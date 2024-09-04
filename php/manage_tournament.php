<?php
require 'db.php';
require 'header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to upload poster image
function uploadPoster($file) {
    if (empty($file["tmp_name"])) {
        return false;
    }

    $target_dir = "uploads/";
    // Check if the directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0775, true);
    }
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $uploadOk = 1;

    // Check if image file is an actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size
    if ($file["size"] > 2000000) { // Increased the file size limit to 2MB
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif") {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        return false;
    } else {
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $target_file;
        } else {
            echo "Sorry, there was an error uploading your file.";
            return false;
        }
    }
}

// Fetch tournaments from the database
$tournaments = [];
$user_id = $_SESSION['user_id']; // Get the user ID from the session
$sql_tournaments = "SELECT t.*, u.username FROM tournaments t JOIN users u ON t.user_id = u.id WHERE t.user_id = ?";
$stmt = $conn->prepare($sql_tournaments);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_tournaments = $stmt->get_result();

if ($result_tournaments->num_rows > 0) {
    while ($row = $result_tournaments->fetch_assoc()) {
        $tournaments[] = $row;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle creating a new tournament
    if (isset($_POST['create_tournament'])) {
        $name = $_POST['tournament_name'];
        $date = $_POST['tournament_date'];
        $game = $_POST['tournament_game'];
        $venue = $_POST['tournament_venue'];
        $format = $_POST['tournament_format'];
        $participants = $_POST['total_participant'];
        $description = $_POST['tournament_description'];
        $fee = $_POST['registration_fee'];
        $price = $_POST['registration_price'] ?? 0.00;
        $start_time = $_POST['start_time'];
        $poster = uploadPoster($_FILES['tournament_poster']);

        if ($poster) {
            $sql = "INSERT INTO tournaments (name, date, game, venue, format, participants, description, fee, price, start_time, poster, user_id)
                    VALUES ('$name', '$date', '$game', '$venue', '$format', '$participants', '$description', '$fee', '$price', '$start_time', '$poster', '$user_id')";

            if ($conn->query($sql) === TRUE) {
                echo "Tournament created successfully!";
                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }

    // Handle updating an existing tournament
    if (isset($_POST['update_tournament'])) {
        $id = $_POST['tournament_id'];
        $name = $_POST['tournament_name'];
        $date = $_POST['tournament_date'];
        $game = $_POST['tournament_game'];
        $venue = $_POST['tournament_venue'];
        $format = $_POST['tournament_format'];
        $participants = $_POST['total_participant'];
        $description = $_POST['tournament_description'];
        $fee = $_POST['registration_fee'];
        $price = $_POST['registration_price'] ?? 0.00;
        $start_time = $_POST['start_time'];
        $poster = uploadPoster($_FILES['tournament_poster']);

        $sql = "UPDATE tournaments SET 
                    name='$name', 
                    date='$date', 
                    game='$game', 
                    venue='$venue', 
                    format='$format', 
                    participants='$participants', 
                    description='$description', 
                    fee='$fee', 
                    price='$price', 
                    start_time='$start_time'";

        if ($poster) {
            $sql .= ", poster='$poster'";
        }

        $sql .= " WHERE id='$id'";

        if ($conn->query($sql) === TRUE) {
            echo "Tournament updated successfully!";
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    // Handle generating bracket
    if (isset($_POST['generate_bracket'])) {
        $tournament_id = $_POST['tournament_id'];
        
        // Update bracket_generated to 1
        $sql = "UPDATE tournaments SET bracket_generated = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $tournament_id);
        if ($stmt->execute()) {
            header("Location: bracket.php?id=$tournament_id&format={$_POST['tournament_format']}");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tournaments - Santai Esports</title>
    <link rel="stylesheet" href="../css/manage_tournament.css">
    <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="main-content">
        <main>
            <section id="manage-section" class="section">
                <h2>Manage Your Tournaments</h2>
                <p>Here you can view and manage your tournaments.</p>

                <!-- Button to create a new tournament -->
                <button id="create-tournament-button">Create New Tournament</button>

                <!-- Search bar for filtering tournaments -->
                <input type="text" id="search-bar" placeholder="Search by name...">

                <!-- List of existing tournaments -->
                <div id="tournament-list">
                    <h3>Existing Tournaments</h3>
                    <ul id="tournament-ul">
                        <?php
                        foreach ($tournaments as $tournament) {
                            echo "<li>
                                    <span>{$tournament['name']} - {$tournament['date']} by {$tournament['username']}</span>
                                    <button class='edit-btn'
                                            data-id='{$tournament['id']}'
                                            data-name='{$tournament['name']}'
                                            data-date='{$tournament['date']}'
                                            data-game='{$tournament['game']}'
                                            data-venue='{$tournament['venue']}'
                                            data-format='{$tournament['format']}'
                                            data-participants='{$tournament['participants']}'
                                            data-description='{$tournament['description']}'
                                            data-fee='{$tournament['fee']}'
                                            data-price='{$tournament['price']}'
                                            data-time='{$tournament['start_time']}'
                                            data-poster='{$tournament['poster']}'>
                                        <span class='iconify' data-icon='mdi-pencil' data-inline='false'></span> Edit
                                    </button>
                                    <button class='participants-btn' onclick=\"location.href='participants.php?tournament_id={$tournament['id']}'\">
                                        <span class='iconify' data-icon='mdi-account-group' data-inline='false'></span> Participants
                                    </button>
                                    <form method='POST' action=''>
                                        <input type='hidden' name='tournament_id' value='{$tournament['id']}'>
                                        <input type='hidden' name='tournament_format' value='{$tournament['format']}'>
                                        <button class='bracket-btn' name='generate_bracket'>
                                            <span class='iconify' data-icon='mdi-bracket' data-inline='false'></span> Generate Bracket
                                        </button>
                                    </form>
                                  </li>";
                        }
                        ?>
                    </ul>
                </div>
            </section>

            <!-- Include the modals for creating and editing tournaments -->
            <?php include 'create_tournament_modal.php'; ?>
            <?php include 'edit_tournament_modal.php'; ?>
        </main>
    </div>

    <script>
        $(document).ready(function() {
            const modalCreate = $('#modal-create');
            const modalEdit = $('#modal-edit');
            const closeButton = $('.close-button');

            // Open the modal for creating a new tournament
            $('#create-tournament-button').on('click', function() {
                modalCreate.show();
            });

            // Handle opening the modal with existing tournament data
            $('.edit-btn').on('click', function() {
                const buttonData = $(this).data();
                $('#tournament-id').val(buttonData.id);
                $('#tournament-name').val(buttonData.name);
                $('#tournament-date').val(buttonData.date);
                $('#tournament-game').val(buttonData.game);
                $('#tournament-venue').val(buttonData.venue);
                $('#tournament-format').val(buttonData.format);
                $('#total-participant').val(buttonData.participants);
                $('#tournament-description').val(buttonData.description);
                $('#registration-fee').val(buttonData.fee);
                $('#registration-price').val(buttonData.price);
                $('#start-time').val(buttonData.time);

                if (buttonData.fee === 'paid') {
                    $('#fee-group').show();
                } else {
                    $('#fee-group').hide();
                }

                const posterPreview = $('#edit-poster-preview');
                if (buttonData.poster) {
                    posterPreview.attr('src', buttonData.poster);
                    posterPreview.show();
                } else {
                    posterPreview.hide();
                }

                modalEdit.show();
            });

            // Close modals
            closeButton.on('click', function() {
                modalCreate.hide();
                modalEdit.hide();
            });

            $(window).on('click', function(event) {
                if ($(event.target).is(modalCreate) || $(event.target).is(modalEdit)) {
                    modalCreate.hide();
                    modalEdit.hide();
                }
            });

            // Handle fee group display based on registration fee
            $('#registration-fee').on('change', function() {
                if ($(this).val() === 'paid') {
                    $('#fee-group').show();
                } else {
                    $('#fee-group').hide();
                }
            });

            $('#new-registration-fee').on('change', function() {
                if ($(this).val() === 'paid') {
                    $('#new-fee-group').show();
                } else {
                    $('#new-fee-group').hide();
                }
            });

            // Handle search functionality
            $('#search-bar').on('keyup', function() {
                const filter = $(this).val().toLowerCase();
                $('#tournament-ul li').each(function() {
                    const text = $(this).text().toLowerCase();
                    if (text.includes(filter)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });

        function previewPoster(event, mode = 'create') {
            const posterPreview = mode === 'create' ? document.getElementById('poster-preview') : document.getElementById('edit-poster-preview');
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    posterPreview.src = e.target.result;
                    posterPreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                posterPreview.style.display = 'none';
            }
        }
    </script>
</body>
</html>
