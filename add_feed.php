<?php
require __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit;
}

$can_manage_feed = is_admin() || isset($_SESSION['doctor_id']);
if (!$can_manage_feed) {
    header('Location: feed_guidelines.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['pet_type'] ?? '');
    $suitable = trim($_POST['suitable'] ?? '');
    $notSuitable = trim($_POST['not_suitable'] ?? '');

    if ($type === '' || $suitable === '' || $notSuitable === '') {
        $error = 'All fields are required.';
    } else {
        $insertData = [
            'pet_type' => $type,
            'suitable' => $suitable,
            'not_suitable' => $notSuitable,
            'created_at' => getCurrentDateTime(),
            'created_by_type' => isset($_SESSION['doctor_id']) ? 'doctor' : 'admin',
        ];

        if (isset($_SESSION['doctor_id'])) {
            $insertData['created_by_id'] = stringToObjectId($_SESSION['doctor_id']);
        } elseif (isset($_SESSION['user_id'])) {
            $insertData['created_by_id'] = stringToObjectId($_SESSION['user_id']);
        }

        $insertId = mongoInsert('feed', $insertData);
        if ($insertId) {
            header('Location: feed_guidelines.php');
            exit;
        }

        $error = 'Unable to save guideline. Please try again.';
    }
}

$logoutUrl = isset($_SESSION['doctor_id']) ? 'doctor_logout.php' : 'logout.php';
$backUrl = isset($_SESSION['doctor_id']) ? 'doctor_dashboard.php' : 'feed_guidelines.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Feed Guideline | PetCare</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e3f2fd);
        }

        .feed-form-wrap {
            max-width: 700px;
            margin: 2rem auto;
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 1.5rem;
        }

        .feed-form-wrap h2 {
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .feed-form-wrap p {
            margin-bottom: 1rem;
            color: var(--gray-700);
        }

        .field {
            margin-bottom: 1rem;
        }

        .field label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
        }

        .field input,
        .field textarea {
            width: 100%;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            padding: 0.75rem;
            font-size: 1rem;
        }

        .field textarea {
            min-height: 120px;
            resize: vertical;
        }

        .field input:focus,
        .field textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.18);
        }

        .error-box {
            background: #fcebea;
            border: 1px solid #f5c6cb;
            color: #a61b29;
            border-radius: var(--radius);
            padding: 0.7rem 0.8rem;
            margin-bottom: 1rem;
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.2rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            color: var(--gray-700);
            background: #fff;
        }
    </style>
</head>

<body>
    <header class="top">
        <nav>
            <a href="<?= esc($backUrl) ?>">Back</a>
            <a href="feed_guidelines.php">Feed Guidelines</a>
            <a href="<?= esc($logoutUrl) ?>">Logout</a>
        </nav>
    </header>

    <main class="container">
        <div class="feed-form-wrap">
            <h2>Add Feed Guideline</h2>
            <p>Use clear and practical food instructions pet owners can follow safely.</p>

            <?php if ($error !== ''): ?>
                <div class="error-box"><?= esc($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="field">
                    <label for="pet_type">Pet Type</label>
                    <input id="pet_type" type="text" name="pet_type" placeholder="Example: Dog" required>
                </div>

                <div class="field">
                    <label for="suitable">Suitable Foods</label>
                    <textarea id="suitable" name="suitable" placeholder="List recommended foods and portions" required></textarea>
                </div>

                <div class="field">
                    <label for="not_suitable">Foods To Avoid</label>
                    <textarea id="not_suitable" name="not_suitable" placeholder="List foods that are harmful" required></textarea>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">Save Guideline</button>
                    <a class="btn-link" href="feed_guidelines.php">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>

</html>
