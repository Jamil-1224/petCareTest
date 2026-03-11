<?php
require __DIR__ . '/functions.php';
require_login();

$uid = $_SESSION['user_id'];
// Get user's pets for dropdown
$pets_result = mongoFind('pets', ['user_id' => stringToObjectId($uid)]);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uid = $_SESSION['user_id'];
    $pet_id = $_POST['pet_id'];
    $title = $_POST['title'];
    $desc = $_POST['description'];

    // Upload photo
    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        $photo = "uploads/" . time() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    $insertId = mongoInsert('memories', [
        'user_id' => stringToObjectId($uid),
        'pet_id' => stringToObjectId($pet_id),
        'title' => $title,
        'description' => $desc,
        'photo' => $photo,
        'created_at' => getCurrentDateTime()
    ]);

    if ($insertId) {
        header("Location: view_memories.php?success=1");
        exit;
    } else {
        $error = "Failed to add memory. Please try again.";
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Add Memory - Pet Care</title>
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
        <h2>Add New Memory</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form">
            <div class="form-group">
                <label for="pet_id">Select Pet</label>
                <select name="pet_id" id="pet_id" required>
                    <option value="">-- Select Pet --</option>
                    <?php foreach ($pets_result as $pet): ?>
                        <option value="<?= (string)$pet['_id'] ?>"><?= esc($pet['pet_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="title">Memory Title</label>
                <input type="text" id="title" name="title" placeholder="Memory Title" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Write your memory" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label for="photo">Photo (Optional)</label>
                <input type="file" id="photo" name="photo" accept="image/*">
            </div>

            <div class="form-actions">
                <a href="view_memories.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Memory</button>
            </div>
        </form>
    </main>
</body>

</html>