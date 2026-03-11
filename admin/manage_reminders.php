<?php
require '../functions.php';
require '../twilio_helper.php';
if (!is_admin()) {
    header("Location: login.php");
    exit;
}

$msg = '';
$sms_status = '';

// Auto-check for due reminders and send SMS
$now = getCurrentDateTime();

// Find all pending reminders that are due using aggregation
$dueReminders = mongoAggregate('reminders', [
    [
        '$match' => [
            'status' => 'pending',
            'reminder_date' => ['$lte' => $now],
            'send_sms' => 1,
            '$or' => [
                ['sms_sent' => 0],
                ['sms_sent' => ['$exists' => false]]
            ]
        ]
    ],
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
    [
        '$project' => [
            'title' => 1,
            'description' => 1,
            'reminder_date' => 1,
            'reminder_time' => 1,
            'phone' => '$user.phone',
            'pet_name' => '$pet.pet_name'
        ]
    ]
]);

$updated_count = 0;
$sms_sent_count = 0;

foreach ($dueReminders as $reminder) {
    // Update reminder status to completed
    $updateResult = mongoUpdate(
        'reminders',
        ['_id' => $reminder['_id']],
        ['$set' => [
            'status' => 'completed',
            'sms_sent' => 1,
            'sms_sent_date' => getCurrentDateTime()
        ]]
    );

    if ($updateResult) {
        $updated_count++;

        // Send SMS if phone number exists
        if (!empty($reminder['phone']) && $reminder['send_sms'] == 1) {
            $sms_message = "PetCare Reminder: {$reminder['title']} for {$reminder['pet_name']} on {$reminder['reminder_date']->toDateTime()->format('Y-m-d')} at {$reminder['reminder_time']}. Details: {$reminder['description']}";

            if (send_sms($reminder['phone'], $sms_message)) {
                $sms_sent_count++;
            }
        }
    }
}

// If any reminders were updated or SMS sent, add a message
if ($updated_count > 0) {
    $msg = "$updated_count reminder(s) automatically marked as completed.";
    if ($sms_sent_count > 0) {
        $sms_status = "$sms_sent_count SMS notification(s) sent.";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM reminders WHERE reminder_id=?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header("Location: manage_reminders.php?success=deleted");
    } else {
        header("Location: manage_reminders.php?error=1");
    }
    exit;
}

if (isset($_GET['send_sms'])) {
    $id = (int)$_GET['send_sms'];

    // Get reminder details
    $r_stmt = $conn->prepare("SELECT r.*, u.phone, u.name AS owner, p.pet_name 
                             FROM reminders r 
                             JOIN users u ON r.user_id=u.user_id 
                             JOIN pets p ON r.pet_id=p.pet_id 
                             WHERE r.reminder_id=?");
    $r_stmt->bind_param('i', $id);
    $r_stmt->execute();
    $r_result = $r_stmt->get_result();

    if ($r_data = $r_result->fetch_assoc()) {
        if (!empty($r_data['phone'])) {
            $sms_message = "PetCare Reminder: {$r_data['title']} for {$r_data['pet_name']} on {$r_data['reminder_date']} at {$r_data['reminder_time']}. Details: {$r_data['description']}";

            if (send_sms($r_data['phone'], $sms_message)) {
                header("Location: manage_reminders.php?sms_sent=1&user=" . urlencode($r_data['owner']));
            } else {
                header("Location: manage_reminders.php?sms_error=1&user=" . urlencode($r_data['owner']));
            }
        } else {
            header("Location: manage_reminders.php?no_phone=1&user=" . urlencode($r_data['owner']));
        }
    }
    exit;
}

// Success/error messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'deleted') {
        $msg = "Reminder deleted successfully!";
    }
}

if (isset($_GET['sms_sent'])) {
    $user = $_GET['user'] ?? 'User';
    $sms_status = "SMS notification sent to $user's phone!";
} elseif (isset($_GET['sms_error'])) {
    $user = $_GET['user'] ?? 'User';
    $sms_status = "Failed to send SMS notification to $user. Please check the phone number.";
} elseif (isset($_GET['no_phone'])) {
    $user = $_GET['user'] ?? 'User';
    $sms_status = "No phone number found for $user.";
}

$res = $conn->query("SELECT r.*, u.name AS owner, u.phone, p.pet_name 
                     FROM reminders r 
                     JOIN users u ON r.user_id=u.user_id 
                     JOIN pets p ON r.pet_id=p.pet_id 
                     ORDER BY r.reminder_date ASC, r.reminder_time ASC");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reminders | Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .page-title {
            margin: 0;
            color: #333;
            font-size: 28px;
        }

        .reminder-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .reminder-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
        }

        .reminder-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .reminder-table tr:last-child td {
            border-bottom: none;
        }

        .reminder-table tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .action-btn {
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.3s;
            display: inline-block;
            margin-right: 5px;
        }

        .btn-sms {
            color: #17a2b8;
            background: rgba(23, 162, 184, 0.1);
        }

        .btn-sms:hover {
            background: rgba(23, 162, 184, 0.2);
        }

        .btn-delete {
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .btn-delete:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 30px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #888;
        }

        .description-cell {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .view-details {
            cursor: pointer;
            color: #4a90e2;
            text-decoration: underline;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: #333;
        }

        @media (max-width: 768px) {
            .reminder-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>
    <header class="top">
        <h1>Admin Panel</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php">Users</a>
            <a href="manage_pets.php">Pets</a>
            <a href="manage_memories.php">Memories</a>
            <a href="manage_articles.php">Articles</a>
        </nav>
    </header>
    <main class="admin-container">
        <div class="page-header">
            <h2 class="page-title">Manage Reminders</h2>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success"><?= esc($msg) ?></div>
        <?php endif; ?>

        <?php if ($sms_status): ?>
            <div class="alert alert-info"><?= esc($sms_status) ?></div>
        <?php endif; ?>

        <?php if ($res->num_rows > 0): ?>
            <table class="reminder-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Owner</th>
                        <th>Pet</th>
                        <th>Date/Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= esc($r['title']) ?></td>
                            <td><?= esc(ucfirst($r['reminder_type'])) ?></td>
                            <td><?= esc($r['owner']) ?></td>
                            <td><?= esc($r['pet_name']) ?></td>
                            <td>
                                <?php if ($r['reminder_date']): ?>
                                    <?= esc($r['reminder_date']) ?>
                                    <?php if ($r['reminder_time']): ?>
                                        at <?= esc($r['reminder_time']) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    No date set
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $r['status'] == 'pending' ? 'badge-pending' : 'badge-completed' ?>">
                                    <?= esc(ucfirst($r['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="view-details" onclick="showDetails(<?= $r['reminder_id'] ?>)">View Details</span>
                                <?php if (!empty($r['phone'])): ?>
                                    <a href="?send_sms=<?= $r['reminder_id'] ?>" class="action-btn btn-sms">Send SMS</a>
                                <?php endif; ?>
                                <a href="?delete=<?= $r['reminder_id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this reminder?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <h3>No Reminders Found</h3>
                <p>There are currently no reminders in the system.</p>
            </div>
        <?php endif; ?>

        <!-- Modal for reminder details -->
        <div id="reminderModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <h3 id="modalTitle"></h3>
                <div id="modalContent"></div>
            </div>
        </div>

        <script>
            // Store reminder data for modal display
            const reminderData = {};

            <?php
            // Reset the result pointer
            $res->data_seek(0);
            while ($r = $res->fetch_assoc()):
            ?>
                reminderData[<?= $r['reminder_id'] ?>] = {
                    title: "<?= esc($r['title']) ?>",
                    type: "<?= esc($r['reminder_type']) ?>",
                    owner: "<?= esc($r['owner']) ?>",
                    pet: "<?= esc($r['pet_name']) ?>",
                    date: "<?= esc($r['reminder_date'] ?: 'Not set') ?>",
                    time: "<?= esc($r['reminder_time'] ?: 'Not set') ?>",
                    description: `<?= esc($r['description']) ?>`,
                    status: "<?= esc($r['status']) ?>"
                };
            <?php endwhile; ?>

            function showDetails(id) {
                const data = reminderData[id];
                if (!data) return;

                document.getElementById('modalTitle').textContent = data.title;

                let content = `
                <p><strong>Type:</strong> ${data.type}</p>
                <p><strong>Owner:</strong> ${data.owner}</p>
                <p><strong>Pet:</strong> ${data.pet}</p>
                <p><strong>Date:</strong> ${data.date}</p>
                <p><strong>Time:</strong> ${data.time}</p>
                <p><strong>Status:</strong> ${data.status}</p>
                <p><strong>Description:</strong></p>
                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;">
                    ${data.description ? data.description.replace(/\n/g, '<br>') : 'No description provided.'}
                </div>
            `;

                document.getElementById('modalContent').innerHTML = content;
                document.getElementById('reminderModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('reminderModal').style.display = 'none';
            }

            // Close modal when clicking outside of it
            window.onclick = function(event) {
                const modal = document.getElementById('reminderModal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        </script>
    </main>
</body>

</html>