<?php
include 'header.php';
include 'db.php';

// Fetch the news ID from the URL
$news_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($news_id > 0) {
    // Fetch the news details from the database
    $stmt = $conn->prepare("SELECT title, content, created_at, image FROM news WHERE id = ?");
    $stmt->bind_param("i", $news_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $news = $result->fetch_assoc();

    if ($news) {
        $title = htmlspecialchars($news['title']);
        $content = $news['content']; // Output the content as is
        $created_at = date('F j, Y', strtotime($news['created_at']));
        $image = htmlspecialchars($news['image']);
    } else {
        $title = "News Not Found";
        $content = "The news you are looking for does not exist.";
        $created_at = "";
        $image = "";
    }
} else {
    $title = "Invalid News ID";
    $content = "The news ID provided is invalid.";
    $created_at = "";
    $image = "";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <title><?php echo $title; ?> - Santai Esports</title>
    <link rel="stylesheet" href="../css/news.css"> <!-- Include the news-specific CSS file here -->
</head>

<body>
    <main>
        <div class="news-container">
            <?php if ($image): ?>
                <div class="news-image-container">
                    <img src="<?php echo $image; ?>" alt="<?php echo $title; ?>" class="news-image">
                </div>
            <?php endif; ?>
            <div class="news-content-container">
                <h1 class="news-title"><?php echo $title; ?></h1>
                <p class="news-date"><?php echo $created_at; ?></p>
                <div class="news-content">
                    <?php echo $content; // Display content as HTML ?>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
