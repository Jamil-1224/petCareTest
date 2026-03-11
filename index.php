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
    <title>PetCare - Pet Health & Care Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800"><defs><pattern id="pawprint" x="0" y="0" width="120" height="120" patternUnits="userSpaceOnUse"><circle cx="40" cy="30" r="8" fill="rgba(255,255,255,0.05)"/><circle cx="60" cy="30" r="8" fill="rgba(255,255,255,0.05)"/><circle cx="30" cy="50" r="8" fill="rgba(255,255,255,0.05)"/><circle cx="70" cy="50" r="8" fill="rgba(255,255,255,0.05)"/><ellipse cx="50" cy="60" rx="18" ry="22" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="1200" height="800" fill="url(%23pawprint)"/></svg>');
            background-size: cover, 300px 300px;
            background-attachment: fixed;
            min-height: 100vh;
        }

        nav {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        nav .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav .logo {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }

        nav .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            color: #333;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        nav .nav-links a:hover {
            background: #667eea;
            color: white;
        }

        .hero {
            text-align: center;
            color: white;
            padding: 80px 20px;
        }

        .hero h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.4em;
            margin-bottom: 50px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .portals {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .portal-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .portal-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .portal-card .icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .portal-card h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.8em;
        }

        .portal-card p {
            color: #666;
            margin-bottom: 25px;
        }

        .portal-card .buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 600;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #4CAF50;
            color: white;
        }

        .btn-secondary:hover {
            background: #45a049;
        }

        .features {
            max-width: 1200px;
            margin: 60px auto;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .features h2 {
            text-align: center;
            font-size: 2.5em;
            color: #333;
            margin-bottom: 40px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature-item {
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            background: #e9ecef;
        }

        .feature-item .icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .feature-item h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .feature-item p {
            color: #666;
        }

        footer {
            text-align: center;
            padding: 30px 20px;
            color: white;
            background: rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <nav>
        <div class="container">
            <div class="logo">🐾 PetCare</div>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#portals">Get Started</a>
            </div>
        </div>
    </nav>

    <div class="hero">
        <h1>🐾 Welcome to PetCare</h1>
        <p>Your Complete Pet Health & Care Management System</p>
    </div>

    <div id="portals" class="portals">
        <div class="portal-card">
            <div class="icon">👤</div>
            <h2>Pet Owner</h2>
            <p>Manage your pet's health records, reminders, appointments, and connect with veterinarians</p>
            <div class="buttons">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-secondary">Sign Up</a>
            </div>
        </div>

        <div class="portal-card">
            <div class="icon">🩺</div>
            <h2>Veterinarian</h2>
            <p>Access patient records, manage appointments, provide consultations, and prescribe treatments</p>
            <div class="buttons">
                <a href="doctor_login.php" class="btn btn-primary">Doctor Login</a>
                <a href="doctor_register.php" class="btn btn-secondary">Register</a>
            </div>
        </div>
    </div>

    <div id="features" class="features">
        <h2>✨ Key Features</h2>
        <div class="features-grid">
            <div class="feature-item">
                <div class="icon">📅</div>
                <h3>Health Reminders</h3>
                <p>Never miss vaccination dates, medication schedules, or vet appointments with SMS alerts</p>
            </div>
            <div class="feature-item">
                <div class="icon">📝</div>
                <h3>Medical Records</h3>
                <p>Keep track of your pet's complete medical history, treatments, and prescriptions</p>
            </div>
            <div class="feature-item">
                <div class="icon">🩺</div>
                <h3>Doctor Consultations</h3>
                <p>Connect with certified veterinarians and get expert medical advice</p>
            </div>
            <div class="feature-item">
                <div class="icon">💊</div>
                <h3>Treatment Tracking</h3>
                <p>Monitor ongoing treatments and view detailed treatment history</p>
            </div>
            <div class="feature-item">
                <div class="icon">🍖</div>
                <h3>Feeding Guidelines</h3>
                <p>Get personalized feeding recommendations based on your pet's breed and age</p>
            </div>
            <div class="feature-item">
                <div class="icon">💬</div>
                <h3>Chat Support</h3>
                <p>Real-time messaging with doctors for quick answers to your questions</p>
            </div>
            <div class="feature-item">
                <div class="icon">📷</div>
                <h3>Pet Memories</h3>
                <p>Store and share precious moments with your beloved pets</p>
            </div>
            <div class="feature-item">
                <div class="icon">📰</div>
                <h3>Pet Care Articles</h3>
                <p>Access expert articles and guides on pet health and wellness</p>
            </div>
            <div class="feature-item">
                <div class="icon">🏠</div>
                <h3>Pet Adoption</h3>
                <p>Browse available pets for adoption and find your perfect companion</p>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 PetCare. All rights reserved. | Your Pet's Health, Our Priority</p>
    </footer>
</body>

</html>