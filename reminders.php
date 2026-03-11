<?php
require __DIR__ . '/functions.php';
require_login();
$uid = $_SESSION['user_id'];
$msg = $err = '';

// Set timezone to match your location (adjust if needed)
date_default_timezone_set('Asia/Dhaka'); // Bangladesh timezone

// AUTO-COMPLETE AND SEND SMS WHEN TIME IS REACHED
global $db;

// Get current timestamp
$currentTimestamp = time();
$currentDateTimeStr = date('Y-m-d H:i:s', $currentTimestamp);

// Get all pending reminders
$pending_reminders = $db->reminders->aggregate([
    ['$match' => [
        'user_id' => stringToObjectId($uid),
        'status' => 'pending'
    ]],
    ['$lookup' => [
        'from' => 'users',
        'localField' => 'user_id',
        'foreignField' => '_id',
        'as' => 'user'
    ]],
    ['$lookup' => [
        'from' => 'pets',
        'localField' => 'pet_id',
        'foreignField' => '_id',
        'as' => 'pet'
    ]],
    ['$unwind' => '$user'],
    ['$unwind' => '$pet']
]);

$completed_count = 0;
$sms_count = 0;
$twilio_available = file_exists(__DIR__ . '/twilio_helper.php');

if ($twilio_available) {
    require_once __DIR__ . '/twilio_helper.php';
} else {
    error_log("WARNING: twilio_helper.php not found!");
}

foreach ($pending_reminders as $reminder) {
    // Get reminder date from MongoDB UTCDateTime
    // MongoDB stores in UTC, we need to convert to local timezone
    $reminderDateObj = $reminder['reminder_date'];
    $phpDateTime = $reminderDateObj->toDateTime();
    $phpDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));

    // Get just the date part (Y-m-d)
    $reminderDateOnly = $phpDateTime->format('Y-m-d');

    // Get reminder time (stored as string like "14:30:00")
    $reminderTime = $reminder['reminder_time'] ?? '00:00:00';

    // Combine date and time into full datetime string
    $reminderFullDateTimeStr = $reminderDateOnly . ' ' . $reminderTime;
    $reminderFullTimestamp = strtotime($reminderFullDateTimeStr);

    // Debug info (you can remove this later)
    // error_log("Reminder: {$reminder['title']}, Due: $reminderFullDateTimeStr ($reminderFullTimestamp), Now: $currentDateTimeStr ($currentTimestamp), Past due: " . ($currentTimestamp >= $reminderFullTimestamp ? 'YES' : 'NO'));

    // Check if reminder time has been reached
    if ($currentTimestamp >= $reminderFullTimestamp) {
        // Send SMS first if enabled and not already sent
        $sms_sent_successfully = false;
        if (
            $twilio_available &&
            isset($reminder['send_sms']) && $reminder['send_sms'] === true &&
            (!isset($reminder['sms_sent']) || $reminder['sms_sent'] !== true)
        ) {
            $phone = $reminder['user']['phone'] ?? '';
            if (!empty($phone)) {
                $reminderDateStr = date('M j, Y g:i A', $reminderFullTimestamp);
                $sms_text = "🐾 PetCare Reminder: {$reminder['title']} for {$reminder['pet']['pet_name']}. Due: {$reminderDateStr}";

                try {
                    $sms_result = send_sms($phone, $sms_text);
                    if ($sms_result === true) {
                        mongoUpdate(
                            'reminders',
                            ['_id' => $reminder['_id']],
                            ['$set' => [
                                'sms_sent' => true,
                                'sms_sent_date' => getCurrentDateTime()
                            ]]
                        );
                        $sms_count++;
                        $sms_sent_successfully = true;
                        error_log("SMS sent successfully to $phone for reminder: {$reminder['title']}");
                    } else {
                        error_log("SMS FAILED to send to $phone for reminder: {$reminder['title']}");
                    }
                } catch (Exception $e) {
                    error_log("SMS EXCEPTION for $phone: " . $e->getMessage());
                }
            } else {
                error_log("SMS not sent - no phone number for user");
            }
        }

        // Auto-complete the reminder
        mongoUpdate(
            'reminders',
            ['_id' => $reminder['_id']],
            ['$set' => [
                'status' => 'completed',
                'completed_at' => getCurrentDateTime(),
                'updated_at' => getCurrentDateTime()
            ]]
        );
        $completed_count++;
    }
}

if ($completed_count > 0) {
    $msg = "$completed_count reminder(s) automatically completed!";
    if ($sms_count > 0) {
        $msg .= " ($sms_count SMS sent)";
    }
}

// MANUAL ACTIONS
if (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    mongoUpdate(
        'reminders',
        ['_id' => stringToObjectId($id), 'user_id' => stringToObjectId($uid)],
        ['$set' => ['status' => 'completed', 'updated_at' => getCurrentDateTime()]]
    );
    header("Location:reminders.php?m=1");
    exit;
}
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mongoDelete('reminders', [
        '_id' => stringToObjectId($id),
        'user_id' => stringToObjectId($uid)
    ]);
    header("Location:reminders.php?m=2");
    exit;
}
if (isset($_GET['sms'])) {
    $id = $_GET['sms'];
    if (file_exists('twilio_helper.php')) {
        require_once 'twilio_helper.php';
        $reminderData = $db->reminders->aggregate([
            ['$match' => ['_id' => stringToObjectId($id), 'user_id' => stringToObjectId($uid)]],
            ['$lookup' => [
                'from' => 'users',
                'localField' => 'user_id',
                'foreignField' => '_id',
                'as' => 'user'
            ]],
            ['$lookup' => [
                'from' => 'pets',
                'localField' => 'pet_id',
                'foreignField' => '_id',
                'as' => 'pet'
            ]],
            ['$unwind' => '$user'],
            ['$unwind' => '$pet']
        ])->toArray();

        if (count($reminderData) > 0) {
            $row = $reminderData[0];
            $phone = $row['user']['phone'] ?? '';

            if (empty($phone)) {
                error_log("SMS FAILED: No phone number set for user");
                header("Location:reminders.php?e=2");
                exit;
            }

            try {
                $sms_text = "🐾 PetCare Reminder: {$row['title']} for {$row['pet']['pet_name']}";
                $sms_result = send_sms($phone, $sms_text);

                if ($sms_result === true) {
                    mongoUpdate(
                        'reminders',
                        ['_id' => stringToObjectId($id)],
                        ['$set' => ['sms_sent' => true, 'sms_sent_date' => getCurrentDateTime()]]
                    );
                    error_log("Manual SMS sent successfully to $phone");
                    header("Location:reminders.php?m=3");
                } else {
                    error_log("Manual SMS FAILED to $phone");
                    header("Location:reminders.php?e=1");
                }
            } catch (Exception $e) {
                error_log("Manual SMS EXCEPTION: " . $e->getMessage());
                header("Location:reminders.php?e=1");
            }
        } else {
            error_log("SMS FAILED: Reminder not found");
            header("Location:reminders.php?e=1");
        }
    } else {
        error_log("SMS FAILED: twilio_helper.php not found");
        header("Location:reminders.php?e=3");
    }
    exit;
}

if (isset($_GET['m'])) {
    $msgs = ['', 'Completed!', 'Deleted!', 'SMS sent!'];
    $msg = $msgs[$_GET['m']] ?? '';
}
if (isset($_GET['e'])) {
    $errors = ['', 'SMS failed! Check logs or Twilio configuration.', 'SMS failed! No phone number set in profile.', 'SMS failed! Twilio not configured.'];
    $err = $errors[$_GET['e']] ?? 'An error occurred!';
}

$reminders = $db->reminders->aggregate([
    ['$match' => ['user_id' => stringToObjectId($uid)]],
    ['$lookup' => [
        'from' => 'pets',
        'localField' => 'pet_id',
        'foreignField' => '_id',
        'as' => 'pet'
    ]],
    ['$unwind' => '$pet'],
    ['$addFields' => [
        'pet_name' => '$pet.pet_name',
        'pet_type' => '$pet.pet_type',
        'sort_order' => ['$cond' => [['$eq' => ['$status', 'pending']], 0, 1]]
    ]],
    ['$sort' => ['sort_order' => 1, 'reminder_date' => -1, 'reminder_time' => -1]]
]);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Reminders - PetCare</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
        }

        .wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: #fff;
            padding: 25px 35px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .15);
            border-left: 5px solid #667eea;
        }

        .page-header h1 {
            color: #2d3748;
            font-size: 32px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1::before {
            content: "🔔";
            font-size: 36px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            display: inline-block;
            transition: all .3s ease;
            font-size: 14px;
            text-transform: capitalize;
        }

        .btn-pri {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-pri:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-sec {
            background: #e2e8f0;
            color: #2d3748;
            margin-right: 10px;
        }

        .btn-sec:hover {
            background: #cbd5e0;
            transform: translateY(-1px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold
        }

        .alert-ok {
            background: #d4edda;
            color: #155724
        }

        .alert-bad {
            background: #f8d7da;
            color: #721c24
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
            border-left: 4px solid #667eea;
            transition: all .3s ease;
            position: relative;
            overflow: hidden
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, .2)
        }

        .card.done {
            opacity: .75;
            background: #f7fafc;
            border-left-color: #48bb78
        }

        .card.done::before {
            background: linear-gradient(90deg, #48bb78 0%, #38a169 100%);
        }

        .card.late {
            border-left-color: #f56565;
            background: #fff5f5
        }

        .card.late::before {
            background: linear-gradient(90deg, #f56565 0%, #c53030 100%);
        }

        .chead {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            gap: 12px
        }

        .ctitle {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            line-height: 1.4;
            flex: 1
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap
        }

        .badge-p {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3)
        }

        .badge-c {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3)
        }

        .cinfo {
            margin-bottom: 16px;
            color: #4a5568;
            font-size: 14px;
            line-height: 2;
            display: grid;
            gap: 6px
        }

        .cinfo>div {
            display: flex;
            align-items: center;
            gap: 8px
        }

        .cinfo>div::before {
            content: '•';
            color: #667eea;
            font-weight: bold;
            font-size: 18px
        }

        .cinfo b {
            color: #2d3748;
            font-weight: 600
        }

        .cdesc {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            color: #4a5568;
            font-size: 14px;
            line-height: 1.7;
            border-left: 3px solid #cbd5e0
        }

        .cact {
            display: flex;
            gap: 10px;
            flex-wrap: wrap
        }

        .btn-sm {
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all .3s ease;
            cursor: pointer;
            border: none
        }

        .btn-ok {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3)
        }

        .btn-ok:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.5)
        }

        .btn-inf {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(66, 153, 225, 0.3)
        }

        .btn-inf:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.5)
        }

        .btn-del {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(245, 101, 101, 0.3)
        }

        .btn-del:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.5);
        }

        .empty {
            text-align: center;
            padding: 80px 40px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .1)
        }

        .empty::before {
            content: '📭';
            font-size: 80px;
            display: block;
            margin-bottom: 20px
        }

        .empty h2 {
            color: #2d3748;
            margin-bottom: 12px;
            font-size: 28px;
            font-weight: 700
        }

        .empty p {
            color: #718096;
            margin-bottom: 24px;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center
            }
        }
    </style>
</head>

<body>
    <header class="top">
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="pets.php">Pets</a>
            <a href="reminders.php" class="active">Reminders</a>
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

    <div class="wrap">
        <div class="page-header">
            <h1>My Reminders</h1>
            <div>
                <a href="add_reminder.php" class="btn btn-pri">+ New Reminder</a>
            </div>
        </div>
        <?php if ($msg): ?>
            <div class="alert alert-ok"> <?= htmlspecialchars($msg) ?></div>
        <?php endif;
        if ($err): ?>
            <div class="alert alert-bad"> <?= htmlspecialchars($err) ?></div>
        <?php endif;
        $remindersArray = iterator_to_array($reminders);
        if (count($remindersArray) > 0): ?>
            <div class="grid">
                <?php foreach ($remindersArray as $r):
                    $reminderDate = $r['reminder_date']->toDateTime()->format('Y-m-d');
                    $dt = $reminderDate . ' ' . ($r['reminder_time'] ?? '00:00:00');
                    $late = (strtotime($dt) < time()) && $r['status'] == 'pending';
                ?>
                    <div class="card <?= $r['status'] == 'completed' ? 'done' : '' ?> <?= $late ? 'late' : '' ?>">
                        <div class="chead">
                            <div class="ctitle"><?= htmlspecialchars($r['title']) ?></div>
                            <span class="badge badge-<?= $r['status'] == 'pending' ? 'p' : 'c' ?>"><?= $r['status'] ?></span>
                        </div>
                        <div class="cinfo">
                            <div> <b><?= htmlspecialchars($r['pet_name']) ?></b> (<?= htmlspecialchars($r['pet_type']) ?>)</div>
                            <div> <?= date('F j, Y', strtotime($reminderDate)) ?></div>
                            <?php if ($r['reminder_time']): ?>
                                <div> <?= date('g:i A', strtotime($r['reminder_time'])) ?></div>
                            <?php endif; ?>
                            <div> <?= htmlspecialchars(ucfirst($r['reminder_type'])) ?></div>
                            <?php if ($late): ?>
                                <div style="color:#dc3545;font-weight:bold"> OVERDUE</div>
                            <?php endif; ?>
                        </div>
                        <?php if ($r['description']): ?>
                            <div class="cdesc"><?= nl2br(htmlspecialchars($r['description'])) ?></div>
                        <?php endif; ?>
                        <div class="cact">
                            <?php if ($r['status'] == 'pending'): ?>
                                <a href="?complete=<?= (string)$r['_id'] ?>" class="btn-sm btn-ok" onclick="return confirm('Mark complete?')"> Complete</a>
                                <a href="?sms=<?= (string)$r['_id'] ?>" class="btn-sm btn-inf"> SMS</a>
                            <?php else: ?>
                                <span style="color:#28a745;font-weight:bold"> Done</span>
                            <?php endif; ?>
                            <a href="?delete=<?= (string)$r['_id'] ?>" class="btn-sm btn-del" onclick="return confirm('Delete?')"> Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <h2> No Reminders</h2>
                <p>Create your first reminder!</p>
                <a href="add_reminder.php" class="btn btn-pri">+ Create Reminder</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh every 30 seconds to check for completed reminders
        let autoRefreshEnabled = true;
        let refreshInterval = 30000; // 30 seconds
        let countdownSeconds = 30;
        let countdownInterval;

        // Create refresh notification
        const refreshNotif = document.createElement('div');
        refreshNotif.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        `;
        refreshNotif.innerHTML = `
            <span>🔄</span>
            <span id="refreshText">Auto-refresh in <strong id="countdown">30</strong>s</span>
            <button id="pauseBtn" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px;">Pause</button>
        `;
        document.body.appendChild(refreshNotif);

        const countdownEl = document.getElementById('countdown');
        const pauseBtn = document.getElementById('pauseBtn');
        const refreshText = document.getElementById('refreshText');

        // Countdown timer
        function startCountdown() {
            countdownSeconds = 30;
            countdownEl.textContent = countdownSeconds;

            if (countdownInterval) clearInterval(countdownInterval);

            countdownInterval = setInterval(() => {
                countdownSeconds--;
                countdownEl.textContent = countdownSeconds;

                if (countdownSeconds <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        }

        // Pause/Resume functionality
        pauseBtn.addEventListener('click', () => {
            autoRefreshEnabled = !autoRefreshEnabled;

            if (autoRefreshEnabled) {
                pauseBtn.textContent = 'Pause';
                refreshText.innerHTML = 'Auto-refresh in <strong id="countdown">' + countdownSeconds + '</strong>s';
                startCountdown();
                scheduleRefresh();
            } else {
                pauseBtn.textContent = 'Resume';
                refreshText.textContent = 'Auto-refresh paused';
                clearInterval(countdownInterval);
                clearTimeout(refreshTimeout);
            }
        });

        let refreshTimeout;

        function scheduleRefresh() {
            if (!autoRefreshEnabled) return;

            refreshTimeout = setTimeout(() => {
                if (autoRefreshEnabled) {
                    // Check if there are pending reminders before refresh
                    const pendingReminders = document.querySelectorAll('.badge-p').length;
                    if (pendingReminders > 0) {
                        window.location.reload();
                    } else {
                        // If no pending, check less frequently (every 2 minutes)
                        refreshInterval = 120000;
                        countdownSeconds = 120;
                        startCountdown();
                        scheduleRefresh();
                    }
                }
            }, refreshInterval);
        }

        // Start auto-refresh
        startCountdown();
        scheduleRefresh();

        // Show notification if reminders were auto-completed
        <?php if ($completed_count > 0): ?>
            const notif = document.createElement('div');
            notif.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            z-index: 1001;
            font-size: 16px;
            font-weight: 600;
            animation: slideIn 0.3s ease-out;
        `;
            notif.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 24px;">✅</span>
                <div>
                    <div><?= $completed_count ?> reminder<?= $completed_count > 1 ? 's' : '' ?> auto-completed!</div>
                    <?php if ($sms_count > 0): ?>
                        <div style="font-size: 14px; opacity: 0.9; margin-top: 4px;">📱 <?= $sms_count ?> SMS sent</div>
                    <?php endif; ?>
                </div>
            </div>
        `;
            document.body.appendChild(notif);

            const style = document.createElement('style');
            style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
            document.head.appendChild(style);

            setTimeout(() => {
                notif.style.transition = 'all 0.3s ease-out';
                notif.style.opacity = '0';
                notif.style.transform = 'translateX(400px)';
                setTimeout(() => notif.remove(), 300);
            }, 5000);
        <?php endif; ?>
    </script>

    <!-- Reminder Background Service -->
    <script src="reminder_background.js"></script>
</body>

</html>