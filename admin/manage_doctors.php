<?php
require '../functions.php';
if (!is_admin()) {
    header("Location: login.php");
    exit;
}

// Handle verification actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $doctor_id = $_GET['id'];

    if ($action === 'approve') {
        mongoUpdate(
            'doctors',
            ['_id' => stringToObjectId($doctor_id)],
            ['$set' => ['verification_status' => 'approved']]
        );
    } elseif ($action === 'reject') {
        mongoUpdate(
            'doctors',
            ['_id' => stringToObjectId($doctor_id)],
            ['$set' => ['verification_status' => 'rejected']]
        );
    }

    header("Location: manage_doctors.php");
    exit;
}

$doctors = iterator_to_array(mongoFind('doctors', [], ['sort' => ['created_at' => -1]]));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - Admin Panel</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: #f4f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 95%;
            margin: 30px auto;
            background: #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        h1 {
            background: linear-gradient(90deg, #00c6ff, #0072ff);
            color: white;
            text-align: center;
            padding: 20px;
            margin: 0;
        }

        nav {
            background: #20232a;
            padding: 12px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }

        nav a {
            color: #ddd;
            margin: 8px 12px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        nav a:hover {
            color: #00c6ff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        thead {
            background: #0072ff;
            color: #fff;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        tbody tr:hover {
            background-color: #f0f8ff;
        }

        a {
            text-decoration: none;
        }

        td a {
            padding: 6px 10px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
        }

        td a[href*="approve"] {
            background: #28a745;
        }

        td a[href*="reject"] {
            background: #dc3545;
        }

        td a[href*="approve"]:hover {
            background: #218838;
        }

        td a[href*="reject"]:hover {
            background: #c82333;
        }

        td a[target="_blank"] {
            background: #17a2b8;
        }

        td a[target="_blank"]:hover {
            background: #138496;
        }

        /* Responsive */
        @media (max-width: 900px) {

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            thead tr {
                display: none;
            }

            tbody tr {
                background: #fff;
                margin-bottom: 15px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
                padding: 10px;
            }

            td {
                text-align: left;
                padding: 8px;
                position: relative;
            }

            td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #0072ff;
                display: block;
                margin-bottom: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Manage Doctors</h1>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php">Users</a>
            <a href="manage_admins.php">Admins</a>
            <a href="manage_doctors.php" style="color:#00c6ff;font-weight:bold;">Doctors</a>
            <a href="manage_pets.php">Pets</a>
            <a href="manage_memories.php">Memories</a>
            <a href="manage_articles.php">Articles</a>
            <a href="manage_reminders.php">Reminders</a>
            <a href="manage_adoptions.php">Adoptions</a>
            <a href="manage_feed.php">Feed Guidelines</a>
            <a href="../logout.php" style="color:#dc3545;">Logout</a>
        </nav>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Verification Status</th>
                    <th>Document</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doctor): ?>
                    <tr>
                        <td data-label="ID"><?php echo substr((string)$doctor['_id'], -8); ?></td>
                        <td data-label="Username"><?php echo htmlspecialchars($doctor['username']); ?></td>
                        <td data-label="Full Name"><?php echo htmlspecialchars($doctor['full_name']); ?></td>
                        <td data-label="Email"><?php echo htmlspecialchars($doctor['email']); ?></td>
                        <td data-label="Status">
                            <?php
                            $status = ucfirst($doctor['verification_status']);
                            if ($status === 'Approved') echo "<span style='color:#28a745;font-weight:bold;'>$status</span>";
                            elseif ($status === 'Rejected') echo "<span style='color:#dc3545;font-weight:bold;'>$status</span>";
                            else echo "<span style='color:#ffb400;font-weight:bold;'>$status</span>";
                            ?>
                        </td>
                        <td data-label="Document">
                            <?php if (!empty($doctor['verification_document'] ?? '')): ?>
                                <a href="../<?php echo htmlspecialchars($doctor['verification_document']); ?>" target="_blank">View</a>
                            <?php else: ?>
                                Not Provided
                            <?php endif; ?>
                        </td>
                        <td data-label="Actions">
                            <?php if ($doctor['verification_status'] === 'pending'): ?>
                                <a href="manage_doctors.php?action=approve&id=<?php echo (string)$doctor['_id']; ?>">Approve</a>
                                <a href="manage_doctors.php?action=reject&id=<?php echo (string)$doctor['_id']; ?>">Reject</a>
                            <?php else: ?>
                                <span style="color:#888;">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>