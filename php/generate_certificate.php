<?php
require 'db.php';
require '../fpdf/fpdf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit();
}

$tournament_id = intval($_GET['tournament_id']);

// Fetch user and tournament details
$sql = "SELECT u.fullname, t.name AS tournament_name, t.date, t.venue, r.rank
        FROM users u
        JOIN participants p ON u.id = p.user_id
        JOIN tournaments t ON p.tournament_id = t.id
        LEFT JOIN rankings r ON t.id = r.tournament_id AND r.user_id = p.user_id
        WHERE u.id = ? AND t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $tournament_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Invalid request.";
    exit();
}

// Create instance of FPDF class
$pdf = new FPDF('L', 'mm', 'A4'); // 'L' for landscape orientation
$pdf->AddPage();

// Add the background image
$pdf->Image('../image/certificate_background.png', 0, 0, 297, 210); // Adjust the size for landscape

// Add a logo
$pdf->Image('../image/Santai Esports Logo.png', 20, 20, 30); // Adjust the position and size as needed

$pdf->SetFont('Arial', 'B', 24);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(0, 50); // Adjust the Y position for better alignment
$pdf->Cell(297, 10, 'Certificate of Participation', 0, 1, 'C');

$pdf->SetFont('Arial', '', 16);
$pdf->SetXY(0, 70); // Adjust the Y position for better alignment
$pdf->Cell(297, 10, 'This is to certify that', 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 20);
$pdf->SetXY(0, 90); // Adjust the Y position for better alignment
$pdf->Cell(297, 10, htmlspecialchars($user['fullname']), 0, 1, 'C');

$pdf->SetFont('Arial', '', 16);
$pdf->SetXY(0, 110); // Adjust the Y position for better alignment
$pdf->Cell(297, 10, 'has participated in the', 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 20);
$pdf->SetXY(0, 130); // Adjust the Y position for better alignment
$pdf->Cell(297, 10, htmlspecialchars($user['tournament_name']), 0, 1, 'C');

$pdf->SetFont('Arial', '', 16);
$pdf->SetXY(0, 150); // Adjust the Y position for better alignment
$pdf->Cell(297, 10, 'held on ' . htmlspecialchars($user['date']) . ' at ' . htmlspecialchars($user['venue']), 0, 1, 'C');

if ($user['rank'] == 1) {
    $resultText = 'Winner';
} elseif ($user['rank'] == 2) {
    $resultText = 'Runner-up';
} elseif ($user['rank'] == 3) {
    $resultText = 'Third Place';
} else {
    $resultText = 'Participant';
}

$pdf->SetFont('Arial', '', 16);
$pdf->SetXY(0, 170); // Adjust the Y position for better alignment
$pdf->Cell(297, 10, '' . $resultText, 0, 1, 'C');

// Output the certificate as a PDF file
$pdf->Output('D', 'Certificate_' . htmlspecialchars($user['tournament_name']) . '.pdf');


?>
