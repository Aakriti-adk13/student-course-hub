<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

$staff = $db->query(
    "SELECT s.*,
            GROUP_CONCAT(DISTINCT p.ProgrammeName ORDER BY p.ProgrammeName SEPARATOR '|||') AS LeadingProgrammes,
            GROUP_CONCAT(DISTINCT m.ModuleName ORDER BY m.ModuleName SEPARATOR '|||') AS LeadingModules
     FROM Staff s
     LEFT JOIN Programmes p ON p.ProgrammeLeaderID = s.StaffID AND p.is_published = 1
     LEFT JOIN Modules m ON m.ModuleLeaderID = s.StaffID
     GROUP BY s.StaffID
     ORDER BY s.Name"
)->fetchAll();

$pageTitle = 'Our Staff';
require_once __DIR__ . '/../templates/header.php';
?>

<section style="background:var(--navy);padding:2.5rem 0;color:var(--white);">
    <div class="container">
        <h1 style="font-family:var(--font-display);font-size:clamp(1.6rem,3.5vw,2.4rem);margin-bottom:0.3rem;">Our Staff</h1>
        <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;"><?= count($staff) ?> staff members across all programmes and modules</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="staff-grid">
            <?php foreach ($staff as $i => $s):
                // Initials
                $parts    = explode(' ', $s['Name']);
                $initials = implode('', array_map(fn($p) => strtoupper(substr(trim($p),0,1)), array_slice($parts,-2)));
                $programmes = $s['LeadingProgrammes'] ? explode('|||', $s['LeadingProgrammes']) : [];
                $modList    = $s['LeadingModules']    ? explode('|||', $s['LeadingModules'])    : [];
            ?>
            <div class="staff-card fade-up" style="animation-delay:<?= min($i * 0.05, 0.4) ?>s">
                <div class="avatar">
                    <?php if ($s['Image'] ?? null): ?>
                        <img src="<?= htmlspecialchars($s['Image']) ?>" alt="<?= htmlspecialchars($s['Name']) ?>">
                    <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                    <?php endif; ?>
                </div>
                <h3><?= htmlspecialchars($s['Name']) ?></h3>
                <div class="role">
                    <?php
                    $roles = [];
                    if (!empty($programmes)) $roles[] = 'Programme Leader';
                    if (!empty($modList))    $roles[] = 'Module Leader';
                    echo $roles ? implode(' · ', $roles) : 'Staff Member';
                    ?>
                </div>

                <?php if (!empty($programmes)): ?>
                <div class="programmes" style="margin-top:0.75rem;">
                    <?php foreach (array_slice($programmes, 0, 2) as $prog): ?>
                    <span class="tag"><?= htmlspecialchars($prog) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($programmes) > 2): ?>
                    <span class="tag">+<?= count($programmes) - 2 ?> more</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($modList)): ?>
                <div style="margin-top:0.5rem;font-size:0.78rem;color:var(--muted);">
                    Leads <?= count($modList) ?> module<?= count($modList) !== 1 ? 's' : '' ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
