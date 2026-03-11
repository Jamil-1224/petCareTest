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
$success = '';
$error = '';

// Fetch doctor data
$doctor = mongoFindOne('doctors', ['_id' => stringToObjectId($doctor_id)]);

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
        $profile_image = $doctor['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $upload_dir = 'uploads/';
            $new_name = time() . '_' . basename($_FILES['profile_image']['name']);
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_name);
            $profile_image = $upload_dir . $new_name;
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
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            background: rgb(52, 76, 109);
            color: white;
            width: 280px;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            margin-bottom: 15px;
        }

        .sidebar h2 {
            margin: 10px 0 5px;
            font-size: 22px;
        }

        .sidebar p {
            font-size: 15px;
            opacity: 0.9;
            margin-bottom: 25px;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px;
            display: block;
            text-align: center;
            border-radius: 6px;
            margin: 5px 0;
            background: rgba(255, 255, 255, 0.15);
            transition: background 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.3);
        }

        .content {
            flex: 1;
            padding: 40px;
        }

        h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 20px;
        }

        form {
            background: white;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            max-width: 700px;
        }

        .form-group {
            margin-bottom: 18px;
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
    </style>
</head>

<body>

    <div class="container">
        <div class="sidebar">
            <img src="<?php echo !empty($doctor['profile_image']) ? htmlspecialchars($doctor['profile_image']) : 'default-avatar.png'; ?>" alt="Doctor Image">
            <h2><?php echo htmlspecialchars($doctor['full_name']); ?></h2>
            <p><?php echo htmlspecialchars($doctor['specialization']); ?></p>
            <a href="doctor_dashboard.php">Appointments</a>
            <a href="doctor_messages.php">Messages</a>
            <a href="post_treatment.php">Post Treatment</a>
            <a href="doctor_profile.php" class="active">Profile</a>
            <a href="doctor_logout.php">Logout</a>
        </div>

        <div class="content">
            <h1>Edit Profile</h1>
            <?php if ($error): ?><div class="message error"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="message success"><?php echo $success; ?></div><?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($doctor['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Specialization *</label>
                    <input type="text" name="specialization" value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"><?php echo htmlspecialchars($doctor['address']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Professional Bio</label>
                    <textarea name="bio"><?php echo htmlspecialchars($doctor['bio']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Profile Image</label>
                    <input type="file" name="profile_image">
                </div>
                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>

</body>

</html>