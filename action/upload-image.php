<?php
/**
 * actions/upload-image.php
 *
 * Standalone image upload handler. Accepts a single image file,
 * validates it, saves it to the appropriate uploads subfolder,
 * and returns a JSON response with the saved path.
 *
 * Called via fetch() / AJAX from:
 *   - admin/programme-add.php   (live image preview before form save)
 *   - admin/programme-edit.php  (replace existing programme image)
 *   - admin/module-add.php      (live image preview before form save)
 *   - admin/module-edit.php     (replace existing module image)
 *   - admin/staff.php           (upload staff profile photo)
 *
 * Expects multipart/form-data POST:
 *   image       ($_FILES)  — JPEG / PNG / WebP, max 2 MB, required
 *   context     (string)   — upload destination subfolder:
 *                            'programmes' | 'modules' | 'staff'
 *                            defaults to 'programmes'
 *   old_image   (string, optional) — relative path of previous image to delete
 *                                    e.g. "uploads/modules/module_123.jpg"
 *
 * Success response (HTTP 200, application/json):
 *   { "success": true, "path": "uploads/programmes/programme_abc.jpg" }
 *
 * Error response (HTTP 4xx, application/json):
 *   { "success": false, "error": "<code>", "message": "<human readable>" }
 *
 * Error codes:
 *   no_file           — no file was attached to the request
 *   upload_error      — PHP upload error (e.g. partial upload, exceeds php.ini limit)
 *   invalid_context   — context is not one of the allowed values
 *   invalid_type      — file MIME type is not JPEG, PNG, or WebP
 *   file_too_large    — file exceeds the 2 MB limit
 *   move_failed       — server could not write the file to disk
 *   auth_error        — request was made without a valid admin session
 */

// ── Only allow POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'method_not_allowed', 'message' => 'POST required.']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// ── Auth check ─────────────────────────────────────────────────
// Returns JSON error instead of redirecting (AJAX endpoint)
if (!isAdminLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'auth_error',
        'message' => 'You must be logged in as an admin.',
    ]);
    exit;
}

// ── JSON error helper ──────────────────────────────────────────
function jsonError(int $httpCode, string $errorCode, string $message): never {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => $errorCode,
        'message' => $message,
    ]);
    exit;
}

// ── Validate context ───────────────────────────────────────────
$allowedContexts = ['programmes', 'modules', 'staff'];
$context = isset($_POST['context']) && in_array($_POST['context'], $allowedContexts, true)
             ? $_POST['context']
             : 'programmes';

// ── Check a file was actually sent ────────────────────────────
if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    jsonError(400, 'no_file', 'No image file was provided.');
}

// ── Handle PHP-level upload errors ────────────────────────────
$phpUploadErrors = [
    UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload limit (php.ini).',
    UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form upload limit.',
    UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
    UPLOAD_ERR_NO_TMP_DIR => 'Missing server temporary folder.',
    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
    UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
];

$uploadError = $_FILES['image']['error'];
if ($uploadError !== UPLOAD_ERR_OK) {
    $msg = $phpUploadErrors[$uploadError] ?? 'Unknown upload error.';
    jsonError(400, 'upload_error', $msg);
}

$file = $_FILES['image'];

// ── Validate MIME type via finfo ───────────────────────────────
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowed  = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if (!array_key_exists($mimeType, $allowed)) {
    jsonError(415, 'invalid_type', 'Only JPEG, PNG, and WebP images are accepted.');
}

// ── Validate file size (max 2 MB) ──────────────────────────────
$maxBytes = 2 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    jsonError(413, 'file_too_large', 'Image must be 2 MB or smaller.');
}

// ── Build upload path ──────────────────────────────────────────
$ext       = $allowed[$mimeType];
$prefix    = rtrim($context, 's'); // 'programmes' → 'programme', etc.
$filename  = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$uploadDir = __DIR__ . '/../uploads/' . $context . '/';
$relPath   = 'uploads/' . $context . '/' . $filename;

// Create folder if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        jsonError(500, 'move_failed', 'Could not create upload directory.');
    }
}

// ── Move uploaded file ─────────────────────────────────────────
if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
    jsonError(500, 'move_failed', 'Could not save the uploaded file. Check folder permissions.');
}

// ── Delete old image if provided ───────────────────────────────
$oldImage = isset($_POST['old_image']) ? trim($_POST['old_image']) : '';

if ($oldImage !== '') {
    // Security: strip any path traversal attempts, only allow uploads/ paths
    $oldImage    = str_replace(['..', '\\'], '', $oldImage);
    $oldFullPath = __DIR__ . '/../' . ltrim($oldImage, '/');

    // Only delete if the file lives inside our uploads directory
    $realOld     = realpath($oldFullPath);
    $realUploads = realpath(__DIR__ . '/../uploads/');

    if ($realOld && $realUploads && str_starts_with($realOld, $realUploads)) {
        @unlink($realOld);
    }
}

// ── Return success with the saved relative path ────────────────
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'path'    => $relPath,
    'url'     => BASE_URL . '/' . $relPath,
]);
exit;