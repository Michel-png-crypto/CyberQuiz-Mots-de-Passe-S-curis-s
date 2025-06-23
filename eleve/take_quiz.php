<?php
// eleve/take_quiz.php
session_start(); // Démarrer la session

require_once '../php/db_connection.php';
require_once '../php/quiz_scoring_logic.php'; // Inclure le script de correction (à créer par P3)

// Vérification des permissions : Seul un élève connecté peut accéder à cette page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'eleve') {
    header('Location: ../php/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$quiz = null;
$questions = [];
$error_message = '';
$success_message = '';
$attempt_id = null; // ID de la tentative en cours

// --- Logique d'affichage du quiz ---
if ($quiz_id > 0) {
    try {
        // 1. Récupérer le quiz et vérifier s'il est actif
        $stmt_quiz = $pdo->prepare("SELECT id, title, description, duration_minutes FROM quizzes WHERE id = ?");
        $stmt_quiz->execute([$quiz_id]);
        $quiz = $stmt_quiz->fetch();

        if (!$quiz) {
            $error_message = "Quiz non trouvé ou non disponible.";
        } else {
            // 2. Vérifier si l'élève a déjà commencé ce quiz et s'il est en cours
            // Ou créer une nouvelle tentative si aucune en cours ou la précédente est terminée
            $stmt_attempt = $pdo->prepare("SELECT id, start_time, is_completed FROM user_quiz_attempts WHERE user_id = ? AND quiz_id = ? AND is_completed = FALSE ORDER BY start_time DESC LIMIT 1");
            $stmt_attempt->execute([$user_id, $quiz_id]);
            $active_attempt = $stmt_attempt->fetch();

            if ($active_attempt) {
                $attempt_id = $active_attempt['id'];
                // Optionnel : vérifier si le temps alloué est déjà écoulé pour cette tentative
                $start_timestamp = strtotime($active_attempt['start_time']);
                $current_timestamp = time();
                $elapsed_time_seconds = $current_timestamp - $start_timestamp;
                $allowed_time_seconds = $quiz['duration_minutes'] * 60;

                if ($elapsed_time_seconds >= $allowed_time_seconds) {
                    // Si le temps est écoulé, marquer la tentative comme complétée et la corriger
                    // Cela empêche l'élève de continuer.
                    // Optionnel: rediriger vers la page de résultats après correction automatique.
                    $stmt_update_attempt = $pdo->prepare("UPDATE user_quiz_attempts SET end_time = NOW(), is_completed = TRUE WHERE id = ?");
                    $stmt_update_attempt->execute([$attempt_id]);
                    // Appeler la fonction de correction
                    correctQuizAttempt($pdo, $attempt_id); // Assurez-vous que cette fonction existe dans quiz_scoring_logic.php

                    $error_message = "Le temps pour ce quiz est écoulé. Votre tentative a été soumise et corrigée.";
                    // Rediriger vers les résultats après avoir traité
                    header("Location: my_results.php");
                    exit();
                }

            } else {
                // Créer une nouvelle tentative
                $stmt_new_attempt = $pdo->prepare("INSERT INTO user_quiz_attempts (user_id, quiz_id, start_time, is_completed) VALUES (?, ?, NOW(), FALSE)");
                $stmt_new_attempt->execute([$user_id, $quiz_id]);
                $attempt_id = $pdo->lastInsertId();
            }

            // 3. Récupérer les questions du quiz
            $stmt_questions = $pdo->prepare("SELECT id, question_text, question_type FROM questions WHERE quiz_id = ? ORDER BY id ASC");
            $stmt_questions->execute([$quiz_id]);
            $questions = $stmt_questions->fetchAll();

            // Pour chaque question, récupérer ses réponses si c'est un QCM ou Vrai/Faux
            foreach ($questions as &$question) {
                if ($question['question_type'] === 'qcm' || $question['question_type'] === 'vrai_faux') {
                    $stmt_answers = $pdo->prepare("SELECT id, answer_text FROM answers WHERE question_id = ? ORDER BY id ASC");
                    $stmt_answers->execute([$question['id']]);
                    $question['options'] = $stmt_answers->fetchAll(); // Renommé 'options' pour éviter confusion avec 'answers' table
                } else {
                    $question['options'] = [];
                }
            }
            unset($question); // Rompre la référence
        }

    } catch (PDOException $e) {
        error_log("Erreur lors du chargement du quiz: " . $e->getMessage());
        $error_message = "Une erreur est survenue lors du chargement du quiz.";
    }
} else {
    $error_message = "Aucun quiz spécifié.";
}


// --- Logique de soumission du quiz ---
if ($quiz_id > 0 && $attempt_id && $_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction(); // Démarre une transaction pour enregistrer toutes les réponses

        // Mettre à jour l'heure de fin et marquer la tentative comme complétée
        $stmt_update_attempt = $pdo->prepare("UPDATE user_quiz_attempts SET end_time = NOW(), is_completed = TRUE WHERE id = ? AND user_id = ? AND quiz_id = ?");
        $stmt_update_attempt->execute([$attempt_id, $user_id, $quiz_id]);

        // Supprimer les réponses précédentes pour cette tentative si l'on permet des soumissions partielles ou multiples dans une même tentative
        // Pour un quiz simple, on pourrait simplement insérer sans suppression préalable
        $stmt_delete_old_answers = $pdo->prepare("DELETE FROM user_answers WHERE attempt_id = ?");
        $stmt_delete_old_answers->execute([$attempt_id]);

        // Parcourir les réponses soumises par l'élève
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'question_') === 0) { // Si le nom du champ commence par 'question_'
                $question_id = (int)str_replace('question_', '', $key);

                // Récupérer le type de question pour savoir comment enregistrer la réponse
                $stmt_q_type = $pdo->prepare("SELECT question_type FROM questions WHERE id = ? AND quiz_id = ?");
                $stmt_q_type->execute([$question_id, $quiz_id]);
                $q_type_row = $stmt_q_type->fetch();

                if ($q_type_row) {
                    $question_type = $q_type_row['question_type'];

                    $selected_answer_id = null;
                    $submitted_text_answer = null;

                    if ($question_type === 'qcm' || $question_type === 'vrai_faux') {
                        $selected_answer_id = (int)$value; // L'ID de la réponse sélectionnée
                    } elseif ($question_type === 'ouverte') {
                        $submitted_text_answer = trim($value); // Le texte de la réponse ouverte
                    }

                    // Insérer la réponse de l'utilisateur
                    $stmt_insert_user_answer = $pdo->prepare("INSERT INTO user_answers (attempt_id, question_id, selected_answer_id, submitted_text_answer) VALUES (?, ?, ?, ?)");
                    $stmt_insert_user_answer->execute([$attempt_id, $question_id, $selected_answer_id, $submitted_text_answer]);
                }
            }
        }

        // Appeler la fonction de correction automatique
        correctQuizAttempt($pdo, $attempt_id); // Fonction dans quiz_scoring_logic.php

        $pdo->commit(); // Valide toutes les insertions et mises à jour
        $success_message = "Votre quiz a été soumis et corrigé !";
        header("Location: my_results.php?attempt_id=" . $attempt_id); // Rediriger vers les résultats
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack(); // Annule en cas d'erreur
        error_log("Erreur lors de la soumission du quiz: " . $e->getMessage());
        $error_message = "Une erreur est survenue lors de la soumission de votre quiz. Veuillez réessayer.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passer le Quiz : <?php echo htmlspecialchars($quiz['title'] ?? 'Chargement...'); ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/eleve_styles.css">
    <script src="../js/quiz_timer.js" defer></script> </head>
<body>
    <div class="container">
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <p><a href="join_quiz.php">Retour aux quiz disponibles</a></p>
        <?php endif; ?>

        <?php if ($quiz && empty($error_message)): ?>
            <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($quiz['description'])); ?></p>
            <p class="quiz-timer" data-duration="<?php echo htmlspecialchars($quiz['duration_minutes']); ?>">Temps restant : <span id="time"></span></p>

            <form action="take_quiz.php?quiz_id=<?php echo htmlspecialchars($quiz_id); ?>" method="POST" id="quizForm">
                <input type="hidden" name="attempt_id" value="<?php echo htmlspecialchars($attempt_id); ?>">

                <?php if (empty($questions)): ?>
                    <p>Ce quiz n'a pas encore de questions. Revenez plus tard.</p>
                <?php else: ?>
                    <?php $q_num = 1; ?>
                    <?php foreach ($questions as $question): ?>
                        <div class="question-block">
                            <h3><?php echo $q_num++; ?>. <?php echo htmlspecialchars($question['question_text']); ?></h3>
                            <?php if ($question['question_type'] === 'qcm'): ?>
                                <div class="options-list">
                                    <?php foreach ($question['options'] as $option): ?>
                                        <label>
                                            <input type="radio" name="question_<?php echo htmlspecialchars($question['id']); ?>" value="<?php echo htmlspecialchars($option['id']); ?>" required>
                                            <?php echo htmlspecialchars($option['answer_text']); ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($question['question_type'] === 'vrai_faux'): ?>
                                <div class="options-list">
                                    <?php foreach ($question['options'] as $option): ?>
                                        <label>
                                            <input type="radio" name="question_<?php echo htmlspecialchars($question['id']); ?>" value="<?php echo htmlspecialchars($option['id']); ?>" required>
                                            <?php echo htmlspecialchars($option['answer_text']); ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($question['question_type'] === 'ouverte'): ?>
                                <textarea name="question_<?php echo htmlspecialchars($question['id']); ?>" rows="3" placeholder="Votre réponse..." required></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <button type="submit" class="submit-quiz-button">Soumettre le Quiz</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>