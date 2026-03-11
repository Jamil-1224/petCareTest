<?php
require __DIR__ . '/../functions.php';
// Include the function to establish a connection or ensure $conn is available.

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $type = $_POST['pet_type'];
  $suitable = $_POST['suitable'];
  $not = $_POST['not_suitable'];

  // Input Validation (Basic Example)
  if (!empty($type) && !empty($suitable) && !empty($not)) {
    $insertId = mongoInsert('feed', [
      'pet_type' => $type,
      'suitable' => $suitable,
      'not_suitable' => $not,
      'created_at' => getCurrentDateTime()
    ]);

    if ($insertId) {
      echo "<script>alert('Feed guideline added successfully!');</script>";
    } else {
      echo "<script>alert('Error adding feed guideline');</script>";
    }
  } else {
    echo "<script>alert('All fields are required!');</script>";
  }
}
// Note: You should typically handle redirection after a successful POST (Post-Redirect-Get pattern) 
// to prevent form resubmission on refresh.
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Feed Guideline | PetCare</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    /*
    * Nav Bar Styles (Based on Image)
    */
    .navbar {
      background-color: #1a2938;
      /* Dark navy/blue background */
      height: 60px;
      display: flex;
      align-items: center;
      padding: 0 30px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      border-bottom: 3px solid #0077b6;
      /* Blue line underneath */
    }

    .logo a {
      color: #ffffff;
      /* White text */
      font-size: 20px;
      font-weight: 700;
      text-decoration: none;
      margin-right: 40px;
      letter-spacing: 0.5px;
    }

    .nav-links {
      display: flex;
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .nav-links li {
      margin-right: 25px;
    }

    .nav-links a {
      color: #ffffff;
      text-decoration: none;
      font-weight: 400;
      /* Regular weight for links */
      font-size: 15px;
      padding: 18px 0;
      /* Padding for click area */
      display: block;
      transition: color 0.3s ease;
    }

    .nav-links a:hover {
      color: #0077b6;
      /* Blue hover effect */
    }

    .logout {
      margin-left: auto;
      /* Push logout to the far right */
    }

    .logout a {
      /* Styling for the Logout link */
      color: #ffffff;
      text-decoration: none;
      font-weight: 400;
      font-size: 15px;
      padding: 18px 0;
      display: block;
      transition: color 0.3s ease;
    }

    .logout a:hover {
      color: #e55;
      /* Reddish hover for logout */
    }

    /*
    * General and Form Styles (Adjusted for overall page layout)
    */
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f8f9fa, #e3f2fd);
      margin: 0;
      padding: 0;
      min-height: 100vh;
      /* Ensure body covers full viewport height */
      display: flex;
      flex-direction: column;
      /* Stack navbar and main content */
    }

    .main-content {
      flex-grow: 1;
      /* Allows content area to take up remaining space */
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .feed-container {
      background: #ffffff;
      width: 400px;
      padding: 30px 40px;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: transform 0.3s ease;
      margin: 20px 0;
      /* Added margin for spacing */
    }

    .feed-container:hover {
      transform: scale(1.02);
    }

    h2 {
      color: #0077b6;
      margin-bottom: 20px;
      font-weight: 600;
    }

    input[type="text"],
    textarea {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 15px;
      transition: border 0.3s ease;
      resize: none;
    }

    input[type="text"]:focus,
    textarea:focus {
      border-color: #0077b6;
      outline: none;
      box-shadow: 0 0 5px rgba(0, 119, 182, 0.3);
    }

    button {
      background: #0077b6;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 16px;
      width: 100%;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #005f8a;
    }

    .footer-note {
      margin-top: 15px;
      color: #555;
      font-size: 13px;
    }

    @media (max-width: 768px) {

      /* Adjustments for smaller screens */
      .navbar {
        padding: 0 15px;
        justify-content: space-between;
      }

      .nav-links {
        display: none;
        /* Hide main links on small screens or implement a mobile menu */
      }

      .logo a {
        margin-right: 0;
      }

      .feed-container {
        width: 90%;
        padding: 25px;
      }
    }
  </style>
</head>

<body>

  <nav class="navbar">
    <div class="logo">
      <a href="#">Pet Care Reminder</a>
    </div>
    <ul class="nav-links">
      <li><a href="#">Pets</a></li>
      <li><a href="#">Reminders</a></li>
      <li><a href="#">Appointments</a></li>
      <li><a href="#">Memories</a></li>
      <li><a href="#">Articles</a></li>
      <li><a href="#">Adoption</a></li>
      <li><a href="#">Feed Guidelines</a></li>
      <li><a href="#">My Profile</a></li>
    </ul>
    <div class="logout">
      <a href="#">Logout</a>
    </div>
  </nav>
  <div class="main-content">
    <div class="feed-container">
      <h2>🐾 Add Feed Guideline</h2>
      <form method="post">
        <input type="text" name="pet_type" placeholder="Enter Pet Type (e.g., Dog, Cat)" required>
        <textarea name="suitable" rows="4" placeholder="Enter Suitable Food Items" required></textarea>
        <textarea name="not_suitable" rows="4" placeholder="Enter Not Suitable Food Items" required></textarea>
        <button type="submit">💾 Save Feed Guideline</button>
      </form>
      <div class="footer-note">PetCare Feed Management System</div>
    </div>
  </div>

</body>

</html>