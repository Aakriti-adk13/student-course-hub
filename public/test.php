<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDB();
    echo "✅ Connected successfully!";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}