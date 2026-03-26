<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'student_course_hub');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_NAME', 'Student Course Hub'); // ✅ ADD THIS

define('BASE_URL', '/student-course-hub');

// local dev debug helpers
if (!ini_get('display_errors')) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}
error_reporting(E_ALL);
?>