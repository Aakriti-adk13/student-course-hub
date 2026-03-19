<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Filter by level
$levelFilter = isset($_GET['level']) && in_array($_GET['level'], ['1','2']) ? (int)$_GET['level'] : null;

$sql = "SELECT p.*, l.LevelName, s.Name AS LeaderName,
               (SELECT COUNT(*) FROM ProgrammeModules pm WHERE pm.ProgrammeID = p.ProgrammeID) AS ModuleCount
        FROM Programmes p
        JOIN Levels l ON p.LevelID = l.LevelID
        LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
        WHERE p.is_published = 1";
$params = [];
if ($levelFilter) {
    $sql .= " AND p.LevelID = ?";
    $params[] = $levelFilter;
}
$sql .= " ORDER BY l.LevelID, p.ProgrammeName";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$programmes = $stmt->fetchAll();

$pageTitle = 'All Programmes';
$pageDesc  = 'Browse all undergraduate and postgraduate programmes.';

require_once __DIR__ . '/../templates/header.php';
?>

<section style="background:var(--navy);padding:2.5rem 0;color:var(--white);">
    <div class="container">
        <h1 style="font-family:var(--font-display);font-size:clamp(1.6rem,3.5vw,2.4rem);margin-bottom:0.3rem;">
            <?= $levelFilter == 1 ? 'Undergraduate' : ($levelFilter == 2 ? 'Postgraduate' : 'All') ?> Programmes
        </h1>
        <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;"><?= count($programmes) ?> programme<?= count($programmes) !== 1 ? 's' : '' ?> found</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <!-- Filter pills -->
        <div class="filter-bar">
            <label>Filter by level:</label>
            <a href="programmes.php" class="filter-pill <?= !$levelFilter ? 'active' : '' ?>">All</a>
            <a href="programmes.php?level=1" class="filter-pill <?= $levelFilter == 1 ? 'active' : '' ?>">Undergraduate</a>
            <a href="programmes.php?level=2" class="filter-pill <?= $levelFilter == 2 ? 'active' : '' ?>">Postgraduate</a>
        </div>

        <?php if (empty($programmes)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <h3>No programmes found</h3>
            <p>Try removing the filter or check back soon.</p>
        </div>
        <?php else: ?>
        <div class="programme-grid">
            <?php
            $emojis = ['💻','🔬','🛡️','📊','🤖','☁️','🔐','📐','⚙️','🧠'];
            foreach ($programmes as $i => $p):
                $emoji = $emojis[$i % count($emojis)];
            ?>
            <a href="programme-detail.php?id=<?= $p['ProgrammeID'] ?>" class="programme-card fade-up" style="animation-delay:<?= min($i * 0.06, 0.5) ?>s">
                <div class="card-img">
                    <?php if ($p['Image']): ?>
                        <img src="<?= htmlspecialchars($p['Image']) ?>" alt="">
                    <?php else: ?>
                        <?= $emoji ?>
                    <?php endif; ?>
                    <span class="card-level-badge <?= $p['LevelID'] == 1 ? 'badge-ug' : 'badge-pg' ?>">
                        <?= $p['LevelID'] == 1 ? 'UG' : 'PG' ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($p['ProgrammeName']) ?></div>
                    <div class="card-leader">Led by <?= htmlspecialchars($p['LeaderName'] ?? 'TBC') ?></div>
                    <div class="card-desc"><?= htmlspecialchars($p['Description'] ?? '') ?></div>
                </div>
                <div class="card-footer">
                    <span class="card-modules"><?= $p['ModuleCount'] ?> modules · <?= htmlspecialchars($p['LevelName']) ?></span>
                    <span class="btn btn-sm btn-primary">View →</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
