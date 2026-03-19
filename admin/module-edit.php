<?php
/**
 * admin/module-edit.php — Form to edit an existing module.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle  = 'Edit Module';
$activePage = 'modules';

$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: ' . BASE_URL . '/admin/modules.php'); exit; }

$stmt = $db->prepare("SELECT * FROM Modules WHERE ModuleID = ?");
$stmt->execute([$id]);
$module = $stmt->fetch();
if (!$module) { header('Location: ' . BASE_URL . '/admin/modules.php'); exit; }

$staff = $db->query("SELECT StaffID, Name FROM Staff ORDER BY Name")->fetchAll();

$errorMessages = [
    'missing_name'      => 'Please enter a module name.',
    'name_too_long'     => 'Module name must be 150 characters or fewer.',
    'invalid_leader'    => 'Selected module leader does not exist.',
    'invalid_module'    => 'Module not found.',
    'duplicate_name'    => 'A module with this name already exists.',
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
<div class="flash flash-success flash-auto">Module saved successfully.</div>
<?php endif; ?>

<div class="admin-page-header">
    <h1>Edit Module</h1>
    <a href="<?= BASE_URL ?>/admin/modules.php"
       class="btn btn-sm btn-outline" style="border-color:var(--muted);color:var(--muted);">← Back</a>
</div>

<div class="admin-form-card">
    <h2>Module Details</h2>
    <form class="admin-form" method="POST"
          action="<?= BASE_URL ?>/actions/save-module.php"
          enctype="multipart/form-data">

        <input type="hidden" name="module_id"   value="<?= $id ?>">
        <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/admin/module-edit.php?id=<?= $id ?>">

        <label for="module_name">
            Module name <span style="color:#c0392b;">*</span>
            <input type="text" id="module_name" name="module_name" required maxlength="150"
                   value="<?= htmlspecialchars($module['ModuleName']) ?>">
        </label>

        <label for="module_leader_id">
            Module leader
            <select id="module_leader_id" name="module_leader_id">
                <option value="">— Unassigned —</option>
                <?php foreach ($staff as $s): ?>
                <option value="<?= $s['StaffID'] ?>"
                    <?= $module['ModuleLeaderID'] == $s['StaffID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['Name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label for="description">
            Description
            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($module['Description'] ?? '') ?></textarea>
        </label>

        <?php if ($module['Image']): ?>
        <div>
            <div style="font-size:0.875rem;font-weight:500;color:var(--ink);margin-bottom:0.4rem;">Current image</div>
            <img src="<?= BASE_URL . '/' . htmlspecialchars($module['Image']) ?>"
                 alt="Current module image"
                 style="max-width:200px;max-height:150px;border-radius:8px;border:1px solid var(--border);object-fit:cover;">
        </div>
        <?php endif; ?>

        <label for="image-upload">
            Replace image (JPEG / PNG / WebP, max 2 MB)
            <input type="file" id="image-upload" name="image" accept="image/jpeg,image/png,image/webp">
            <img id="image-preview" src="" alt="New image preview">
            <span class="field-hint">Leave blank to keep the existing image.</span>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= BASE_URL ?>/admin/modules.php"
               class="btn btn-outline" style="border-color:var(--muted);color:var(--muted);">Cancel</a>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../templates/admin-footer.php'; ?>