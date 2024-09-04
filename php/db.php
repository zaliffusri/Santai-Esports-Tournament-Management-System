<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "santai_esports";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// You can close the connection when it's no longer needed:
// $conn->close();
?>
