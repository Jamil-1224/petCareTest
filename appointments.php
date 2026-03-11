<?php
require __DIR__ . '/functions.php';
require_login();
$uid = $_SESSION['user_id'];
$msg = '';
$pets = mongoFind('pets', ['user_id' => stringToObjectId($uid)]);

// Get available doctors
$doctors_query = mongoFind(
  'doctors',
  ['status' => 'active'],
  ['sort' => ['full_name' => 1]]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pet_id = $_POST['pet_id'];
  $doctor_id = isset($_POST['doctor_id']) && !empty($_POST['doctor_id']) ? $_POST['doctor_id'] : null;
  $vet = trim($_POST['vet_name']);
  $clinic = trim($_POST['clinic']);
  $adate = $_POST['appointment_date'] ?: null;
  $atime = $_POST['appointment_time'] ?: null;
  $notes = trim($_POST['notes']);
  $reason = trim($_POST['reason']);

  $appointmentData = [
    'user_id' => stringToObjectId($uid),
    'pet_id' => stringToObjectId($pet_id),
    'appointment_date' => stringToDateTime($adate),
    'appointment_time' => $atime,
    'notes' => $notes,
    'created_at' => getCurrentDateTime(),
    'updated_at' => getCurrentDateTime()
  ];

  if ($doctor_id) {
    // If doctor is selected, use doctor appointment
    $appointmentData['doctor_id'] = stringToObjectId($doctor_id);
    $appointmentData['reason'] = $reason;
    $appointmentData['appointment_status'] = 'pending';
  } else {
    // Otherwise use traditional appointment
    $appointmentData['vet_name'] = $vet;
    $appointmentData['clinic'] = $clinic;
  }

  $insertId = mongoInsert('appointments', $appointmentData);
  if ($insertId) $msg = "Appointment saved.";
  else $msg = "Error saving appointment!";
}

global $db;
$apps = $db->appointments->aggregate([
  ['$match' => ['user_id' => stringToObjectId($uid)]],
  ['$lookup' => [
    'from' => 'pets',
    'localField' => 'pet_id',
    'foreignField' => '_id',
    'as' => 'pet'
  ]],
  ['$lookup' => [
    'from' => 'doctors',
    'localField' => 'doctor_id',
    'foreignField' => '_id',
    'as' => 'doctor'
  ]],
  ['$unwind' => '$pet'],
  ['$unwind' => ['path' => '$doctor', 'preserveNullAndEmptyArrays' => true]],
  ['$addFields' => [
    'pet_name' => '$pet.pet_name',
    'doctor_name' => '$doctor.full_name',
    'specialization' => '$doctor.specialization'
  ]],
  ['$sort' => ['appointment_date' => -1]]
]);
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Appointments - Pet Care</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <header class="top">
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="pets.php">Pets</a>
      <a href="reminders.php">Reminders</a>
      <a href="appointments.php" class="active">Appointments</a>
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
    <h2>Appointments</h2>
    <?php if ($msg) echo "<div class='success'>" . esc($msg) . "</div>"; ?>
    <div class="card">
      <form method="post">
        <label>Pet</label><select name="pet_id"><?php foreach ($pets as $p): ?><option value="<?= esc((string)$p['_id']) ?>"><?= esc($p['pet_name']) ?></option><?php endforeach; ?></select>

        <div class="form-tabs">
          <div class="tab-header">
            <button type="button" class="tab-btn active" onclick="showTab('doctor-tab')">Book with Doctor</button>
            <button type="button" class="tab-btn" onclick="showTab('external-tab')">External Vet</button>
          </div>

          <div id="doctor-tab" class="tab-content active">
            <label>Select Doctor</label>
            <select name="doctor_id">
              <option value="">-- Select a Doctor --</option>
              <?php foreach ($doctors_query as $doc): ?>
                <option value="<?= esc((string)$doc['_id']) ?>"><?= esc($doc['full_name']) ?> (<?= esc($doc['specialization']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <label>Reason for Visit</label><textarea name="reason"></textarea>
          </div>

          <div id="external-tab" class="tab-content">
            <label>Vet Name</label><input name="vet_name">
            <label>Clinic</label><input name="clinic">
          </div>
        </div>

        <label>Date</label><input type="date" name="appointment_date" required>
        <label>Time</label><input type="time" name="appointment_time" required>
        <label>Notes</label><textarea name="notes"></textarea>
        <button type="submit">Save Appointment</button>
      </form>
    </div>

    <script>
      function showTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
          tab.classList.remove('active');
        });

        // Remove active class from all tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
          btn.classList.remove('active');
        });

        // Show the selected tab content
        document.getElementById(tabId).classList.add('active');

        // Add active class to the clicked button
        event.target.classList.add('active');
      }
    </script>

    <h3>Upcoming & Past</h3>
    <?php foreach ($apps as $a):
      $appointmentDate = $a['appointment_date']->toDateTime()->format('Y-m-d');
      $appointmentTime = $a['appointment_time'];
    ?>
      <div class="card item">
        <?php if (isset($a['doctor_id']) && $a['doctor_id']): ?>
          <h4><?= esc($a['pet_name']) ?> — Dr. <?= esc($a['doctor_name']) ?> (<?= esc($a['specialization']) ?>)</h4>
          <p><?= esc($appointmentDate) ?> <?= esc($appointmentTime) ?></p>
          <p><strong>Reason:</strong> <?= esc($a['reason'] ?? '') ?></p>
          <p><?= nl2br(esc($a['notes'])) ?></p>
          <p class="status-badge status-<?= esc($a['appointment_status'] ?? 'pending') ?>"><?= ucfirst(esc($a['appointment_status'] ?? 'pending')) ?></p>
        <?php else: ?>
          <h4><?= esc($a['pet_name']) ?> — <?= esc($a['clinic'] ?? 'N/A') ?></h4>
          <p><?= esc($appointmentDate) ?> <?= esc($appointmentTime) ?></p>
          <p><?= nl2br(esc($a['notes'])) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </main>
</body>

</html>