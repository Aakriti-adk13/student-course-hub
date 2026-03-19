<?php
/**
 * admin/module-delete.php — Deletes a module and its programme assignments.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/modules.php');
    exit;
}

$moduleId   = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
$redirectTo = $_POST['redirect_to'] ?? BASE_URL . '/admin/modules.php';

if (!$moduleId) {
    header('Location: ' . $redirectTo . '?error=missing_id');
    exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT Image FROM Modules WHERE ModuleID = ?");
    $stmt->execute([$moduleId]);
    $module = $stmt->fetch();

    if (!$module) {
        header('Location: ' . $redirectTo . '?error=not_found');
        exit;
    }

    $db->beginTransaction();
    $db->prepare("DELETE FROM ProgrammeModules WHERE ModuleID = ?")->execute([$moduleId]);
    $db->prepare("DELETE FROM Modules          WHERE ModuleID = ?")->execute([$moduleId]);
    $db->commit();

    if ($module['Image']) {
        $imgPath = __DIR__ . '/../' . $module['Image'];
        if (file_exists($imgPath)) @unlink($imgPath);
    }

    header('Location: ' . $redirectTo . '?deleted=1');
    exit;

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('module-delete DB error: ' . $e->getMessage());
    header('Location: ' . $redirectTo . '?error=db_error');
    exit;
}