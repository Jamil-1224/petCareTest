<?php
require '../functions.php';
if (!is_admin()) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

// Handle promoting existing user to admin
if (isset($_POST['promote_user'])) {
    $user_id = $_POST['user_id'];

    $updateResult = mongoUpdate(
        'users',
        ['_id' => stringToObjectId($user_id)],
        ['$set' => ['role' => 'admin']]
    );

    if ($updateResult) {
        $success = "User has been promoted to admin successfully!";
    } else {
        $error = "Failed to promote user!";
    }
}

// Handle creating new admin
if (isset($_POST['create_admin'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = 'admin'; // Always set as admin

    // Check if email already exists
    $existing = mongoFindOne('users', ['email' => $email]);

    if ($existing) {
        $error = "Email already exists in the system!";
    } else {
        $passHash = password_hash($password, PASSWORD_DEFAULT);

        $insertId = mongoInsert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $passHash,
            'phone' => $phone,
            'role' => $role,
            'created_at' => getCurrentDateTime()
        ]);

        if ($insertId) {
            $success = "New admin account created successfully!";
        } else {
            $error = "Failed to create admin account!";
        }
    }
}

// Get all regular users for promotion
$users = $conn->query("SELECT user_id, name, email FROM users WHERE role = 'user' ORDER BY name");

// Get all admin users
$admins = $conn->query("SELECT user_id, name, email, created_at FROM users WHERE role = 'admin' ORDER BY name");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Manage Admins - PetCare</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-section {
            margin-bottom: 2rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .admin-section h3 {
            margin-top: 0;
            color: var(--primary);
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .admin-list {
            margin-top: 1.5rem;
        }

        .admin-card {
            background: var(--gray-100);
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 0.5rem;
        }

        .admin-card h4 {
            margin-top: 0;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        .admin-card p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .success-message {
            background: var(--success-light);
            color: var(--success);
            padding: 0.75rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            border-left: 4px solid var(--success);
        }

        .error-message {
            background: var(--danger-light);
            color: var(--danger);
            padding: 0.75rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            border-left: 4px solid var(--danger);
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
        <h2>Manage Administrators</h2>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="admin-section">
            <h3>Create New Admin Account</h3>
            <form method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone">
                    </div>
                </div>

                <button type="submit" name="create_admin" class="btn btn-primary">Create Admin Account</button>
            </form>
        </div>

        <div class="admin-section">
            <h3>Promote Existing User to Admin</h3>
            <?php if ($users->num_rows > 0): ?>
                <form method="post">
                    <div class="form-group">
                        <label for="user_id">Select User</label>
                        <select id="user_id" name="user_id" required>
                            <option value="">-- Select User --</option>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" name="promote_user" class="btn btn-primary">Promote to Admin</button>
                </form>
            <?php else: ?>
                <p>No regular users available for promotion.</p>
            <?php endif; ?>
        </div>

        <div class="admin-section">
            <h3>Current Administrators</h3>
            <div class="admin-list">
                <?php if ($admins->num_rows > 0): ?>
                    <?php while ($admin = $admins->fetch_assoc()): ?>
                        <div class="admin-card">
                            <h4><?php echo htmlspecialchars($admin['name']); ?></h4>
                            <p>Email: <?php echo htmlspecialchars($admin['email']); ?></p>
                            <p>Added: <?php echo htmlspecialchars($admin['created_at']); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No administrators found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>