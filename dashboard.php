<?php
require __DIR__ . '/functions.php';
require_login();
$uid = $_SESSION['user_id'];

// fetch today's reminders
$today = date('Y-m-d');
$todayStart = new MongoDB\BSON\UTCDateTime(strtotime($today) * 1000);
$todayEnd = new MongoDB\BSON\UTCDateTime(strtotime($today . ' 23:59:59') * 1000);

$todayRem = mongoFind('reminders', [
    'user_id' => stringToObjectId($uid),
    'reminder_date' => ['$gte' => $todayStart, '$lte' => $todayEnd],
    'status' => 'pending'
]);

// quick counts
$pc = mongoCount('pets', ['user_id' => stringToObjectId($uid)]);
$rc = mongoCount('reminders', [
    'user_id' => stringToObjectId($uid),
    'status' => 'pending'
]);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pet Care</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <header class="top">
        <nav>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="pets.php">Pets</a>
            <a href="reminders.php">Reminders</a>
            <a href="appointments.php">Appointments</a>
            <a href="view_memories.php">Memories</a>
            <a href="articles.php">Articles</a>
            <a href="adoption.php">Adoption</a>
            <a href="feed_guidelines.php">Feed Guidelines</a>
            <a href="messages.php">Messages</a>
            <a href="view_treatments.php">Treatments</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <div class="dashboard-welcome">
            <h2>Welcome, <?= esc($_SESSION['name']) ?></h2>
            <p class="dashboard-date"><?= date('l, F j, Y') ?></p>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <div class="stat-content">
                    <h3>My Pets</h3>
                    <p class="stat-number"><?= esc($pc) ?></p>
                    <a href="pets.php" class="stat-link">Manage Pets</a>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="stat-content">
                    <h3>Pending Reminders</h3>
                    <p class="stat-number"><?= esc($rc) ?></p>
                    <a href="reminders.php" class="stat-link">View Reminders</a>
                </div>
            </div>
        </div>

        <section class="dashboard-actions">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="actions">
                <a class="action-btn" href="add_pet.php">
                    <i class="fas fa-plus-circle"></i> Add Pet
                </a>
                <a class="action-btn" href="add_reminder.php">
                    <i class="fas fa-calendar-plus"></i> Add Reminder
                </a>
                <a class="action-btn" href="add_memory.php">
                    <i class="fas fa-camera"></i> Add Memory
                </a>
            </div>
        </section>

        <section class="dashboard-reminders">
            <h3><i class="fas fa-calendar-day"></i> Today's Reminders</h3>
            <div class="today-reminders">
                <?php
                $todayRemArray = mongoResultToArray($todayRem);
                if (count($todayRemArray) > 0): ?>
                    <ul class="reminder-list">
                        <?php foreach ($todayRemArray as $t): ?>
                            <li class="reminder-item">
                                <div class="reminder-type-icon">
                                    <?php
                                    $icon = 'calendar-check';
                                    switch ($t['reminder_type']) {
                                        case 'feeding':
                                            $icon = 'utensils';
                                            break;
                                        case 'medication':
                                            $icon = 'pills';
                                            break;
                                        case 'vaccination':
                                            $icon = 'syringe';
                                            break;
                                        case 'grooming':
                                            $icon = 'cut';
                                            break;
                                        case 'appointment':
                                            $icon = 'stethoscope';
                                            break;
                                    }
                                    ?>
                                    <i class="fas fa-<?= $icon ?>"></i>
                                </div>
                                <div class="reminder-details">
                                    <strong><?= esc($t['title']) ?></strong>
                                    <span class="reminder-meta">
                                        <span class="reminder-type"><?= esc(ucfirst($t['reminder_type'])) ?></span> at
                                        <span class="reminder-time"><?= esc($t['reminder_time']) ?></span>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-reminders">
                        <i class="fas fa-check-circle"></i>
                        <p>No reminders for today 🎉</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Reminder Background Service -->
    <script src="reminder_background.js"></script>
</body>

</html>