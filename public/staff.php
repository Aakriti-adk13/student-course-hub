<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Detect columns in Programmes
$programmeColumns = $db->query("SHOW COLUMNS FROM Programmes")->fetchAll(PDO::FETCH_COLUMN);
$statusFilter = '';
if (in_array('published', $programmeColumns)) {
    $statusFilter = 'p.published = 1';
} elseif (in_array('active', $programmeColumns)) {
    $statusFilter = 'p.active = 1';
}

// Detect columns in Staff
$staffColumns = $db->query("SHOW COLUMNS FROM Staff")->fetchAll(PDO::FETCH_COLUMN);

// Determine what columns we can safely select
$selectCols = [];
$selectCols[] = in_array('StaffID', $staffColumns) ? 's.StaffID' : 'NULL AS StaffID';
$selectCols[] = in_array('Name', $staffColumns) ? 's.Name AS StaffName' : 'NULL AS StaffName';
$selectCols[] = in_array('Role', $staffColumns) ? 's.Role' : (in_array('Position', $staffColumns) ? 's.Position AS Role' : 'NULL AS Role');
$selectCols[] = in_array('Email', $staffColumns) ? 's.Email' : 'NULL AS Email';
$selectCols[] = in_array('Photo', $staffColumns) ? 's.Photo' : 'NULL AS Photo';

// Determine if we can join Programmes
$programmeJoin = '';
$programmeSelect = '';
if (in_array('ProgrammeID', $staffColumns) && in_array('ProgrammeID', $programmeColumns)) {
    $programmeJoin = 'LEFT JOIN Programmes p ON s.ProgrammeID = p.ProgrammeID';
    if ($statusFilter) {
        $programmeJoin .= " AND $statusFilter";
    }
    $programmeSelect = in_array('ProgrammeName', $programmeColumns) ? ', p.ProgrammeName' : '';
}

// Build SQL query
$sql = "SELECT " . implode(', ', $selectCols) . " $programmeSelect
        FROM Staff s
        $programmeJoin
        ORDER BY s.Name";

$stmt = $db->query($sql);
$staffMembers = $stmt->fetchAll();

$pageTitle = 'Staff Directory';
$pageDesc  = 'Browse all staff and their assigned programmes.';

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
    <div class="container">
        <h1 style="font-family:var(--font-display);font-size:clamp(1.6rem,3.5vw,2.4rem);margin-bottom:1rem;">Staff Directory</h1>
        <p style="color:rgba(0,0,0,0.6);font-size:0.9rem;"><?= count($staffMembers) ?> staff member<?= count($staffMembers) !== 1 ? 's' : '' ?> found</p>

        <?php if (empty($staffMembers)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <h3>No staff found</h3>
            <p>Check back later or ensure programmes exist.</p>
        </div>
        <?php else: ?>
        <div class="staff-grid">
            <?php foreach ($staffMembers as $s): ?>
            <div class="staff-card fade-up">
                <div class="staff-photo">
                    <?php if (!empty($s['Photo'])): ?>
                        <img src="<?= htmlspecialchars($s['Photo']) ?>" alt="<?= htmlspecialchars($s['StaffName'] ?? 'Staff') ?>">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                <div class="staff-info">
                    <div class="staff-name"><?= htmlspecialchars($s['StaffName'] ?? 'TBC') ?></div>
                    <div class="staff-role"><?= htmlspecialchars($s['Role'] ?? 'TBC') ?></div>
                    <div class="staff-programme"><?= htmlspecialchars($s['ProgrammeName'] ?? 'TBC') ?></div>
                    <div class="staff-email"><?= !empty($s['Email']) ? '<a href="mailto:' . htmlspecialchars($s['Email']) . '">' . htmlspecialchars($s['Email']) . '</a>' : 'TBC' ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>