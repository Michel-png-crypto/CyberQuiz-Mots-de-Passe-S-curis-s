<?php
// eleve/join_quiz.php
session_start(); // Démarrer la session

// Inclure la connexion à la base de données
require_once '../php/db_connection.php';

// Vérification des permissions : Seul un élève connecté peut accéder à cette page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'eleve') {
    header('Location: ../php/login.php'); // Rediriger vers la page de connexion si non autorisé
    exit();
}

$user_id = $_SESSION['user_id'];
$available_quizzes = [];
$error_message = '';

try {
    // Récupérer les quiz disponibles
    // Pour l'instant, on liste tous les quiz. Plus tard, vous pourriez ajouter un statut (actif/inactif)
    $stmt = $pdo->query("SELECT id, title, description, duration_minutes FROM quizzes ORDER BY created_at DESC");
    $available_quizzes = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des quiz: " . $e->getMessage());
    $error_message = "Impossible de charger les quiz disponibles pour le moment.";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Disponibles - CyberQuiz</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/eleve_styles.css"> </head>
<body>
    <div class="container">
        <h1>Quiz Disponibles pour <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <p><a href="my_results.php">Voir mes résultats</a> | <a href="../php/logout.php">Déconnexion</a></p>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <?php if (empty($available_quizzes)): ?>
            <p>Aucun quiz n'est disponible pour le moment. Revenez plus tard !</p>
        <?php else: ?>
            <div class="quiz-list">
                <?php foreach ($available_quizzes as $quiz): ?>
                    <div class="quiz-item">
                        <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
                        <p><?php echo nl2br(htmlspecialchars($quiz['description'])); ?></p>
                        <p>Durée estimée : <?php echo htmlspecialchars($quiz['duration_minutes']); ?> minutes</p>
                        <a href="take_quiz.php?quiz_id=<?php echo htmlspecialchars($quiz['id']); ?>" class="button">Passer le Quiz</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>