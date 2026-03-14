<?php
require __DIR__ . '/../functions.php';
if (!is_admin()) {
  header("Location: login.php");
  exit;
}

if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  mongoDelete('pets', ['_id' => stringToObjectId($id)]);
  header("Location: manage_pets.php");
  exit;
}

$res = mongoAggregate('pets', [
  [
    '$lookup' => [
      'from' => 'users',
      'localField' => 'user_id',
      'foreignField' => '_id',
      'as' => 'user'
    ]
  ],
  ['$unwind' => '$user'],
  ['$sort' => ['created_at' => -1]],
  [
    '$project' => [
      'pet_name' => 1,
      'pet_type' => 1,
      'breed' => 1,
      'age' => 1,
      'gender' => 1,
      'photo' => 1,
      'created_at' => 1,
      'owner' => '$user.name'
    ]
  ]
]);
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Pets</title>
  <link rel="stylesheet" href="../style.css">
</head>

<body>
  <header class="top">
    <h1>Admin Panel</h1>
    <nav><a href="dashboard.php">Dashboard</a></nav>
  </header>
  <main class="container">
    <h2>Manage Pets</h2>
    <div class="list">
      <?php
      $petsArray = iterator_to_array($res);
      foreach ($petsArray as $p): ?>
        <div class="card item">
          <?php if ($p['photo']): ?><img src="../<?= esc($p['photo']) ?>" class="petimg"><?php endif; ?>
          <div class="right">
            <h3><?= esc($p['pet_name']) ?> (<?= esc($p['pet_type']) ?>)</h3>
            <p>Owner: <?= esc($p['owner']) ?></p>
            <p>Breed: <?= esc($p['breed']) ?> | Age: <?= esc($p['age']) ?> | Gender: <?= esc($p['gender']) ?></p>
            <a href="?delete=<?= (string)$p['_id'] ?>" class="danger" onclick="return confirm('Delete this pet?')">Delete</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>

</html>