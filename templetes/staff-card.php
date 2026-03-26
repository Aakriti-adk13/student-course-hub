<?php
/**
 * staff-card.php — Reusable card for a single staff member.
 * Used on staff.php and optionally in programme-detail sidebars.
 *
 * Required variables:
 *   $staffMember  (array) — row from Staff (with optional extras)
 *                           expects: StaffID, Name, Image (optional),
 *                           LeadingProgrammes (pipe-separated string, optional),
 *                           LeadingModules    (pipe-separated string, optional)
 *   $index        (int)   — position for animation delay (0-based)
 *   $mode         (string)— 'grid' (default) full card | 'mini' compact sidebar block
 *
 * Usage:
 *   $mode = 'grid';
 *   foreach ($staff as $i => $staffMember) {
 *       $index = $i;
 *       include __DIR__ . '/../templates/staff-card.php';
 *   }
 */

$mode  = $mode ?? 'grid';
$index = $index ?? 0;
$delay = min($index * 0.05, 0.45);

// Build initials (last two name parts)
$nameParts = array_filter(explode(' ', $staffMember['Name']));
$initials  = implode('', array_map(
    fn($p) => strtoupper(substr(trim($p), 0, 1)),
    array_slice($nameParts, -2)
));

// Parse pipe-separated programme / module lists
$programmes = !empty($staffMember['LeadingProgrammes'])
    ? array_filter(explode('|||', $staffMember['LeadingProgrammes']))
    : [];
$modules    = !empty($staffMember['LeadingModules'])
    ? array_filter(explode('|||', $staffMember['LeadingModules']))
    : [];

// Role label
$roles = [];
if (!empty($programmes)) $roles[] = 'Programme Leader';
if (!empty($modules))    $roles[] = 'Module Leader';
$roleLabel = $roles ? implode(' · ', $roles) : 'Staff Member';

if ($mode === 'mini'): ?>

    <!-- Mini mode: compact sidebar block -->
    <div class="staff-info" style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid var(--border);">
        <div class="staff-avatar" aria-hidden="true">
            <?php if (!empty($staffMember['Image'])): ?>
                <img src="<?= htmlspecialchars($staffMember['Image']) ?>"
                     alt="<?= htmlspecialchars($staffMember['Name']) ?>">
            <?php else: ?>
                <?= htmlspecialchars($initials) ?>
            <?php endif; ?>
        </div>
        <div>
            <div class="staff-name"><?= htmlspecialchars($staffMember['Name']) ?></div>
            <div class="staff-role"><?= htmlspecialchars($roleLabel) ?></div>
        </div>
    </div>

<?php else: // 'grid' mode — full card on staff.php ?>

    <article class="staff-card fade-up" style="animation-delay:<?= $delay ?>s"
             aria-label="<?= htmlspecialchars($staffMember['Name']) ?>">

        <!-- Avatar -->
        <div class="avatar" aria-hidden="true">
            <?php if (!empty($staffMember['Image'])): ?>
                <img src="<?= htmlspecialchars($staffMember['Image']) ?>"
                     alt="Photo of <?= htmlspecialchars($staffMember['Name']) ?>"
                     loading="lazy">
            <?php else: ?>
                <?= htmlspecialchars($initials) ?>
            <?php endif; ?>
        </div>

        <!-- Name & role -->
        <h3><?= htmlspecialchars($staffMember['Name']) ?></h3>
        <div class="role"><?= htmlspecialchars($roleLabel) ?></div>

        <!-- Programme tags (max 2 visible) -->
        <?php if (!empty($programmes)): ?>
        <div class="programmes" aria-label="Programme leader for">
            <?php foreach (array_slice($programmes, 0, 2) as $prog): ?>
                <span class="tag"><?= htmlspecialchars($prog) ?></span>
            <?php endforeach; ?>
            <?php if (count($programmes) > 2): ?>
                <span class="tag">+<?= count($programmes) - 2 ?> more</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Module count -->
        <?php if (!empty($modules)): ?>
        <div style="margin-top:0.5rem;font-size:0.78rem;color:var(--muted);">
            Leads <?= count($modules) ?> module<?= count($modules) !== 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>

    </article>

<?php endif; ?>