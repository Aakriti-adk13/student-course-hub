<?php
/**
 * admin/students.php — View and manage the student interest mailing list.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle  = 'Mailing List';
$activePage = 'students';

$db = getDB();

// Filter by programme
$filterProgramme = isset($_GET['programme_id']) && $_GET['programme_id'] !== ''
                     ? (int)$_GET['programme_id']
                     : null;

// Build query
$sql    = "SELECT i.InterestID, i.StudentName, i.Email, i.RegisteredAt,
                  p.ProgrammeName, p.ProgrammeID
           FROM InterestedStudents i
           JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID";
$params = [];

if ($filterProgramme) {
    $sql    .= " WHERE i.ProgrammeID = ?";
    $params[] = $filterProgramme;
}
$sql .= " ORDER BY i.RegisteredAt DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Programme list for filter dropdown
$programmes = $db->query(
    "SELECT ProgrammeID, ProgrammeName FROM Programmes ORDER BY ProgrammeName"
)->fetchAll();

require_once __DIR__ . '/../templates/admin-header.php';
?>

<?php if (isset($_GET['withdrawn'])): ?>
<div class="flash flash-success flash-auto">Student registration removed.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="flash flash-error">Error: <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="admin-page-header">
    <h1>Mailing List</h1>
    <a href="<?= BASE_URL ?>/admin/export-mailing.php<?= $filterProgramme ? '?programme_id=' . $filterProgramme : '' ?>"
       class="btn btn-primary">⬇ Export CSV</a>
</div>

<!-- Filter bar -->
<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <form method="GET" action="<?= BASE_URL ?>/admin/students.php" style="display:flex;gap:0.5rem;align-items:center;">
        <label for="programme_id" style="font-size:0.875rem;font-weight:500;color:var(--ink);">Filter by programme:</label>
        <select id="programme_id" name="programme_id"
                onchange="this.form.submit()"
                style="padding:0.45rem 0.75rem;border:1px solid var(--border);border-radius:6px;font-family:var(--font-body);font-size:0.875rem;background:var(--white);outline:none;">
            <option value="">All programmes</option>
            <?php foreach ($programmes as $p): ?>
            <option value="<?= $p['ProgrammeID'] ?>"
                <?= $filterProgramme == $p['ProgrammeID'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['ProgrammeName']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
    <span style="font-size:0.875rem;color:var(--muted);">
        <?= count($students) ?> record<?= count($students) !== 1 ? 's' : '' ?>
    </span>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Student Name</th>
                <th>Email</th>
                <th>Programme</th>
                <th>Registered</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
            <tr>
                <td colspan="6" style="text-align:center;color:var(--muted);padding:2rem;">
                    No registrations found.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($students as $i => $s): ?>
            <tr>
                <td style="color:var(--muted);font-size:0.8rem;"><?= $i + 1 ?></td>
                <td style="font-weight:500;color:var(--navy);"><?= htmlspecialchars($s['StudentName']) ?></td>
                <td>
                    <a href="mailto:<?= htmlspecialchars($s['Email']) ?>"
                       style="color:var(--teal);text-decoration:none;font-size:0.875rem;">
                        <?= htmlspecialchars($s['Email']) ?>
                    </a>
                </td>
                <td style="font-size:0.875rem;"><?= htmlspecialchars($s['ProgrammeName']) ?></td>
                <td style="font-size:0.8rem;color:var(--muted);white-space:nowrap;">
                    <?= date('d M Y, H:i', strtotime($s['RegisteredAt'])) ?>
                </td>
                <td>
                    <form method="POST" action="<?= BASE_URL ?>/actions/withdraw-interest.php" style="display:inline;">
                        <input type="hidden" name="interest_id" value="<?= $s['InterestID'] ?>">
                        <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/admin/students.php<?= $filterProgramme ? '?programme_id=' . $filterProgramme : '' ?>">
                        <button type="submit" class="btn btn-sm btn-danger"
                                data-confirm="Remove <?= htmlspecialchars($s['StudentName'], ENT_QUOTES) ?> from the mailing list?">
                            Remove
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>