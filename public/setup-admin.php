<?php
/**
 * setup-admin.php — ONE-TIME setup script to create an admin account.
 * DELETE THIS FILE immediately after running it.
 * Place in your project ROOT (not inside /admin/).
 *
 * Access: http://localhost/student-course-hub/setup-admin.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if ($username === '' || $password === '') {
        $message = 'Username and password are required.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
    } else {
        try {
            $db   = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Create Admins table if it doesn't exist yet
            $db->exec("
                CREATE TABLE IF NOT EXISTS Admins (
                    AdminID      INT AUTO_INCREMENT PRIMARY KEY,
                    Username     VARCHAR(80)  NOT NULL UNIQUE,
                    PasswordHash VARCHAR(255) NOT NULL,
                    CreatedAt    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Insert or update
            $stmt = $db->prepare(
                "INSERT INTO Admins (Username, PasswordHash)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE PasswordHash = VALUES(PasswordHash)"
            );
            $stmt->execute([$username, $hash]);

            $success = true;
            $message = "Admin account '{$username}' created successfully! Delete this file now.";

        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #0d1b2a; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 12px; padding: 2.5rem; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
        h1 { font-size: 1.4rem; color: #0d1b2a; margin-bottom: 0.3rem; }
        .subtitle { color: #7a8a99; font-size: 0.875rem; margin-bottom: 1.75rem; }
        .warning { background: #fff8e1; border-left: 4px solid #f59e0b; padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.825rem; color: #92400e; margin-bottom: 1.5rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 1rem; }
        label { font-size: 0.875rem; font-weight: 500; color: #1a2e44; }
        input { padding: 0.65rem 0.9rem; border: 1px solid #ddd8cf; border-radius: 8px; font-size: 0.95rem; outline: none; transition: border-color 0.2s; }
        input:focus { border-color: #0a7c6e; }
        button { width: 100%; padding: 0.8rem; background: #0a7c6e; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; margin-top: 0.5rem; }
        button:hover { background: #0d9b8a; }
        .msg { padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1rem; border-left: 4px solid; }
        .msg-success { background: #e8f8f5; border-color: #0a7c6e; color: #0a5c52; }
        .msg-error   { background: #fdf0ee; border-color: #c0392b; color: #922b21; }
        .next-steps { margin-top: 1.5rem; background: #f0ebe3; border-radius: 8px; padding: 1rem; font-size: 0.85rem; color: #1a2e44; line-height: 1.7; }
        .next-steps strong { display: block; margin-bottom: 0.4rem; }
        a.login-btn { display: block; text-align: center; margin-top: 1rem; padding: 0.7rem; background: #0d1b2a; color: #fff; border-radius: 8px; text-decoration: none; font-size: 0.9rem; }
        a.login-btn:hover { opacity: 0.85; }
    </style>
</head>
<body>
<div class="card">
    <h1>Admin Setup</h1>
    <p class="subtitle">Create your administrator account.</p>

    <div class="warning">
        ⚠ <strong>Security notice:</strong> Delete this file immediately after use.
        It should never be accessible on a live server.
    </div>

    <?php if ($message): ?>
    <div class="msg <?= $success ? 'msg-success' : 'msg-error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="setup-admin.php">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="off"
                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        </div>
        <div class="form-group">
            <label for="password">Password (min 8 characters)</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="confirm">Confirm password</label>
            <input type="password" id="confirm" name="confirm" required autocomplete="new-password">
        </div>
        <button type="submit">Create Admin Account</button>
    </form>

    <?php else: ?>
    <div class="next-steps">
        <strong>✅ Next steps:</strong>
        1. Delete <code>setup-admin.php</code> from your server immediately.<br>
        2. Log in to the admin panel below.<br>
        3. Start managing your programmes.
    </div>
    <a class="login-btn" href="<?= BASE_URL ?>/admin/login.php">Go to Admin Login →</a>
    <?php endif; ?>
</div>
</body>
</html>