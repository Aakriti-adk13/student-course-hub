<?php
/**
 * admin/login.php — Admin login page.
 * Creates a session on successful authentication.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sanitize.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in — go to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error      = '';
$redirectTo = sanitize($_GET['redirect'] ?? BASE_URL . '/admin/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare(
                "SELECT AdminID, Username, PasswordHash
                 FROM Admins WHERE Username = ? LIMIT 1"
            );
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['PasswordHash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']       = $admin['AdminID'];
                $_SESSION['admin_username'] = $admin['Username'];
                header('Location: ' . $redirectTo);
                exit;
            } else {
                // Generic message — don't reveal whether username or password was wrong
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log('Login DB error: ' . $e->getMessage());
            $error = 'A server error occurred. Please try again.';
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <style>
        body { background: var(--navy); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card {
            background: var(--white); border-radius: var(--radius);
            padding: 2.5rem; width: 100%; max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-brand { text-align: center; margin-bottom: 2rem; }
        .login-brand h1 { font-family: var(--font-display); color: var(--navy); font-size: 1.5rem; }
        .login-brand p  { color: var(--muted); font-size: 0.875rem; margin-top: 0.25rem; }
        .login-form { display: flex; flex-direction: column; gap: 1rem; }
        .login-form label { font-size: 0.875rem; font-weight: 500; color: var(--ink); }
        .login-form input {
            display: block; width: 100%; margin-top: 0.3rem;
            padding: 0.7rem 1rem; border: 1px solid var(--border);
            border-radius: 8px; font-family: var(--font-body); font-size: 0.95rem;
            background: var(--cream); outline: none; transition: border-color 0.2s;
        }
        .login-form input:focus { border-color: var(--teal); background: var(--white); }
        .login-form button {
            margin-top: 0.5rem; padding: 0.8rem;
            background: var(--teal); color: var(--white);
            border: none; border-radius: 8px; font-family: var(--font-body);
            font-size: 1rem; font-weight: 500; cursor: pointer; transition: background 0.2s;
        }
        .login-form button:hover { background: var(--teal-l); }
        .login-error {
            background: #fdf0ee; border-left: 4px solid #c0392b;
            color: #922b21; padding: 0.75rem 1rem;
            border-radius: 6px; font-size: 0.875rem; margin-bottom: 0.5rem;
        }
        .back-link { text-align: center; margin-top: 1.5rem; font-size: 0.825rem; }
        .back-link a { color: var(--muted); text-decoration: none; }
        .back-link a:hover { color: var(--teal); }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-brand">
        <h1>Student Course Hub</h1>
        <p>Admin Panel</p>
    </div>

    <?php if ($error): ?>
    <div class="login-error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="login-form" method="POST"
          action="<?= BASE_URL ?>/admin/login.php<?= $redirectTo !== BASE_URL . '/admin/dashboard.php' ? '?redirect=' . urlencode($redirectTo) : '' ?>"
          novalidate>
        <label for="username">
            Username
            <input type="text" id="username" name="username" required
                   autocomplete="username"
                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                   autofocus>
        </label>
        <label for="password">
            Password
            <input type="password" id="password" name="password" required
                   autocomplete="current-password">
        </label>
        <button type="submit">Sign In</button>
    </form>

    <div class="back-link">
        <a href="<?= BASE_URL ?>/public/index.php">← Back to public site</a>
    </div>
</div>

</body>
</html>