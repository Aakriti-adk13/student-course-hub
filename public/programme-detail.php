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

// Fetch programme details
$sql = "SELECT p.*, l.LevelName, s.Name AS LeaderName, s.Image AS LeaderImage
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

// Fetch modules grouped by year
$modStmt = $db->prepare(
    "SELECT m.ModuleID, m.ModuleName, m.Description, m.Image,
            s.Name AS LeaderName,
            pm.Year
     FROM ProgrammeModules pm
     JOIN Modules m ON pm.ModuleID = m.ModuleID
     LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
     WHERE pm.ProgrammeID = ?
     ORDER BY pm.Year, m.ModuleName"
);
$modStmt->execute([$programmeID]);
$allModules = $modStmt->fetchAll();

// Group modules by year
$modulesByYear = [];
foreach ($allModules as $mod) {
    $modulesByYear[$mod['Year']][] = $mod;
}
ksort($modulesByYear);

// Build leader initials for avatar fallback
$leaderName  = $programme['LeaderName'] ?? '';
$nameParts   = array_filter(explode(' ', $leaderName));
$initials    = implode('', array_map(fn($p) => strtoupper(substr($p, 0, 1)), array_slice($nameParts, -2)));

$pageTitle = $programme['ProgrammeName'];
$pageDesc  = substr($programme['Description'] ?? '', 0, 160);
require_once __DIR__ . '/../templetes/header.php';
?>

<!-- HERO -->
<section class="detail-hero">
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">Home</a> ›
            <a href="programmes.php">Programmes</a> ›
            <?= htmlspecialchars($programme['ProgrammeName']) ?>
        </nav>

        <h1><?= htmlspecialchars($programme['ProgrammeName']) ?></h1>

        <div class="detail-meta">
            <span>📚 <?= htmlspecialchars($programme['LevelName']) ?></span>
            <span>👤 Led by <?= htmlspecialchars($programme['LeaderName'] ?? 'TBC') ?></span>
            <span>📦 <?= count($allModules) ?> module<?= count($allModules) !== 1 ? 's' : '' ?></span>
        </div>
    </div>
</section>

<!-- MAIN LAYOUT -->
<section class="section">
    <div class="container">
        <div class="detail-layout">

            <!-- LEFT: Modules by Year -->
            <div>
                <?php if (!empty($programme['Description'])): ?>
                <p style="color:var(--muted);margin-bottom:2rem;line-height:1.8;font-size:0.95rem;">
                    <?= nl2br(htmlspecialchars($programme['Description'])) ?>
                </p>
                <?php endif; ?>

                <?php if (empty($modulesByYear)): ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>No modules assigned yet</h3>
                    <p>Check back soon — modules are being added to this programme.</p>
                </div>

                <?php else: ?>
                    <?php foreach ($modulesByYear as $year => $modules): ?>
                    <div class="year-section">
                        <div class="year-label">Year <?= (int)$year ?></div>
                        <div class="module-list">
                            <?php foreach ($modules as $module): ?>
                            <div class="module-item">
                                <?php if (!empty($module['Image'])): ?>
                                <div style="width:64px;height:64px;border-radius:8px;overflow:hidden;flex-shrink:0;border:1px solid var(--border);">
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($module['Image']) ?>"
                                         alt="" style="width:100%;height:100%;object-fit:cover;">
                                </div>
                                <?php endif; ?>

                                <div style="flex:1;">
                                    <div class="module-item-name">
                                        <?= htmlspecialchars($module['ModuleName']) ?>
                                    </div>
                                    <?php if (!empty($module['LeaderName'])): ?>
                                    <div class="module-item-leader">
                                        Module leader: <?= htmlspecialchars($module['LeaderName']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($module['Description'])): ?>
                                    <div style="font-size:0.82rem;color:var(--muted);margin-top:0.35rem;line-height:1.55;">
                                        <?= htmlspecialchars(mb_substr($module['Description'], 0, 160)) ?>
                                        <?= mb_strlen($module['Description']) > 160 ? '…' : '' ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Sidebar -->
            <aside>

                <!-- Programme Leader card -->
                <?php if (!empty($programme['LeaderName'])): ?>
                <div class="sidebar-card">
                    <h3>Programme Leader</h3>
                    <div class="staff-info">
                        <div class="staff-avatar" aria-hidden="true">
                            <?php if (!empty($programme['LeaderImage'])): ?>
                                <img src="<?= htmlspecialchars($programme['LeaderImage']) ?>"
                                     alt="<?= htmlspecialchars($programme['LeaderName']) ?>">
                            <?php else: ?>
                                <?= htmlspecialchars($initials) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="staff-name"><?= htmlspecialchars($programme['LeaderName']) ?></div>
                            <div class="staff-role">Programme Leader</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick stats card -->
                <div class="sidebar-card">
                    <h3>Programme Info</h3>
                    <table style="width:100%;font-size:0.875rem;border-collapse:collapse;">
                        <tr>
                            <td style="padding:0.45rem 0;color:var(--muted);">Level</td>
                            <td style="padding:0.45rem 0;font-weight:500;text-align:right;">
                                <?= htmlspecialchars($programme['LevelName']) ?>
                            </td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:0.45rem 0;color:var(--muted);">Total modules</td>
                            <td style="padding:0.45rem 0;font-weight:500;text-align:right;">
                                <?= count($allModules) ?>
                            </td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:0.45rem 0;color:var(--muted);">Duration</td>
                            <td style="padding:0.45rem 0;font-weight:500;text-align:right;">
                                <?= count($modulesByYear) ?> year<?= count($modulesByYear) !== 1 ? 's' : '' ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Register Interest card -->
                <div class="sidebar-card">
                    <h3>Register Your Interest</h3>

                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="margin-bottom:0;">
                        ✅ Thanks! We'll keep you updated.
                    </div>

                    <?php else: ?>

                    <?php
                    $interestErrors = [
                        'missing_fields'    => 'Please fill in all fields.',
                        'invalid_name'      => 'Please enter your full name.',
                        'invalid_email'     => 'Please enter a valid email address.',
                        'invalid_programme' => 'This programme is not available.',
                        'duplicate'         => 'You have already registered for this programme.',
                        'db_error'          => 'Something went wrong. Please try again.',
                    ];
                    $interestError = isset($_GET['error']) ? ($interestErrors[$_GET['error']] ?? 'An error occurred.') : '';
                    ?>

                    <?php if ($interestError): ?>
                    <div class="alert alert-error" style="margin-bottom:0.75rem;">
                        <?= htmlspecialchars($interestError) ?>
                    </div>
                    <?php endif; ?>

                    <form class="interest-form" method="POST"
                          action="<?= BASE_URL ?>/actions/register-interest.php">
                        <input type="hidden" name="programme_id" value="<?= $programmeID ?>">
                        <input type="hidden" name="redirect_to"
                               value="<?= BASE_URL ?>/public/programme-detail.php?id=<?= $programmeID ?>">

                        <div class="form-group">
                            <label for="student_name">Full name</label>
                            <input type="text" id="student_name" name="student_name" required
                                   placeholder="Jane Smith"
                                   value="<?= isset($_GET['name']) ? htmlspecialchars(urldecode($_GET['name'])) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="student_email">Email address</label>
                            <input type="email" id="student_email" name="student_email" required
                                   placeholder="jane@example.com"
                                   value="<?= isset($_GET['email']) ? htmlspecialchars(urldecode($_GET['email'])) : '' ?>">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.25rem;">
                            Register Interest
                        </button>
                        <p style="font-size:0.75rem;color:var(--muted);text-align:center;line-height:1.5;margin-top:0.5rem;">
                            Your data is held securely and used only for programme updates.
                        </p>
                    </form>
                    <?php endif; ?>
                </div>

            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templetes/footer.php'; ?>