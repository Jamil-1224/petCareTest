<?php
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $role = $_POST['role'] ?? 'user';

  $passHash = password_hash($password, PASSWORD_DEFAULT);

  // MongoDB insert
  $insertId = mongoInsert('users', [
    'name' => $name,
    'email' => $email,
    'password' => $passHash,
    'phone' => $phone,
    'role' => $role,
    'created_at' => getCurrentDateTime()
  ]);

  if ($insertId) {
    $_SESSION['user_id'] = (string)$insertId;
    $_SESSION['name'] = $name;
    $_SESSION['role'] = $role;

    if ($role === 'admin') {
      header("Location: admin/dashboard.php");
    } else {
      header("Location: dashboard.php");
    }
    exit;
  } else {
    $error = "Registration failed!";
  }
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Register | PetCare</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .register-container {
      display: flex;
      min-height: 100vh;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
      padding: 20px;
    }

    .register-card {
      width: 100%;
      max-width: 450px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      padding: 40px;
      transition: transform 0.3s ease;
    }

    .register-card:hover {
      transform: translateY(-5px);
    }

    .register-card h2 {
      color: var(--primary);
      text-align: center;
      margin-bottom: 30px;
      font-size: 28px;
    }

    .register-card form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .register-card input {
      padding: 15px;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      font-size: 16px;
      transition: border-color 0.3s ease;
    }

    .register-card input:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.2);
    }

    .register-card button {
      background: var(--primary);
      color: white;
      border: none;
      padding: 15px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .register-card button:hover {
      background: var(--primary-dark);
    }

    .register-card p {
      text-align: center;
      margin-top: 25px;
      color: #666;
    }

    .register-card a {
      color: var(--primary);
      font-weight: 600;
      text-decoration: none;
    }

    .register-card a:hover {
      text-decoration: underline;
    }

    .error {
      background: #ffebee;
      color: #c62828;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 20px;
      text-align: center;
    }
  </style>
</head>

<body>
  <div class="register-container">
    <div class="register-card">
      <h2>Create Your Account</h2>
      <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="post">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Create Password" required>
        <input type="text" name="phone" placeholder="Phone Number (Optional)">
        <input type="hidden" name="role" value="user">
        <button type="submit">Sign Up</button>
      </form>

      <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
  </div>
</body>

</html>