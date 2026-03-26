<?php
/**
 * programme-card.php — Reusable card for a single programme.
 *
 * Required variables (pass via extract() or set before include):
 *   $programme  (array)  — row from Programmes JOIN Levels JOIN Staff
 *                          expects: ProgrammeID, ProgrammeName, LevelID,
 *                          LevelName, LeaderName, Description, Image,
 *                          ModuleCount (optional)
 *   $index      (int)    — card position for staggered animation delay (0-based)
 *
 * Usage example:
 *   foreach ($programmes as $i => $programme) {
 *       $index = $i;
 *       include __DIR__ . '/../templates/programme-card.php';
 *   }
 */

// Fallback emoji icons by position
$cardEmojis = ['💻','🔬','🛡️','📊','🤖','☁️','🔐','📐','⚙️','🧠','🌐','📡'];
$emoji      = $cardEmojis[($index ?? 0) % count($cardEmojis)];

$isUG    = ($programme['LevelID'] ?? 1) == 1;
$delay   = min(($index ?? 0) * 0.07, 0.56);
$modCount = $programme['ModuleCount'] ?? '';
?>

<a href="<?= BASE_URL ?>/public/programme-detail.php?id=<?= (int)$programme['ProgrammeID'] ?>"
   class="programme-card fade-up"
   style="animation-delay:<?= $delay ?>s"
   aria-label="<?= htmlspecialchars($programme['ProgrammeName']) ?> – <?= htmlspecialchars($programme['LevelName'] ?? '') ?>">

    <!-- Card image / emoji fallback -->
    <div class="card-img" role="img" aria-label="<?= htmlspecialchars($programme['ProgrammeName']) ?>">
        <?php if (!empty($programme['Image'])): ?>
            <img src="<?= htmlspecialchars($programme['Image']) ?>"
                 alt="<?= htmlspecialchars($programme['ProgrammeName']) ?>"
                 loading="lazy">
        <?php else: ?>
            <span aria-hidden="true"><?= $emoji ?></span>
        <?php endif; ?>

        <span class="card-level-badge <?= $isUG ? 'badge-ug' : 'badge-pg' ?>"
              aria-label="<?= $isUG ? 'Undergraduate' : 'Postgraduate' ?>">
            <?= $isUG ? 'UG' : 'PG' ?>
        </span>
    </div>

    <!-- Card body -->
    <div class="card-body">
        <div class="card-title"><?= htmlspecialchars($programme['ProgrammeName']) ?></div>
        <div class="card-leader">
            Led by <?= htmlspecialchars($programme['LeaderName'] ?? 'TBC') ?>
        </div>
        <div class="card-desc">
            <?= htmlspecialchars($programme['Description'] ?? '') ?>
        </div>
    </div>

    <!-- Card footer -->
    <div class="card-footer">
        <span class="card-modules">
            <?php if ($modCount): ?>
                <?= (int)$modCount ?> modules &middot;
            <?php endif; ?>
            <?= htmlspecialchars($programme['LevelName'] ?? '') ?>
        </span>
        <span class="btn btn-sm btn-primary" aria-hidden="true">View →</span>
    </div>

</a>