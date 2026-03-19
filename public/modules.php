<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Detect published/active column in Programmes
$programmesColumns = $db->query("SHOW COLUMNS FROM Programmes")->fetchAll(PDO::FETCH_COLUMN);
$statusFilter = '';
if (in_array('published', $programmesColumns)) {
    $statusFilter = 'p.published = 1';
} elseif (in_array('active', $programmesColumns)) {
    $statusFilter = 'p.active = 1';
}

// Detect linkage column from Modules to Programmes
$moduleColumns = $db->query("SHOW COLUMNS FROM Modules")->fetchAll(PDO::FETCH_COLUMN);
$programmeJoin = '';
$programmeSelect = '';
if (in_array('ProgrammeID', $moduleColumns)) {
    $programmeJoin = 'LEFT JOIN Programmes p ON m.ProgrammeID = p.ProgrammeID';
    $programmeSelect = ', p.ProgrammeName';
    if ($statusFilter) {
        $programmeJoin .= " AND $statusFilter";
    }
}

// Build SQL query
$sql = "SELECT m.ModuleID, m.ModuleName, m.Description, s.Name AS LeaderName $programmeSelect
        FROM Modules m
        LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
        $programmeJoin
        ORDER BY m.ModuleName";

$stmt = $db->query($sql);
$modules = $stmt->fetchAll();

$pageTitle = 'All Modules';
$pageDesc  = 'Browse all modules and their programme information.';

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
    <div class="container">
        <h1 style="font-family:var(--font-display);font-size:clamp(1.6rem,3.5vw,2.4rem);margin-bottom:1rem;">All Modules</h1>
        <p style="color:rgba(0,0,0,0.6);font-size:0.9rem;"><?= count($modules) ?> module<?= count($modules) !== 1 ? 's' : '' ?> found</p>

        <?php if (empty($modules)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <h3>No modules found</h3>
            <p>Check back soon or ensure programmes exist.</p>
        </div>
        <?php else: ?>
        <div class="modules-table-wrap">
            <table class="modules-table">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Leader</th>
                        <th>Programme</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $m): ?>
                    <tr>
                        <td style="font-weight:500;color:var(--navy);"><?= htmlspecialchars($m['ModuleName']) ?></td>
                        <td style="color:var(--muted);font-size:0.85rem;"><?= htmlspecialchars($m['LeaderName'] ?? 'TBC') ?></td>
                        <td style="color:var(--muted);font-size:0.85rem;"><?= htmlspecialchars($m['ProgrammeName'] ?? 'TBC') ?></td>
                        <td style="font-size:0.85rem;"><?= htmlspecialchars(substr($m['Description'] ?? '', 0, 100)) ?>…</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>