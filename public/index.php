<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Home';
$pageDesc  = 'Discover undergraduate and postgraduate degree programmes. Browse modules, meet staff, and register your interest.';

$db = getDB();

// ✅ Counts (no is_published to avoid errors)
$totalProgrammes = $db->query("SELECT COUNT(*) FROM Programmes")->fetchColumn();
$totalModules    = $db->query("SELECT COUNT(*) FROM Modules")->fetchColumn();
$totalStaff      = $db->query("SELECT COUNT(*) FROM Staff")->fetchColumn();

// ✅ Featured programmes
$featured = $db->query(
    "SELECT p.*, l.LevelName, s.Name AS LeaderName
     FROM Programmes p
     JOIN Levels l ON p.LevelID = l.LevelID
     LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
     ORDER BY RAND() LIMIT 4"
)->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-content fade-up">
        <h1>Find Your <em>Perfect</em><br>Programme</h1>
        <p>Explore undergraduate and postgraduate degrees. Meet the staff, discover your modules, and take the first step towards your future.</p>
        <div class="hero-actions">
            <a href="programmes.php" class="btn btn-primary">Browse Programmes</a>
            <a href="programmes.php?level=2" class="btn btn-outline">Postgraduate Courses</a>
        </div>
    </div>
</section>

<!-- STATS -->
<section style="background: var(--white); border-bottom: 1px solid var(--border); padding: 2rem 0;">
    <div class="container" style="display:grid; grid-template-columns: repeat(3,1fr); text-align:center; gap:1rem;">
        
        <div class="fade-up delay-1">
            <div style="font-size:2.5rem;color:var(--teal);font-weight:700;">
                <?= $totalProgrammes ?>
            </div>
            <div style="color:var(--muted);font-size:0.9rem;">Programmes</div>
        </div>

        <div class="fade-up delay-2">
            <div style="font-size:2.5rem;color:var(--teal);font-weight:700;">
                <?= $totalModules ?>
            </div>
            <div style="color:var(--muted);font-size:0.9rem;">Modules</div>
        </div>

        <div class="fade-up delay-3">
            <div style="font-size:2.5rem;color:var(--teal);font-weight:700;">
                <?= $totalStaff ?>
            </div>
            <div style="color:var(--muted);font-size:0.9rem;">Staff</div>
        </div>

    </div>
</section>

<!-- FEATURED PROGRAMMES -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Featured Programmes</h2>
        <p class="section-subtitle">A selection of our most popular courses.</p>

        <div class="programme-grid">
            <?php foreach ($featured as $i => $p): ?>
                <?php $emoji = ['💻','🔬','📊','🤖','☁️','🛡️'][$i % 6]; ?>

                <a href="programme-detail.php?id=<?= $p['ProgrammeID'] ?>" class="programme-card">
                    
                    <div class="card-img">
                        <?php if (!empty($p['Image'])): ?>
                            <img src="<?= htmlspecialchars($p['Image']) ?>" alt="<?= htmlspecialchars($p['ProgrammeName']) ?>">
                        <?php else: ?>
                            <div style="font-size:3rem; text-align:center; padding:2rem;">
                                <?= $emoji ?>
                            </div>
                        <?php endif; ?>

                        <span class="card-level-badge <?= $p['LevelID'] == 1 ? 'badge-ug' : 'badge-pg' ?>">
                            <?= $p['LevelID'] == 1 ? 'UG' : 'PG' ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <h3><?= htmlspecialchars($p['ProgrammeName']) ?></h3>
                        <p><strong>Leader:</strong> <?= htmlspecialchars($p['LeaderName'] ?? 'TBC') ?></p>
                        <p><?= htmlspecialchars(substr($p['Description'] ?? '', 0, 100)) ?>...</p>
                    </div>

                    <div class="card-footer">
                        <span><?= htmlspecialchars($p['LevelName']) ?></span>
                        <span class="btn btn-sm btn-primary">View →</span>
                    </div>

                </a>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:2rem;">
            <a href="programmes.php" class="btn btn-outline">View All Programmes</a>
        </div>
    </div>
</section>

<!-- CTA -->
<section style="background:#0a2540;color:white;padding:3rem;text-align:center;">
    <h2>Ready to Take the Next Step?</h2>
    <p>Register your interest and stay updated.</p>
    <a href="programmes.php" class="btn btn-primary">Register Interest</a>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>