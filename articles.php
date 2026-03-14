<?php
require __DIR__ . '/functions.php';
require_login();

$res = mongoFind('articles', [], ['sort' => ['created_at' => -1]]);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles - Pet Care</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header class="top">
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="pets.php">Pets</a>
            <a href="reminders.php">Reminders</a>
            <a href="appointments.php">Appointments</a>
            <a href="view_memories.php">Memories</a>
            <a href="articles.php" class="active">Articles</a>
            <a href="adoption.php">Adoption</a>
            <a href="feed_guidelines.php">Feed Guidelines</a>
            <a href="messages.php">Messages</a>
            <a href="view_treatments.php">Treatments</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <h2>Pet Care Articles</h2>

        <div class="articles-list">
            <?php
            $articlesArray = iterator_to_array($res);
            if (count($articlesArray) > 0): ?>
                <?php foreach ($articlesArray as $article): ?>
                    <article class="article-card">
                        <h3><?= esc($article['title']) ?></h3>
                        <div class="article-date"><?= date('M d, Y', $article['created_at']->toDateTime()->getTimestamp()) ?></div>
                        <div class="article-content">
                            <?= nl2br(esc($article['content'])) ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No articles available at the moment.</p>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>