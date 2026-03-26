<?php
/**
 * admin/programmes.php — List all programmes with actions.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle  = 'Manage Programmes';
$activePage = 'programmes';

$db = getDB();

$programmes = $db->query(
    "SELECT p.*, l.LevelName, s.Name AS LeaderName,
            (SELECT COUNT(*) FROM ProgrammeModules pm WHERE pm.ProgrammeID = p.ProgrammeID) AS ModuleCount,
            (SELECT COUNT(*) FROM InterestedStudents i WHERE i.ProgrammeID = p.ProgrammeID) AS StudentCount
     FROM Programmes p
     JOIN Levels l ON p.LevelID = l.LevelID
     LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
     ORDER BY l.LevelID, p.ProgrammeName"
)->fetchAll();

require_once __DIR__ . '/../templates/admin-header.php';
?>

<!-- Flash messages -->
<?php if (isset($_GET['saved'])): ?>
<div class="flash flash-success flash-auto">Programme saved successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="flash flash-success flash-auto">Programme deleted.</div>
<?php endif; ?>
<?php if (isset($_GET['toggled'])): ?>
<div class="flash flash-success flash-auto">
    Programme <?= $_GET['published'] == '1' ? 'published' : 'unpublished' ?> successfully.
</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="flash flash-error">An error occurred: <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="admin-page-header">
    <h1>Programmes</h1>
    <a href="<?= BASE_URL ?>/admin/programme-add.php" class="btn btn-primary">+ Add Programme</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Programme Name</th>
                <th>Level</th>
                <th>Leader</th>
                <th>Modules</th>
                <th>Interested</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($programmes)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--muted);">No programmes yet.</td></tr>
            <?php else: ?>
            <?php foreach ($programmes as $p): ?>
            <tr>
                <td style="font-weight:500;color:var(--navy);"><?= htmlspecialchars($p['ProgrammeName']) ?></td>
                <td style="white-space:nowrap;">
                    <span class="badge <?= $p['LevelID'] == 1 ? 'badge-ug' : 'badge-pg' ?>"
                          style="<?= $p['LevelID'] == 1 ? 'background:#fef3d0;color:#7a5c00;' : 'background:#d1f5ed;color:#0a5c52;' ?>">
                        <?= htmlspecialchars($p['LevelName']) ?>
                    </span>
                </td>
                <td style="font-size:0.85rem;color:var(--muted);">
                    <?= htmlspecialchars($p['LeaderName'] ?? '—') ?>
                </td>
                <td style="text-align:center;"><?= $p['ModuleCount'] ?></td>
                <td style="text-align:center;color:var(--teal);font-weight:600;"><?= $p['StudentCount'] ?></td>
                <td>
                    <span class="badge <?= $p['is_published'] ? 'badge-published' : 'badge-draft' ?>">
                        <?= $p['is_published'] ? 'Published' : 'Draft' ?>
                    </span>
                </td>
                <td>
                    <div class="actions">
                        <!-- Edit -->
                        <a href="<?= BASE_URL ?>/admin/programme-edit.php?id=<?= $p['ProgrammeID'] ?>"
                           class="btn btn-sm btn-primary">Edit</a>

                        <!-- Toggle publish -->
                        <form method="POST" action="<?= BASE_URL ?>/actions/toggle-publish.php" style="display:inline;">
                            <input type="hidden" name="programme_id" value="<?= $p['ProgrammeID'] ?>">
                            <input type="hidden" name="redirect_to"  value="<?= BASE_URL ?>/admin/programmes.php">
                            <button type="submit"
                                    class="btn btn-sm <?= $p['is_published'] ? 'btn-outline' : 'btn-gold' ?>"
                                    style="<?= $p['is_published'] ? 'border-color:var(--muted);color:var(--muted);' : '' ?>">
                                <?= $p['is_published'] ? 'Unpublish' : 'Publish' ?>
                            </button>
                        </form>

                        <!-- Delete -->
                        <form method="POST" action="<?= BASE_URL ?>/admin/programme-delete.php" style="display:inline;">
                            <input type="hidden" name="programme_id" value="<?= $p['ProgrammeID'] ?>">
                            <input type="hidden" name="redirect_to"  value="<?= BASE_URL ?>/admin/programmes.php">
                            <button type="submit"
                                    class="btn btn-sm btn-danger"
                                    data-confirm="Delete '<?= htmlspecialchars($p['ProgrammeName'], ENT_QUOTES) ?>'? This cannot be undone.">
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