<?php
require 'functions.php';
require_login();
$uid = $_SESSION['user_id'];
$msg = '';
$sms_status = '';

// Get user phone number
$user = mongoFindOne('users', ['_id' => stringToObjectId($uid)]);
$phone_number = $user['phone'] ?? '';

// fetch pets for select
$pets = mongoFind('pets', ['user_id' => stringToObjectId($uid)]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id = $_POST['pet_id'];
    $type = $_POST['type'] ?? 'other';
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $rdate = $_POST['reminder_date'] ?: null;
    $rtime = $_POST['reminder_time'] ?: null;
    $send_sms = isset($_POST['send_sms']) ? 1 : 0;

    $insertId = mongoInsert('reminders', [
        'user_id' => stringToObjectId($uid),
        'pet_id' => stringToObjectId($pet_id),
        'reminder_type' => $type,
        'title' => $title,
        'description' => $desc,
        'reminder_date' => stringToDateTime($rdate),
        'reminder_time' => $rtime,
        'status' => 'pending',
        'send_sms' => (bool)$send_sms,
        'sms_sent' => false,
        'created_at' => getCurrentDateTime(),
        'updated_at' => getCurrentDateTime(),
        'repeat_interval' => 'none',
        'sent_status' => 'pending'
    ]);

    if ($insertId) {
        $msg = "Reminder added successfully!";

        // Inform user if SMS is scheduled
        if ($send_sms && !empty($phone_number)) {
            $sms_status = "📱 SMS will be sent automatically at $rdate $rtime";
        } elseif ($send_sms && empty($phone_number)) {
            $sms_status = "⚠️ SMS enabled but no phone number in profile. Please update your profile to receive SMS.";
        }
    } else {
        $msg = "Error adding reminder!";
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Reminder | PetCare</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #4a90e2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }

        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
        }

        button {
            background: #4a90e2;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #3a7bc8;
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

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        .nav-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .nav-links a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 600;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <header class="top">
        <h1>Pet Care Reminder</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="reminders.php">My Reminders</a>
            <a href="pets.php">My Pets</a>
        </nav>
    </header>
    <main class="container">
        <div class="page-header">
            <h2>Create New Reminder</h2>
            <p>Set up reminders for your pet's care needs with optional SMS notifications</p>
        </div>

        <div class="form-container">
            <?php if ($msg): ?>
                <div class="alert alert-success"><?= esc($msg) ?></div>
            <?php endif; ?>

            <?php if ($sms_status): ?>
                <div class="alert alert-info"><?= esc($sms_status) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="pet_id">Select Pet</label>
                    <select id="pet_id" name="pet_id" required>
                        <option value="">-- Choose a pet --</option>
                        <?php foreach ($pets as $row): ?>
                            <option value="<?= esc((string)$row['_id']) ?>"><?= esc($row['pet_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type">Reminder Type</label>
                    <select id="type" name="type">
                        <option value="feeding">Feeding</option>
                        <option value="medication">Medication</option>
                        <option value="vaccination">Vaccination</option>
                        <option value="grooming">Grooming</option>
                        <option value="appointment">Appointment</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Title</label>
                    <input id="title" name="title" required placeholder="Enter a title for this reminder">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Add details about this reminder"></textarea>
                </div>

                <div class="form-group">
                    <label for="reminder_date">Date</label>
                    <input type="date" id="reminder_date" name="reminder_date">
                </div>

                <div class="form-group">
                    <label for="reminder_time">Time</label>
                    <input type="time" id="reminder_time" name="reminder_time">
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="send_sms" name="send_sms" <?php echo !empty($phone_number) ? '' : 'disabled'; ?>>
                    <label for="send_sms">
                        Send SMS notification at scheduled time
                        <?php if (empty($phone_number)): ?>
                            <span style="color: #dc3545;">(No phone number found in your profile)</span>
                        <?php endif; ?>
                    </label>
                </div>
                <?php if (!empty($phone_number)): ?>
                    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 12px; border-radius: 6px; margin-top: 10px; font-size: 14px;">
                        <strong>ℹ️ Note:</strong> SMS will be sent automatically at the scheduled date and time, not immediately.
                        <br>Your phone: <strong><?= htmlspecialchars($phone_number) ?></strong>
                    </div>
                <?php else: ?>
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; border-radius: 6px; margin-top: 10px; font-size: 14px;">
                        <strong>⚠️ Warning:</strong> To receive SMS notifications, please <a href="profile.php" style="color: #856404; font-weight: bold;">update your phone number</a> in your profile.
                    </div>
                <?php endif; ?>

                <div style="margin-top: 25px;">
                    <button type="submit">Save Reminder</button>
                </div>
            </form>

            <div class="nav-links">
                <a href="reminders.php">← Back to Reminders</a>
            </div>
        </div>
    </main>
</body>

</html>