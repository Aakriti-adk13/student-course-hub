<?php
/**
 * admin/programme-edit.php — Form to edit an existing programme.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle  = 'Edit Programme';
$activePage = 'programmes';

$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: ' . BASE_URL . '/admin/programmes.php'); exit; }

// Load programme
$stmt = $db->prepare(
    "SELECT * FROM Programmes WHERE ProgrammeID = ?"
);
$stmt->execute([$id]);
$programme = $stmt->fetch();
if (!$programme) { header('Location: ' . BASE_URL . '/admin/programmes.php'); exit; }

// Currently assigned modules with years
$assignedStmt = $db->prepare(
    "SELECT ModuleID, Year FROM ProgrammeModules WHERE ProgrammeID = ?"
);
$assignedStmt->execute([$id]);
$assignedRaw = $assignedStmt->fetchAll();
// Build lookup: ModuleID => Year
$assigned = [];
foreach ($assignedRaw as $a) {
    $assigned[$a['ModuleID']] = $a['Year'];
}

$staff   = $db->query("SELECT StaffID, Name FROM Staff ORDER BY Name")->fetchAll();
$levels  = $db->query("SELECT LevelID, LevelName FROM Levels ORDER BY LevelID")->fetchAll();
$modules = $db->query(
    "SELECT m.ModuleID, m.ModuleName, s.Name AS LeaderName
     FROM Modules m LEFT JOIN Staff s ON m.ModuleLeaderID = s.StaffID
     ORDER BY m.ModuleName"
)->fetchAll();

$errorMessages = [
    'missing_name'      => 'Please enter a programme name.',
    'name_too_long'     => 'Programme name must be 200 characters or fewer.',
    'invalid_level'     => 'Please select a valid level.',
    'invalid_leader'    => 'Selected programme leader does not exist.',
    'duplicate_name'    => 'A programme with this name already exists at this level.',
    'invalid_modules'   => 'One or more selected modules are invalid.',
    'invalid_years'     => 'Year values must be 1 or higher.',
    'image_type'        => 'Image must be JPEG, PNG, or WebP.',
    'image_size'        => 'Image must be 2 MB or smaller.',
    'image_move_failed' => 'Image could not be saved. Check server permissions.',
    'db_error'          => 'A database error occurred. Please try again.',
];
$errorMsg = isset($_GET['error']) ? ($errorMessages[$_GET['error']] ?? 'An error occurred.') : '';

require_once __DIR__ . '/../templates/admin-header.php';
?>

<?php if ($errorMsg): ?>
<div class="flash flash-error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
<div class="flash flash-success flash-auto">Programme saved successfully.</div>
<?php endif; ?>

<div class="admin-page-header">
    <h1>Edit Programme</h1>
    <div style="display:flex;gap:0.5rem;">
        <a href="<?= BASE_URL ?>/public/programme-detail.php?id=<?= $id ?>" target="_blank"
           class="btn btn-sm btn-outline" style="border-color:var(--muted);color:var(--muted);">
            ↗ Preview
        </a>
        <a href="<?= BASE_URL ?>/admin/programmes.php"
           class="btn btn-sm btn-outline" style="border-color:var(--muted);color:var(--muted);">
            ← Back
        </a>
    </div>
</div>

<div class="admin-form-card">
    <h2>Programme Details</h2>
    <form class="admin-form" method="POST"
          action="<?= BASE_URL ?>/actions/save-programme.php"
          enctype="multipart/form-data">

        <input type="hidden" name="programme_id" value="<?= $id ?>">
        <input type="hidden" name="redirect_to"  value="<?= BASE_URL ?>/admin/programme-edit.php?id=<?= $id ?>">

        <label for="programme_name">
            Programme name <span style="color:#c0392b;">*</span>
            <input type="text" id="programme_name" name="programme_name" required maxlength="200"
                   value="<?= htmlspecialchars($programme['ProgrammeName']) ?>">
        </label>

        <label for="level_id">
            Level <span style="color:#c0392b;">*</span>
            <select id="level_id" name="level_id" required>
                <?php foreach ($levels as $l): ?>
                <option value="<?= $l['LevelID'] ?>"
                    <?= $programme['LevelID'] == $l['LevelID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['LevelName']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label for="programme_leader_id">
            Programme leader
            <select id="programme_leader_id" name="programme_leader_id">
                <option value="">— Unassigned —</option>
                <?php foreach ($staff as $s): ?>
                <option value="<?= $s['StaffID'] ?>"
                    <?= $programme['ProgrammeLeaderID'] == $s['StaffID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['Name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label for="description">
            Description
            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($programme['Description'] ?? '') ?></textarea>
        </label>

        <!-- Current image -->
        <?php if ($programme['Image']): ?>
        <div>
            <div style="font-size:0.875rem;font-weight:500;color:var(--ink);margin-bottom:0.4rem;">Current image</div>
            <img src="<?= BASE_URL . '/' . htmlspecialchars($programme['Image']) ?>"
                 alt="Current programme image"
                 style="max-width:200px;max-height:150px;border-radius:8px;border:1px solid var(--border);object-fit:cover;">
        </div>
        <?php endif; ?>

        <label for="image-upload">
            Replace image (JPEG / PNG / WebP, max 2 MB)
            <input type="file" id="image-upload" name="image" accept="image/jpeg,image/png,image/webp">
            <img id="image-preview" src="" alt="New image preview">
            <span class="field-hint">Leave blank to keep the existing image.</span>
        </label>

        <label style="flex-direction:row;align-items:center;gap:0.6rem;cursor:pointer;">
            <input type="checkbox" name="is_published" value="1"
                   <?= $programme['is_published'] ? 'checked' : '' ?>
                   style="width:auto;margin:0;">
            Published (visible to students)
        </label>

        <!-- Module assignments -->
        <div>
            <div style="font-size:0.875rem;font-weight:500;color:var(--ink);margin-bottom:0.6rem;">
                Assigned modules
                <span class="field-hint" style="display:block;margin-top:0.2rem;">
                    Tick modules and set the year each is taught.
                </span>
            </div>
            <div style="max-height:320px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;">
                <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                    <thead>
                        <tr style="background:var(--navy);">
                            <th style="padding:0.6rem 1rem;color:var(--white);font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;width:40px;"></th>
                            <th style="padding:0.6rem 1rem;color:var(--white);text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">Module</th>
                            <th style="padding:0.6rem 1rem;color:var(--white);text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">Leader</th>
                            <th style="padding:0.6rem 1rem;color:var(--white);text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;width:90px;">Year</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $i => $m):
                            $isAssigned = isset($assigned[$m['ModuleID']]);
                            $year       = $isAssigned ? $assigned[$m['ModuleID']] : 1;
                        ?>
                        <tr style="<?= $i % 2 === 0 ? 'background:var(--white);' : 'background:var(--cream);' ?>border-bottom:1px solid var(--border);">
                            <td style="padding:0.5rem 1rem;text-align:center;">
                                <input type="checkbox"
                                       name="module_ids[]"
                                       value="<?= $m['ModuleID'] ?>"
                                       id="mod_<?= $m['ModuleID'] ?>"
                                       class="mod-check"
                                       style="width:auto;margin:0;"
                                       <?= $isAssigned ? 'checked' : '' ?>
                                       onchange="toggleYear(this)">
                            </td>
                            <td style="padding:0.5rem 1rem;">
                                <label for="mod_<?= $m['ModuleID'] ?>" style="cursor:pointer;font-weight:500;color:var(--navy);">
                                    <?= htmlspecialchars($m['ModuleName']) ?>
                                </label>
                            </td>
                            <td style="padding:0.5rem 1rem;color:var(--muted);font-size:0.82rem;">
                                <?= htmlspecialchars($m['LeaderName'] ?? '—') ?>
                            </td>
                            <td style="padding:0.5rem 1rem;">
                                <input type="number"
                                       name="module_years[]"
                                       value="<?= $year ?>"
                                       min="1" max="4"
                                       id="year_<?= $m['ModuleID'] ?>"
                                       <?= !$isAssigned ? 'disabled' : '' ?>
                                       style="width:60px;padding:0.3rem 0.4rem;border:1px solid var(--border);border-radius:4px;font-size:0.85rem;background:var(--cream);opacity:<?= $isAssigned ? '1' : '0.4' ?>;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= BASE_URL ?>/admin/programmes.php"
               class="btn btn-outline" style="border-color:var(--muted);color:var(--muted);">Cancel</a>
        </div>

    </form>
</div>

<script>
function toggleYear(checkbox) {
    var yearEl = document.getElementById('year_' + checkbox.value);
    if (!yearEl) return;
    yearEl.disabled      = !checkbox.checked;
    yearEl.style.opacity = checkbox.checked ? '1' : '0.4';
}
</script>

<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>