<?php
require __DIR__ . '/functions.php';
require_login();
$uid = $_SESSION['user_id'];
// Join with pets table to get pet names
global $db;
$res = $db->memories->aggregate([
    ['$match' => ['user_id' => stringToObjectId($uid)]],
    ['$lookup' => [
        'from' => 'pets',
        'localField' => 'pet_id',
        'foreignField' => '_id',
        'as' => 'pet'
    ]],
    ['$unwind' => ['path' => '$pet', 'preserveNullAndEmptyArrays' => true]],
    ['$addFields' => ['pet_name' => '$pet.pet_name']],
    ['$sort' => ['created_at' => -1]]
]);

// Display success message if memory was added
$success_msg = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_msg = "Memory added successfully!";
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Memories - Pet Care</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header class="top">
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="pets.php">Pets</a>
            <a href="reminders.php">Reminders</a>
            <a href="appointments.php">Appointments</a>
            <a href="view_memories.php" class="active">Memories</a>
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
        <h2>Pet Memories</h2>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
        <?php endif; ?>

        <div class="actions">
            <a class="btn" href="add_memory.php">Add New Memory</a>
        </div>

        <div class="memories-grid">
            <div class="memories-grid">
                <?php
                $memoriesArray = iterator_to_array($res);
                if (count($memoriesArray) > 0): ?>
                    <?php foreach ($memoriesArray as $row):
                        $createdAt = $row['created_at']->toDateTime()->format('Y-m-d H:i:s');
                    ?>
                        <div class="memory-card">
                            <h3><?= esc($row['title']) ?></h3>
                            <p class="pet-name">Pet: <?= esc($row['pet_name'] ?? 'Unknown') ?></p>
                            <p><?= esc($row['description']) ?></p>
                            <?php if ($row['photo']): ?>
                                <img src="<?= esc($row['photo']) ?>" alt="Memory Photo">
                            <?php endif; ?>
                            <div class="memory-date"><?= date('M d, Y', $row['created_at']->toDateTime()->getTimestamp()) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No memories found. Create your first pet memory!</p>
                <?php endif; ?>
            </div>
    </main>
</body>

</html>