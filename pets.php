<?php
require __DIR__ . '/functions.php';
require_login();
$uid = $_SESSION['user_id'];

if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  mongoDelete('pets', [
    '_id' => stringToObjectId($id),
    'user_id' => stringToObjectId($uid)
  ]);
  header("Location: pets.php");
  exit;
}

$res = mongoFind(
  'pets',
  ['user_id' => stringToObjectId($uid)],
  ['sort' => ['created_at' => -1]]
);

// Get current page for active nav
$current_page = 'pets.php';
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>My Pets - Pet Care</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
  <header class="top">
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="pets.php" class="active">Pets</a>
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
    <h2>My Pets</h2>
    <a class="btn" href="add_pet.php">Add Pet</a>
    <div class="list">
      <?php foreach ($res as $p): ?>
        <div class="card item">
          <div class="left">
            <?php if ($p['photo']): ?><img src="<?= esc($p['photo']) ?>" alt="photo" class="petimg"><?php endif; ?>
          </div>
          <div class="right">
            <h3><?= esc($p['pet_name']) ?> (<?= esc($p['pet_type']) ?>)</h3>
            <p>Breed: <?= esc($p['breed']) ?> | Age: <?= esc($p['age']) ?> | Gender: <?= esc($p['gender']) ?></p>
            <a href="pets.php?delete=<?= esc((string)$p['_id']) ?>" class="danger" onclick="return confirm('Delete pet?')">Delete</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>

</html>