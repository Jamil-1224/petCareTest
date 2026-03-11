<?php
require '../functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  $row = mongoFindOne('users', ['email' => $email]);

  if ($row) {
    if (password_verify($password, $row['password'])) {
      // Only allow admin users to login through this page
      if ($row['role'] !== 'admin') {
        $error = "Access denied! Admin credentials required.";
      } else {
        $_SESSION['user_id'] = (string)$row['_id'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['role'] = $row['role'];
        header("Location: dashboard.php");
        exit;
      }
    } else {
      $error = "Invalid password!";
    }
  } else {
    $error = "User not found!";
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | PetCare</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    body::before {
      content: '';
      position: absolute;
      width: 500px;
      height: 500px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      top: -250px;
      right: -250px;
      animation: float 6s ease-in-out infinite;
    }

    body::after {
      content: '';
      position: absolute;
      width: 400px;
      height: 400px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 50%;
      bottom: -200px;
      left: -200px;
      animation: float 8s ease-in-out infinite reverse;
    }

    @keyframes float {

      0%,
      100% {
        transform: translateY(0px);
      }

      50% {
        transform: translateY(-20px);
      }
    }

    .login-container {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      max-width: 450px;
      width: 100%;
      padding: 3rem 2.5rem;
      position: relative;
      z-index: 1;
      animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .admin-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 2.5rem;
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }

    .login-header h2 {
      font-size: 1.8rem;
      color: #2d3436;
      margin-bottom: 0.5rem;
      font-weight: 700;
    }

    .login-header p {
      color: #636e72;
      font-size: 0.95rem;
    }

    .error-message {
      background: #fff5f5;
      border-left: 4px solid #e74c3c;
      color: #c0392b;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      animation: shake 0.5s;
    }

    @keyframes shake {

      0%,
      100% {
        transform: translateX(0);
      }

      25% {
        transform: translateX(-10px);
      }

      75% {
        transform: translateX(10px);
      }
    }

    .error-message::before {
      content: '⚠️';
      font-size: 1.2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
      position: relative;
    }

    .form-group label {
      display: block;
      color: #2d3436;
      font-weight: 600;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }

    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #b2bec3;
      font-size: 1.1rem;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 0.9rem 1rem 0.9rem 3rem;
      border: 2px solid #dfe6e9;
      border-radius: 10px;
      font-size: 1rem;
      font-family: inherit;
      transition: all 0.3s ease;
      background: #f8f9fa;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: #667eea;
      background: white;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    input[type="email"]:focus+.input-icon,
    input[type="password"]:focus+.input-icon {
      color: #667eea;
    }

    .btn-login {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1.05rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
      margin-top: 1rem;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .back-link {
      text-align: center;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid #dfe6e9;
    }

    .back-link a {
      color: #667eea;
      text-decoration: none;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
    }

    .back-link a:hover {
      color: #764ba2;
      gap: 0.7rem;
    }

    .back-link a::before {
      content: '←';
      font-size: 1.2rem;
    }

    @media (max-width: 480px) {
      .login-container {
        padding: 2rem 1.5rem;
      }

      .login-header h2 {
        font-size: 1.5rem;
      }

      .admin-icon {
        width: 70px;
        height: 70px;
        font-size: 2rem;
      }
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="login-header">
      <div class="admin-icon">🛡️</div>
      <h2>Admin Access</h2>
      <p>Secure login to PetCare admin panel</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="error-message">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-wrapper">
          <input type="email" id="email" name="email" placeholder="admin@petcare.com" required autofocus>
          <span class="input-icon">📧</span>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <input type="password" id="password" name="password" placeholder="Enter your password" required>
          <span class="input-icon">🔒</span>
        </div>
      </div>

      <button type="submit" class="btn-login">Login to Admin Panel</button>
    </form>

    <div class="back-link">
      <a href="../index.php">Back to Main Site</a>
    </div>
  </div>
</body>

</html>