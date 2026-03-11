<?php
require '../functions.php';
if (!is_admin()) {
  header("Location: login.php");
  exit;
}

// Delete adoption listing
if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  mongoDelete('adoptions', ['_id' => stringToObjectId($id)]);
}

// Fetch all adoptions
$res = mongoAggregate('adoptions', [
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
      'pet_age' => 1,
      'pet_type' => 1,
      'photo' => 1,
      'description' => 1,
      'contact_info' => 1,
      'location' => 1,
      'city' => 1,
      'latitude' => 1,
      'longitude' => 1,
      'status' => 1,
      'created_at' => 1,
      'owner' => '$user.name'
    ]
  ]
]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Adoptions | PetCare Admin</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .adoption-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 2rem;
      border-radius: 15px;
      margin-bottom: 2rem;
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .adoption-header h2 {
      color: white;
      margin: 0;
      font-size: 2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .adoption-header h2::before {
      content: '🐾';
      font-size: 2.5rem;
    }

    .adoption-stats {
      display: flex;
      gap: 1.5rem;
      margin-top: 1rem;
      flex-wrap: wrap;
    }

    .stat-badge {
      background: rgba(255, 255, 255, 0.2);
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      backdrop-filter: blur(10px);
    }

    .table-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .table-responsive {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    thead th {
      padding: 1.2rem 1rem;
      text-align: left;
      font-weight: 700;
      color: #2d3436;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 3px solid #667eea;
    }

    tbody tr {
      border-bottom: 1px solid #f1f3f5;
      transition: all 0.3s ease;
    }

    tbody tr:hover {
      background: #f8f9ff;
      transform: scale(1.01);
      box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
    }

    tbody td {
      padding: 1rem;
      color: #495057;
      vertical-align: middle;
    }

    .pet-photo {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }

    .pet-photo:hover {
      transform: scale(1.5);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      cursor: pointer;
    }

    .no-photo {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #dfe6e9 0%, #b2bec3 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
    }

    .pet-type-badge {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .badge-dog {
      background: #fff3cd;
      color: #856404;
    }

    .badge-cat {
      background: #d1ecf1;
      color: #0c5460;
    }

    .badge-bird {
      background: #d4edda;
      color: #155724;
    }

    .badge-other {
      background: #e2e3e5;
      color: #383d41;
    }

    .contact-info {
      font-size: 0.9rem;
      color: #667eea;
      font-weight: 500;
    }

    .owner-name {
      font-weight: 600;
      color: #2d3436;
    }

    .btn-delete {
      background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
      color: white;
      border: none;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      font-size: 0.9rem;
    }

    .btn-delete:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
    }

    .id-badge {
      background: #667eea;
      color: white;
      padding: 0.4rem 0.8rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.85rem;
    }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: #95a5a6;
    }

    .empty-state::before {
      content: '🐶';
      font-size: 5rem;
      display: block;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .empty-state h3 {
      color: #636e72;
      margin-bottom: 0.5rem;
    }

    .location-info {
      font-size: 0.9rem;
      line-height: 1.6;
    }

    .location-address {
      color: #2d3436;
      font-weight: 500;
      margin-bottom: 0.3rem;
    }

    .location-coords {
      color: #636e72;
      font-size: 0.8rem;
      font-family: 'Courier New', monospace;
    }

    .btn-view-map {
      background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
      color: white;
      border: none;
      padding: 0.4rem 0.8rem;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      margin-top: 0.3rem;
    }

    .btn-view-map:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 184, 148, 0.4);
    }

    .no-location {
      color: #95a5a6;
      font-style: italic;
      font-size: 0.85rem;
    }

    @media (max-width: 1024px) {
      .adoption-stats {
        gap: 1rem;
      }

      .stat-badge {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
      }

      tbody td {
        font-size: 0.85rem;
      }

      .pet-photo,
      .no-photo {
        width: 70px;
        height: 70px;
      }
    }

    @media (max-width: 768px) {
      .adoption-header {
        padding: 1.5rem;
      }

      .adoption-header h2 {
        font-size: 1.5rem;
      }

      .adoption-stats {
        flex-direction: column;
        gap: 0.5rem;
      }

      .stat-badge {
        font-size: 0.8rem;
      }

      thead th {
        padding: 1rem 0.5rem;
        font-size: 0.75rem;
      }

      tbody td {
        padding: 0.8rem 0.4rem;
        font-size: 0.8rem;
      }

      .pet-photo,
      .no-photo {
        width: 60px;
        height: 60px;
      }

      .location-info {
        font-size: 0.8rem;
      }

      .location-coords {
        font-size: 0.7rem;
      }

      .btn-view-map,
      .btn-delete {
        padding: 0.4rem 0.6rem;
        font-size: 0.75rem;
      }

      .pet-type-badge {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
      }
    }

    @media (max-width: 480px) {
      .adoption-header {
        padding: 1rem;
      }

      .adoption-header h2 {
        font-size: 1.2rem;
      }

      .table-responsive {
        font-size: 0.75rem;
      }

      .pet-photo,
      .no-photo {
        width: 50px;
        height: 50px;
      }

      thead th,
      tbody td {
        padding: 0.5rem 0.3rem;
      }
    }
  </style>
</head>

<body>
  <header class="top">
    <h1>PetCare Admin</h1>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="manage_users.php">Users</a>
      <a href="manage_admins.php">Admins</a>
      <a href="manage_doctors.php">Doctors</a>
      <a href="manage_pets.php">Pets</a>
      <a href="manage_memories.php">Memories</a>
      <a href="manage_articles.php">Articles</a>
      <a href="manage_reminders.php">Reminders</a>
      <a href="manage_adoptions.php">Adoptions</a>
      <a href="manage_feed.php">Feed Guidelines</a>
      <a href="../logout.php">Logout</a>
    </nav>
  </header>

  <main class="container">
    <?php $adoptionsArray = mongoResultToArray($res); ?>
    <div class="adoption-header">
      <h2>Manage Adoption Listings</h2>
      <div class="adoption-stats">
        <div class="stat-badge">
          📊 Total Listings: <?= count($adoptionsArray) ?>
        </div>
        <div class="stat-badge">
          ⏰ Last Updated: <?= date('M d, Y') ?>
        </div>
      </div>
    </div>

    <div class="table-container">
      <div class="table-responsive">
        <?php
        if (count($adoptionsArray) > 0): ?>
          <table>
            <thead>
              <tr>
                <th>Photo</th>
                <th>Pet Name</th>
                <th>Age</th>
                <th>Type</th>
                <th>Location</th>
                <th>Owner</th>
                <th>Contact</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($adoptionsArray as $a): ?>
                <tr>
                  <td>
                    <?php if ($a['photo']): ?>
                      <img src="../<?= htmlspecialchars($a['photo']) ?>" alt="<?= htmlspecialchars($a['pet_name']) ?>" class="pet-photo">
                    <?php else: ?>
                      <div class="no-photo">🐾</div>
                    <?php endif; ?>
                  </td>
                  <td><strong><?= htmlspecialchars($a['pet_name']) ?></strong></td>
                  <td><?= htmlspecialchars($a['pet_age']) ?> years</td>
                  <td>
                    <?php
                    $type = strtolower($a['pet_type']);
                    $badgeClass = 'badge-other';
                    if (strpos($type, 'dog') !== false) $badgeClass = 'badge-dog';
                    elseif (strpos($type, 'cat') !== false) $badgeClass = 'badge-cat';
                    elseif (strpos($type, 'bird') !== false) $badgeClass = 'badge-bird';
                    ?>
                    <span class="pet-type-badge <?= $badgeClass ?>">
                      <?= htmlspecialchars($a['pet_type']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($a['city']) || !empty($a['location'])): ?>
                      <div class="location-info">
                        <div class="location-address">
                          📍 <?php
                              $locationParts = array_filter([$a['location'] ?? '', $a['city'] ?? '']);
                              echo htmlspecialchars(implode(', ', $locationParts));
                              ?>
                        </div>
                        <?php if ($a['latitude'] && $a['longitude']): ?>
                          <div class="location-coords">
                            <?= number_format($a['latitude'], 6) ?>°N, <?= number_format($a['longitude'], 6) ?>°E
                          </div>
                          <a href="https://www.openstreetmap.org/?mlat=<?= $a['latitude'] ?>&mlon=<?= $a['longitude'] ?>&zoom=15"
                            target="_blank"
                            class="btn-view-map">
                            🗺️ View on Map
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <span class="no-location">📍 No location set</span>
                    <?php endif; ?>
                  </td>
                  <td class="owner-name"><?= htmlspecialchars($a['owner']) ?></td>
                  <td class="contact-info"><?= htmlspecialchars($a['contact_info']) ?></td>
                  <td>
                    <a href="?delete=<?= (string)$a['_id'] ?>"
                      onclick="return confirm('⚠️ Are you sure you want to delete this adoption listing for <?= htmlspecialchars($a['pet_name']) ?>?')"
                      class="btn-delete">
                      🗑️ Delete
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-state">
            <h3>No Adoption Listings Found</h3>
            <p>There are currently no pets available for adoption.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>

</html>