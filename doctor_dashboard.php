<?php
require_once 'db_connect.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['doctor_id'])) {
    header('Location: doctor_login.php');
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];

// Use aggregation to join appointments with pets and users
$appointments = mongoAggregate('appointments', [
    ['$match' => ['doctor_id' => stringToObjectId($doctor_id)]],
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
        '$lookup' => [
            'from' => 'users',
            'localField' => 'pet.user_id',
            'foreignField' => '_id',
            'as' => 'owner'
        ]
    ],
    ['$unwind' => '$owner'],
    ['$sort' => ['appointment_date' => -1]],
    [
        '$project' => [
            'appointment_id' => ['$toString' => '$_id'],
            'appointment_date' => 1,
            'appointment_status' => 1,
            'reason' => 1,
            'pet_name' => '$pet.pet_name',
            'owner_name' => '$owner.name',
            'owner_phone' => '$owner.phone'
        ]
    ]
]);

$appointmentsArray = mongoResultToArray($appointments);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['appointment_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['appointment_status'];

    mongoUpdate(
        'appointments',
        ['_id' => stringToObjectId($appointment_id), 'doctor_id' => stringToObjectId($doctor_id)],
        ['$set' => ['appointment_status' => $status]]
    );

    header('Location: doctor_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | PetCare</title>
    <style>
        /* General layout */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e0f7fa, #ffffff);
            color: #333;
            min-height: 100vh;
        }

        header {
            background: #00796b;
            color: white;
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 1.6rem;
            font-weight: 600;
        }

        header .user-info {
            font-size: 1rem;
            font-weight: 500;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 1rem;
        }

        nav ul li a {
            text-decoration: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        nav ul li a:hover,
        nav ul li a.active {
            background: #004d40;
        }

        main {
            padding: 2rem;
        }

        .appointments {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .appointments h2 {
            color: #004d40;
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            border-left: 5px solid #00796b;
            padding-left: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #00796b;
            color: white;
            padding: 0.9rem;
            text-align: left;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 0.9rem;
            border-bottom: 1px solid #e0e0e0;
            font-size: 0.95rem;
        }

        tr:hover {
            background-color: #f5f9f9;
            transition: background-color 0.3s;
        }

        /* Status badges */
        .badge {
            padding: 0.35rem 0.8rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
        }

        .badge-pending {
            background-color: #ffb300;
        }

        .badge-confirmed {
            background-color: #43a047;
        }

        .badge-completed {
            background-color: #1e88e5;
        }

        .badge-cancelled {
            background-color: #e53935;
        }

        /* Status dropdown */
        select {
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 0.9rem;
            cursor: pointer;
            background: white;
        }

        select:focus {
            outline: none;
            border-color: #00796b;
            box-shadow: 0 0 4px rgba(0, 121, 107, 0.4);
        }

        .no-appointments {
            text-align: center;
            padding: 1.5rem;
            color: #777;
            font-style: italic;
        }

        footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.85rem;
            color: #666;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                text-align: center;
            }

            nav ul {
                flex-direction: column;
            }

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            thead {
                display: none;
            }

            tr {
                background: #fff;
                margin-bottom: 1rem;
                border-radius: 10px;
                box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            }

            td {
                padding: 0.75rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #004d40;
            }
        }
    </style>
</head>

<body>

    <header>
        <h1>Doctor Dashboard</h1>
        <nav>
            <ul>
                <li><a href="doctor_dashboard.php" class="active">Appointments</a></li>
                <li><a href="doctor_messages.php">Messages</a></li>
                <li><a href="post_treatment.php">Post Treatment</a></li>
                <li><a href="doctor_profile.php">Profile</a></li>
                <li><a href="doctor_logout.php">Logout</a></li>
            </ul>
        </nav>
        <div class="user-info">Welcome, Dr. <?= htmlspecialchars($doctor_name) ?></div>
    </header>

    <main>
        <section class="appointments">
            <h2>My Appointments</h2>

            <?php if (count($appointmentsArray) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Pet</th>
                            <th>Owner</th>
                            <th>Contact</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointmentsArray as $appointment): ?>
                            <?php
                            $st = $appointment['appointment_status'];
                            $badgeClass = match ($st) {
                                'pending' => 'badge-pending',
                                'confirmed' => 'badge-confirmed',
                                'completed' => 'badge-completed',
                                'cancelled' => 'badge-cancelled',
                                default => 'badge-pending'
                            };
                            ?>
                            <tr>
                                <td data-label="Date"><?php echo date('M d, Y g:i A', strtotime($appointment['appointment_date'])); ?></td>
                                <td data-label="Pet"><?php echo htmlspecialchars($appointment['pet_name']); ?></td>
                                <td data-label="Owner"><?php echo htmlspecialchars($appointment['owner_name']); ?></td>
                                <td data-label="Contact"><?php echo htmlspecialchars($appointment['owner_phone']); ?></td>
                                <td data-label="Reason"><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                <td data-label="Status">
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($st); ?></span>
                                </td>
                                <td data-label="Action">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <select name="appointment_status" onchange="this.form.submit()">
                                            <option value="pending" <?php if ($st === 'pending') echo 'selected'; ?>>Pending</option>
                                            <option value="confirmed" <?php if ($st === 'confirmed') echo 'selected'; ?>>Confirmed</option>
                                            <option value="completed" <?php if ($st === 'completed') echo 'selected'; ?>>Completed</option>
                                            <option value="cancelled" <?php if ($st === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-appointments">No appointments found.</div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        © <?php echo date("Y"); ?> PetCare | All Rights Reserved
    </footer>

</body>

</html>