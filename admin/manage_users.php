<?php
require __DIR__ . '/../functions.php';
if (!is_admin()) {
  header("Location: login.php");
  exit;
}

if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  mongoDelete('users', ['_id' => stringToObjectId($id), 'role' => 'user']);
  header("Location: manage_users.php");
  exit;
}

$res = mongoFind('users', [], ['sort' => ['created_at' => -1]]);
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users</title>
  <link rel="stylesheet" href="../style.css">
</head>

<body>
  <header class="top">
    <h1>Admin Panel</h1>
    <nav><a href="dashboard.php">Dashboard</a></nav>
  </header>
  <main class="container">
    <h2>Manage Users</h2>
    <div class="list">
      <?php
      $usersArray = iterator_to_array($res);
      foreach ($usersArray as $u): ?>
        <div class="card item">
          <div class="right">
            <h3><?= esc($u['name']) ?> (<?= esc($u['role']) ?>)</h3>
            <p>Email: <?= esc($u['email']) ?> | Phone: <?= esc($u['phone']) ?></p>
            <p>Joined: <?= $u['created_at']->toDateTime()->format('Y-m-d H:i:s') ?></p>
            <?php if ($u['role'] == 'user'): ?>
              <a href="?delete=<?= (string)$u['_id'] ?>" class="danger" onclick="return confirm('Delete this user?')">Delete</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>

</html>