<?php
// pages/edit_question.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

startSession();

// Vérifier si l'utilisateur est un professeur et est connecté
if (!hasRole('teacher')) {
    redirect(getBasePath() . 'pages/login.php');
}

// Récupération des messages flash de session
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message']); 
unset($_SESSION['success_message']); 

$question_id = filter_input(INPUT_GET, 'question_id', FILTER_VALIDATE_INT);
$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$question_data = null;
$quiz_title = '';

if (!$question_id || !$quiz_id) {
    $error_message = 'Aucune question ou quiz sélectionné pour l\'édition.';
} else {
    try {
        // Récupérer les détails de la question et vérifier l'appartenance au professeur (sécurité)
        $stmt = $pdo->prepare("
            SELECT q.*, quiz.title AS quiz_title
            FROM questions q
            JOIN quizzes quiz ON q.quiz_id = quiz.id
            WHERE q.id = ? AND q.quiz_id = ? AND quiz.teacher_id = ?
        ");
        $stmt->execute([$question_id, $quiz_id, $_SESSION['user_id']]);
        $question_data = $stmt->fetch();

        if (!$question_data) {
            $error_message = 'Question non trouvée ou vous n\'avez pas la permission de la modifier.';
        } else {
            $quiz_title = $question_data['quiz_title'];
            // Pour les options QCM, décoder le JSON si non nul pour pré-remplir le textarea
            if ($question_data['question_type'] === 'multiple-choice' && $question_data['options']) {
                $question_data['options_array'] = json_decode($question_data['options'], true);
                if (!is_array($question_data['options_array'])) { // Gérer le cas où le JSON est malformé en BDD
                    $question_data['options_array'] = []; 
                    error_log("Malformed JSON for options in question ID: " . $question_id);
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur chargement question pour édition: " . $e->getMessage());
        $error_message = 'Une erreur est survenue lors du chargement de la question.';
    }
}

// --- Traitement de la soumission du formulaire d'édition ---
// Ce bloc ne s'exécute que si question_data est chargée ET que la méthode est POST
if ($question_data && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Erreur de sécurité: Requête non valide (CSRF).';
    } else {
        $new_question_text = trim(filter_input(INPUT_POST, 'question_text', FILTER_SANITIZE_SPECIAL_CHARS));
        $new_question_type = filter_input(INPUT_POST, 'question_type', FILTER_SANITIZE_SPECIAL_CHARS);
        $new_correct_answer = trim(filter_input(INPUT_POST, 'correct_answer', FILTER_SANITIZE_SPECIAL_CHARS));
        $new_options_input = trim(filter_input(INPUT_POST, 'options', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES));

        $new_options_json = null;
        $has_edit_error = false; 
        $current_error_message = ''; // Message d'erreur spécifique à cette soumission

        if (empty($new_question_text) || empty($new_question_type) || empty($new_correct_answer)) {
            $current_error_message = 'Tous les champs marqués comme requis sont obligatoires.';
            $has_edit_error = true;
        }

        if (!$has_edit_error) {
            switch ($new_question_type) {
                case 'multiple-choice':
                    $options_array = array_map('trim', explode("\n", $new_options_input));
                    $options_array = array_filter($options_array);
                    if (empty($options_array) || count($options_array) < 2) {
                        $current_error_message = 'Pour les questions à choix multiples, au moins deux options sont requises.';
                        $has_edit_error = true;
                    } else {
                        $valid_correct_answer = false;
                        $alpha_index_map = range('A', 'Z');
                        $correct_answer_upper = strtoupper($new_correct_answer);
                        foreach ($options_array as $index => $option) {
                            if (isset($alpha_index_map[$index]) && $correct_answer_upper === $alpha_index_map[$index]) {
                                $valid_correct_answer = true;
                                break;
                            }
                        }
                        if (!$valid_correct_answer) {
                            $current_error_message = 'La réponse correcte pour un QCM doit être une lettre correspondant à une option (ex: A, B).';
                            $has_edit_error = true;
                        } else {
                            $new_options_json = json_encode($options_array);
                        }
                    }
                    break;
                case 'true-false':
                    $normalized_correct_answer = strtolower($new_correct_answer);
                    if (!in_array($normalized_correct_answer, ['vrai', 'faux', 'true', 'false'])) {
                        $current_error_message = 'Pour Vrai/Faux, la réponse correcte doit être "Vrai" ou "Faux".';
                        $has_edit_error = true;
                    }
                    break;
                // text-answer n'a pas de validation spéciale pour les options/réponse
            }
        }

        if (!$has_edit_error) {
            try {
                $update_stmt = $pdo->prepare("UPDATE questions SET question_text = ?, question_type = ?, options = ?, correct_answer = ? WHERE id = ? AND quiz_id = ?");
                $update_stmt->execute([$new_question_text, $new_question_type, $new_options_json, $new_correct_answer, $question_id, $quiz_id]);
                
                $_SESSION['success_message'] = 'Question mise à jour avec succès !';
                // Rediriger vers la page add_questions.php pour voir la liste mise à jour
                redirect(getBasePath() . 'pages/add_questions.php?quiz_id=' . htmlspecialchars($quiz_id));

            } catch (PDOException $e) {
                error_log("Erreur de mise à jour question: " . $e->getMessage());
                $_SESSION['error_message'] = 'Une erreur est survenue lors de la mise à jour de la question.';
            }
        } else {
            $_SESSION['error_message'] = $current_error_message; // Définit le message d'erreur si validation échoue
        }
    }
    // Redirige toujours pour afficher le message flash et éviter la resoumission du formulaire
    redirect(getBasePath() . 'pages/edit_question.php?question_id=' . htmlspecialchars($question_id) . '&quiz_id=' . htmlspecialchars($quiz_id));
}

// Générer un nouveau jeton CSRF pour le formulaire
$csrf_token = generateCsrfToken();
?>

<?php include '../includes/header.php'; ?>

<div class="container fade-in-on-load">
    <h2>Modifier une Question pour le Quiz: "<?php echo htmlspecialchars($quiz_title); ?>"</h2>
    <p class="back-link"><a href="<?php echo getBasePath(); ?>pages/add_questions.php?quiz_id=<?php echo htmlspecialchars($quiz_id); ?>">← Retour aux Questions</a></p>

    <?php if ($error_message): ?>
        <p class="message error"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <p class="message success"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>

    <?php if ($question_data): // Afficher le formulaire seulement si les données de la question ont été chargées ?>
        <div class="form-container animated-form">
            <h3>Éditer la Question</h3>
            <form action="edit_question.php?question_id=<?php echo htmlspecialchars($question_id); ?>&quiz_id=<?php echo htmlspecialchars($quiz_id); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-group">
                    <label for="question_text">Texte de la Question:</label>
                    <textarea id="question_text" name="question_text" rows="3" required><?php echo htmlspecialchars($_POST['question_text'] ?? $question_data['question_text']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="question_type">Type de Question:</label>
                    <select id="question_type" name="question_type" required onchange="toggleEditQuestionOptions()">
                        <option value="multiple-choice" <?php echo (($_POST['question_type'] ?? $question_data['question_type']) === 'multiple-choice') ? 'selected' : ''; ?>>Choix Multiple (QCM)</option>
                        <option value="true-false" <?php echo (($_POST['question_type'] ?? $question_data['question_type']) === 'true-false') ? 'selected' : ''; ?>>Vrai/Faux</option>
                        <option value="text-answer" <?php echo (($_POST['question_type'] ?? $question_data['question_type']) === 'text-answer') ? 'selected' : ''; ?>>Réponse Libre (Texte)</option>
                    </select>
                </div>

                <div id="edit_qcm_options_group" class="form-group" style="display:none;">
                    <label>Options de Réponse (pour QCM, une option par ligne):</label>
                    <textarea id="edit_options" name="options" rows="4" placeholder="Ex:&#10;Option A&#10;Option B&#10;Option C"><?php
                        // Pré-remplir avec les options du POST si erreur, sinon avec les options de la BDD
                        if (isset($_POST['options'])) {
                            echo htmlspecialchars($_POST['options']);
                        } elseif (isset($question_data['options_array']) && is_array($question_data['options_array'])) {
                            echo htmlspecialchars(implode("\n", $question_data['options_array']));
                        }
                    ?></textarea>
                    <small>Pour le QCM, la réponse correcte doit être la lettre correspondant à l'option (A, B, C...).</small>
                </div>

                <div class="form-group">
                    <label for="correct_answer">Réponse Correcte:</label>
                    <input type="text" id="correct_answer" name="correct_answer" placeholder="Ex: A, Vrai, ou la bonne réponse textuelle" value="<?php echo htmlspecialchars($_POST['correct_answer'] ?? $question_data['correct_answer']); ?>" required>
                    <small id="correct_answer_help" class="form-text text-muted"></small>
                </div>

                <button type="submit" class="btn-primary animated-button">Mettre à Jour la Question</button>
            </form>
        </div>

        <script>
            // JavaScript pour gérer l'affichage des options de question lors de l'édition
            function toggleEditQuestionOptions() {
                const questionType = document.getElementById('question_type').value;
                const qcmOptionsGroup = document.getElementById('edit_qcm_options_group');
                const correctAnswerField = document.getElementById('correct_answer');
                const correctAnswerHelp = document.getElementById('correct_answer_help');

                if (questionType === 'multiple-choice') {
                    qcmOptionsGroup.style.display = 'block';
                    correctAnswerField.placeholder = 'Ex: A, B, C (la lettre de l\'option)';
                    correctAnswerHelp.textContent = 'Entrez la lettre (A, B, C...) de la bonne option.';
                } else {
                    qcmOptionsGroup.style.display = 'none';
                    if (questionType === 'true-false') {
                        correctAnswerField.placeholder = 'Ex: Vrai ou Faux';
                        correctAnswerHelp.textContent = 'Entrez "Vrai" ou "Faux".';
                    } else { // text-answer
                        correctAnswerField.placeholder = 'Ex: La bonne réponse textuelle';
                        correctAnswerHelp.textContent = 'Entrez le texte exact de la bonne réponse.';
                    }
                }
            }
            // Appeler au chargement pour l'état initial (important pour les rechargements de page avec POST)
            document.addEventListener('DOMContentLoaded', toggleEditQuestionOptions);
            // Appeler immédiatement au chargement pour s'assurer que l'état initial est correct
            // Surtout si la page est chargée suite à un POST (erreurs de validation)
            toggleEditQuestionOptions();
        </script>

    <?php else: ?>
        <p class="message info">La question n'a pas pu être chargée. Veuillez vérifier l'ID ou vos permissions.</p>
        <p class="form-link"><a href="<?php echo getBasePath(); ?>pages/dashboard.php">Retour au Tableau de Bord</a></p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>