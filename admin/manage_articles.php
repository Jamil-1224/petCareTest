<?php
require '../functions.php';
if (!is_admin()) {
    header("Location: login.php");
    exit;
}

// Handle article deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mongoDelete('articles', ['_id' => stringToObjectId($id)]);
    header("Location: manage_articles.php?success=deleted");
    exit;
}

// Handle article submission
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $admin_id = $_SESSION['user_id']; // Using user_id for admin

    $insertId = mongoInsert('articles', [
        'admin_id' => stringToObjectId($admin_id),
        'title' => $title,
        'content' => $content,
        'created_at' => getCurrentDateTime()
    ]);

    if ($insertId) {
        $success_msg = "Article posted successfully!";
    } else {
        $error = "Failed to post article. Please try again.";
    }
}

// Get all articles
$res = mongoFind('articles', [], ['sort' => ['created_at' => -1]]);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Manage Articles - Admin</title>
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
        <h2>Manage Articles</h2>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
            <div class="alert alert-success">Article deleted successfully!</div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Add New Article</h3>
            <form method="post" class="form">
                <div class="form-group">
                    <label for="title">Article Title</label>
                    <input type="text" id="title" name="title" placeholder="Article Title" required>
                </div>

                <div class="form-group">
                    <label for="content">Article Content</label>
                    <textarea id="content" name="content" placeholder="Write content" rows="6" required></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Post Article</button>
                </div>
            </form>
        </div>

        <div class="articles-list admin">
            <h3>Existing Articles</h3>
            <?php
            $articlesArray = mongoResultToArray($res);
            if (count($articlesArray) > 0): ?>
                <?php foreach ($articlesArray as $article): ?>
                    <article class="article-card">
                        <h3><?= esc($article['title']) ?></h3>
                        <div class="article-date"><?= date('M d, Y', strtotime($article['created_at'])) ?></div>
                        <div class="article-content">
                            <?= nl2br(esc($article['content'])) ?>
                        </div>
                        <div class="actions">
                            <a href="?delete=<?= (string)$article['_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this article?')">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No articles available. Create your first article!</p>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>