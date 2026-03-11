<?php
require __DIR__ . '/../functions.php';
if (!is_admin()) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mongoDelete('memories', ['_id' => stringToObjectId($id)]);
    header("Location: manage_memories.php?success=deleted");
    exit;
}

$res = mongoAggregate('memories', [
    [
        '$lookup' => [
            'from' => 'users',
            'localField' => 'user_id',
            'foreignField' => '_id',
            'as' => 'user'
        ]
    ],
    ['$unwind' => '$user'],
    [
        '$lookup' => [
            'from' => 'pets',
            'localField' => 'pet_id',
            'foreignField' => '_id',
            'as' => 'pet'
        ]
    ],
    [
        '$unwind' => [
            'path' => '$pet',
            'preserveNullAndEmptyArrays' => true
        ]
    ],
    ['$sort' => ['created_at' => -1]],
    [
        '$project' => [
            'title' => 1,
            'description' => 1,
            'photo' => 1,
            'created_at' => 1,
            'owner' => '$user.name',
            'pet_name' => '$pet.pet_name'
        ]
    ]
]);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Manage Memories - Admin</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <header class="top">
        <h1>Admin Panel</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php">Users</a>
            <a href="manage_admins.php">Admins</a>
            <a href="manage_pets.php">Pets</a>
            <a href="manage_reminders.php">Reminders</a>
            <a href="manage_memories.php">Memories</a>
            <a href="manage_articles.php">Articles</a>
            <a href="manage_adoptions.php">Adoptions</a>
            <a href="manage_feed.php">Feed Guidelines</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <h2>Manage Pet Memories</h2>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
            <div class="alert alert-success">Memory deleted successfully!</div>
        <?php endif; ?>

        <div class="memories-grid admin">
            <?php
            $memoriesArray = mongoResultToArray($res);
            if (count($memoriesArray) > 0): ?>
                <?php foreach ($memoriesArray as $row): ?>
                    <div class="memory-card">
                        <h3><?= esc($row['title']) ?></h3>
                        <p><strong>Owner:</strong> <?= esc($row['owner']) ?></p>
                        <p><strong>Pet:</strong> <?= esc($row['pet_name'] ?: 'Unknown') ?></p>
                        <p><?= esc($row['description']) ?></p>
                        <?php if ($row['photo']): ?>
                            <img src="../<?= esc($row['photo']) ?>" alt="Memory Photo">
                        <?php endif; ?>
                        <div class="memory-date"><?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                        <div class="actions">
                            <a href="?delete=<?= (string)$row['_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this memory?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No memories found in the system.</p>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>