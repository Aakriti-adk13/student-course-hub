<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sanitize.php';

$db = getDB();

$query = sanitize($_GET['q'] ?? '');
$results = [];

if (strlen($query) >= 2) {
    $like = '%' . $query . '%';

    // Search programmes
    $stmt = $db->prepare(
        "SELECT p.ProgrammeID, p.ProgrammeName, p.Description, l.LevelName, s.Name AS LeaderName
         FROM Programmes p
         JOIN Levels l ON p.LevelID = l.LevelID
         LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
         WHERE p.is_published = 1 AND (p.ProgrammeName LIKE ? OR p.Description LIKE ?)
         ORDER BY p.ProgrammeName LIMIT 20"
    );
    $stmt->execute([$like, $like]);
    $progResults = $stmt->fetchAll();

    // Search modules
    $stmt2 = $db->prepare(
        "SELECT m.ModuleID, m.ModuleName, m.Description, s.Name AS LeaderName
         FROM Modules m
         LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
         WHERE m.ModuleName LIKE ? OR m.Description LIKE ?
         ORDER BY m.ModuleName LIMIT 10"
    );
    $stmt2->execute([$like, $like]);
    $modResults = $stmt2->fetchAll();
} else {
    $progResults = [];
    $modResults  = [];
}

$pageTitle = $query ? "Search: {$query}" : 'Search';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="search-header">
    <div class="container">
        <h1 style="font-family:var(--font-display);font-size:1.4rem;color:var(--white);margin-bottom:1rem;">Search Programmes</h1>
        <form class="search-bar-large" action="search.php" method="GET" role="search">
            <input type="search" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="e.g. Cyber Security, Machine Learning…" aria-label="Search query" autofocus>
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<section class="section">
    <div class="container">

        <?php if (strlen($query) < 2 && $query !== ''): ?>
        <div class="alert alert-info">Please enter at least 2 characters to search.</div>

        <?php elseif ($query === ''): ?>
        <div class="empty-state">
            <div class="icon">🔍</div>
            <h3>Start your search</h3>
            <p>Enter a keyword above to find programmes and modules.</p>
        </div>

        <?php else: ?>
        <p class="search-result-count">
            Found <strong><?= count($progResults) ?></strong> programme<?= count($progResults) !== 1 ? 's' : '' ?>
            and <strong><?= count($modResults) ?></strong> module<?= count($modResults) !== 1 ? 's' : '' ?>
            for "<strong><?= htmlspecialchars($query) ?></strong>"
        </p>

        <?php if (!empty($progResults)): ?>
        <h2 style="font-family:var(--font-display);color:var(--navy);font-size:1.3rem;margin-bottom:1rem;">Programmes</h2>
        <div class="programme-grid" style="margin-bottom:3rem;">
            <?php
            $emojis = ['💻','🔬','🛡️','📊','🤖','☁️'];
            foreach ($progResults as $i => $p):
            ?>
            <a href="programme-detail.php?id=<?= $p['ProgrammeID'] ?>" class="programme-card fade-up">
                <div class="card-img">
                    <?= $emojis[$i % count($emojis)] ?>
                    <span class="card-level-badge badge-ug"><?= htmlspecialchars($p['LevelName']) ?></span>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($p['ProgrammeName']) ?></div>
                    <div class="card-leader"><?= htmlspecialchars($p['LeaderName'] ?? 'TBC') ?></div>
                    <div class="card-desc"><?= htmlspecialchars($p['Description'] ?? '') ?></div>
                </div>
                <div class="card-footer">
                    <span class="card-modules"><?= htmlspecialchars($p['LevelName']) ?></span>
                    <span class="btn btn-sm btn-primary">View →</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($modResults)): ?>
        <h2 style="font-family:var(--font-display);color:var(--navy);font-size:1.3rem;margin-bottom:1rem;">Modules</h2>
        <div class="modules-table-wrap">
            <table class="modules-table">
                <thead>
                    <tr><th>Module</th><th>Leader</th><th>Description</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($modResults as $m): ?>
                    <tr>
                        <td style="font-weight:500;color:var(--navy);"><?= htmlspecialchars($m['ModuleName']) ?></td>
                        <td style="color:var(--muted);font-size:0.85rem;"><?= htmlspecialchars($m['LeaderName'] ?? 'TBC') ?></td>
                        <td style="font-size:0.85rem;"><?= htmlspecialchars(substr($m['Description'] ?? '', 0, 100)) ?>…</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (empty($progResults) && empty($modResults)): ?>
        <div class="empty-state">
            <div class="icon">😕</div>
            <h3>No results found</h3>
            <p>Try different keywords or <a href="programmes.php" style="color:var(--teal);">browse all programmes</a>.</p>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
