<?php
include 'sidebar.php';
require 'db.php'; // Adjust the path to your db.php file

// Directory to store uploaded images
$upload_dir = '../uploads/';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_news.php');
    exit();
}

$news_id = $_GET['id'];

// Fetch the news item from the database
$sql_news = "SELECT * FROM news WHERE id = ?";
$stmt = $conn->prepare($sql_news);
$stmt->bind_param("i", $news_id);
$stmt->execute();
$result_news = $stmt->get_result();

if ($result_news->num_rows == 0) {
    header('Location: manage_news.php');
    exit();
}

$news = $result_news->fetch_assoc();

// Handle form submission for editing the news item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image = $news['image'];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $new_image = $upload_dir . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $new_image)) {
            $image = $new_image;
        } else {
            $error = "Error uploading image.";
        }
    }

    $stmt = $conn->prepare("UPDATE news SET title = ?, content = ?, image = ? WHERE id = ?");
    $stmt->bind_param("sssi", $title, $content, $image, $news_id);
    if ($stmt->execute()) {
        header('Location: manage_news.php');
        exit();
    } else {
        $error = "Error editing news: " . $stmt->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit News - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/manage_news.css"> <!-- Include your manage_news-specific CSS file -->
    <script src="https://cdn.tiny.cloud/1/qg5162ex1s81v9vkt0qu7hp0w3qpducrlvnm2vk38x3clq8w/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'advlist autolink lists link image charmap print preview hr anchor pagebreak',
            toolbar_mode: 'floating',
        });
    </script>
</head>
<body>
    <div class="main-content">
        <h2>Edit News</h2>
        
        <div class="form-container">
            <form action="edit_news.php?id=<?php echo $news_id; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_news" value="1">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($news['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="content">Content:</label>
                    <textarea id="content" name="content" rows="5" required><?php echo htmlspecialchars($news['content']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Image:</label>
                    <input type="file" id="image" name="image">
                    <?php if ($news['image']): ?>
                        <p>Current Image: <img src="<?php echo $news['image']; ?>" alt="News Image" width="100"></p>
                    <?php endif; ?>
                </div>
                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>
