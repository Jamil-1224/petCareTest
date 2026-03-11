<?php
/**
 * PetCare - Main Landing Page
 */
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'admin':
                header("Location: admin/dashboard.php");
                exit;
            case 'doctor':
                header("Location: doctor_dashboard.php");
                exit;
            default:
                header("Location: dashboard.php");
                exit;
        }
    } else {
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare - Pet Health & Care Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .landing-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            text-align: center;
        }
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            border-radius: 10px;
            margin-bottom: 40px;
        }
        .hero h1 {
            font-size: 3em;
            margin-bottom: 20px;
        }
        .hero p {
            font-size: 1.3em;
            margin-bottom: 30px;
        }
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 15px 40px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        .btn-secondary {
            background: white;
            color: #667eea;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 60px;
        }
        .feature {
            padding: 30px;
            background: #f5f5f5;
            border-radius: 10px;
        }
        .feature h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <div class="hero">
            <h1>🐾 Welcome to PetCare</h1>
            <p>Your Complete Pet Health & Care Management System</p>
            <div class="cta-buttons">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-secondary">Sign Up</a>
            </div>
        </div>

        <div class="features">
            <div class="feature">
                <h3>📅 Health Reminders</h3>
                <p>Never miss vaccination dates, medication schedules, or vet appointments</p>
            </div>
            <div class="feature">
                <h3>📝 Medical Records</h3>
                <p>Keep track of your pet's complete medical history in one place</p>
            </div>
            <div class="feature">
                <h3>🩺 Doctor Consultations</h3>
                <p>Connect with veterinarians and get expert advice</p>
            </div>
            <div class="feature">
                <h3>🍖 Feeding Guidelines</h3>
                <p>Get personalized feeding recommendations for your pet</p>
            </div>
            <div class="feature">
                <h3>💬 Chat Support</h3>
                <p>Message doctors and get quick answers to your questions</p>
            </div>
            <div class="feature">
                <h3>📷 Pet Memories</h3>
                <p>Store and share precious moments with your beloved pets</p>
            </div>
        </div>
    </div>
</body>
</html>
