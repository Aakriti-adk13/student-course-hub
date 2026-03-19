<?php
/**
 * footer.php — Closes <main>, renders site footer, closes <body> and <html>.
 * Include at the bottom of every public page.
 */
?>
</main><!-- /#main-content -->

<footer class="site-footer" role="contentinfo">
    <div class="footer-grid">

        <!-- Brand blurb -->
        <div>
            <div class="footer-brand">Student <span>Course</span> Hub</div>
            <p class="footer-desc">
                Helping prospective students discover the right programme.
                Browse undergraduate and postgraduate degrees, meet your future
                lecturers, and register your interest today.
            </p>
        </div>

        <!-- Explore links -->
        <div class="footer-col">
            <h4>Explore</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>/public/programmes.php">All Programmes</a></li>
                <li><a href="<?= BASE_URL ?>/public/programmes.php?level=1">Undergraduate</a></li>
                <li><a href="<?= BASE_URL ?>/public/programmes.php?level=2">Postgraduate</a></li>
                <li><a href="<?= BASE_URL ?>/public/modules.php">All Modules</a></li>
                <li><a href="<?= BASE_URL ?>/public/staff.php">Our Staff</a></li>
            </ul>
        </div>

        <!-- University links -->
        <div class="footer-col">
            <h4>University</h4>
            <ul>
                <li><a href="#">Open Days</a></li>
                <li><a href="#">How to Apply</a></li>
                <li><a href="#">Fees &amp; Funding</a></li>
                <li><a href="#">Student Support</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Student Course Hub &mdash; All rights reserved.
    </div>
</footer>

</body>
</html>