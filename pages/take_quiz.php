<?php
// pages/take_quiz.php

require_once '../includes/db.php';
require_once '../includes/functions.php';

startSession();

if (!isLoggedIn()) {
    redirect(getBasePath() . 'pages/login.php');
}

$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
if (!$quiz_id) {
    $_SESSION['error_message'] = "Aucun quiz n'a été sélectionné.";
    redirect(getBasePath() . 'pages/dashboard.php');
}

try {
    $stmt = $pdo->prepare("SELECT title, duration FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz_info) {
        $_SESSION['error_message'] = "Ce quiz n'est pas disponible ou n'existe pas.";
        redirect(getBasePath() . 'pages/dashboard.php');
    }

    $stmt = $pdo->prepare("SELECT id, question_text, question_type, options FROM questions WHERE quiz_id = ? ORDER BY RAND()");
    $stmt->execute([$quiz_id]);
    $questions_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $quiz_data_for_js = [];
    foreach ($questions_from_db as $q) {
        $options_array = [];
        if ($q['question_type'] === 'multiple-choice' && !empty($q['options'])) {
            $options_array = json_decode($q['options'], true);
        } elseif ($q['question_type'] === 'true-false') {
            $options_array = ['Vrai', 'Faux'];
        }

        $quiz_data_for_js[] = [
            'question_id' => $q['id'],
            'text' => $q['question_text'],
            'type' => $q['question_type'],
            'options' => $options_array,
        ];
    }

} catch (PDOException $e) {
    error_log("Erreur de chargement du quiz (take_quiz.php): " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur technique est survenue lors du chargement du quiz.";
    redirect(getBasePath() . 'pages/dashboard.php');
}

include '../includes/header.php';
?>

<div class="container fade-in-on-load">
    <div id="quiz-container">
        <div class="quiz-header">
            <h1><?php echo htmlspecialchars($quiz_info['title']); ?></h1>
            <div id="timer" class="message info">Temps restant: <span id="time"><?php echo htmlspecialchars($quiz_info['duration']); ?>:00</span></div>
        </div>

        <div id="question-area" class="animated-form" style="display:none;">
            <h3 id="question-text">Chargement...</h3>
            <div id="options-container"></div>
        </div>
        
        <div class="quiz-navigation" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <button id="stop-btn" class="btn-danger">Arrêter le Quiz</button>
            <button id="next-btn" class="btn-primary animated-button">Question Suivante</button>
        </div>
    </div>
</div>

<form id="quiz-form" action="<?php echo getBasePath(); ?>pages/submit_quiz.php" method="POST" style="display:none;">
    <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz_id); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
</form>

<script>
    const quizData = <?php echo json_encode($quiz_data_for_js); ?>;
    const quizDuration = <?php echo intval($quiz_info['duration']); ?>;
</script>
<script src="<?php echo getBasePath(); ?>js/quiz_player.js"></script>

<?php include '../includes/footer.php'; ?>