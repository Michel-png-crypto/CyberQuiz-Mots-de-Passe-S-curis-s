<?php
// pages/delete_quiz.php

require_once '../includes/db.php';
require_once '../includes/functions.php';

startSession();

if (!isLoggedIn()) {
    redirect(getBasePath() . 'pages/login.php');
}

$quiz_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$quiz_id) {
    $_SESSION['error_message'] = "Requête invalide : ID de quiz manquant.";
    redirect(getBasePath() . 'pages/dashboard.php');
}

try {
    // On récupère le quiz pour vérifier les droits
    $stmt = $pdo->prepare("SELECT teacher_id FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        $_SESSION['error_message'] = "Quiz non trouvé.";
        redirect(getBasePath() . 'pages/dashboard.php');
    }

    // NOUVELLE RÈGLE DE SÉCURITÉ :
    // L'utilisateur peut supprimer s'il est le propriétaire OU s'il a le rôle de professeur.
    if ($quiz['teacher_id'] == $_SESSION['user_id'] || hasRole('teacher')) {
        
        // Procéder à la suppression
        $delete_stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
        $delete_stmt->execute([$quiz_id]);

        $_SESSION['success_message'] = "Le quiz a été supprimé avec succès.";
        
    } else {
        // L'utilisateur n'a pas les droits
        $_SESSION['error_message'] = "Vous n'avez pas la permission de supprimer ce quiz.";
    }

} catch (PDOException $e) {
    error_log("Erreur de suppression de quiz: " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la suppression du quiz.";
}

// Rediriger dans tous les cas vers le tableau de bord pour voir le résultat
redirect(getBasePath() . 'pages/dashboard.php');