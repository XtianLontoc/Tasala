<?php
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['admin_logged_in']);
?>

<header>
    <nav class="navbar section-content">
        <a href="index.php" class="nav-logo">
            <img src="./images/Logo.png" alt="Tasala Logo" class="logo-img">
            <h2 class="logo-text">Tasala</h2>
        </a>
        <ul class="nav-menu">
            <button id="menu-close-button" class="fas fa-times"></button>
            
            <li class="nav-item">
                <a href="index.html#home" class="nav-link">Home</a>
            </li>
            <li class="nav-item">
                <a href="index.html#about" class="nav-link">About</a>
            </li>
            <li class="nav-item">
                <a href="index.html#gallery" class="nav-link">Gallery</a>
            </li>
            <li class="nav-item">
                <a href="index.html#contact" class="nav-link">Contact</a>
            </li>
            <li class="nav-item">
                <?php if ($isLoggedIn): ?>
                    <a href="logout.php" class="nav-link login-button">Log Out</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link login-button">Log In</a>
                <?php endif; ?>
            </li>
        </ul>
        <button id="menu-open-button" class="fas fa-bars"></button>
    </nav>
</header>