<?php
// pages/errors.php
require_once '../includes/functions.php'; // Pour getBasePath()

// Récupérer le code d'erreur de l'URL ou définir un défaut
$errorCode = $_GET['code'] ?? '???'; // Par exemple: 404, 403, 500
$errorMessage = '';
$errorDescription = '';

switch ($errorCode) {
    case '400':
        $errorMessage = 'Mauvaise Requête';
        $errorDescription = 'Le serveur n\'a pas pu comprendre la requête en raison d\'une syntaxe invalide.';
        break;
    case '401':
        $errorMessage = 'Non Autorisé';
        $errorDescription = 'Vous devez vous authentifier pour accéder à cette ressource.';
        break;
    case '403':
        $errorMessage = 'Accès Interdit';
        $errorDescription = 'Vous n\'avez pas la permission d\'accéder à cette ressource.';
        break;
    case '404':
        $errorMessage = 'Page Non Trouvée';
        $errorDescription = 'La page que vous recherchez n\'existe pas ou a été déplacée.';
        break;
    case '500':
        $errorMessage = 'Erreur Interne du Serveur';
        $errorDescription = 'Une erreur inattendue est survenue sur le serveur. Nous travaillons à la résoudre !';
        break;
    default:
        $errorMessage = 'Erreur Inconnue';
        $errorDescription = 'Une erreur est survenue. Veuillez réessayer plus tard.';
        break;
}

// Définir le code de statut HTTP pour la page d'erreur
// Cela aide les moteurs de recherche à comprendre qu'il s'agit d'une vraie erreur
if (isset($_SERVER['SERVER_PROTOCOL'])) { // Vérifier si nous sommes dans un contexte HTTP
    header($_SERVER['SERVER_PROTOCOL'] . ' ' . $errorCode . ' ' . $errorMessage);
}
?>
<?php include '../includes/header.php'; // Inclut l'en-tête de votre site ?>

<div class="error-container fade-in-on-load">
    <div class="error-content">
        <h1 class="error-code animated-text-shadow"><?php echo htmlspecialchars($errorCode); ?></h1>
        <h2 class="error-message-title"><?php echo htmlspecialchars($errorMessage); ?></h2>
        <p class="error-description"><?php echo htmlspecialchars($errorDescription); ?></p>
        <a href="<?php echo getBasePath(); ?>index.php" class="btn-primary animated-button">Retour à l'accueil</a>
    </div>
</div>

<?php include '../includes/footer.php'; // Inclut le pied de page de votre site ?>

<style>
/* Styles spécifiques pour la page d'erreur - peut être dans style.css si vous le souhaitez */
.error-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh; /* Prend la majeure partie de la hauteur de l'écran */
    text-align: center;
    color: var(--text-color);
}

.error-content {
    background-color: rgba(255, 255, 255, 0.95);
    padding: 50px 30px;
    border-radius: 15px;
    box-shadow: var(--box-shadow-medium);
    max-width: 600px;
    width: 90%;
    transform: scale(0.9); /* Commence plus petit pour l'animation */
    opacity: 0;
    animation: popIn 0.8s ease-out forwards;
}

.error-code {
    font-size: 8em; /* Très grand */
    margin: 0;
    line-height: 1;
    color: var(--danger-color); /* Rouge pour les erreurs */
    font-weight: bold;
    text-shadow: 4px 4px 8px rgba(220, 53, 69, 0.3); /* Ombre pour le chiffre */
    animation: bounceIn 1s ease-out forwards;
}

.error-message-title {
    font-size: 2.5em;
    margin-top: 10px;
    color: var(--primary-color);
}

.error-description {
    font-size: 1.2em;
    margin-bottom: 30px;
    color: var(--secondary-color);
}

/* Animations spécifiques pour la page d'erreur */
@keyframes bounceIn {
    0% { opacity: 0; transform: scale(0.3); }
    50% { opacity: 1; transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); }
}

/* Pour le texte d'ombre animé - optionnel, nécessite une classe spécifique */
.animated-text-shadow {
    transition: text-shadow 0.3s ease-in-out;
}
.animated-text-shadow:hover {
    text-shadow: 6px 6px 12px rgba(220, 53, 69, 0.5); /* Ombre plus prononcée au survol */
}
</style>