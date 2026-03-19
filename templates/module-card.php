<?php
/**
 * module-card.php — Reusable card for a single module.
 * Works both as a table row (in modules.php) and as a standalone card
 * (in programme-detail.php).
 *
 * Required variables:
 *   $module   (array) — row from Modules JOIN Staff (optional)
 *                       expects: ModuleID, ModuleName, Description,
 *                       LeaderName (optional), Year (optional)
 *   $mode     (string) — 'row' (default) renders a <tr>, 'card' renders a <div>
 *
 * Usage (card mode, e.g. inside programme-detail):
 *   $mode = 'card';
 *   foreach ($modules as $module) {
 *       include __DIR__ . '/../templates/module-card.php';
 *   }
 *
 * Usage (row mode, e.g. inside modules.php table tbody):
 *   $mode = 'row';
 *   foreach ($modules as $module) {
 *       include __DIR__ . '/../templates/module-card.php';
 *   }
 */

$mode = $mode ?? 'card';

// Truncate description helper
$desc = $module['Description'] ?? '';
$shortDesc = mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120) . '…' : $desc;

if ($mode === 'row'): ?>

    <tr>
        <td style="font-weight:500;color:var(--navy);">
            <?= htmlspecialchars($module['ModuleName']) ?>
        </td>
        <td style="color:var(--muted);font-size:0.85rem;white-space:nowrap;">
            <?= htmlspecialchars($module['LeaderName'] ?? 'TBC') ?>
        </td>
        <td style="font-size:0.85rem;max-width:260px;">
            <?php if ($shortDesc): ?>
                <?= htmlspecialchars($shortDesc) ?>
            <?php else: ?>
                <span style="color:var(--muted);font-style:italic;">No description</span>
            <?php endif; ?>
        </td>
        <?php if (isset($module['Programmes'])): ?>
        <td style="font-size:0.8rem;color:var(--muted);max-width:200px;">
            <?= $module['Programmes'] ? htmlspecialchars($module['Programmes']) : '<em>None</em>' ?>
        </td>
        <?php endif; ?>
    </tr>

<?php else: // 'card' mode — used inside programme-detail year sections ?>

    <div class="module-item">
        <div>
            <div class="module-item-name">
                <?= htmlspecialchars($module['ModuleName']) ?>
            </div>
            <?php if (!empty($module['LeaderName'])): ?>
            <div class="module-item-leader">
                Module leader: <?= htmlspecialchars($module['LeaderName']) ?>
            </div>
            <?php endif; ?>
            <?php if ($shortDesc): ?>
            <div style="font-size:0.82rem;color:var(--muted);margin-top:0.3rem;line-height:1.5;">
                <?= htmlspecialchars($shortDesc) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>