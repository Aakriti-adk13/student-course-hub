<?php
/**
 * nav.php — Sticky top navigation bar, included by header.php.
 * Requires $currentPage (string) and BASE_URL (constant) to be defined.
 */
?>
<nav class="site-nav" role="navigation" aria-label="Main navigation">

    <a href="<?= BASE_URL ?>/public/index.php" class="nav-brand" aria-label="Student Course Hub – Home">
        Student <span>Course</span> Hub
    </a>

    <ul class="nav-links" id="nav-links" role="list">
        <li>
            <a href="<?= BASE_URL ?>/public/index.php"
               class="<?= $currentPage === 'index' ? 'active' : '' ?>"
               <?= $currentPage === 'index' ? 'aria-current="page"' : '' ?>>Home</a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/public/programmes.php"
               class="<?= in_array($currentPage, ['programmes','programme-detail']) ? 'active' : '' ?>"
               <?= in_array($currentPage, ['programmes','programme-detail']) ? 'aria-current="page"' : '' ?>>Programmes</a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/public/programmes.php?level=1"
               class="<?= (isset($_GET['level']) && $_GET['level'] === '1') ? 'active' : '' ?>">Undergraduate</a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/public/programmes.php?level=2"
               class="<?= (isset($_GET['level']) && $_GET['level'] === '2') ? 'active' : '' ?>">Postgraduate</a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/public/modules.php"
               class="<?= $currentPage === 'modules' ? 'active' : '' ?>"
               <?= $currentPage === 'modules' ? 'aria-current="page"' : '' ?>>Modules</a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/public/staff.php"
               class="<?= $currentPage === 'staff' ? 'active' : '' ?>"
               <?= $currentPage === 'staff' ? 'aria-current="page"' : '' ?>>Staff</a>
        </li>
    </ul>

    <form class="nav-search" action="<?= BASE_URL ?>/public/search.php" method="GET" role="search" aria-label="Site search">
        <label for="nav-search-q" class="sr-only">Search programmes</label>
        <input
            type="search"
            id="nav-search-q"
            name="q"
            placeholder="Search programmes…"
            value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
            autocomplete="off"
            aria-label="Search"
        >
        <button type="submit">Search</button>
    </form>

    <button
        class="nav-toggle"
        id="nav-toggle"
        aria-controls="nav-links"
        aria-expanded="false"
        aria-label="Toggle navigation menu"
    >
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
    </button>

</nav>

<script>
(function () {
    var btn  = document.getElementById('nav-toggle');
    var menu = document.getElementById('nav-links');
    if (!btn || !menu) return;

    btn.addEventListener('click', function () {
        var open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!open));
        menu.classList.toggle('open', !open);
    });

    menu.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
}());
</script>