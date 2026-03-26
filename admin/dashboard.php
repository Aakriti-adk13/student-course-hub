<?php
/**
 * admin/dashboard.php — Admin overview dashboard.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php'; // Add this for auth function

// Start session if not already started//
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//Protect page using the auth function (consistent with other admin files)
requireAdmin();

$pageTitle  = 'Dashboard';                                                                                                  
$activePage = 'dashboard';

$db = getDB();

//Stats//
$stats = [
    'programmes_total' => $db->query("SELECT COUNT(*) FROM Programmes")->fetchColumn(),
    'modules'          => $db->query("SELECT COUNT(*) FROM Modules")->fetchColumn(),
    'staff'            => $db->query("SELECT COUNT(*) FROM Staff")->fetchColumn(),
    'interested'       => $db->query("SELECT COUNT(*) FROM InterestedStudents")->fetchColumn(),
];

//Recent students//
$recentStudents = $db->query(
    "SELECT i.StudentName, i.Email, i.RegisteredAt, p.ProgrammeName
     FROM InterestedStudents i
     JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
     ORDER BY i.RegisteredAt DESC LIMIT 5"
)->fetchAll();

//Top programmes//
$topProgrammes = $db->query(
    "SELECT p.ProgrammeName, COUNT(i.InterestID) AS StudentCount
     FROM Programmes p
     LEFT JOIN InterestedStudents i ON p.ProgrammeID = i.ProgrammeID
     GROUP BY p.ProgrammeID
     ORDER BY StudentCount DESC LIMIT 5"
)->fetchAll();

//CORRECT HEADER PATH (matching your other admin files)
require_once __DIR__ . '/../templates/admin-header.php';
?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['programmes_total'] ?></div>
        <div class="stat-label">Total Programmes</div>
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
        <div class="stat-number"><?= $stats['interested'] ?></div>
        <div class="stat-label">Interested Students</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
    <!-- Recent registrations -->
    <div>
        <h2>Recent Registrations</h2>
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
                    <tr><td colspan="3">No registrations yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentStudents as $s): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($s['StudentName']) ?><br>
                                <small><?= htmlspecialchars($s['Email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($s['ProgrammeName']) ?></td>
                            <td><?= date('d M Y', strtotime($s['RegisteredAt'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Top programmes -->
    <div>
        <h2>Top Programmes</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Programme</th>
                    <th>Students</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProgrammes as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['ProgrammeName']) ?></td>
                        <td><?= $p['StudentCount'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Footer (matching the pattern used in other admin files) -->
<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>