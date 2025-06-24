<?php
// php/quiz_scoring_logic.php

/**
 * Corrige une tentative de quiz spécifique et met à jour le score dans la base de données.
 *
 * @param PDO $pdo L'objet PDO pour la connexion à la base de données.
 * @param int $attemptId L'ID de la tentative de quiz à corriger.
 * @return bool Vrai si la correction a réussi, faux sinon.
 */
function correctQuizAttempt(PDO $pdo, int $attemptId): bool
{
    try {
        $pdo->beginTransaction(); // Démarre une transaction pour les opérations de correction

        $totalScore = 0;

        // 1. Récupérer toutes les questions et leurs bonnes réponses pour le quiz de cette tentative
        $stmt_get_quiz_info = $pdo->prepare("
            SELECT qz.id AS quiz_id, q.id AS question_id, q.question_type, q.score_points, a.id AS correct_answer_id
            FROM user_quiz_attempts ua
            JOIN quizzes qz ON ua.quiz_id = qz.id
            JOIN questions q ON q.quiz_id = qz.id
            LEFT JOIN answers a ON q.id = a.question_id AND a.is_correct = TRUE
            WHERE ua.id = ?
        ");
        $stmt_get_quiz_info->execute([$attemptId]);
        $quiz_questions_data = $stmt_get_quiz_info->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        // 2. Récupérer les réponses soumises par l'utilisateur pour cette tentative
        $stmt_user_answers = $pdo->prepare("SELECT question_id, selected_answer_id, submitted_text_answer FROM user_answers WHERE attempt_id = ?");
        $stmt_user_answers->execute([$attemptId]);
        $user_answers = $stmt_user_answers->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        foreach ($quiz_questions_data as $question_id => $question_details) {
            $question_detail = $question_details[0]; // Chaque question aura ses détails une seule fois
            $user_answer = $user_answers[$question_id][0] ?? null; // Réponse de l'utilisateur pour cette question

            // Par défaut, la question est fausse
            $is_correct = false;

            if ($user_answer) { // Si l'utilisateur a répondu à cette question
                switch ($question_detail['question_type']) {
                    case 'qcm':
                    case 'vrai_faux':
                        // Vérifier si la réponse sélectionnée est la bonne
                        // Note: $question_details peut avoir plusieurs entrées si une question QCM a plusieurs réponses correctes (non géré ici)
                        // Pour l'instant, on suppose une seule bonne réponse par question
                        if ($question_detail['correct_answer_id'] && $user_answer['selected_answer_id'] == $question_detail['correct_answer_id']) {
                            $is_correct = true;
                        }
                        break;
                    case 'ouverte':
                        // Les questions ouvertes nécessitent une correction manuelle
                        // Pour l'auto-correction, on peut les ignorer ou leur donner 0 point ici
                        // Ou implémenter une logique simple (ex: mots-clés) si applicable
                        $is_correct = false; // Par défaut, non corrigé automatiquement
                        break;
                    // Ajoutez d'autres types de questions ici si nécessaire
                }
            }

            if ($is_correct) {
                $totalScore += $question_detail['score_points'];
            }
        }

        // 3. Mettre à jour le score de la tentative de l'utilisateur
        $stmt_update_score = $pdo->prepare("UPDATE user_quiz_attempts SET score = ? WHERE id = ?");
        $stmt_update_score->execute([$totalScore, $attemptId]);

        $pdo->commit(); // Valide la transaction
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack(); // Annule la transaction en cas d'erreur
        error_log("Erreur lors de la correction du quiz (Attempt ID: $attemptId): " . $e->getMessage());
        return false;
    }
}
?>