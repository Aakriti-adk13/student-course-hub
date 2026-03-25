
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

$username = 'admin';
$email    = 'admin@example.com';
$password = '123'; // change later!

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password, role)
        VALUES (?, ?, ?, 'admin')
    ");
    $stmt->execute([$username, $email, $hash]);

    echo "✅ Admin created successfully!";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
