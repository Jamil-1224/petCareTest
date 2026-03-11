<?php
require 'functions.php';
require_login();
$uid = $_SESSION['user_id'];
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pet_name = trim($_POST['pet_name']);
  $pet_type = trim($_POST['pet_type']);
  $breed = trim($_POST['breed']);
  $age = (int)($_POST['age'] ?? 0);
  $gender = $_POST['gender'] ?? 'male';
  $photo = upload_image($_FILES['photo'] ?? null);

  $insertId = mongoInsert('pets', [
    'user_id' => stringToObjectId($uid),
    'pet_name' => $pet_name,
    'pet_type' => $pet_type,
    'breed' => $breed,
    'age' => $age,
    'gender' => $gender,
    'photo' => $photo,
    'created_at' => getCurrentDateTime()
  ]);

  if ($insertId) {
    $msg = "Pet added successfully!";
  } else {
    $msg = "Error adding pet!";
  }
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Add Pet</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <header class="top">
    <h1>Pet Care Reminder</h1>
    <nav><a href="dashboard.php">Dashboard</a></nav>
  </header>
  <main class="container">
    <div class="card">
      <h2>Add Pet</h2>
      <?php if ($msg) echo "<div class='success'>" . esc($msg) . "</div>"; ?>
      <form method="post" enctype="multipart/form-data">
        <label>Pet Name</label><input name="pet_name" required>
        <label>Pet Type</label><input name="pet_type" placeholder="Dog, Cat" required>
        <label>Breed</label><input name="breed">
        <label>Age</label><input name="age" type="number" min="0">
        <label>Gender</label>
        <select name="gender">
          <option>male</option>
          <option>female</option>
        </select>
        <label>Photo (optional)</label><input type="file" name="photo" accept="image/*">
        <button type="submit">Save Pet</button>
      </form>
    </div>
  </main>
</body>

</html>