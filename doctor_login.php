<?php
require_once 'db_connect.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['doctor_id'])) {
    header('Location: doctor_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $doctor = mongoFindOne('doctors', ['username' => $username]);

        if ($doctor) {
            if (password_verify($password, $doctor['password'])) {
                if ($doctor['verification_status'] === 'approved') {
                    $_SESSION['doctor_id'] = (string)$doctor['_id'];
                    $_SESSION['doctor_name'] = $doctor['full_name'];
                    $_SESSION['doctor_username'] = $doctor['username'];
                    header('Location: doctor_dashboard.php');
                    exit;
                } elseif ($doctor['verification_status'] === 'pending') {
                    $error = "Your account is pending approval.";
                } else {
                    $error = "Your account has been rejected. Please contact support.";
                }
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Username not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login | PetCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Base Layout */
        body {
            font-family: "Poppins", sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #74b9ff, #a29bfe);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Login Card */
        .container {
            background: #fff;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: #2d3436;
            font-size: 28px;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        label {
            font-weight: 500;
            color: #2d3436;
            display: block;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1.5px solid #dfe6e9;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s;
        }

        input:focus {
            border-color: #0984e3;
        }

        .btn {
            background: linear-gradient(90deg, #0072ff, #00c6ff);
            border: none;
            color: #fff;
            font-size: 16px;
            padding: 12px 0;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s, background 0.3s;
        }

        .btn:hover {
            transform: scale(1.03);
            background: linear-gradient(90deg, #0062cc, #00aaff);
        }

        .error {
            background: #ffe0e0;
            color: #d63031;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .form-footer {
            margin-top: 20px;
            font-size: 14px;
            color: #636e72;
        }

        .form-footer a {
            color: #0984e3;
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .container {
                margin: 15px;
                padding: 30px 20px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Doctor Login</h1>

        <?php if (!empty($error)): ?>
            <div class="error"><?= $error; ?></div>
        <?php endif; ?>

        <form action="doctor_login.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn">Login</button>

            <div class="form-footer">
                <p>Don't have an account? <a href="doctor_register.php">Register here</a></p>
                <p><a href="index.php">← Back to Home</a></p>
            </div>
        </form>
    </div>
</body>

</html>