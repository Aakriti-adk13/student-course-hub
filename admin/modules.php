<?php
/**
 * admin/modules.php — List all modules with actions.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle  = 'Manage Modules';
$activePage = 'modules';

$db = getDB();

$modules = $db->query(
    "SELECT m.*, s.Name AS LeaderName,
            COUNT(DISTINCT pm.ProgrammeID) AS ProgrammeCount
     FROM Modules m
     LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
     LEFT JOIN ProgrammeModules pm ON m.ModuleID = pm.ModuleID
     GROUP BY m.ModuleID
     ORDER BY m.ModuleName"
)->fetchAll();

require_once __DIR__ . '/../templates/admin-header.php';
?>

<?php if (isset($_GET['saved'])): ?>
<div class="flash flash-success flash-auto">Module saved successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="flash flash-success flash-auto">Module deleted.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="flash flash-error">Error: <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="admin-page-header">
    <h1>Modules</h1>
    <a href="<?= BASE_URL ?>/admin/module-add.php" class="btn btn-primary">+ Add Module</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Module Name</th>
                <th>Leader</th>
                <th>Description</th>
                <th>Programmes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($modules)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--muted);">No modules yet.</td></tr>
            <?php else: ?>
            <?php foreach ($modules as $m): ?>
            <tr>
                <td style="font-weight:500;color:var(--navy);"><?= htmlspecialchars($m['ModuleName']) ?></td>
                <td style="color:var(--muted);font-size:0.85rem;white-space:nowrap;">
                    <?= htmlspecialchars($m['LeaderName'] ?? '—') ?>
                </td>
                <td style="font-size:0.85rem;max-width:260px;">
                    <?php if ($m['Description']): ?>
                        <?= htmlspecialchars(mb_substr($m['Description'], 0, 80)) ?>…
                    <?php else: ?>
                        <span style="color:var(--muted);font-style:italic;">No description</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= $m['ProgrammeCount'] ?></td>
                <td>
                    <div class="actions">
                        <a href="<?= BASE_URL ?>/admin/module-edit.php?id=<?= $m['ModuleID'] ?>"
                           class="btn btn-sm btn-primary">Edit</a>

                        <form method="POST" action="<?= BASE_URL ?>/admin/module-delete.php" style="display:inline;">
                            <input type="hidden" name="module_id"   value="<?= $m['ModuleID'] ?>">
                            <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/admin/modules.php">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    data-confirm="Delete '<?= htmlspecialchars($m['ModuleName'], ENT_QUOTES) ?>'? This cannot be undone.">
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>