<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['doctor_id'])) {
    header('Location: doctor_login.php');
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$success = '';
$error = '';

// Fetch doctor data
$doctor = mongoFindOne('doctors', ['_id' => stringToObjectId($doctor_id)]);
$profileImage = '';
if (!empty($doctor['profile_image']) && is_string($doctor['profile_image'])) {
    $profileImage = $doctor['profile_image'];
}

$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($doctor['full_name'] ?? 'Doctor') . '&background=00796b&color=ffffff&size=256';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $full_name = trim($_POST['full_name']);
    $specialization = trim($_POST['specialization']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $bio = trim($_POST['bio']);

    if (empty($full_name) || empty($specialization) || empty($phone)) {
        $error = "Please fill in all required fields.";
    } else {
        // Handle image upload
        $profile_image = $doctor['profile_image'] ?? '';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $upload_dir = 'uploads/';
            $new_name = time() . '_' . basename($_FILES['profile_image']['name']);
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_name)) {
                $profile_image = $upload_dir . $new_name;
            }
        }

        $updateResult = mongoUpdate(
            'doctors',
            ['_id' => stringToObjectId($doctor_id)],
            ['$set' => [
                'full_name' => $full_name,
                'specialization' => $specialization,
                'phone' => $phone,
                'address' => $address,
                'bio' => $bio,
                'profile_image' => $profile_image
            ]]
        );

        if ($updateResult) {
            $success = "Profile updated successfully!";
            $_SESSION['doctor_name'] = $full_name;
            // Refresh doctor data
            $doctor = mongoFindOne('doctors', ['_id' => stringToObjectId($doctor_id)]);
            $profileImage = !empty($doctor['profile_image']) && is_string($doctor['profile_image']) ? $doctor['profile_image'] : '';
        } else {
            $error = "Error updating profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - Pet Care</title>
    <style>
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
            flex-wrap: nowrap;
            overflow-x: auto;
            max-width: 100%;
            padding-bottom: 4px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s ease;
            white-space: nowrap;
            display: inline-block;
        }

        nav ul li a:hover,
        nav ul li a.active {
            background: #004d40;
        }

        main {
            padding: 2rem;
        }

        .profile-wrap {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.25rem;
        }

        .doctor-card,
        form {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .doctor-card {
            text-align: center;
            height: fit-content;
        }

        .doctor-card img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #00796b;
            margin-bottom: 0.75rem;
        }

        .doctor-card h2 {
            font-size: 1.3rem;
            margin-bottom: 0.25rem;
        }

        .doctor-card p {
            color: #555;
            margin-bottom: 0.25rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            font-weight: 600;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }

        input,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 10px 18px;
            font-size: 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #155fc1;
        }

        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .error {
            background: #ffcccc;
            color: #a00;
        }

        .success {
            background: #c9f7c4;
            color: #1a531b;
        }

        @media (max-width: 900px) {
            .profile-wrap {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                text-align: left;
                align-items: flex-start;
                gap: 0.8rem;
            }

            nav ul {
                width: 100%;
                gap: 0.5rem;
            }

            header .user-info {
                width: 100%;
                font-size: 0.95rem;
            }

            main {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>Doctor Dashboard - Profile</h1>
        <nav>
            <ul>
                <li><a href="doctor_dashboard.php">Appointments</a></li>
                <li><a href="doctor_messages.php">Messages</a></li>
                <li><a href="post_treatment.php">Post Treatment</a></li>
                <li><a href="feed_guidelines.php">Feed Guidelines</a></li>
                <li><a href="doctor_profile.php" class="active">Profile</a></li>
                <li><a href="doctor_logout.php">Logout</a></li>
            </ul>
        </nav>
        <div class="user-info">Welcome, Dr. <?= htmlspecialchars($_SESSION['doctor_name'] ?? 'Doctor') ?></div>
    </header>

    <main>
        <div class="profile-wrap">
            <aside class="doctor-card">
                <img src="<?= esc($profileImage ?: $defaultAvatar) ?>" alt="Doctor Image" onerror="this.src='<?= esc($defaultAvatar) ?>'">
                <h2><?= esc($doctor['full_name'] ?? 'Doctor') ?></h2>
                <p><?= esc($doctor['specialization'] ?? 'Veterinarian') ?></p>
                <p><?= esc($doctor['email'] ?? '') ?></p>
            </aside>

            <section>
                <h1>Edit Profile</h1>
                <?php if ($error): ?><div class="message error"><?php echo $error; ?></div><?php endif; ?>
                <?php if ($success): ?><div class="message success"><?php echo $success; ?></div><?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($doctor['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Specialization *</label>
                        <input type="text" name="specialization" value="<?php echo htmlspecialchars($doctor['specialization'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address"><?php echo htmlspecialchars($doctor['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Professional Bio</label>
                        <textarea name="bio"><?php echo htmlspecialchars($doctor['bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Profile Image</label>
                        <input type="file" name="profile_image" accept="image/*">
                    </div>
                    <button type="submit" class="btn">Save Changes</button>
                </form>
            </section>
        </div>
    </main>

</body>

</html>