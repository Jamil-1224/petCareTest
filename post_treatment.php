<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//require_doctor_login();

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header('Location: doctor_login.php');
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    mongoInsert('cat_treatments', [
        'doctor_id' => stringToObjectId($doctor_id),
        'title' => $title,
        'content' => $content,
        'created_at' => getCurrentDateTime()
    ]);

    header("Location: doctor_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Treatment - Pet Care</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e0f7fa, #ffffff);
            color: #333;
            min-height: 100vh;
        }

        header {
            background: #00796b;
            color: white;
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 1.6rem;
            font-weight: 600;
        }

        header .user-info {
            font-size: 1rem;
            font-weight: 500;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 1rem;
        }

        nav ul li a {
            text-decoration: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        nav ul li a:hover,
        nav ul li a.active {
            background: #004d40;
        }

        main {
            padding: 2rem;
        }

        main h2 {
            margin-bottom: 1.5rem;
            color: #00796b;
        }

        .appointments {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
        }

        textarea {
            resize: vertical;
        }

        .btn {
            background: #00796b;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #004d40;
        }
    </style>
</head>

<body>

    <header>
        <h1>Doctor Dashboard - Post Treatment</h1>
        <nav>
            <ul>
                <li><a href="doctor_dashboard.php">Appointments</a></li>
                <li><a href="doctor_messages.php">Messages</a></li>
                <li><a href="post_treatment.php" class="active">Post Treatment</a></li>
                <li><a href="doctor_profile.php">Profile</a></li>
                <li><a href="doctor_logout.php">Logout</a></li>
            </ul>
        </nav>
        <div class="user-info">Welcome, Dr. <?= htmlspecialchars($doctor_name) ?></div>
    </header>

    <main>
        <div class="container">
            <h2>Post a New Cat Treatment</h2>
            <form action="post_treatment.php" method="POST">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="10" required></textarea>
                </div>
                <button type="submit" class="btn">Post Treatment</button>
            </form>
        </div>
    </main>

</body>

</html>