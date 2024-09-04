<?php
session_start();
require 'db.php'; // Adjust the path to your db.php file

// Check if the user is logged in and is an administrator
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header('Location: authenticate.php'); // Adjust the path to your authenticate.php file
    exit();
}

// For now, we use fake data for demonstration purposes.
$applicants = [
    [
        'id' => 1,
        'name' => 'Participant A',
        'email' => 'participantA@example.com',
        'phone' => '123-456-7890',
        'document' => 'docA.pdf',
        'reason' => 'Reason A'
    ],
    [
        'id' => 2,
        'name' => 'Participant B',
        'email' => 'participantB@example.com',
        'phone' => '098-765-4321',
        'document' => 'docB.pdf',
        'reason' => 'Reason B'
    ],
    // Add more fake data as needed
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Organizer Applications - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/manage_organizer.css"> <!-- Adjust the path as necessary -->
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
                <h2>Manage Organizer Applications</h2>
                <div class="application-list">
                    <table>
                        <thead>
                            <tr>
                                <th><span class="iconify" data-icon="mdi:account"></span> Name</th>
                                <th><span class="iconify" data-icon="mdi:email"></span> Email</th>
                                <th><span class="iconify" data-icon="mdi:phone"></span> Phone</th>
                                <th><span class="iconify" data-icon="mdi:file-document"></span> Document</th>
                                <th><span class="iconify" data-icon="mdi:cog"></span> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applicants as $applicant) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($applicant['name']); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['phone']); ?></td>
                                    <td><a href="../documents/<?php echo htmlspecialchars($applicant['document']); ?>" target="_blank"><?php echo htmlspecialchars($applicant['document']); ?></a></td>
                                    <td>
                                        <button class="view-details-btn" onclick="showDetails(<?php echo htmlspecialchars(json_encode($applicant)); ?>)">
                                            <span class="iconify" data-icon="mdi:eye"></span> View Details
                                        </button>
                                        <button class="approve-btn" onclick="approveApplication(<?php echo $applicant['id']; ?>)">
                                            <span class="iconify" data-icon="mdi:check"></span> Approve
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

    <!-- Modal Structure -->
    <div id="details-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Applicant Details</h3>
            <p><strong>Name:</strong> <span id="modal-name"></span></p>
            <p><strong>Email:</strong> <span id="modal-email"></span></p>
            <p><strong>Phone:</strong> <span id="modal-phone"></span></p>
            <p><strong>Document:</strong> <a id="modal-document" href="#" target="_blank">View Document</a></p>
            <p><strong>Reason:</strong> <span id="modal-reason"></span></p>
        </div>
    </div>

    <script>
        // Function to show the modal with applicant details
        function showDetails(applicant) {
            document.getElementById('modal-name').innerText = applicant.name;
            document.getElementById('modal-email').innerText = applicant.email;
            document.getElementById('modal-phone').innerText = applicant.phone;
            document.getElementById('modal-document').href = '../documents/' + applicant.document;
            document.getElementById('modal-reason').innerText = applicant.reason;

            document.getElementById('details-modal').style.display = 'block';
        }

        // Function to approve the application
        function approveApplication(applicantId) {
            // Add AJAX call or form submission to approve the application in the database
            alert(`Application ID ${applicantId} approved.`);
            // Remove the applicant from the list (this is just for demonstration, actual implementation would involve refreshing the data)
            location.reload();
        }

        // Close the modal when the close button is clicked
        document.querySelector('.close-button').onclick = function () {
            document.getElementById('details-modal').style.display = 'none';
        };

        // Close the modal when clicking outside of the modal content
        window.onclick = function (event) {
            if (event.target == document.getElementById('details-modal')) {
                document.getElementById('details-modal').style.display = 'none';
            }
        };
    </script>
</body>
</html>
