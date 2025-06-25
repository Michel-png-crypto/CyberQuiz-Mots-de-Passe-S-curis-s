<?php
// index.php

// --------------------------------------------------------------------------
// LIGNES DE DÉBOGAGE PHP - À RETIRER OU COMMENTER EN PRODUCTION !
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --------------------------------------------------------------------------

// Inclut le fichier de connexion à la base de données
require_once 'includes/db.php';
// Inclut le fichier des fonctions utilitaires et de sécurité
require_once 'includes/functions.php';

?>

<?php 
// Inclut l'en-tête de la page.
include 'includes/header.php'; 
?>

<div class="hero-section">
    <div class="hero-content fade-in-on-load">
        <h1>Bienvenue sur BrainBuzz !</h1>
        <p>Créez, gérez et répondez à des quiz interactifs. Testez vos connaissances ou partagez votre savoir.</p>
        <?php if (!isLoggedIn()): // Si l'utilisateur n'est pas connecté ?>
            <a href="<?php echo getBasePath(); ?>pages/register.php" class="btn-primary animated-button">Commencer l'aventure !</a>
            <a href="<?php echo getBasePath(); ?>pages/login.php" class="btn-primary animated-button">J'ai déjà un compte</a>
        <?php else: // Si l'utilisateur est connecté ?>
            <a href="<?php echo getBasePath(); ?>pages/dashboard.php" class="btn-primary animated-button">Accéder à mon tableau de bord</a>
            <?php if(hasRole('teacher')): // Si c'est un professeur ?>
                <a href="<?php echo getBasePath(); ?>pages/create_quiz.php" class="btn-secondary animated-button">Créer un nouveau Quiz</a>
            <?php else: // Si c'est un élève ?>
                <a href="<?php echo getBasePath(); ?>pages/select_quiz.php" class="btn-primary animated-button">Passer un Quiz</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="container dark-container fade-in-on-load" style="margin-top: 50px;">
    <section class="info-section">
        <h3>Pourquoi choisir notre plateforme ?</h3>
        <div class="feature-grid">
            <div class="feature-item">
                <p>✨</p> 
                <h4>Facile d'utilisation</h4>
                <p>Interface intuitive pour les professeurs et les élèves, conçue pour la simplicité.</p>
            </div>
            <div class="feature-item">
                <p>🔒</p> 
                <h4>Sécurité avancée</h4>
                <p>Vos données sont protégées avec des méthodes de hachage de mots de passe robustes et une protection CSRF.</p>
            </div>
            <div class="feature-item">
                <p>💡</p>
                <h4>Quiz dynamiques</h4>
                <p>Créez divers types de questions (QCM, Vrai/Faux, Texte libre) et suivez les progrès en temps réel.</p>
            </div>
        </div>
    </section>
</div>

<?php 
// Inclut le pied de page de la page.
include 'includes/footer.php'; 
?>