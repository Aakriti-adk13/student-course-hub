<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Get ProgrammeID from URL
$programmeID = (int)($_GET['id'] ?? 0);
if (!$programmeID) {
    die("Invalid programme ID.");
}

// Detect published/active column
$progColumns = $db->query("SHOW COLUMNS FROM Programmes")->fetchAll(PDO::FETCH_COLUMN);
$statusFilter = '';
if (in_array('published', $progColumns)) {
    $statusFilter = ' AND p.published = 1';
} elseif (in_array('active', $progColumns)) {
    $statusFilter = ' AND p.active = 1';
}

// Build query
$sql = "SELECT p.*, l.LevelName, s.Name AS LeaderName
        FROM Programmes p
        JOIN Levels l ON p.LevelID = l.LevelID
        LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
        WHERE p.ProgrammeID = ? $statusFilter
        LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute([$programmeID]);
$programme = $stmt->fetch();

if (!$programme) {
    die("Programme not found.");
}

$pageTitle = $programme['ProgrammeName'] ?? 'Programme Detail';
require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
    <div class="container">
        <h1><?= htmlspecialchars($programme['ProgrammeName'] ?? 'TBC') ?></h1>
        <p>Level: <?= htmlspecialchars($programme['LevelName'] ?? 'TBC') ?></p>
        <p>Leader: <?= htmlspecialchars($programme['LeaderName'] ?? 'TBC') ?></p>
        <div class="programme-description">
            <?= nl2br(htmlspecialchars($programme['Description'] ?? '')) ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>