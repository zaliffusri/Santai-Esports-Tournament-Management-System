<?php
session_start();
require 'db.php'; // Ensure you have the db.php file set up with the right database connection

// Function to sanitize input data
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isPasswordStrong($password)
{
    $hasUpperCase = preg_match('/[A-Z]/', $password);
    $hasLowerCase = preg_match('/[a-z]/', $password);
    $hasDigits = preg_match('/[0-9]/', $password);
    $hasSpecialChars = preg_match('/[^\w]/', $password);
    $hasValidLength = strlen($password) >= 8;

    return $hasUpperCase && $hasLowerCase && $hasDigits && $hasSpecialChars && $hasValidLength;
}

$signupMessage = '';
$showLoginForm = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = sanitizeInput($_POST['username']);
        $password = sanitizeInput($_POST['password']);
        $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : null;

        if (!empty($email)) { // Sign-up attempt
            if (!isPasswordStrong($password)) {
                $signupMessage = "Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.";
            } else {
                $user_role = 'participant';
                $checkUser = $conn->prepare("SELECT * FROM users WHERE username=? OR email=?");
                $checkUser->bind_param("ss", $username, $email);
                $checkUser->execute();
                $result = $checkUser->get_result();
                if ($result->num_rows > 0) {
                    $signupMessage = "Username or email already exists. Please try another one.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $conn->prepare("INSERT INTO users (username, email, user_role, password) VALUES (?, ?, ?, ?)");
                    $insert->bind_param("ssss", $username, $email, $user_role, $hashedPassword);
                    if ($insert->execute()) {
                        $signupMessage = "User registered successfully! Please log in.";
                        $showLoginForm = true;
                    } else {
                        $signupMessage = "Error: " . $conn->error;
                    }
                }
            }
        } else { // This implies it's a login attempt
            // Authenticate user
            $query = $conn->prepare("SELECT id, password, user_role FROM users WHERE username=? OR email=?");
            $query->bind_param("ss", $username, $username);
            $query->execute();
            $result = $query->get_result();
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Start session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['user_role']; // Storing user role in session

                    // Redirect to the appropriate dashboard based on role
                    if ($user['user_role'] == 'administrator') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: home.php");
                    }
                    exit;
                } else {
                    $signupMessage = "Incorrect username/email or password.";
                }
            } else {
                $signupMessage = "Incorrect username/email or password.";
            }
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
    <title>Authentication - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/authenticate.css"> <!-- Link to your CSS file -->
    <script src="https://code.iconify.design/2/2.1.2/iconify.min.js"></script> <!-- Include Iconify -->
</head>

<body>
    <video autoplay muted loop id="background-video">
        <source src="../video/background.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <main>
        <div class="container">
            <div class="image-container">
                <img src="../image/Santai Esports Logo.png" alt="Descriptive Alt Text">
            </div>
            <div class="forms-container">
                <section id="login-form" style="display: none;">
                    <h2>Login</h2>
                    <form action="authenticate.php" method="post">
                        <input type="text" id="username" name="username" placeholder="Username or Email" required>
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <button type="submit">Login</button>
                        <p>Don't have an account? <a href="#" onclick="toggleForms()">Sign up now</a></p>
                    </form>
                </section>
                <section id="signup-form">
                    <h2>Sign Up</h2>
                    <form action="authenticate.php" method="post">
                        <input type="text" id="new-username" name="username" placeholder="Username" required>
                        <input type="email" id="email" name="email" placeholder="Email" required>
                        <div class="password-input-container">
                            <input type="password" id="new-password" name="password" placeholder="Password" required oninput="validatePassword()">
                            <button type="button" class="hint-button">
                                <span class="iconify" data-icon="mdi:information-outline" data-inline="false"></span>
                                <span id="password-hint" class="password-hint">At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character</span>
                            </button>
                        </div>
                        <span id="strength-indicator" class="strength-indicator">Password is not strong enough</span>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required>
                        <button type="submit" id="signup-button" disabled>Sign Up</button>
                        <p>Already have an account? <a href="#" onclick="toggleForms()">Log in here</a></p>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <!-- Modal Structure -->
    <div id="message-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <p id="modal-message"></p>
        </div>
    </div>

    <script>
        // JavaScript to handle modal
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('message-modal');
            var span = document.getElementsByClassName('close-button')[0];
            var message = "<?php echo $signupMessage; ?>";

            if (message) {
                document.getElementById('modal-message').textContent = message;
                modal.style.display = "block";
            }

            span.onclick = function() {
                modal.style.display = "none";
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }

            var showLoginForm = <?php echo $showLoginForm ? 'true' : 'false'; ?>;
            if (showLoginForm) {
                toggleForms();
            }
        });

        function toggleForms() {
            var loginForm = document.getElementById('login-form');
            var signupForm = document.getElementById('signup-form');
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
            } else {
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
            }
        }

        function validatePassword() {
            var password = document.getElementById('new-password').value;
            var strengthIndicator = document.getElementById('strength-indicator');
            var signupButton = document.getElementById('signup-button');

            var hasUpperCase = /[A-Z]/.test(password);
            var hasLowerCase = /[a-z]/.test(password);
            var hasDigits = /[0-9]/.test(password);
            var hasSpecialChars = /[^\w]/.test(password);
            var hasValidLength = password.length >= 8;

            if (hasUpperCase && hasLowerCase && hasDigits && hasSpecialChars && hasValidLength) {
                strengthIndicator.style.display = 'none';
                signupButton.disabled = false;
            } else {
                strengthIndicator.style.display = 'inline';
                signupButton.disabled = true;
            }
        }
    </script>

</body>

</html>