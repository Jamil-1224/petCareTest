<?php
// PHP code remains the same
require __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit;
}

$can_manage_feed = is_admin() || isset($_SESSION['doctor_id']);
$res = mongoFind('feed', [], ['sort' => ['pet_type' => 1]]);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feed Guidelines | PetCare</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Base Page */
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #e3f2fd, #f8f9fa);
        }

        /*
        * Main Section Styles (Retained and slightly adjusted)
        */
        main.container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
        }

        main h2 {
            text-align: center;
            font-size: 32px;
            font-weight: 600;
            color: #222;
            position: relative;
            margin-bottom: 40px;
        }

        main h2::after {
            content: "";
            width: 80px;
            height: 4px;
            background: #0077b6;
            /* Changed to match nav bar color */
            display: block;
            margin: 10px auto 0;
            border-radius: 10px;
        }

        /* Grid Cards */
        .feed-guidelines {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        .guideline-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
            padding: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .guideline-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.15);
        }

        .guideline-card h3 {
            color: #0077b6;
            font-size: 24px;
            margin-bottom: 15px;
            text-align: center;
        }

        .guideline-section {
            background: #f8faff;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-left: 5px solid #0077b6;
        }

        .guideline-section h4 {
            color: #0077b6;
            margin: 0 0 8px;
            font-size: 17px;
        }

        .guideline-section p {
            color: #444;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }

        /* Foods to Avoid styling */
        .guideline-card .guideline-section:last-of-type {
            border-left-color: #d9534f;
            /* Red for avoidance */
            background: #fff5f5;
        }

        .guideline-card .guideline-section:last-of-type h4 {
            color: #d9534f;
        }

        /* Empty Message */
        .feed-guidelines p {
            grid-column: 1 / -1;
            /* Make empty message span all columns */
            text-align: center;
            color: #666;
            font-size: 18px;
            margin-top: 40px;
        }

        /* Floating Add Button */
        .add-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: #0077b6;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 28px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: 0.3s;
        }

        .add-btn:hover {
            background: #005f8a;
            transform: scale(1.1);
        }

        /* Responsive */
        @media (max-width: 900px) {

            /* Hide most links on smaller screens to prevent wrapping */
            .nav-links {
                display: none;
            }

            .navbar {
                justify-content: space-between;
                padding: 0 15px;
            }

            .logo a {
                margin-right: 0;
            }

            .logout-link {
                margin-left: 10px;
                /* Small gap from logo */
            }
        }
    </style>
</head>

<body>
    <header class="top">
        <nav>
            <?php if (isset($_SESSION['doctor_id'])): ?>
                <a href="doctor_dashboard.php">Doctor Dashboard</a>
                <a href="feed_guidelines.php" class="active">Feed Guidelines</a>
                <?php if ($can_manage_feed): ?>
                    <a href="add_feed.php">Add Guideline</a>
                <?php endif; ?>
                <a href="doctor_logout.php">Logout</a>
            <?php else: ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="pets.php">Pets</a>
                <a href="reminders.php">Reminders</a>
                <a href="appointments.php">Appointments</a>
                <a href="view_memories.php">Memories</a>
                <a href="articles.php">Articles</a>
                <a href="adoption.php">Adoption</a>
                <a href="feed_guidelines.php" class="active">Feed Guidelines</a>
                <a href="view_treatments.php">Treatments</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <h2>Manage Pet Feed Guidelines</h2>

        <div class="feed-guidelines">
            <?php
            $feedArray = iterator_to_array($res);
            if (count($feedArray) > 0): ?>
                <?php foreach ($feedArray as $f): ?>
                    <div class="guideline-card">
                        <h3><?= esc($f['pet_type']) ?></h3>

                        <div class="guideline-section">
                            <h4> Suitable Foods</h4>
                            <p><?= nl2br(esc($f['suitable'])) ?></p>
                        </div>

                        <div class="guideline-section">
                            <h4> Foods to Avoid</h4>
                            <p><?= nl2br(esc($f['not_suitable'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No feed guidelines available at the moment.</p>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($can_manage_feed): ?>
        <a href="add_feed.php" aria-label="Add New Feed Guideline">
            <button class="add-btn" title="Add New Feed Guideline">+</button>
        </a>
    <?php endif; ?>

</body>

</html>