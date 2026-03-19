<?php
/**
 * admin/programme-delete.php — Deletes a programme and its module assignments.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/programmes.php');
    exit;
}

$programmeId = isset($_POST['programme_id']) ? (int)$_POST['programme_id'] : 0;
$redirectTo  = $_POST['redirect_to'] ?? BASE_URL . '/admin/programmes.php';

if (!$programmeId) {
    header('Location: ' . $redirectTo . '?error=missing_id');
    exit;
}

try {
    $db = getDB();

    // Fetch programme for image cleanup
    $stmt = $db->prepare("SELECT Image FROM Programmes WHERE ProgrammeID = ?");
    $stmt->execute([$programmeId]);
    $programme = $stmt->fetch();

    if (!$programme) {
        header('Location: ' . $redirectTo . '?error=not_found');
        exit;
    }

    $db->beginTransaction();

    // Delete related records first
    $db->prepare("DELETE FROM ProgrammeModules    WHERE ProgrammeID = ?")->execute([$programmeId]);
    $db->prepare("DELETE FROM InterestedStudents  WHERE ProgrammeID = ?")->execute([$programmeId]);
    $db->prepare("DELETE FROM Programmes          WHERE ProgrammeID = ?")->execute([$programmeId]);

    $db->commit();

    // Remove image from disk
    if ($programme['Image']) {
        $imgPath = __DIR__ . '/../' . $programme['Image'];
        if (file_exists($imgPath)) @unlink($imgPath);
    }

    header('Location: ' . $redirectTo . '?deleted=1');
    exit;

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('programme-delete DB error: ' . $e->getMessage());
    header('Location: ' . $redirectTo . '?error=db_error');
    exit;
}