<?php
// includes/header.php
require_once dirname(__DIR__) . '/includes/functions.php';

startSession(); 
$basePath = getBasePath(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BrainBuzz</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/style.css">
</head>
<body>
    <header class="fade-in-on-load">
        <div class="container">
            <div class="logo">
                <a href="<?php echo $basePath; ?>index.php">
                    <img src="<?php echo $basePath; ?>images/logo.png" alt="Logo BrainBuzz">
                    <span class="site-name">BrainBuzz</span>
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo $basePath; ?>index.php" class="nav-link">Accueil</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo $basePath; ?>pages/dashboard.php" class="nav-link">Mon Tableau de Bord</a></li>
                        <li><a href="<?php echo $basePath; ?>pages/select_quiz.php" class="nav-link">Quiz</a></li>
                        <li><a href="<?php echo $basePath; ?>pages/results.php" class="nav-link">Mes Résultats</a></li>
                        <li><a href="<?php echo $basePath; ?>pages/logout.php" class="nav-link">Déconnexion</a></li>
                    <?php else: // Si l'utilisateur n'est pas connecté ?>
                        <li><a href="<?php echo $basePath; ?>pages/login.php" class="nav-link">Connexion</a></li>
                        <li><a href="<?php echo $basePath; ?>pages/register.php" class="nav-link animated-link">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>