<?php
// pages/submit_quiz.php

require_once '../includes/db.php';
require_once '../includes/functions.php';
startSession();

// Sécurité : vérifier la méthode, le rôle et le jeton CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBasePath() . 'pages/dashboard.php');
}
if (!hasRole('student')) {
    redirect(getBasePath() . 'pages/login.php');
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_message'] = "Erreur de sécurité. Requête invalide.";
    redirect(getBasePath() . 'pages/dashboard.php');
}

// 1. Récupérer les données envoyées par le formulaire
$quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
$user_answers_json = $_POST['answers'] ?? '[]';
$user_answers = json_decode($user_answers_json, true);
$user_id = $_SESSION['user_id'];

if (!$quiz_id || !is_array($user_answers)) {
    $_SESSION['error_message'] = "Les données soumises sont invalides.";
    redirect(getBasePath() . 'pages/dashboard.php');
}

try {
    // 2. Récupérer les bonnes réponses de la BDD pour ce quiz
    $stmt = $pdo->prepare("SELECT id, correct_answer FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $correct_answers_from_db = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 3. Calculer le score
    $score = 0;
    foreach ($user_answers as $answer) {
        $question_id = $answer['question_id'];
        $user_answer = $answer['answer'];

        // Comparaison insensible à la casse et sans espaces superflus
        if (isset($correct_answers_from_db[$question_id]) && strcasecmp(trim($correct_answers_from_db[$question_id]), trim($user_answer)) === 0) {
            $score++;
        }
    }
    $total_questions = count($correct_answers_from_db);

    // 4. Enregistrer le résultat dans la table `results`
    $stmt = $pdo->prepare(
        "INSERT INTO results (user_id, quiz_id, score, total_questions, submission_date) 
         VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->execute([$user_id, $quiz_id, $score, $total_questions]);
    $result_id = $pdo->lastInsertId();

    // 5. Rediriger vers la page de résultats avec l'ID du résultat
    redirect(getBasePath() . 'pages/results.php?result_id=' . $result_id);

} catch (PDOException $e) {
    error_log("Erreur lors de la soumission du quiz (submit_quiz.php): " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la sauvegarde de vos résultats.";
    redirect(getBasePath() . 'pages/dashboard.php');
}