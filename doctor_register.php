<?php
require_once 'db_connect.php';
require_once 'functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $specialization = trim($_POST['specialization']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $bio = trim($_POST['bio']);

    if (empty($username) || empty($password) || empty($full_name) || empty($specialization) || empty($email) || empty($phone) || empty($_FILES['verification_document']['name'])) {
        $error = "All required fields must be filled out";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        $existing = mongoFindOne('doctors', [
            '$or' => [
                ['username' => $username],
                ['email' => $email]
            ]
        ]);

        if ($existing) {
            $error = "Username or email already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $profile_image = '';
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $temp_name = $_FILES['profile_image']['tmp_name'];
                $new_name = time() . '_' . $_FILES['profile_image']['name'];
                move_uploaded_file($temp_name, $upload_dir . $new_name);
                $profile_image = $upload_dir . $new_name;
            }

            $verification_document = '';
            if (isset($_FILES['verification_document']) && $_FILES['verification_document']['error'] === 0) {
                $upload_dir = 'uploads/verification/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $temp_name = $_FILES['verification_document']['tmp_name'];
                $new_name = time() . '_verification_' . $_FILES['verification_document']['name'];
                move_uploaded_file($temp_name, $upload_dir . $new_name);
                $verification_document = $upload_dir . $new_name;
            }

            if (empty($error)) {
                $insertId = mongoInsert('doctors', [
                    'username' => $username,
                    'password' => $hashed_password,
                    'full_name' => $full_name,
                    'specialization' => $specialization,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'bio' => $bio,
                    'profile_image' => $profile_image,
                    'verification_document' => $verification_document,
                    'verification_status' => 'pending',
                    'created_at' => getCurrentDateTime()
                ]);

                if ($insertId) {
                    $success = "Registration successful! Your account is pending admin approval.";
                } else {
                    $error = "Registration failed!";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration | PetCare</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #c3e0e5);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 750px;
            background: #ffffff;
            margin: 60px auto;
            padding: 40px 50px;
            border-radius: 20px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #00796b;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .error,
        .success {
            text-align: center;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .error {
            background: #ffebee;
            color: #c62828;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 15px;
            transition: 0.3s;
        }

        input:focus,
        textarea:focus {
            border-color: #00796b;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 121, 107, 0.3);
        }

        .btn {
            display: block;
            width: 100%;
            background: #00796b;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #004d40;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
        }

        .form-footer a {
            color: #00796b;
            font-weight: 600;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container {
                padding: 25px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Doctor Registration</h1>

        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

        <form action="doctor_register.php" method="post" enctype="multipart/form-data">
            <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
            <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" required></div>
            <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required></div>
            <div class="form-group"><label>Specialization *</label><input type="text" name="specialization" required></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Phone *</label><input type="tel" name="phone" required></div>
            <div class="form-group"><label>Address</label><textarea name="address" rows="3"></textarea></div>
            <div class="form-group"><label>Professional Bio</label><textarea name="bio" rows="4"></textarea></div>
            <div class="form-group"><label>Profile Image</label><input type="file" name="profile_image"></div>
            <div class="form-group"><label>Verification Document *</label><input type="file" name="verification_document" required></div>
            <button type="submit" class="btn">Register</button>
            <div class="form-footer">
                <p>Already registered? <a href="doctor_login.php">Login here</a></p>
                <p><a href="index.php">← Back to Home</a></p>
            </div>
        </form>
    </div>
</body>

</html>