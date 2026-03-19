<?php
/**
 * admin/dashboard.php — Admin overview dashboard.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$db = getDB();

// Stats
$stats = [
    'programmes_total'     => $db->query("SELECT COUNT(*) FROM Programmes")->fetchColumn(),
    'programmes_published' => $db->query("SELECT COUNT(*) FROM Programmes WHERE is_published = 1")->fetchColumn(),
    'programmes_draft'     => $db->query("SELECT COUNT(*) FROM Programmes WHERE is_published = 0")->fetchColumn(),
    'modules'              => $db->query("SELECT COUNT(*) FROM Modules")->fetchColumn(),
    'staff'                => $db->query("SELECT COUNT(*) FROM Staff")->fetchColumn(),
    'interested'           => $db->query("SELECT COUNT(*) FROM InterestedStudents")->fetchColumn(),
];

// 5 most recently registered students
$recentStudents = $db->query(
    "SELECT i.StudentName, i.Email, i.RegisteredAt, p.ProgrammeName
     FROM InterestedStudents i
     JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
     ORDER BY i.RegisteredAt DESC LIMIT 5"
)->fetchAll();

// Programmes with most interest
$topProgrammes = $db->query(
    "SELECT p.ProgrammeName, p.is_published, COUNT(i.InterestID) AS StudentCount
     FROM Programmes p
     LEFT JOIN InterestedStudents i ON p.ProgrammeID = i.ProgrammeID
     GROUP BY p.ProgrammeID
     ORDER BY StudentCount DESC LIMIT 5"
)->fetchAll();

require_once __DIR__ . '/../templates/admin-header.php';
?>

<!-- Stats grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['programmes_total'] ?></div>
        <div class="stat-label">Total Programmes</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color:var(--teal)"><?= $stats['programmes_published'] ?></div>
        <div class="stat-label">Published</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color:var(--gold)"><?= $stats['programmes_draft'] ?></div>
        <div class="stat-label">Drafts</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['modules'] ?></div>
        <div class="stat-label">Modules</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['staff'] ?></div>
        <div class="stat-label">Staff Members</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color:var(--teal)"><?= $stats['interested'] ?></div>
        <div class="stat-label">Interested Students</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

    <!-- Recent registrations -->
    <div>
        <div class="admin-page-header" style="margin-bottom:1rem;">
            <h2 style="font-family:var(--font-display);font-size:1.1rem;color:var(--navy);">Recent Registrations</h2>
            <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-sm btn-primary">View all</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Programme</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentStudents)): ?>
                    <tr><td colspan="3" style="color:var(--muted);text-align:center;">No registrations yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($recentStudents as $s): ?>
                    <tr>
                        <td>
                            <div style="font-weight:500;color:var(--navy);"><?= htmlspecialchars($s['StudentName']) ?></div>
                            <div style="font-size:0.78rem;color:var(--muted);"><?= htmlspecialchars($s['Email']) ?></div>
                        </td>
                        <td style="font-size:0.85rem;"><?= htmlspecialchars($s['ProgrammeName']) ?></td>
                        <td style="font-size:0.8rem;color:var(--muted);white-space:nowrap;">
                            <?= date('d M Y', strtotime($s['RegisteredAt'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top programmes by interest -->
    <div>
        <div class="admin-page-header" style="margin-bottom:1rem;">
            <h2 style="font-family:var(--font-display);font-size:1.1rem;color:var(--navy);">Top Programmes by Interest</h2>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Programme</th>
                        <th>Status</th>
                        <th>Students</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topProgrammes as $p): ?>
                    <tr>
                        <td style="font-weight:500;color:var(--navy);font-size:0.875rem;">
                            <?= htmlspecialchars($p['ProgrammeName']) ?>
                        </td>
                        <td>
                            <span class="badge <?= $p['is_published'] ? 'badge-published' : 'badge-draft' ?>">
                                <?= $p['is_published'] ? 'Published' : 'Draft' ?>
                            </span>
                        </td>
                        <td style="font-weight:600;color:var(--teal);"><?= $p['StudentCount'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Quick actions -->
<div style="margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap;">
    <a href="<?= BASE_URL ?>/admin/programme-add.php" class="btn btn-primary">+ Add Programme</a>
    <a href="<?= BASE_URL ?>/admin/module-add.php"    class="btn btn-primary">+ Add Module</a>
    <a href="<?= BASE_URL ?>/admin/export-mailing.php" class="btn btn-outline" style="border-color:var(--teal);color:var(--teal);">⬇ Export Mailing List</a>
</div>

<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>