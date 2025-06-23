<?php
// admin/view_results.php
session_start();

require_once '../php/db_connection.php';

// Vérification des permissions : Seul un professeur connecté peut accéder à cette page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professeur') {
    header('Location: ../php/login.php');
    exit();
}

$prof_id = $_SESSION['user_id'];
$quizzes = [];
$selected_quiz_id = (int)($_GET['quiz_id'] ?? 0);
$quiz_results = [];
$error_message = '';

try {
    // 1. Récupérer tous les quiz créés par ce professeur
    $stmt_quizzes = $pdo->prepare("SELECT id, title FROM quizzes WHERE prof_id = ? ORDER BY created_at DESC");
    $stmt_quizzes->execute([$prof_id]);
    $quizzes = $stmt_quizzes->fetchAll();

    // 2. Si un quiz est sélectionné, récupérer ses résultats
    if ($selected_quiz_id > 0) {
        // Vérifier que le quiz sélectionné appartient bien au professeur
        $stmt_check_quiz = $pdo->prepare("SELECT id FROM quizzes WHERE id = ? AND prof_id = ?");
        $stmt_check_quiz->execute([$selected_quiz_id, $prof_id]);
        if (!$stmt_check_quiz->fetch()) {
            $error_message = "Quiz non trouvé ou vous n'êtes pas autorisé à voir ses résultats.";
            $selected_quiz_id = 0; // Invalider le quiz sélectionné
        } else {
            // Récupérer toutes les tentatives pour le quiz sélectionné, avec les infos de l'élève
            $stmt_results = $pdo->prepare("
                SELECT
                    ua.id AS attempt_id, u.username, ua.score, ua.start_time, ua.end_time, ua.is_completed
                FROM user_quiz_attempts ua
                JOIN users u ON ua.user_id = u.id
                WHERE ua.quiz_id = ?
                ORDER BY ua.start_time DESC
            ");
            $stmt_results->execute([$selected_quiz_id]);
            $quiz_results = $stmt_results->fetchAll();
        }
    }

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des résultats du prof: " . $e->getMessage());
    $error_message = "Impossible de charger les résultats pour le moment.";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voir les Résultats des Quiz - CyberQuiz</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin_dashboard.css">
</head>
<body>
    <div class="container">
        <h1>Voir les Résultats des Quiz</h1>
        <p><a href="prof_dashboard.php">Retour au tableau de bord</a></p>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <h2>Sélectionner un Quiz</h2>
        <?php if (empty($quizzes)): ?>
            <p>Vous n'avez pas encore créé de quiz.</p>
        <?php else: ?>
            <form action="view_results.php" method="GET">
                <div class="form-group">
                    <label for="quiz_select">Choisissez un quiz :</label>
                    <select id="quiz_select" name="quiz_id" onchange="this.form.submit()">
                        <option value="">-- Sélectionnez --</option>
                        <?php foreach ($quizzes as $quiz): ?>
                            <option value="<?php echo htmlspecialchars($quiz['id']); ?>"
                                <?php echo ($selected_quiz_id == $quiz['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($quiz['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($selected_quiz_id > 0 && empty($error_message)): ?>
            <hr>
            <h2>Résultats pour le Quiz : <?php echo htmlspecialchars($pdo->query("SELECT title FROM quizzes WHERE id = $selected_quiz_id")->fetchColumn()); ?></h2>

            <?php if (empty($quiz_results)): ?>
                <p>Aucune tentative pour ce quiz pour le moment.</p>
            <?php else: ?>
                <div class="results-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Élève</th>
                                <th>Score</th>
                                <th>Début Tentative</th>
                                <th>Fin Tentative</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quiz_results as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['username']); ?></td>
                                    <td><?php echo htmlspecialchars($result['score'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($result['start_time']); ?></td>
                                    <td><?php echo htmlspecialchars($result['end_time']); ?></td>
                                    <td><?php echo $result['is_completed'] ? 'Complété' : 'En cours'; ?></td>
                                    <td>
                                        <a href="view_attempt_details.php?attempt_id=<?php echo htmlspecialchars($result['attempt_id']); ?>" class="button-small">Détails</a>
                                        </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>