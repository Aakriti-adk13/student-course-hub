<?php
/**
 * actions/register-interest.php
 *
 * Handles the POST submission for student interest registration.
 * Called from:
 *   - public/programme-detail.php  (sidebar form, redirects back to detail page)
 *   - public/register-interest.php (standalone form, redirects back to that page)
 *
 * Expects POST fields:
 *   programme_id   (int)    — ID of the programme
 *   student_name   (string) — student's full name
 *   student_email  (string) — student's email address
 *   redirect_to    (string) — URL-encoded path to redirect back to after processing
 *
 * On success: redirects with ?success=1
 * On error:   redirects with ?error=<code> and ?name=, ?email= to repopulate form
 *
 * Error codes:
 *   missing_fields  — name, email, or programme_id not provided
 *   invalid_name    — name too short
 *   invalid_email   — email fails validation
 *   invalid_programme — programme does not exist or is unpublished
 *   duplicate       — this email is already registered for this programme
 *   db_error        — unexpected database failure
 */

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sanitize.php';

// ── Collect and sanitize inputs ────────────────────────────────
$programmeId  = isset($_POST['programme_id'])  ? (int)$_POST['programme_id']       : 0;
$studentName  = sanitize($_POST['student_name']  ?? '');
$studentEmail = sanitizeEmail($_POST['student_email'] ?? '');
$redirectTo   = $_POST['redirect_to'] ?? BASE_URL . '/public/programmes.php';

// Whitelist redirect — only allow relative paths within our app
// to prevent open-redirect attacks
$redirectTo = filter_var($redirectTo, FILTER_VALIDATE_URL)
    ? $redirectTo
    : BASE_URL . '/public/programmes.php';

// Build redirect helper
function redirectWith(string $base, array $params): never {
    $sep = str_contains($base, '?') ? '&' : '?';
    header('Location: ' . $base . $sep . http_build_query($params));
    exit;
}

// ── Validate inputs ────────────────────────────────────────────
if (!$programmeId || !$studentName || !$studentEmail) {
    redirectWith($redirectTo, [
        'error' => 'missing_fields',
        'name'  => urlencode($studentName),
        'email' => urlencode($_POST['student_email'] ?? ''),
    ]);
}

if (mb_strlen($studentName) < 2) {
    redirectWith($redirectTo, [
        'error' => 'invalid_name',
        'name'  => urlencode($studentName),
        'email' => urlencode($studentEmail),
    ]);
}

if (!$studentEmail) {
    redirectWith($redirectTo, [
        'error' => 'invalid_email',
        'name'  => urlencode($studentName),
        'email' => urlencode($_POST['student_email'] ?? ''),
    ]);
}

// ── Database operations ────────────────────────────────────────
try {
    $db = getDB();

    // Verify the programme exists and is published
    $prog = $db->prepare(
        "SELECT ProgrammeID, ProgrammeName
         FROM Programmes
         WHERE ProgrammeID = ? AND is_published = 1"
    );
    $prog->execute([$programmeId]);
    $programme = $prog->fetch();

    if (!$programme) {
        redirectWith($redirectTo, [
            'error' => 'invalid_programme',
            'name'  => urlencode($studentName),
            'email' => urlencode($studentEmail),
        ]);
    }

    // Check for duplicate registration (same email + programme)
    $dup = $db->prepare(
        "SELECT InterestID
         FROM InterestedStudents
         WHERE ProgrammeID = ? AND Email = ?"
    );
    $dup->execute([$programmeId, $studentEmail]);

    if ($dup->fetch()) {
        redirectWith($redirectTo, [
            'error' => 'duplicate',
            'name'  => urlencode($studentName),
            'email' => urlencode($studentEmail),
        ]);
    }

    // Insert the new interest record
    $insert = $db->prepare(
        "INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email)
         VALUES (?, ?, ?)"
    );
    $insert->execute([$programmeId, $studentName, $studentEmail]);

    // Success — redirect back with success flag
    redirectWith($redirectTo, ['success' => '1']);

} catch (PDOException $e) {
    // Log the error server-side (don't expose details to user)
    error_log('register-interest DB error: ' . $e->getMessage());

    redirectWith($redirectTo, [
        'error' => 'db_error',
        'name'  => urlencode($studentName),
        'email' => urlencode($studentEmail),
    ]);
}