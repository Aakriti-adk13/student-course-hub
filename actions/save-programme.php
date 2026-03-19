<?php
/**
 * actions/save-programme.php
 *
 * Handles POST for both CREATE and EDIT of a programme.
 * Called from:
 *   - admin/programme-add.php  (new programme, no programme_id in POST)
 *   - admin/programme-edit.php (edit existing, programme_id present in POST)
 *
 * Expects POST fields:
 *   programme_id      (int, optional)  — present when editing an existing programme
 *   programme_name    (string)         — programme title, required
 *   level_id          (int)            — FK to Levels.LevelID (1=UG, 2=PG), required
 *   programme_leader_id (int, optional)— FK to Staff.StaffID, 0 = unassigned
 *   description       (string, opt)    — free-text description
 *   is_published      (int)            — 1 = published, 0 = draft (checkbox)
 *   module_ids[]      (array, opt)     — array of ModuleIDs to assign
 *   module_years[]    (array, opt)     — parallel array of year values per module
 *   redirect_to       (string)         — URL to redirect back to on error
 *
 * File upload (optional):
 *   image             ($_FILES)        — JPEG/PNG/WebP, max 2 MB
 *
 * On success: redirects to admin/programmes.php?saved=1
 * On error:   redirects back to redirect_to with ?error=<code>
 *
 * Error codes:
 *   missing_name        — programme name not provided
 *   name_too_long       — programme name exceeds 200 chars
 *   invalid_level       — level_id is not 1 or 2
 *   invalid_leader      — programme_leader_id does not exist in Staff
 *   invalid_programme   — programme_id not found (edit mode)
 *   duplicate_name      — another programme already has this name at this level
 *   invalid_modules     — one or more module_ids do not exist
 *   invalid_years       — a year value is not a positive integer
 *   image_type          — uploaded file is not an allowed image type
 *   image_size          — uploaded file exceeds 2 MB
 *   image_move_failed   — server could not save the uploaded file
 *   db_error            — unexpected database failure
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

// ── Collect and sanitize inputs ────────────────────────────────
$programmeId = isset($_POST['programme_id']) && $_POST['programme_id'] !== ''
                 ? (int)$_POST['programme_id']
                 : null;

$programmeName  = sanitize($_POST['programme_name']       ?? '');
$levelId        = isset($_POST['level_id'])               ? (int)$_POST['level_id']                : 0;
$leaderId       = isset($_POST['programme_leader_id']) && $_POST['programme_leader_id'] !== ''
                    ? (int)$_POST['programme_leader_id']
                    : null;
$description    = sanitize($_POST['description']          ?? '');
$isPublished    = isset($_POST['is_published'])            ? 1 : 0;
$redirectTo     = $_POST['redirect_to']
                    ?? BASE_URL . '/admin/programmes.php';

// Module assignments — parallel arrays: module_ids[] and module_years[]
$moduleIds   = isset($_POST['module_ids'])   && is_array($_POST['module_ids'])
                 ? array_map('intval', $_POST['module_ids'])
                 : [];
$moduleYears = isset($_POST['module_years']) && is_array($_POST['module_years'])
                 ? array_map('intval', $_POST['module_years'])
                 : [];

$isEdit = $programmeId !== null;

// ── Validate: programme name ───────────────────────────────────
if ($programmeName === '') {
    redirectWith($redirectTo, ['error' => 'missing_name']);
}

if (mb_strlen($programmeName) > 200) {
    redirectWith($redirectTo, ['error' => 'name_too_long']);
}

// ── Validate: level ────────────────────────────────────────────
if (!in_array($levelId, [1, 2], true)) {
    redirectWith($redirectTo, ['error' => 'invalid_level']);
}

// ── Validate: module years (must all be positive integers) ─────
foreach ($moduleYears as $yr) {
    if ($yr < 1) {
        redirectWith($redirectTo, ['error' => 'invalid_years']);
    }
}

// ── Database operations ────────────────────────────────────────
try {
    $db = getDB();

    // Verify programme exists when editing
    if ($isEdit) {
        $exists = $db->prepare("SELECT ProgrammeID FROM Programmes WHERE ProgrammeID = ?");
        $exists->execute([$programmeId]);
        if (!$exists->fetch()) {
            redirectWith($redirectTo, ['error' => 'invalid_programme']);
        }
    }

    // Verify staff member exists (if a leader was provided)
    if ($leaderId !== null) {
        $staffCheck = $db->prepare("SELECT StaffID FROM Staff WHERE StaffID = ?");
        $staffCheck->execute([$leaderId]);
        if (!$staffCheck->fetch()) {
            redirectWith($redirectTo, ['error' => 'invalid_leader']);
        }
    }

    // Check for duplicate programme name at the same level (exclude self when editing)
    $dupSql    = "SELECT ProgrammeID FROM Programmes WHERE ProgrammeName = ? AND LevelID = ?";
    $dupParams = [$programmeName, $levelId];
    if ($isEdit) {
        $dupSql    .= " AND ProgrammeID != ?";
        $dupParams[] = $programmeId;
    }
    $dup = $db->prepare($dupSql);
    $dup->execute($dupParams);
    if ($dup->fetch()) {
        redirectWith($redirectTo, ['error' => 'duplicate_name']);
    }

    // Verify all submitted module IDs actually exist
    if (!empty($moduleIds)) {
        $placeholders = implode(',', array_fill(0, count($moduleIds), '?'));
        $modCheck     = $db->prepare(
            "SELECT COUNT(*) FROM Modules WHERE ModuleID IN ($placeholders)"
        );
        $modCheck->execute($moduleIds);
        if ((int)$modCheck->fetchColumn() !== count($moduleIds)) {
            redirectWith($redirectTo, ['error' => 'invalid_modules']);
        }
    }

    // ── Handle image upload (optional) ────────────────────────
    $imagePath = null; // null = no change to existing image

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['image'];
        $maxBytes = 2 * 1024 * 1024; // 2 MB

        // Validate real MIME type via finfo
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mimeType, $allowed, true)) {
            redirectWith($redirectTo, ['error' => 'image_type']);
        }

        if ($file['size'] > $maxBytes) {
            redirectWith($redirectTo, ['error' => 'image_size']);
        }

        // Build unique filename and move to uploads/programmes/
        $ext = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };
        $filename  = 'programme_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/programmes/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $dest = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            redirectWith($redirectTo, ['error' => 'image_move_failed']);
        }

        $imagePath = 'uploads/programmes/' . $filename;

        // Delete old image from disk if replacing during an edit
        if ($isEdit) {
            $oldImg = $db->prepare("SELECT Image FROM Programmes WHERE ProgrammeID = ?");
            $oldImg->execute([$programmeId]);
            $oldRow = $oldImg->fetch();
            if ($oldRow && $oldRow['Image']) {
                $oldFile = __DIR__ . '/../' . $oldRow['Image'];
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
        }
    }

    // ── Wrap everything in a transaction ──────────────────────
    $db->beginTransaction();

    if ($isEdit) {
        // UPDATE programme row
        if ($imagePath !== null) {
            $stmt = $db->prepare(
                "UPDATE Programmes
                 SET ProgrammeName = ?, LevelID = ?, ProgrammeLeaderID = ?,
                     Description = ?, is_published = ?, Image = ?
                 WHERE ProgrammeID = ?"
            );
            $stmt->execute([
                $programmeName, $levelId, $leaderId,
                $description, $isPublished, $imagePath,
                $programmeId,
            ]);
        } else {
            $stmt = $db->prepare(
                "UPDATE Programmes
                 SET ProgrammeName = ?, LevelID = ?, ProgrammeLeaderID = ?,
                     Description = ?, is_published = ?
                 WHERE ProgrammeID = ?"
            );
            $stmt->execute([
                $programmeName, $levelId, $leaderId,
                $description, $isPublished,
                $programmeId,
            ]);
        }

        // Replace module assignments: delete existing, re-insert submitted ones
        $delModules = $db->prepare(
            "DELETE FROM ProgrammeModules WHERE ProgrammeID = ?"
        );
        $delModules->execute([$programmeId]);

    } else {
        // INSERT new programme
        $stmt = $db->prepare(
            "INSERT INTO Programmes
                 (ProgrammeName, LevelID, ProgrammeLeaderID, Description, is_published, Image)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $programmeName, $levelId, $leaderId,
            $description, $isPublished, $imagePath,
        ]);
        $programmeId = (int)$db->lastInsertId();
    }

    // Re-insert module assignments (shared for both create and edit)
    if (!empty($moduleIds)) {
        $insModule = $db->prepare(
            "INSERT INTO ProgrammeModules (ProgrammeID, ModuleID, Year)
             VALUES (?, ?, ?)"
        );
        foreach ($moduleIds as $i => $mid) {
            $year = $moduleYears[$i] ?? 1;
            $insModule->execute([$programmeId, $mid, $year]);
        }
    }

    $db->commit();

    // ── Redirect on success ────────────────────────────────────
    header('Location: ' . BASE_URL . '/admin/programmes.php?saved=1');
    exit;

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('save-programme DB error: ' . $e->getMessage());
    redirectWith($redirectTo, ['error' => 'db_error']);
}