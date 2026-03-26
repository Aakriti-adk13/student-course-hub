<?php
/**
 * header.php — Public site HTML head and sticky nav.
 *
 * Set these variables BEFORE including:
 *   $pageTitle  (string) — browser tab prefix, e.g. "Programmes"
 *   $pageDesc   (string) — meta description
 */
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – ' . SITE_NAME : SITE_NAME ?></title>
    <meta name="description" content="<?= isset($pageDesc) ? htmlspecialchars($pageDesc) : 'Discover undergraduate and postgraduate programmes at our university.' ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body>

<!-- Skip-to-content (WCAG 2.1 – keyboard accessibility) -->
<a href="#main-content"
   style="position:absolute;top:-40px;left:0;background:var(--teal);color:#fff;
          padding:0.5rem 1rem;z-index:9999;border-radius:0 0 6px 0;
          text-decoration:none;font-size:0.9rem;transition:top 0.2s;"
   onfocus="this.style.top='0'"
   onblur="this.style.top='-40px'">
    Skip to main content
</a>

<?php require_once __DIR__ . '/nav.php'; ?>

<main id="main-content" tabindex="-1">