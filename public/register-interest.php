<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sanitize.php';

$db = getDB();

// Load all published programmes for dropdown
$programmes = $db->query(
    "SELECT p.ProgrammeID, p.ProgrammeName, l.LevelName
     FROM Programmes p
     JOIN Levels l ON p.LevelID = l.LevelID
     WHERE p.is_published = 1
     ORDER BY l.LevelID, p.ProgrammeName"
)->fetchAll();

$formSuccess = $formError = '';
$selectedID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['student_name'] ?? '');
    $email = sanitizeEmail($_POST['student_email'] ?? '');
    $pid   = (int)($_POST['programme_id'] ?? 0);

    if (!$name || strlen($name) < 2) {
        $formError = 'Please enter your full name.';
    } elseif (!$email) {
        $formError = 'Please enter a valid email address.';
    } elseif (!$pid) {
        $formError = 'Please select a programme.';
    } else {
        // Verify programme exists
        $check = $db->prepare("SELECT ProgrammeID FROM Programmes WHERE ProgrammeID = ? AND is_published = 1");
        $check->execute([$pid]);
        if (!$check->fetch()) {
            $formError = 'Invalid programme selected.';
        } else {
            // Check duplicate
            $dup = $db->prepare("SELECT InterestID FROM InterestedStudents WHERE ProgrammeID = ? AND Email = ?");
            $dup->execute([$pid, $email]);
            if ($dup->fetch()) {
                $formError = 'You have already registered interest in this programme.';
            } else {
                $ins = $db->prepare("INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email) VALUES (?,?,?)");
                $ins->execute([$pid, $name, $email]);
                $formSuccess = "Thank you, {$name}! You've been added to the mailing list.";
            }
        }
    }
}

$pageTitle = 'Register Your Interest';
require_once __DIR__ . '/../templates/header.php';
?>

<section style="background:var(--navy);padding:2.5rem 0;color:var(--white);">
    <div class="container">
        <nav style="font-size:0.825rem;color:rgba(255,255,255,0.5);margin-bottom:0.5rem;">
            <a href="index.php" style="color:rgba(255,255,255,0.6);text-decoration:none;">Home</a> ›
            <a href="programmes.php" style="color:rgba(255,255,255,0.6);text-decoration:none;">Programmes</a> › Register Interest
        </nav>
        <h1 style="font-family:var(--font-display);font-size:clamp(1.6rem,3vw,2.2rem);">Register Your Interest</h1>
    </div>
</section>

<section class="section">
    <div class="page-form-wrap">
        <div class="form-card">
            <h2>Stay in the Loop</h2>
            <p>Tell us which programme interests you and we'll keep you updated about open days, application deadlines, and news.</p>

            <?php if ($formSuccess): ?>
            <div class="alert alert-success"><?= htmlspecialchars($formSuccess) ?></div>
            <div style="text-align:center;margin-top:1rem;">
                <a href="programmes.php" class="btn btn-primary">Browse More Programmes</a>
            </div>
            <?php else: ?>

            <?php if ($formError): ?>
            <div class="alert alert-error"><?= htmlspecialchars($formError) ?></div>
            <?php endif; ?>

            <form class="form-full" method="POST" action="register-interest.php" novalidate>
                <label for="student_name">
                    Full name
                    <input type="text" id="student_name" name="student_name" required
                           placeholder="Jane Smith"
                           value="<?= isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : '' ?>">
                </label>

                <label for="student_email">
                    Email address
                    <input type="email" id="student_email" name="student_email" required
                           placeholder="jane@example.com"
                           value="<?= isset($_POST['student_email']) ? htmlspecialchars($_POST['student_email']) : '' ?>">
                </label>

                <label for="programme_id">
                    Programme of interest
                    <select id="programme_id" name="programme_id" required>
                        <option value="">— Select a programme —</option>
                        <?php
                        $currentLevel = null;
                        foreach ($programmes as $p):
                            if ($p['LevelName'] !== $currentLevel):
                                if ($currentLevel !== null) echo '</optgroup>';
                                echo '<optgroup label="' . htmlspecialchars($p['LevelName']) . '">';
                                $currentLevel = $p['LevelName'];
                            endif;
                            $selected = (isset($_POST['programme_id']) && (int)$_POST['programme_id'] === (int)$p['ProgrammeID'])
                                     || (!isset($_POST['programme_id']) && $selectedID === (int)$p['ProgrammeID'])
                                     ? 'selected' : '';
                        ?>
                        <option value="<?= $p['ProgrammeID'] ?>" <?= $selected ?>><?= htmlspecialchars($p['ProgrammeName']) ?></option>
                        <?php endforeach; ?>
                        <?php if ($currentLevel !== null) echo '</optgroup>'; ?>
                    </select>
                </label>

                <div class="form-submit">
                    <button type="submit" class="btn btn-primary">Register Interest</button>
                </div>

                <p style="font-size:0.78rem;color:var(--muted);text-align:center;line-height:1.5;">
                    Your data is held securely and used only for programme communications.
                    You can withdraw your interest at any time.
                </p>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
