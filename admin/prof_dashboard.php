<?php
// admin/prof_dashboard.php
session_start(); // Démarrer la session

// --- 1. Vérifier si l'utilisateur est connecté ---
if (!isset($_SESSION['user_id'])) {
    // Si non connecté, rediriger vers la page de connexion
    header('Location: ../php/login.php');
    exit();
}

// --- 2. Vérifier si l'utilisateur a le rôle "professeur" ---
if ($_SESSION['role'] !== 'professeur') {
    // Si l'utilisateur n'est pas un professeur, le rediriger ou afficher un message d'erreur
    header('Location: ../php/Accueil.php?error=access_denied'); // Ou une page d'erreur spécifique
    exit();
}

// Si l'utilisateur est un professeur et est connecté, le reste du code de la page s'exécute.
$username = htmlspecialchars($_SESSION['username']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Professeur</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin_dashboard.css"> </head>
<body>
    <div class="container">
        <h1>Bienvenue, Professeur <?php echo $username; ?> !</h1>
        <p>Ceci est votre tableau de bord. Vous pouvez gérer les quiz ici.</p>

        <nav>
            <ul>
                <li><a href="create_quiz.php">Créer un nouveau quiz</a></li>
                <li><a href="quiz/manage_questions.php">Gérer les questions des quiz</a></li>
                <li><a href="#">Voir les résultats des élèves (à venir)</a></li>
                <li><a href="../php/logout.php">Déconnexion</a></li>
            </ul>
        </nav>

        <h2>Vos Quiz Actuels</h2>
        <p>Liste de vos quiz sera affichée ici.</p>
    </div>
</body>
</html>