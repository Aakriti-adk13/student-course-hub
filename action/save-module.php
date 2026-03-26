<?php
/**
 * actions/save-module.php
 *
 * Handles POST for both CREATE and EDIT of a module.
 * Called from:
 *   - admin/module-add.php  (new module, no module_id in POST)
 *   - admin/module-edit.php (edit existing, module_id present in POST)
 *
 * Expects POST fields:
 *   module_id        (int, optional) — present when editing an existing module
 *   module_name      (string)        — module title, required
 *   module_leader_id (int, optional) — FK to Staff.StaffID, 0 = unassigned
 *   description      (string, opt)   — free-text description
 *   redirect_to      (string)        — URL to redirect back to on error
 *
 * File upload (optional):
 *   image            ($_FILES)       — JPEG/PNG/WebP, max 2 MB
 *
 * On success: redirects to admin/modules.php?saved=1
 * On error:   redirects back to redirect_to with ?error=<code>
 *
 * Error codes:
 *   missing_name       — module name not provided
 *   name_too_long      — module name exceeds 150 chars
 *   invalid_leader     — module_leader_id does not exist in Staff table
 *   invalid_module     — module_id not found (edit mode)
 *   duplicate_name     — another module already has this name
 *   image_type         — uploaded file is not an allowed image type
 *   image_size         — uploaded file exceeds 2 MB
 *   image_move_failed  — server could not save the uploaded file
 *   db_error           — unexpected database failure
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireAdmin();

// ── Redirect helper ────────────────────────────────────────────
function redirectWith(string $base, array $params): never {
    $sep = str_contains($base, '?') ? '&' : '?';
    header('Location: ' . $base . $sep . http_build_query($params));
    exit;
}

// ── Collect inputs ─────────────────────────────────────────────
$moduleId   = isset($_POST['module_id']) && $_POST['module_id'] !== ''
                ? (int)$_POST['module_id']
                : null;

$moduleName   = sanitize($_POST['module_name']      ?? '');
$leaderId     = isset($_POST['module_leader_id']) && $_POST['module_leader_id'] !== ''
                  ? (int)$_POST['module_leader_id']
                  : null;
$description  = sanitize($_POST['description']      ?? '');
$redirectTo   = $_POST['redirect_to']
                  ?? BASE_URL . '/admin/modules.php';

$isEdit = $moduleId !== null;

// ── Validate: module name ──────────────────────────────────────
if ($moduleName === '') {
    redirectWith($redirectTo, ['error' => 'missing_name']);
}

if (mb_strlen($moduleName) > 150) {
    redirectWith($redirectTo, ['error' => 'name_too_long']);
}

// ── Database operations ────────────────────────────────────────
try {
    $db = getDB();

    // Verify module exists when editing
    if ($isEdit) {
        $exists = $db->prepare("SELECT ModuleID FROM Modules WHERE ModuleID = ?");
        $exists->execute([$moduleId]);
        if (!$exists->fetch()) {
            redirectWith($redirectTo, ['error' => 'invalid_module']);
        }
    }

    // Verify staff member exists (if a leader was selected)
    if ($leaderId !== null) {
        $staffCheck = $db->prepare("SELECT StaffID FROM Staff WHERE StaffID = ?");
        $staffCheck->execute([$leaderId]);
        if (!$staffCheck->fetch()) {
            redirectWith($redirectTo, ['error' => 'invalid_leader']);
        }
    }

    // Check for duplicate module name (exclude self when editing)
    $dupSql = "SELECT ModuleID FROM Modules WHERE ModuleName = ?";
    $dupParams = [$moduleName];
    if ($isEdit) {
        $dupSql    .= " AND ModuleID != ?";
        $dupParams[] = $moduleId;
    }
    $dup = $db->prepare($dupSql);
    $dup->execute($dupParams);
    if ($dup->fetch()) {
        redirectWith($redirectTo, ['error' => 'duplicate_name']);
    }

    // ── Handle image upload (optional) ────────────────────────
    $imagePath = null; // null = no change

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['image'];
        $maxBytes = 2 * 1024 * 1024; // 2 MB

        // Validate MIME type using finfo (more reliable than extension)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mimeType, $allowed, true)) {
            redirectWith($redirectTo, ['error' => 'image_type']);
        }

        if ($file['size'] > $maxBytes) {
            redirectWith($redirectTo, ['error' => 'image_size']);
        }

        // Build a unique filename and save to uploads/modules/
        $ext       = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };
        $filename  = 'module_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/modules/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $dest = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            redirectWith($redirectTo, ['error' => 'image_move_failed']);
        }

        // Store relative path for the DB
        $imagePath = 'uploads/modules/' . $filename;

        // Delete old image if editing and a previous one existed
        if ($isEdit) {
            $oldImg = $db->prepare("SELECT Image FROM Modules WHERE ModuleID = ?");
            $oldImg->execute([$moduleId]);
            $oldRow = $oldImg->fetch();
            if ($oldRow && $oldRow['Image']) {
                $oldFile = __DIR__ . '/../' . $oldRow['Image'];
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
        }
    }

    // ── INSERT or UPDATE ───────────────────────────────────────
    if ($isEdit) {
        if ($imagePath !== null) {
            // Update including new image
            $stmt = $db->prepare(
                "UPDATE Modules
                 SET ModuleName = ?, ModuleLeaderID = ?, Description = ?, Image = ?
                 WHERE ModuleID = ?"
            );
            $stmt->execute([$moduleName, $leaderId, $description, $imagePath, $moduleId]);
        } else {
            // Update without touching the existing image
            $stmt = $db->prepare(
                "UPDATE Modules
                 SET ModuleName = ?, ModuleLeaderID = ?, Description = ?
                 WHERE ModuleID = ?"
            );
            $stmt->execute([$moduleName, $leaderId, $description, $moduleId]);
        }
    } else {
        // INSERT new module
        $stmt = $db->prepare(
            "INSERT INTO Modules (ModuleName, ModuleLeaderID, Description, Image)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$moduleName, $leaderId, $description, $imagePath]);
    }

    // ── Redirect to admin modules list on success ──────────────
    header('Location: ' . BASE_URL . '/admin/modules.php?saved=1');
    exit;

} catch (PDOException $e) {
    error_log('save-module DB error: ' . $e->getMessage());
    redirectWith($redirectTo, ['error' => 'db_error']);
}