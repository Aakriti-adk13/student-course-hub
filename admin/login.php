<?php
/**
 * admin/login.php — Admin login page.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sanitize.php';

if (session_status() === PHP_SESSION_NONE) session_start();

//Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';
$redirectTo = sanitize($_GET['redirect'] ?? BASE_URL . '/admin/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {

        try {
            $db = getDB();

            //CORRECT QUERY (matches DB)
            $stmt = $db->prepare("
                SELECT UserID, Username, Password, Role
                FROM users
                WHERE Username = ? AND Role = 'admin'
                LIMIT 1
            ");

            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            //PASSWORD CHECK (for HASHED passwords)
            if ($admin && password_verify($password, $admin['Password'])) {

                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['UserID'];
                $_SESSION['admin_username'] = $admin['Username'];

                header('Location: ' . $redirectTo);
                exit;

            } else {
                $error = 'Invalid username or password.';
            }

        } catch (PDOException $e) {
            echo "DB ERROR: " . $e->getMessage();
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – <?= SITE_NAME ?></title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">

    <style>
        body {
            background: #0b1d2a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }

        .login-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-brand h1 {
            font-size: 1.5rem;
            color: #0b1d2a;
        }

        .login-brand p {
            color: #777;
            font-size: 0.9rem;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .login-form label {
            font-size: 0.85rem;
            font-weight: bold;
        }

        .login-form input {
            padding: 0.7rem;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .login-form button {
            margin-top: 0.5rem;
            padding: 0.8rem;
            background: #198754;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .login-form button:hover {
            background: #157347;
        }

        .login-error {
            background: #f8d7da;
            color: #842029;
            padding: 0.7rem;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<div class="login-card">

    <div class="login-brand">
        <h1>Student Course Hub</h1>
        <p>Admin Panel</p>
    </div>

    <?php if ($error): ?>
        <div class="login-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="login-form" method="POST">
        <label>
            Username
            <input type="text" name="username" required>
        </label>

        <label>
            Password
            <input type="password" name="password" required>
        </label>

        <button type="submit">Sign In</button>
    </form>

    <div class="back-link">
        <a href="<?= BASE_URL ?>/public/index.php">← Back to site</a>
    </div>

</div>

</body>
</html>