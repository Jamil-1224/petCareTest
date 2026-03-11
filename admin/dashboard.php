<?php
require __DIR__ . '/../functions.php';
if (!is_admin()) {
    header("Location: login.php");
    exit;
}

$users = mongoCount('users');
$pets = mongoCount('pets');
$memories = mongoCount('memories');
$reminders = mongoCount('reminders');
$articles = mongoCount('articles');
$pending_doctors = mongoCount('doctors', ['verification_status' => 'pending']);

// Get recent reminders
$recent_reminders_cursor = mongoAggregate('reminders', [
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
    ['$unwind' => '$pet'],
    ['$sort' => ['reminder_date' => 1]],
    ['$limit' => 5],
    [
        '$project' => [
            'reminder_date' => 1,
            'reminder_time' => 1,
            'note' => 1,
            'status' => 1,
            'title' => 1,
            'owner' => '$user.name',
            'pet_name' => '$pet.pet_name'
        ]
    ]
]);
$recent_reminders = mongoResultToArray($recent_reminders_cursor);

// Get recent memories
$recent_memories_cursor = mongoAggregate('memories', [
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
    ['$limit' => 5],
    [
        '$project' => [
            'photo_path' => 1,
            'caption' => 1,
            'title' => 1,
            'created_at' => 1,
            'owner' => '$user.name',
            'pet_name' => '$pet.pet_name'
        ]
    ]
]);
$recent_memories = mongoResultToArray($recent_memories_cursor);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | PetCare</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .welcome-banner h2 {
            color: white;
            margin-bottom: 0.5rem;
        }

        .welcome-banner p {
            opacity: 0.9;
            margin-bottom: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--gray-200);

            /* new: make card a column flex container so the button can sit at the bottom */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 140px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .stat-card h3 {
            color: var(--gray-700);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            line-height: 1;
        }

        /* ensure button stays at the bottom when content grows */
        .stat-card .btn {
            margin-top: auto;
            width: 100%;
            padding: 0.6rem;
        }

        /* pets-specific design update */
        .stat-card.pets {
            border-left: 4px solid var(--success);
            background: linear-gradient(180deg, rgba(46, 204, 113, 0.04) 0%, rgba(255, 255, 255, 0.8) 100%);
        }

        /* optional users-specific slight accent */
        .stat-card.users {
            border-left: 4px solid var(--primary);
        }

        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .dashboard-section {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .section-title {
            margin: 0;
            font-size: 1.25rem;
            color: var(--gray-800);
        }

        .section-link {
            font-size: 0.875rem;
            color: var(--primary);
        }

        .item-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .list-item {
            padding: 0.75rem;
            border-radius: var(--radius-sm);
            background: var(--gray-100);
            border-left: 3px solid var(--primary);
        }

        .list-item h4 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
        }

        .list-item p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .badge-primary {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary);
        }

        .badge-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray-500);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-sections {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
    <main class="dashboard-container">
        <div class="welcome-banner">
            <h2>Welcome to PetCare Admin</h2>
            <p>Manage your pet care platform from this central dashboard.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card users">
                <h3>Users</h3>
                <div class="stat-value"><?= $users ?></div>
                <a href="manage_users.php" class="btn btn-primary">Manage</a>
            </div>
            <div class="stat-card pets">
                <h3>Pets</h3>
                <div class="stat-value"><?= $pets ?></div>
                <a href="manage_pets.php" class="btn btn-primary">Manage</a>
            </div>
            <div class="stat-card">
                <h3>Memories</h3>
                <div class="stat-value"><?= $memories ?></div>
                <a href="manage_memories.php" class="btn btn-primary">Manage</a>
            </div>
            <div class="stat-card">
                <h3>Reminders</h3>
                <div class="stat-value"><?= $reminders ?></div>
                <a href="manage_reminders.php" class="btn btn-primary">Manage</a>
            </div>
            <div class="stat-card">
                <h3>Articles</h3>
                <div class="stat-value"><?= $articles ?></div>
                <a href="manage_articles.php" class="btn btn-primary">Manage</a>
            </div>
            <div class="stat-card">
                <h3>Pending Doctors</h3>
                <div class="stat-value"><?= $pending_doctors ?></div>
                <a href="manage_doctors.php" class="btn btn-primary">Manage</a>
            </div>
        </div>

        <div class="dashboard-sections">
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Reminders</h3>
                    <a href="manage_reminders.php" class="section-link">View All</a>
                </div>

                <div class="item-list">
                    <?php if (count($recent_reminders) > 0): ?>
                        <?php foreach ($recent_reminders as $reminder): ?>
                            <div class="list-item">
                                <h4>
                                    <?= esc($reminder['title']) ?>
                                    <span class="badge <?= $reminder['status'] == 'pending' ? 'badge-warning' : 'badge-success' ?>">
                                        <?= ucfirst(esc($reminder['status'])) ?>
                                    </span>
                                </h4>
                                <p>
                                    <strong>Pet:</strong> <?= esc($reminder['pet_name']) ?> |
                                    <strong>Owner:</strong> <?= esc($reminder['owner']) ?> |
                                    <strong>Date:</strong> <?= esc($reminder['reminder_date']) ?> <?= esc($reminder['reminder_time']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No reminders found</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Memories</h3>
                    <a href="manage_memories.php" class="section-link">View All</a>
                </div>

                <div class="item-list">
                    <?php if (count($recent_memories) > 0): ?>
                        <?php foreach ($recent_memories as $memory): ?>
                            <div class="list-item">
                                <h4><?= esc($memory['title']) ?></h4>
                                <p>
                                    <strong>Pet:</strong> <?= esc($memory['pet_name']) ?> |
                                    <strong>Owner:</strong> <?= esc($memory['owner']) ?> |
                                    <strong>Added:</strong> <?= esc(date('M d, Y', strtotime($memory['created_at']))) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No memories found</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>

</html>