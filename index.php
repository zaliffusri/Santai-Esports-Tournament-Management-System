<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Santai Gaming-Esports Tournament Management System</title>
  <link rel="icon" type="image/png" href="image/Santai Esports Logo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gurmukhi:wght@100..900&family=Permanent+Marker&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Honk&family=Permanent+Marker&display=swap" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Honk&family=Permanent+Marker&family=Shojumaru&display=swap" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Honk&family=Permanent+Marker&family=Trade+Winds&display=swap" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&family=Honk&family=Permanent+Marker&display=swap" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Honk&family=Josefin+Slab:ital,wght@0,100..700;1,100..700&family=Permanent+Marker&display=swap" rel="stylesheet">

  <style>
    /* styles.css */
    body {
      background-image: url("image/background.png");
      /* Replace with the path to your image */
      background-size: cover;
      /* This will make sure the image covers the entire background */
      background-repeat: no-repeat;
      /* This prevents the image from repeating */
      background-attachment: fixed;
      /* This makes the background image fixed while scrolling */
      margin: 0;
    }

    /* Loading Animation Styles */
    .loading-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgb(6, 5, 5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      opacity: 1;
      transition: opacity 0.5s ease, transform 0.5s ease;
      /* Adjust the transition properties */
    }

    .loading-container.hidden {
      opacity: 0.5;
      transform: translateY(100%);
    }

    .loader {
      --path: #d99058;
      --dot: #29c966;
      --duration: 3s;
      width: 44px;
      height: 44px;
      position: relative;
    }

    .loader:before {
      content: "";
      width: 6px;
      height: 6px;
      border-radius: 50%;
      position: absolute;
      display: block;
      background: var(--dot);
      top: 37px;
      left: 19px;
      transform: translate(-18px, -18px);
      animation: dotRect var(--duration) cubic-bezier(0.785, 0.135, 0.15, 0.86) infinite;
    }

    .loader svg {
      display: block;
      width: 100%;
      height: 100%;
    }

    .loader svg rect,
    .loader svg polygon,
    .loader svg circle {
      fill: none;
      stroke: var(--path);
      stroke-width: 10px;
      stroke-linejoin: round;
      stroke-linecap: round;
    }

    .loader svg polygon {
      stroke-dasharray: 145 76 145 76;
      stroke-dashoffset: 0;
      animation: pathTriangle var(--duration) cubic-bezier(0.785, 0.135, 0.15, 0.86) infinite;
    }

    .loader svg rect {
      stroke-dasharray: 192 64 192 64;
      stroke-dashoffset: 0;
      animation: pathRect 3s cubic-bezier(0.785, 0.135, 0.15, 0.86) infinite;
    }

    .loader svg circle {
      stroke-dasharray: 150 50 150 50;
      stroke-dashoffset: 75;
      animation: pathCircle var(--duration) cubic-bezier(0.785, 0.135, 0.15, 0.86) infinite;
    }

    .loader.triangle {
      width: 48px;
    }

    .loader.triangle:before {
      left: 21px;
      transform: translate(-10px, -18px);
      animation: dotTriangle var(--duration) cubic-bezier(0.785, 0.135, 0.15, 0.86) infinite;
    }

    @keyframes pathTriangle {
      33% {
        stroke-dashoffset: 74;
      }

      66% {
        stroke-dashoffset: 147;
      }

      100% {
        stroke-dashoffset: 221;
      }
    }

    @keyframes dotTriangle {
      33% {
        transform: translate(0, 0);
      }

      66% {
        transform: translate(10px, -18px);
      }

      100% {
        transform: translate(-10px, -18px);
      }
    }

    @keyframes pathRect {
      25% {
        stroke-dashoffset: 64;
      }

      50% {
        stroke-dashoffset: 128;
      }

      75% {
        stroke-dashoffset: 192;
      }

      100% {
        stroke-dashoffset: 256;
      }
    }

    @keyframes dotRect {
      25% {
        transform: translate(0, 0);
      }

      50% {
        transform: translate(18px, -18px);
      }

      75% {
        transform: translate(0, -36px);
      }

      100% {
        transform: translate(-18px, -18px);
      }
    }

    @keyframes pathCircle {
      25% {
        stroke-dashoffset: 125;
      }

      50% {
        stroke-dashoffset: 175;
      }

      75% {
        stroke-dashoffset: 225;
      }

      100% {
        stroke-dashoffset: 275;
      }
    }

    .loader {
      display: inline-block;
      margin: 0 16px;
    }

    /* Homepage Content Styles */
    .homepage-content {
      position: relative;
      text-align: center;
      opacity: 0;
      transition: transform 1s ease-in-out, opacity 1s ease-in-out;
    }

    .visible {
      opacity: 1;
    }

    .logo {
      display: block;
      margin-left: auto;
      margin-right: auto;
      margin-top: 50px;
      width: 130px;
      height: 130px;
    }

    .h4 {
      text-align: center;
      margin-top: 35px;
      margin-bottom: 2px;
      font-family: "Josefin Slab", serif;
      font-size: 30px;
      color: goldenrod;
    }

    .h1 {
      text-align: center;
      margin-top: 8px;
      font-family: "Black Ops One", system-ui;
      font-size: 80px;
      color: whitesmoke;
      margin-bottom: 40px;
    }





    .btn {
      width: 140px;
      height: 50px;
      background: linear-gradient(to top, rgb(42, 36, 24), rgb(162, 129, 46), rgb(186, 146, 45));
      color: #362b2b;
      border-radius: 50px;
      border: none;
      outline: none;
      cursor: pointer;
      position: relative;
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
      overflow: hidden;
      margin: 0 auto;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 40px;
      font-family: "Shojumaru", system-ui;
      -webkit-text-stroke: 0.5px white;
      /* WebKit browsers */
    }

    .btn span {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      transition: top 0.5s;
      display: block;
      /* Add this line */
      text-align: center;
      /* Add this line */
      width: 100%;
      /* Add this line */
    }

    .btn-text-one {
      position: absolute;
      width: 100%;
      top: 50%;
      left: 0;
      transform: translateY(-50%);
    }

    .btn-text-two {
      position: absolute;
      width: 100%;
      top: 150%;
      left: 0;
      transform: translateY(-50%);
    }

    .btn:hover .btn-text-one {
      top: -100%;
    }

    .btn:hover .btn-text-two {
      top: 50%;
    }

    .social-media-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 145px;
      transition: color 0.3s ease;
      /* add this line */

    }

    .social-media-list a {
      text-decoration: none;
      color: #ffffff;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      overflow: hidden;
      margin-right: 50px;
    }

    .social-media-list a img {
      position: relative;
      width: 32px;
      height: 32px;
      transition: transform 0.1s ease;
    }

    .social-media-list a:before,
    .social-media-list a:after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.2);
      /* adjust transparency as needed */
      opacity: 0;
      transition: transform 0.1s ease, opacity 0.1s ease;
    }

    .social-media-list a:hover img {
      transform: translateX(2px) translateY(2px) rotate(2deg);
    }

    .social-media-list a:hover {
      color: #ffcc00;
      /* add this line */
    }

    .social-media-list a:hover:before {
      transform: translateX(-10px) rotate(-2deg);
      opacity: 0.5;
    }

    .social-media-list a:hover:after {
      transform: translateX(10px) rotate(2deg);
      opacity: 0.5;
    }

    @media (max-width: 768px) {
      .social-media-list a img {
        width: 24px;
        height: 24px;
      }
    }
  </style>
</head>

<body>
  <!-- Loading Animation -->
  <div class="loading-container">
    <div class="loader">
      <svg viewBox="0 0 80 80">
        <circle r="32" cy="40" cx="40" id="test"></circle>
      </svg>
    </div>

    <div class="loader triangle">
      <svg viewBox="0 0 86 80">
        <polygon points="43 8 79 72 7 72"></polygon>
      </svg>
    </div>

    <div class="loader">
      <svg viewBox="0 0 80 80">
        <rect height="64" width="64" y="8" x="8"></rect>
      </svg>
    </div>
  </div>

  <!-- Homepage Content -->
  <img src="image/Santai Esports Logo.png" alt="Santai Esports" class="logo">

  <div class="homepage-content">

    <h4 class="h4">ESPORTS AND TOURNAMENTS</h4>
    <h1 class="h1">SANTAI ESPORTS</h1>

    <button class="btn" onclick="location.href='php/authenticate.php'">
      <span class="btn-text-one">SIGN UP</span>
      <span class="btn-text-two">NOW!</span>
    </button>


    <!-- Social Media Links -->
    <ul class="social-media-list">
      <li>
        <a href="https://www.facebook.com/santaiesportsstudio" target="_blank" class="facebook">
          <img src="image/facebook-logo.png" alt="Facebook Logo">
          FACEBOOK <span class="name">Santai Esports Studio</span>
        </a>
      </li>
      <li>
        <a href="https://www.instagram.com/santaiesportstudio/" target="_blank" class="instagram">
          <img src="image/instagram-logo.png" alt="Instagram Logo">
          INSTAGRAM <span class="name">santaiesportstudio</span>
        </a>
      </li>
      <li>
        <a href="https://www.tiktok.com/@santaiesportsstudio" target="_blank" class="tiktok">
          <img src="image/tiktok-logo.png" alt="TikTok Logo">
          TIKTOK <span class="name">santaiesportsstudio</span>
        </a>
      </li>
    </ul>

  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Show the loading container
      const loadingContainer = document.querySelector('.loading-container');
      loadingContainer.classList.remove('hidden');

      // Simulate loading time (you can adjust the duration)
      setTimeout(function() {
        // Hide the loading animation with a transition
        loadingContainer.classList.add('hidden');

        // Wait for 500 milliseconds (0.5 seconds) for the transition to complete
        setTimeout(function() {
          // Show the homepage content with a transition
          document.querySelector('.homepage-content').style.opacity = '1';
          document.querySelector('.homepage-content').style.display = 'block';
        }, 500);
      }, 2000);
    });

    setTimeout(function() {
      homepageContent.style.transform = 'translate(-50%, -30%)';
      homepageContent.classList.add('visible');
    }, 5000);
  </script>


</body>

</html>