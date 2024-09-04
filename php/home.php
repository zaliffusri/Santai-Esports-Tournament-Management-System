<?php include 'header.php'; 
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Santai Esports</title>
    <link rel="icon" type="image/png" href="../image/Santai Esports Logo.png">
    <link rel="stylesheet" href="../css/home.css"> <!-- Include the home-specific CSS file here -->
</head>

<body>
    <main>
        <div class="slideshow-container">
            <div class="slides">
                <div class="slide">
                    <img src="../image/banner1.png" alt="Banner 1">
                </div>
                <div class="slide">
                    <img src="../image/banner2.png" alt="Banner 2">
                </div>
                <div class="slide">
                    <video autoplay muted loop>
                        <source src="../video/banner3.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>
            <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
            <a class="next" onclick="plusSlides(1)">&#10095;</a>
        </div>

        <section class="new-news">
            <h2>Latest News</h2>
            <div class="news-container">
                <?php
                // Fetch news from the database
                $sql_news = "SELECT id, title, content, created_at, image FROM news ORDER BY created_at DESC LIMIT 6";
                $result_news = $conn->query($sql_news);

                if ($result_news->num_rows > 0) {
                    while ($news_item = $result_news->fetch_assoc()) {
                        echo '<a href="news.php?id=' . $news_item['id'] . '" class="news-item">';
                        if ($news_item['image']) {
                            echo '<img src="' . htmlspecialchars($news_item['image']) . '" alt="' . htmlspecialchars($news_item['title']) . '">';
                        }
                        echo '<div class="news-content">';
                        echo '<span class="news-date">' . date('F j, Y', strtotime($news_item['created_at'])) . '</span>';
                        echo '<h3>' . htmlspecialchars($news_item['title']) . '</h3>';
                        echo '<p>' . htmlspecialchars(substr(strip_tags($news_item['content']), 0, 100)) . '...</p>';
                        echo '</div></a>';
                    }
                } else {
                    echo '<p>No news available.</p>';
                }

                $conn->close();
                ?>
            </div>
        </section>
    </main>

    <script>
        let slideIndex = 0;
        let slideInterval;

        function showSlides(n) {
            let slides = document.getElementsByClassName("slide");
            if (n >= slides.length) {
                slideIndex = 0;
            } else if (n < 0) {
                slideIndex = slides.length - 1;
            } else {
                slideIndex = n;
            }

            let offset = -slideIndex * 100;
            document.querySelector('.slides').style.transform = `translateX(${offset}%)`;

            // Pause all videos
            Array.from(slides).forEach(slide => {
                let video = slide.querySelector("video");
                if (video) {
                    video.pause();
                    video.currentTime = 0;
                }
            });

            // Play the video in the current slide, if any
            let currentSlide = slides[slideIndex];
            let video = currentSlide.querySelector("video");
            if (video) {
                video.play();
            }

            // Clear the interval and set a new one
            clearInterval(slideInterval);
            slideInterval = setInterval(() => {
                plusSlides(1);
            }, 3000); // Change slide every 3 seconds
        }

        function plusSlides(n) {
            showSlides(slideIndex + n);
        }

        // Initialize the slideshow
        showSlides(slideIndex);
    </script>
</body>

</html>
