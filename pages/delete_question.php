<?php
// pages/delete_question.php

// Inclut les fichiers nécessaires pour la connexion à la base de données et les fonctions utilitaires/de sécurité
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Démarre la session. C'est crucial pour accéder aux variables de session (comme user_id, user_role, et les messages flash)
startSession();

// --- Vérification des autorisations ---
// Si l'utilisateur n'est pas connecté ou n'a pas le rôle 'teacher', le rediriger vers la page de connexion.
if (!hasRole('teacher')) {
    redirect(getBasePath() . 'pages/login.php');
}

// --- Récupération et validation des paramètres de l'URL ---
// Récupère l'ID de la question à supprimer, en s'assurant que c'est un entier valide.
$question_id = filter_input(INPUT_GET, 'question_id', FILTER_VALIDATE_INT);
// Récupère l'ID du quiz auquel la question appartient, pour la redirection ultérieure.
$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
// Récupère le jeton CSRF passé dans l'URL. C'est une sécurité importante pour les actions de suppression.
$csrf_token = filter_input(INPUT_GET, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS);

// Si l'ID de la question ou du quiz est manquant, ou si le jeton CSRF n'est pas fourni,
// stocke un message d'erreur et redirige l'utilisateur.
if (!$question_id || !$quiz_id || !$csrf_token) {
    $_SESSION['error_message'] = 'Requête de suppression de question invalide : paramètres manquants.';
    // Tente de rediriger vers le dashboard, ou la page d'ajout de questions si quiz_id est connu.
    redirect(getBasePath() . ($quiz_id ? 'pages/add_questions.php?quiz_id=' . htmlspecialchars($quiz_id) : 'pages/dashboard.php'));
}

// --- Vérification du jeton CSRF ---
// Si le jeton CSRF ne correspond pas à celui stocké en session, l'action est potentiellement malveillante.
// Stocke un message d'erreur et redirige.
if (!verifyCsrfToken($csrf_token)) {
    $_SESSION['error_message'] = 'Erreur de sécurité: Requête non valide (CSRF).';
    redirect(getBasePath() . 'pages/add_questions.php?quiz_id=' . htmlspecialchars($quiz_id));
}

// --- Logique de suppression ---
try {
    // Avant de supprimer, on vérifie que :
    // 1. La question existe.
    // 2. Elle appartient bien au quiz spécifié.
    // 3. Le quiz appartient bien au professeur actuellement connecté (sécurité cruciale).
    $stmt = $pdo->prepare("
        SELECT q.id AS question_id
        FROM questions q
        JOIN quizzes quiz ON q.quiz_id = quiz.id
        WHERE q.id = ? AND q.quiz_id = ? AND quiz.teacher_id = ?
    ");
    $stmt->execute([$question_id, $quiz_id, $_SESSION['user_id']]);
    $question = $stmt->fetch();

    if ($question) {
        // Si la question est trouvée et les autorisations sont correctes, procéder à la suppression.
        $delete_stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $delete_stmt->execute([$question_id]);
        $_SESSION['success_message'] = 'Question supprimée avec succès !';
    } else {
        // Si la question n'est pas trouvée ou n'appartient pas au professeur/quiz attendu.
        $_SESSION['error_message'] = 'Question non trouvée ou vous n\'avez pas la permission de la supprimer.';
    }
} catch (PDOException $e) {
    // En cas d'erreur de base de données, log l'erreur et informe l'utilisateur.
    error_log("Erreur de suppression de question: " . $e->getMessage());
    $_SESSION['error_message'] = 'Une erreur est survenue lors de la suppression de la question.';
}

// --- Redirection finale ---
// Redirige toujours vers la page d'ajout/gestion des questions du quiz
// pour que l'utilisateur voie la liste mise à jour.
redirect(getBasePath() . 'pages/add_questions.php?quiz_id=' . htmlspecialchars($quiz_id));
?>