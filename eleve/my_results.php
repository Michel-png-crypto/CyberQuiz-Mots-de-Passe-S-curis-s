<?php
// eleve/my_results.php
session_start();

require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'eleve') {
    header('Location: ../php/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_attempts = [];
$error_message = '';
$specific_attempt_details = null;

try {
    // Récupérer toutes les tentatives de quiz de l'utilisateur
    $stmt_attempts = $pdo->prepare("
        SELECT ua.id, q.title, ua.score, ua.start_time, ua.end_time, ua.is_completed
        FROM user_quiz_attempts ua
        JOIN quizzes q ON ua.quiz_id = q.id
        WHERE ua.user_id = ?
        ORDER BY ua.start_time DESC
    ");
    $stmt_attempts->execute([$user_id]);
    $user_attempts = $stmt_attempts->fetchAll();

    // Si un attempt_id spécifique est passé en GET, afficher les détails
    if (isset($_GET['attempt_id']) && (int)$_GET['attempt_id'] > 0) {
        $specific_attempt_id = (int)$_GET['attempt_id'];

        // S'assurer que la tentative appartient bien à l'utilisateur
        $stmt_details = $pdo->prepare("
            SELECT
                ua.id AS attempt_id, qz.title AS quiz_title, ua.score, ua.start_time, ua.end_time, ua.is_completed,
                que.id AS question_id, que.question_text, que.question_type, que.score_points,
                ans_user.selected_answer_id, ans_user.submitted_text_answer,
                ans_correct.id AS correct_answer_id, ans_correct.answer_text AS correct_answer_text
            FROM user_quiz_attempts ua
            JOIN quizzes qz ON ua.quiz_id = qz.id
            JOIN questions que ON que.quiz_id = qz.id
            LEFT JOIN user_answers ans_user ON ans_user.attempt_id = ua.id AND ans_user.question_id = que.id
            LEFT JOIN answers ans_correct ON ans_correct.question_id = que.id AND ans_correct.is_correct = TRUE
            WHERE ua.id = ? AND ua.user_id = ?
            ORDER BY que.id ASC
        ");
        $stmt_details->execute([$specific_attempt_id, $user_id]);
        $specific_attempt_details_raw = $stmt_details->fetchAll();

        if ($specific_attempt_details_raw) {
            $specific_attempt_details = [
                'quiz_title' => htmlspecialchars($specific_attempt_details_raw[0]['quiz_title']),
                'score' => htmlspecialchars($specific_attempt_details_raw[0]['score']),
                'start_time' => htmlspecialchars($specific_attempt_details_raw[0]['start_time']),
                'end_time' => htmlspecialchars($specific_attempt_details_raw[0]['end_time']),
                'is_completed' => htmlspecialchars($specific_attempt_details_raw[0]['is_completed']),
                'questions' => []
            ];
            foreach ($specific_attempt_details_raw as $row) {
                $question_id = $row['question_id'];
                if (!isset($specific_attempt_details['questions'][$question_id])) {
                    $specific_attempt_details['questions'][$question_id] = [
                        'text' => htmlspecialchars($row['question_text']),
                        'type' => htmlspecialchars($row['question_type']),
                        'score_points' => htmlspecialchars($row['score_points']),
                        'user_selected_answer_id' => $row['selected_answer_id'],
                        'user_submitted_text_answer' => htmlspecialchars($row['submitted_text_answer']),
                        'correct_answer_id' => $row['correct_answer_id'],
                        'correct_answer_text' => htmlspecialchars($row['correct_answer_text']),
                        'user_answer_text_qcm' => '' // Initialiser
                    ];

                    // Récupérer le texte de la réponse choisie par l'utilisateur pour les QCM/VraiFaux
                    if ($row['question_type'] === 'qcm' || $row['question_type'] === 'vrai_faux') {
                        $stmt_user_selected_answer_text = $pdo->prepare("SELECT answer_text FROM answers WHERE id = ?");
                        $stmt_user_selected_answer_text->execute([$row['selected_answer_id']]);
                        $selected_text = $stmt_user_selected_answer_text->fetchColumn();
                        $specific_attempt_details['questions'][$question_id]['user_answer_text_qcm'] = htmlspecialchars($selected_text);
                    }
                }
            }
        } else {
            $error_message = "Détails de la tentative non trouvés ou non autorisés.";
        }
    }

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des résultats: " . $e->getMessage());
    $error_message = "Impossible de charger vos résultats pour le moment.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Résultats de Quiz - CyberQuiz</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/eleve_styles.css">
</head>
<body>
    <div class="container">
        <h1>Mes Résultats de Quiz</h1>
        <p><a href="join_quiz.php">Retour aux quiz disponibles</a> | <a href="../php/logout.php">Déconnexion</a></p>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <?php if ($specific_attempt_details): ?>
            <div class="attempt-details">
                <h2>Détails de la Tentative : <?php echo $specific_attempt_details['quiz_title']; ?></h2>
                <p>Score : *<?php echo $specific_attempt_details['score']; ?>* / Total des points (à calculer)</p>
                <p>Début : <?php echo $specific_attempt_details['start_time']; ?></p>
                <p>Fin : <?php echo $specific_attempt_details['end_time']; ?></p>
                <p>Statut : <?php echo $specific_attempt_details['is_completed'] ? 'Complété' : 'En Cours / Non Complété'; ?></p>

                <h3>Questions et Réponses :</h3>
                <?php $q_num = 1; ?>
                <?php foreach ($specific_attempt_details['questions'] as $question_id => $q_detail): ?>
                    <div class="question-result-item">
                        <h4><?php echo $q_num++; ?>. <?php echo $q_detail['text']; ?> (<?php echo $q_detail['score_points']; ?> pts)</h4>
                        <?php if ($q_detail['type'] === 'qcm' || $q_detail['type'] === 'vrai_faux'): ?>
                            <p>Votre réponse : *<?php echo $q_detail['user_answer_text_qcm'] ?: 'Non répondu'; ?>*</p>
                            <p class="<?php echo ($q_detail['user_selected_answer_id'] == $q_detail['correct_answer_id']) ? 'correct' : 'incorrect'; ?>">
                                Réponse correcte : *<?php echo $q_detail['correct_answer_text'] ?: 'N/A'; ?>*
                            </p>
                            <?php if ($q_detail['user_selected_answer_id'] == $q_detail['correct_answer_id']): ?>
                                <span class="badge correct">Bonne réponse</span>
                            <?php else: ?>
                                <span class="badge incorrect">Mauvaise réponse</span>
                            <?php endif; ?>
                        <?php elseif ($q_detail['type'] === 'ouverte'): ?>
                            <p>Votre réponse : *<?php echo $q_detail['user_submitted_text_answer'] ?: 'Non répondu'; ?>*</p>
                            <p class="info-text">Cette question nécessite une correction manuelle.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <p><a href="my_results.php">Retour à la liste des tentatives</a></p>
            </div>
        <?php else: ?>
            <?php if (empty($user_attempts)): ?>
                <p>Vous n'avez pas encore passé de quiz.</p>
            <?php else: ?>
                <h2>Historique de mes tentatives</h2>
                <div class="attempts-list">
                    <?php foreach ($user_attempts as $attempt): ?>
                        <div class="attempt-item">
                            <h3>Quiz: <?php echo htmlspecialchars($attempt['title']); ?></h3>
                            <p>Date: <?php echo htmlspecialchars($attempt['start_time']); ?></p>
                            <p>Score: *<?php echo htmlspecialchars($attempt['score'] ?? 'N/A'); ?>*</p>
                            <p>Statut: <?php echo $attempt['is_completed'] ? 'Complété' : 'En cours / Non complété'; ?></p>
                            <a href="my_results.php?attempt_id=<?php echo htmlspecialchars($attempt['id']); ?>" class="button">Voir les détails</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>