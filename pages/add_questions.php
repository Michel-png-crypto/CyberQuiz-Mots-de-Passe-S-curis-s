<?php
// pages/add_questions.php

require_once '../includes/db.php';
require_once '../includes/functions.php';
startSession();

if (!isLoggedIn()) {
    redirect(getBasePath() . 'pages/login.php');
}

$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);

$new_quiz_pin = $_SESSION['new_quiz_pin'] ?? null;
unset($_SESSION['new_quiz_pin']);

$quiz_title = '';
$questions = [];

if (!$quiz_id) {
    $error_message = 'Aucun quiz sélectionné.';
} else {
    $stmt = $pdo->prepare("SELECT title FROM quizzes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        $error_message = 'Quiz non trouvé ou vous n\'avez pas la permission de le modifier.';
        $quiz_id = null;
    } else {
        $quiz_title = $quiz['title'];
        $questions_stmt = $pdo->prepare("SELECT id, question_text, question_type FROM questions WHERE quiz_id = ? ORDER BY id ASC");
        $questions_stmt->execute([$quiz_id]);
        $questions = $questions_stmt->fetchAll();
    }
}

// --- LOGIQUE DE TRAITEMENT DU FORMULAIRE D'AJOUT DE QUESTION ---
if ($quiz_id && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Erreur de sécurité.';
    } else {
        $question_text = trim(filter_input(INPUT_POST, 'question_text', FILTER_SANITIZE_SPECIAL_CHARS));
        $question_type = filter_input(INPUT_POST, 'question_type', FILTER_SANITIZE_SPECIAL_CHARS);
        $correct_answer = trim(filter_input(INPUT_POST, 'correct_answer', FILTER_SANITIZE_SPECIAL_CHARS));
        $options_input = trim($_POST['options'] ?? '');

        $options_json = null;
        $validation_error = false;

        if (empty($question_text) || empty($question_type) || empty($correct_answer)) {
            $_SESSION['error_message'] = 'Le texte de la question, le type et la réponse correcte sont obligatoires.';
            $validation_error = true;
        }

        if (!$validation_error) {
            if ($question_type === 'multiple-choice') {
                $options_array = array_filter(array_map('trim', explode("\n", $options_input)));
                if (count($options_array) < 2) {
                    $_SESSION['error_message'] = 'Pour un QCM, au moins deux options sont requises.';
                    $validation_error = true;
                } else {
                    $valid_letters = range('A', chr(ord('A') + count($options_array) - 1));
                    if (!in_array(strtoupper($correct_answer), $valid_letters)) {
                        $_SESSION['error_message'] = 'La réponse correcte pour un QCM doit être une lettre (A, B, C...) correspondant à une option valide.';
                        $validation_error = true;
                    } else {
                        $options_json = json_encode($options_array);
                    }
                }
            } elseif ($question_type === 'true-false') {
                if (!in_array(strtolower($correct_answer), ['vrai', 'faux'])) {
                    $_SESSION['error_message'] = 'Pour Vrai/Faux, la réponse doit être "Vrai" ou "Faux".';
                    $validation_error = true;
                }
            }
        }

        if (!$validation_error) {
            try {
                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, options, correct_answer) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$quiz_id, $question_text, $question_type, $options_json, $correct_answer]);
                $_SESSION['success_message'] = 'Question ajoutée avec succès !';
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'Erreur lors de l\'ajout de la question.';
                error_log("Erreur ajout question: " . $e->getMessage());
            }
        }
    }
    redirect(getBasePath() . 'pages/add_questions.php?quiz_id=' . htmlspecialchars($quiz_id));
}

$csrf_token = generateCsrfToken();
include '../includes/header.php';
?>

<div class="container fade-in-on-load">
    <?php if ($quiz_id): ?>
        <h2>Ajouter des Questions au Quiz: "<?php echo htmlspecialchars($quiz_title); ?>"</h2>
        
        <div class="page-actions" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:20px; border-bottom: 1px solid #ddd;">
            <p class="back-link" style="margin:0;">
                <a href="<?php echo getBasePath(); ?>pages/edit_quiz.php?id=<?php echo $quiz_id; ?>">← Retour à la gestion du quiz</a>
            </p>
            <a href="<?php echo getBasePath(); ?>pages/edit_quiz.php?id=<?php echo htmlspecialchars($quiz_id); ?>" class="btn-success animated-button">
                ✔ Terminer & Gérer
            </a>
        </div>

        <?php if ($error_message) echo "<p class='message error'>".htmlspecialchars($error_message)."</p>"; ?>
        <?php if ($success_message) echo "<p class='message success'>".htmlspecialchars($success_message)."</p>"; ?>
        <?php if ($new_quiz_pin): ?>
            <div class="message success">Quiz créé ! Partagez ce code PIN : <strong><?php echo $new_quiz_pin; ?></strong></div>
        <?php endif; ?>

        <div class="form-container animated-form" style="margin-top: 20px;">
            <h3>Nouvelle Question</h3>
            <form action="add_questions.php?quiz_id=<?php echo htmlspecialchars($quiz_id); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="question_text">Texte de la Question :</label>
                    <textarea id="question_text" name="question_text" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="question_type">Type de Question :</label>
                    <select id="question_type" name="question_type" required onchange="toggleQuestionOptions()">
                        <option value="multiple-choice">Choix Multiple (QCM)</option>
                        <option value="true-false">Vrai / Faux</option>
                        <option value="text-answer">Réponse Libre (Texte)</option>
                    </select>
                </div>

                <div id="qcm_options_group" class="form-group">
                    <label for="options">Options de Réponse (une par ligne) :</label>
                    <textarea id="options" name="options" rows="4" placeholder="Option A&#10;Option B&#10;Option C&#10;..."></textarea>
                </div>

                <div class="form-group">
                    <label for="correct_answer">Réponse Correcte :</label>
                    <input type="text" id="correct_answer" name="correct_answer" required>
                    <small id="correct_answer_help" class="form-text text-muted"></small>
                </div>

                <button type="submit" name="add_question" class="btn-primary animated-button">Ajouter la Question</button>
            </form>
        </div>

        <hr style="margin: 40px 0;">

        <div class="dashboard-section">
            <h3>Questions Actuelles</h3>
            <?php if (empty($questions)): ?>
                <p class="message info">Ce quiz n'a pas encore de questions.</p>
            <?php else: ?>
                <ul class="quiz-list question-list">
                    <?php foreach ($questions as $q): ?>
                        <li class="quiz-item question-item">
                            <span><?php echo htmlspecialchars($q['question_text']); ?></span>
                            <div class="quiz-actions">
                                <a href="<?php echo getBasePath() . 'pages/edit_question.php?question_id=' . $q['id'] . '&quiz_id=' . $quiz_id; ?>" class="btn-secondary animated-button-small">Modifier</a>
                                <a href="<?php echo getBasePath() . 'pages/delete_question.php?question_id=' . $q['id'] . '&quiz_id=' . $quiz_id . '&csrf_token=' . $csrf_token; ?>" class="btn-danger animated-button-small" onclick="return confirm('Êtes-vous sûr ?');">Supprimer</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

    <?php else: // Si $quiz_id est invalide ?>
        <p class="message error"><?php echo htmlspecialchars($error_message); ?></p>
        <a href="<?php echo getBasePath(); ?>pages/dashboard.php">Retour au tableau de bord</a>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const questionTypeSelect = document.getElementById('question_type');
    const qcmOptionsGroup = document.getElementById('qcm_options_group');
    const correctAnswerInput = document.getElementById('correct_answer');
    const correctAnswerHelp = document.getElementById('correct_answer_help');

    function toggleQuestionOptions() {
        const selectedType = questionTypeSelect.value;

        if (selectedType === 'multiple-choice') {
            qcmOptionsGroup.style.display = 'block';
            options.required = true;
            correctAnswerInput.placeholder = 'Ex: A';
            correctAnswerHelp.textContent = 'Entrez la lettre (A, B, C...) de la bonne réponse.';
        } else {
            qcmOptionsGroup.style.display = 'none';
            options.required = false;
            if (selectedType === 'true-false') {
                correctAnswerInput.placeholder = 'Vrai ou Faux';
                correctAnswerHelp.textContent = 'Entrez "Vrai" ou "Faux".';
            } else { // text-answer
                correctAnswerInput.placeholder = 'Entrez la réponse exacte';
                correctAnswerHelp.textContent = 'La réponse de l\'utilisateur devra correspondre exactement.';
            }
        }
    }

    // Appeler la fonction au changement de sélection
    questionTypeSelect.addEventListener('change', toggleQuestionOptions);

    // Appeler la fonction au chargement de la page pour définir l'état initial
    toggleQuestionOptions();
});
</script>

<?php include '../includes/footer.php'; ?>