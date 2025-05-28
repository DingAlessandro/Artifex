<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artifex - Visite guidate storico-culturali</title>
    <link rel="stylesheet" href="style/style.css?v=3">
</head>
<body>
<header>
    <div class="container">
        <nav>
            <div class="logo">Artifex</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="visite.php">Visite</a></li>
                <li><a href="guide.php">Guide</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="profilo.php">Profilo</a></li>
                    <li><a href="carrello.php">Carrello</a></li>
                    <li><a href="logout.php" class="login-link">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="login-link">Login/Registrati</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>