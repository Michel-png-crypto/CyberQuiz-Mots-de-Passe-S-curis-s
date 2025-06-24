<?php
// admin/create_quiz.php
session_start(); // Démarrer la session

// Inclure la connexion à la base de données
require_once '../php/db_connection.php';

// Vérification des permissions : Seul un professeur connecté peut accéder à cette page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professeur') {
    header('Location: ../php/login.php'); // Rediriger vers la page de connexion si non autorisé
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et nettoyer les données du formulaire
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 0); // Convertir en entier

    $prof_id = $_SESSION['user_id']; // L'ID du professeur connecté

    // --- 1. Validation des entrées côté serveur ---
    if (empty($title) || empty($description) || $duration_minutes <= 0) {
        $error_message = "Veuillez remplir tous les champs et s'assurer que la durée est valide.";
    } else {
        try {
            // --- 2. Insertion du nouveau quiz dans la table 'quizzes' ---
            $sql = "INSERT INTO quizzes (prof_id, title, description, duration_minutes) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$prof_id, $title, $description, $duration_minutes]);

            $new_quiz_id = $pdo->lastInsertId(); // Récupérer l'ID du quiz nouvellement créé

            $success_message = "Quiz '" . htmlspecialchars($title) . "' créé avec succès !";
            // Rediriger le professeur vers la page de gestion des questions pour ce quiz
            header("Location: quiz/manage_questions.php?quiz_id=" . $new_quiz_id . "&new_quiz=true");
            exit();

        } catch (PDOException $e) {
            error_log("Erreur lors de la création du quiz: " . $e->getMessage());
            $error_message = "Une erreur est survenue lors de la création du quiz. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Quiz</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin_dashboard.css">
</head>
<body>
    <div class="container">
        <h1>Créer un nouveau Quiz</h1>
        <p><a href="prof_dashboard.php">Retour au tableau de bord</a></p>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <form action="create_quiz.php" method="POST">
            <div class="form-group">
                <label for="title">Titre du Quiz :</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($title ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="description">Description :</label>
                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="duration_minutes">Durée du Quiz (en minutes) :</label>
                <input type="number" id="duration_minutes" name="duration_minutes" required min="1" value="<?php echo htmlspecialchars($duration_minutes ?? ''); ?>">
            </div>
            <button type="submit">Créer le Quiz</button>
        </form>
    </div>
</body>
</html>