<?php
include 'header.php';
include 'db.php';

// Check if session is not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: authenticate.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details function
function fetchUserDetails($conn, $user_id) {
    $query = "SELECT username, email, fullname, phone, image_profile FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fetch user details initially
$user = fetchUserDetails($conn, $user_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];

    // Handle profile image upload if a file is selected
    if (isset($_FILES['image_profile']) && $_FILES['image_profile']['error'] === UPLOAD_ERR_OK) {
        $image_temp = $_FILES['image_profile']['tmp_name'];
        $image_name = basename($_FILES['image_profile']['name']);
        $image_path = '../image/' . $image_name;  // Corrected folder path

        // Move the uploaded file to the images directory
        if (move_uploaded_file($image_temp, $image_path)) {
            $profile_image = $image_name;
        } else {
            $profile_image = isset($user['image_profile']) ? $user['image_profile'] : '';
        }
    } else {
        $profile_image = isset($user['image_profile']) ? $user['image_profile'] : '';
    }

    // Update user details in the database
    $query = "UPDATE users SET fullname = ?, phone = ?, image_profile = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("sssi", $fullname, $phone, $profile_image, $user_id);
    if ($stmt->execute() === false) {
        die('Execute failed: ' . htmlspecialchars($stmt->error));
    }

    // Refresh user details
    $user = fetchUserDetails($conn, $user_id);

    // Show confirmation modal
    echo "<script>document.getElementById('confirmationModal').style.display = 'block';</script>";
}

$profile_image = isset($user['image_profile']) && $user['image_profile'] ? $user['image_profile'] : 'user1.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/profile.css">
    <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
</head>

<body>
    <div class="main-content">
        <main>
            <section id="profile-section" class="section">
                <h2><span class="iconify" data-icon="mdi:account-circle" data-inline="false"></span> Profile</h2>
                <form id="profileForm" action="profile.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="image_profile">Profile Image:</label>
                        <div class="profile-image-container">
                            <img src="../image/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="profile-image" onclick="triggerFileInput()">
                        </div>
                        <input type="file" id="image_profile" name="image_profile" accept="image/*" style="display: none;" onchange="updateImagePreview(event)">
                    </div>
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="fullname">Full Name:</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    <button type="submit">Update Profile</button>
                </form>
            </section>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <h3>Profile Updated</h3>
            <p>Your profile has been successfully updated.</p>
            <button onclick="closeModal()">Close</button>
        </div>
    </div>

    <script>
        function updateImagePreview(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.querySelector('.profile-image');
                output.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        function triggerFileInput() {
            document.getElementById('image_profile').click();
        }

        function closeModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>

</html>
