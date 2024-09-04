<?php
include 'sidebar.php';
require 'db.php'; // Adjust the path to your db.php file

// Directory to store uploaded images
$upload_dir = '../uploads/';

// Fetch news from the database
$news_items = [];
$sql_news = "SELECT * FROM news";
$result_news = $conn->query($sql_news);

if ($result_news->num_rows > 0) {
    while ($row = $result_news->fetch_assoc()) {
        $news_items[] = $row;
    }
}

// Handle form submission for adding a new news item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = $upload_dir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
            $image = ''; // Reset image if upload fails
            $error = "Error uploading image.";
        }
    }

    $stmt = $conn->prepare("INSERT INTO news (title, content, image) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $content, $image);
    if ($stmt->execute()) {
        header('Location: manage_news.php');
        exit();
    } else {
        $error = "Error adding news: " . $stmt->error;
    }
}

// Handle form submission for editing a news item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])) {
    $news_id = $_POST['news_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image = $_POST['current_image'];

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

// Handle news deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $news_id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $news_id);
    if ($stmt->execute()) {
        header('Location: manage_news.php');
        exit();
    } else {
        $error = "Error deleting news: " . $stmt->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage News - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/manage_news.css"> <!-- Include your manage_news-specific CSS file -->
    <script src="https://cdn.tiny.cloud/1/qg5162ex1s81v9vkt0qu7hp0w3qpducrlvnm2vk38x3clq8w/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea#content',
            menubar: false,
            plugins: 'lists link image',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
            setup: function (editor) {
                editor.on('change', function () {
                    tinymce.triggerSave();
                });
            }
        });
    </script>
</head>

<body>
    <div class="main-content">
        <h2>Manage News</h2>
        
        <!-- Add News Form -->
        <div class="form-container">
            <h3>Add News</h3>
            <form action="manage_news.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_news" value="1">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content">Content:</label>
                    <textarea id="content" name="content" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Image:</label>
                    <input type="file" id="image" name="image">
                </div>
                <button type="submit">Add News</button>
            </form>
        </div>

        <!-- News Table -->
        <div class="table-container">
            <h3>Existing News</h3>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($news_items as $news): ?>
                        <tr>
                            <td><?php echo $news['id']; ?></td>
                            <td><?php echo htmlspecialchars($news['title']); ?></td>
                            <td><?php echo $news['created_at']; ?></td>
                            <td><?php echo $news['updated_at']; ?></td>
                            <td>
                                <?php if ($news['image']): ?>
                                    <img src="<?php echo $news['image']; ?>" alt="News Image" width="100">
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_news.php?id=<?php echo $news['id']; ?>">Edit</a>
                                <a href="manage_news.php?delete=<?php echo $news['id']; ?>" onclick="return confirm('Are you sure you want to delete this news?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
