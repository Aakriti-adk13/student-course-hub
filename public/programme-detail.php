<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: programmes.php'); exit; }

// Get programme
$stmt = $db->prepare(
    "SELECT p.*, l.LevelName, s.Name AS LeaderName, s.StaffID AS LeaderID
     FROM Programmes p
     JOIN Levels l ON p.LevelID = l.LevelID
     LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
     WHERE p.ProgrammeID = ? AND p.is_published = 1"
);
$stmt->execute([$id]);
$programme = $stmt->fetch();
if (!$programme) { header('Location: programmes.php'); exit; }

// Get modules grouped by year
$modules = $db->prepare(
    "SELECT m.*, s.Name AS LeaderName, pm.Year
     FROM ProgrammeModules pm
     JOIN Modules m ON pm.ModuleID = m.ModuleID
     LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
     WHERE pm.ProgrammeID = ?
     ORDER BY pm.Year, m.ModuleName"
);
$modules->execute([$id]);
$allModules = $modules->fetchAll();

$byYear = [];
foreach ($allModules as $m) {
    $byYear[$m['Year']][] = $m;
}
ksort($byYear);

// Count interested students
$intCount = $db->prepare("SELECT COUNT(*) FROM InterestedStudents WHERE ProgrammeID = ?");
$intCount->execute([$id]);
$interested = $intCount->fetchColumn();

// Handle interest form POST
$formSuccess = $formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_interest'])) {
    require_once __DIR__ . '/../includes/sanitize.php';
    $name  = sanitize($_POST['student_name'] ?? '');
    $email = sanitizeEmail($_POST['student_email'] ?? '');

    if (!$name || strlen($name) < 2) {
        $formError = 'Please enter your full name.';
    } elseif (!$email) {
        $formError = 'Please enter a valid email address.';
    } else {
        // Check for duplicate
        $check = $db->prepare("SELECT InterestID FROM InterestedStudents WHERE ProgrammeID = ? AND Email = ?");
        $check->execute([$id, $email]);
        if ($check->fetch()) {
            $formError = 'You have already registered interest in this programme.';
        } else {
            $ins = $db->prepare("INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email) VALUES (?, ?, ?)");
            $ins->execute([$id, $name, $email]);
            $formSuccess = "Thanks, {$name}! We'll keep you updated about " . htmlspecialchars($programme['ProgrammeName']) . '.';
        }
    }
}

$pageTitle = $programme['ProgrammeName'];
$pageDesc  = $programme['Description'] ?? '';
require_once __DIR__ . '/../templates/header.php';
?>

<!-- DETAIL HERO -->
<div class="detail-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a> › <a href="programmes.php">Programmes</a> › <?= htmlspecialchars($programme['ProgrammeName']) ?>
        </nav>
        <h1><?= htmlspecialchars($programme['ProgrammeName']) ?></h1>
        <div class="detail-meta">
            <span>📚 <?= htmlspecialchars($programme['LevelName']) ?></span>
            <span>👤 <?= htmlspecialchars($programme['LeaderName'] ?? 'TBC') ?></span>
            <span>📋 <?= count($allModules) ?> modules</span>
            <span>🎓 <?= $interested ?> interested student<?= $interested !== 1 ? 's' : '' ?></span>
        </div>
    </div>
</div>

<section>
    <div class="container detail-layout">

        <!-- MAIN: modules by year -->
        <main>
            <?php if ($programme['Description']): ?>
            <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:2rem;">
                <h2 style="font-family:var(--font-display);color:var(--navy);margin-bottom:0.6rem;font-size:1.2rem;">About this Programme</h2>
                <p style="color:var(--ink);line-height:1.8;"><?= htmlspecialchars($programme['Description']) ?></p>
            </div>
            <?php endif; ?>

            <h2 style="font-family:var(--font-display);color:var(--navy);font-size:1.4rem;margin-bottom:1.5rem;">Programme Structure</h2>

            <?php if (empty($byYear)): ?>
            <p style="color:var(--muted);">No modules assigned yet.</p>
            <?php else: ?>
            <?php foreach ($byYear as $year => $mods): ?>
            <div class="year-section">
                <div class="year-label">
                    <?= $programme['LevelID'] == 2 ? 'Postgraduate Modules' : "Year {$year}" ?>
                </div>
                <div class="module-list">
                    <?php foreach ($mods as $m): ?>
                    <div class="module-item">
                        <div>
                            <div class="module-item-name"><?= htmlspecialchars($m['ModuleName']) ?></div>
                            <?php if ($m['LeaderName']): ?>
                            <div class="module-item-leader">Module leader: <?= htmlspecialchars($m['LeaderName']) ?></div>
                            <?php endif; ?>
                            <?php if ($m['Description']): ?>
                            <div style="font-size:0.82rem;color:var(--muted);margin-top:0.3rem;line-height:1.5;"><?= htmlspecialchars($m['Description']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <!-- SIDEBAR -->
        <aside>
            <!-- Programme leader -->
            <?php if ($programme['LeaderName']): ?>
            <div class="sidebar-card">
                <h3>Programme Leader</h3>
                <div class="staff-info">
                    <div class="staff-avatar"><?= strtoupper(substr($programme['LeaderName'], 0, 1)) ?></div>
                    <div>
                        <div class="staff-name"><?= htmlspecialchars($programme['LeaderName']) ?></div>
                        <div class="staff-role">Programme Leader</div>
                    </div>
                </div>
                <a href="staff.php" class="btn btn-sm btn-outline" style="border-color:var(--teal);color:var(--teal);">View all staff →</a>
            </div>
            <?php endif; ?>

            <!-- Register interest -->
            <div class="sidebar-card">
                <h3>Register Your Interest</h3>
                <?php if ($formSuccess): ?>
                <div class="alert alert-success"><?= $formSuccess ?></div>
                <?php else: ?>
                <?php if ($formError): ?>
                <div class="alert alert-error"><?= htmlspecialchars($formError) ?></div>
                <?php endif; ?>
                <form class="interest-form" method="POST" action="programme-detail.php?id=<?= $id ?>" novalidate>
                    <input type="hidden" name="register_interest" value="1">
                    <div class="form-group">
                        <label for="student_name">Your name</label>
                        <input type="text" id="student_name" name="student_name" required
                               placeholder="Jane Smith"
                               value="<?= isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="student_email">Email address</label>
                        <input type="email" id="student_email" name="student_email" required
                               placeholder="jane@example.com"
                               value="<?= isset($_POST['student_email']) ? htmlspecialchars($_POST['student_email']) : '' ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;">Register Interest</button>
                    <p style="font-size:0.75rem;color:var(--muted);margin-top:0.5rem;text-align:center;">
                        We'll contact you about open days and updates only.
                    </p>
                </form>
                <?php endif; ?>
            </div>

            <!-- Quick facts -->
            <div class="sidebar-card">
                <h3>Quick Facts</h3>
                <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                    <tr>
                        <td style="padding:0.5rem 0;color:var(--muted);border-bottom:1px solid var(--border);">Level</td>
                        <td style="padding:0.5rem 0;border-bottom:1px solid var(--border);font-weight:500;"><?= htmlspecialchars($programme['LevelName']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:0.5rem 0;color:var(--muted);border-bottom:1px solid var(--border);">Modules</td>
                        <td style="padding:0.5rem 0;border-bottom:1px solid var(--border);font-weight:500;"><?= count($allModules) ?></td>
                    </tr>
                    <?php if ($programme['LevelID'] == 1): ?>
                    <tr>
                        <td style="padding:0.5rem 0;color:var(--muted);border-bottom:1px solid var(--border);">Duration</td>
                        <td style="padding:0.5rem 0;border-bottom:1px solid var(--border);font-weight:500;">3 Years</td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td style="padding:0.5rem 0;color:var(--muted);border-bottom:1px solid var(--border);">Duration</td>
                        <td style="padding:0.5rem 0;border-bottom:1px solid var(--border);font-weight:500;">1 Year</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="padding:0.5rem 0;color:var(--muted);">Interested</td>
                        <td style="padding:0.5rem 0;font-weight:500;color:var(--teal);"><?= $interested ?> student<?= $interested !== 1 ? 's' : '' ?></td>
                    </tr>
                </table>
            </div>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
