<?php
require __DIR__ . '/functions.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // MongoDB query
    $user = mongoFindOne('users', ['email' => $email]);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            // Check if user is trying to log in as admin through user login
            if ($user['role'] === 'admin') {
                $error = "Please use the Admin Login page for administrator access.";
            } else {
                $_SESSION['user_id'] = (string)$user['_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
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
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PetCare</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--gray-600);
        }

        .login-form .form-group {
            margin-bottom: 1.5rem;
        }

        .login-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .login-form input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            transition: border-color 0.3s ease;
        }

        .login-form input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.2);
        }

        .login-form button {
            width: 100%;
            padding: 0.75rem;
            margin-top: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-form button:hover {
            background: var(--primary-dark);
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-600);
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: var(--danger-light);
            color: var(--danger);
            padding: 0.75rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--danger);
        }
    </style>
</head>

<body>
    <header class="top">
        <h1>PetCare</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="register.php">Register</a>
        </nav>
    </header>

    <main class="container">
        <div class="login-container">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access your pet care dashboard</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>

            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Create Account</a></p>
            </div>
        </div>
    </main>
</body>

</html>