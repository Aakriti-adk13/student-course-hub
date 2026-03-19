<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sanitize.php';

$db = getDB();
$query = sanitize($_GET['q'] ?? '');
$progResults = [];
$modResults  = [];

// Check for multi-word queries
if (strlen($query) >= 2) {
    $words = preg_split('/\s+/', $query); // split query into words

    // Build programme WHERE clause dynamically
    $progWhereParts = [];
    $progParams = [];

    foreach ($words as $word) {
        $progWhereParts[] = "(p.ProgrammeName LIKE ? OR p.Description LIKE ?)";
        $like = '%' . $word . '%';
        $progParams[] = $like;
        $progParams[] = $like;
    }

    $progWhereSql = implode(' AND ', $progWhereParts);

    // Check if table has a 'published' or 'active' column
    $columns = $db->query("SHOW COLUMNS FROM Programmes")->fetchAll(PDO::FETCH_COLUMN);
    $statusFilter = '';
    if (in_array('published', $columns)) {
        $statusFilter = 'AND p.published = 1';
    } elseif (in_array('active', $columns)) {
        $statusFilter = 'AND p.active = 1';
    }

    // Search programmes
    $stmt = $db->prepare(
        "SELECT p.ProgrammeID, p.ProgrammeName, p.Description, l.LevelName, s.Name AS LeaderName
         FROM Programmes p
         JOIN Levels l ON p.LevelID = l.LevelID
         LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
         WHERE $progWhereSql $statusFilter
         ORDER BY p.ProgrammeName
         LIMIT 20"
    );
    $stmt->execute($progParams);
    $progResults = $stmt->fetchAll();

    // Search modules
    $modWhereParts = [];
    $modParams = [];
    foreach ($words as $word) {
        $modWhereParts[] = "(m.ModuleName LIKE ? OR m.Description LIKE ?)";
        $like = '%' . $word . '%';
        $modParams[] = $like;
        $modParams[] = $like;
    }
    $modWhereSql = implode(' AND ', $modWhereParts);

    $stmt2 = $db->prepare(
        "SELECT m.ModuleID, m.ModuleName, m.Description, s.Name AS LeaderName
         FROM Modules m
         LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
         WHERE $modWhereSql
         ORDER BY m.ModuleName
         LIMIT 10"
    );
    $stmt2->execute($modParams);
    $modResults = $stmt2->fetchAll();
}
?>