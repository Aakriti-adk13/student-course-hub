<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

$modules = $db->query(
    "SELECT m.*, s.Name AS LeaderName,
            GROUP_CONCAT(DISTINCT p.ProgrammeName ORDER BY p.ProgrammeName SEPARATOR ', ') AS Programmes
     FROM Modules m
     LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
     LEFT JOIN ProgrammeModules pm ON m.ModuleID = pm.ModuleID
     LEFT JOIN Programmes p ON pm.ProgrammeID = p.ProgrammeID AND p.is_published = 1
     GROUP BY m.ModuleID
     ORDER BY m.ModuleName"
)->fetchAll();

$pageTitle = 'All Modules';
require_once __DIR__ . '/../templates/header.php';
?>

<section style="background:var(--navy);padding:2.5rem 0;color:var(--white);">
    <div class="container">
        <h1 style="font-family:var(--font-display);font-size:clamp(1.6rem,3.5vw,2.4rem);margin-bottom:0.3rem;">All Modules</h1>
        <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;"><?= count($modules) ?> modules across all programmes</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="modules-table-wrap">
            <table class="modules-table">
                <thead>
                    <tr>
                        <th>Module Name</th>
                        <th>Module Leader</th>
                        <th>Description</th>
                        <th>Programmes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $m): ?>
                    <tr>
                        <td style="font-weight:500;color:var(--navy);white-space:nowrap;"><?= htmlspecialchars($m['ModuleName']) ?></td>
                        <td style="white-space:nowrap;color:var(--muted);font-size:0.85rem;"><?= htmlspecialchars($m['LeaderName'] ?? 'TBC') ?></td>
                        <td style="font-size:0.85rem;max-width:280px;">
                            <?php if ($m['Description']): ?>
                            <?= htmlspecialchars(substr($m['Description'], 0, 100)) ?><?= strlen($m['Description']) > 100 ? '…' : '' ?>
                            <?php else: ?>
                            <span style="color:var(--muted);">No description</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.8rem;color:var(--muted);max-width:200px;">
                            <?= $m['Programmes'] ? htmlspecialchars($m['Programmes']) : '<em>None assigned</em>' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
