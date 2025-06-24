<?php
// admin/quiz/manage_questions.php
session_start(); // Démarrer la session

// Inclure la connexion à la base de données
require_once '../../php/db_connection.php'; // Remonter de deux niveaux pour atteindre php/

// Vérification des permissions : Seul un professeur connecté peut accéder à cette page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professeur') {
    header('Location: ../../php/login.php');
    exit();
}

$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$prof_id = $_SESSION['user_id'];

$quiz_title = '';
$questions = [];
$error_message = '';
$success_message = '';

// --- Vérifier si le quiz existe et appartient bien au professeur connecté ---
if ($quiz_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE id = ? AND prof_id = ?");
        $stmt->execute([$quiz_id, $prof_id]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            $error_message = "Quiz non trouvé ou vous n'avez pas la permission de le gérer.";
            $quiz_id = 0; // Invalider l'ID du quiz pour ne pas tenter d'ajouter des questions
        } else {
            $quiz_title = htmlspecialchars($quiz['title']);

            // --- Récupérer les questions existantes pour ce quiz ---
            $stmt_q = $pdo->prepare("SELECT q.id, q.question_text, q.question_type, q.score_points FROM questions q WHERE q.quiz_id = ? ORDER BY q.id ASC");
            $stmt_q->execute([$quiz_id]);
            $questions = $stmt_q->fetchAll();

            // Pour chaque question, récupérer ses réponses si c'est un QCM ou Vrai/Faux
            foreach ($questions as &$question) {
                if ($question['question_type'] === 'qcm' || $question['question_type'] === 'vrai_faux') {
                    $stmt_a = $pdo->prepare("SELECT id, answer_text, is_correct FROM answers WHERE question_id = ? ORDER BY id ASC");
                    $stmt_a->execute([$question['id']]);
                    $question['answers'] = $stmt_a->fetchAll();
                } else {
                    $question['answers'] = [];
                }
            }
            unset($question); // Rompre la référence de la dernière variable
        }

    } catch (PDOException $e) {
        error_log("Erreur de récupération du quiz/questions: " . $e->getMessage());
        $error_message = "Une erreur est survenue lors du chargement des informations du quiz.";
        $quiz_id = 0;
    }
} else {
    $error_message = "Aucun ID de quiz spécifié.";
}

// --- Logique d'ajout d'une nouvelle question ---
if ($quiz_id > 0 && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $question_text = trim($_POST['question_text'] ?? '');
    $question_type = $_POST['question_type'] ?? '';
    $score_points = (int)($_POST['score_points'] ?? 1);
    $answers_data = $_POST['answers'] ?? []; // Tableau des réponses pour QCM/VraiFaux

    if (empty($question_text)) {
        $error_message = "Le texte de la question ne peut pas être vide.";
    } elseif (!in_array($question_type, ['qcm', 'vrai_faux', 'ouverte'])) {
        $error_message = "Type de question invalide.";
    } else {
        try {
            $pdo->beginTransaction(); // Démarre une transaction pour l'insertion de question et réponses

            // Insérer la question
            $stmt_insert_q = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, score_points) VALUES (?, ?, ?, ?)");
            $stmt_insert_q->execute([$quiz_id, $question_text, $question_type, $score_points]);
            $new_question_id = $pdo->lastInsertId();

            // Gérer les réponses pour QCM et Vrai/Faux
            if ($question_type === 'qcm' || $question_type === 'vrai_faux') {
                $has_correct_answer = false;
                foreach ($answers_data as $answer_text) {
                    $is_correct = (isset($_POST['correct_answer']) && $answer_text === $_POST['correct_answer']); // Pour les radios/checkbox

                    if ($is_correct) {
                        $has_correct_answer = true;
                    }

                    $stmt_insert_a = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                    $stmt_insert_a->execute([$new_question_id, trim($answer_text), $is_correct]);
                }
                if (!$has_correct_answer && ($question_type === 'qcm' || $question_type === 'vrai_faux')) {
                    throw new Exception("Une réponse correcte doit être sélectionnée pour les questions QCM/Vrai ou Faux.");
                }
            }

            $pdo->commit(); // Valide la transaction
            $success_message = "Question ajoutée avec succès !";
            // Recharger la page pour afficher la nouvelle question
            header("Location: manage_questions.php?quiz_id=" . $quiz_id . "&success=question_added");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack(); // Annule la transaction en cas d'erreur
            error_log("Erreur lors de l'ajout de la question: " . $e->getMessage());
            $error_message = "Erreur lors de l'ajout de la question: " . $e->getMessage();
        }
    }
}

// --- Logique de suppression d'une question ---
if ($quiz_id > 0 && isset($_GET['action']) && $_GET['action'] === 'delete_question' && isset($_GET['question_id'])) {
    $question_to_delete_id = (int)$_GET['question_id'];
    try {
        $pdo->beginTransaction();

        // Option 1: La DB gère la suppression en cascade des réponses (recommandé si la FK est ON DELETE CASCADE)
        // Vérifier d'abord que la question appartient bien à ce quiz et à ce prof pour éviter les suppressions non autorisées
        $stmt_check_q = $pdo->prepare("SELECT q.id FROM questions q JOIN quizzes qz ON q.quiz_id = qz.id WHERE q.id = ? AND qz.id = ? AND qz.prof_id = ?");
        $stmt_check_q->execute([$question_to_delete_id, $quiz_id, $prof_id]);
        if (!$stmt_check_q->fetch()) {
            throw new Exception("Question non trouvée ou non autorisée pour suppression.");
        }

        $stmt_delete_q = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt_delete_q->execute([$question_to_delete_id]);

        $pdo->commit();
        $success_message = "Question supprimée avec succès !";
        header("Location: manage_questions.php?quiz_id=" . $quiz_id . "&success=question_deleted");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur de suppression de question: " . $e->getMessage());
        $error_message = "Erreur lors de la suppression de la question: " . $e->getMessage();
    }
}

// Messages de succès via GET (après redirection)
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'question_added') {
        $success_message = "Question ajoutée avec succès !";
    } elseif ($_GET['success'] === 'question_deleted') {
        $success_message = "Question supprimée avec succès !";
    } elseif (isset($_GET['new_quiz']) && $_GET['new_quiz'] == 'true') {
        $success_message = "Quiz créé ! Ajoutez maintenant des questions.";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Questions du Quiz <?php echo $quiz_title; ?></title>
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/admin_dashboard.css">
</head>
<body>
    <div class="container">
        <h1>Gérer les Questions du Quiz : <?php echo $quiz_title; ?></h1>
        <p><a href="../prof_dashboard.php">Retour au tableau de bord</a> | <a href="../create_quiz.php">Créer un nouveau quiz</a></p>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <?php if ($quiz_id > 0): // Afficher les formulaires et listes si le quiz est valide ?>
            <h2>Ajouter une nouvelle question</h2>
            <form action="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" method="POST" class="add-question-form">
                <input type="hidden" name="action" value="add_question">
                <div class="form-group">
                    <label for="question_text">Texte de la Question :</label>
                    <textarea id="question_text" name="question_text" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="question_type">Type de Question :</label>
                    <select id="question_type" name="question_type" required onchange="toggleAnswerFields(this.value)">
                        <option value="">Sélectionner un type</option>
                        <option value="qcm">QCM (Choix Multiples)</option>
                        <option value="vrai_faux">Vrai ou Faux</option>
                        <option value="ouverte">Question Ouverte</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="score_points">Points :</label>
                    <input type="number" id="score_points" name="score_points" value="1" min="1" required>
                </div>

                <div id="answer-fields" style="display: none;">
                    <h3>Réponses :</h3>
                    <div id="qcm-answers">
                        </div>
                    <button type="button" onclick="addAnswerField()">Ajouter une réponse</button>
                    <p class="help-text">Pour QCM, sélectionnez la bonne réponse avec la case radio. Pour Vrai/Faux, "Vrai" sera la réponse correcte par défaut.</p>
                </div>
                
                <button type="submit">Ajouter la Question</button>
            </form>

            <hr>

            <h2>Questions du Quiz</h2>
            <?php if (empty($questions)): ?>
                <p>Aucune question ajoutée à ce quiz pour le moment.</p>
            <?php else: ?>
                <div class="questions-list">
                    <?php foreach ($questions as $q): ?>
                        <div class="question-item">
                            <h3><?php echo htmlspecialchars($q['question_text']); ?> (<?php echo htmlspecialchars($q['question_type']); ?> - <?php echo $q['score_points']; ?> pts)</h3>
                            <?php if (!empty($q['answers'])): ?>
                                <ul>
                                    <?php foreach ($q['answers'] as $a): ?>
                                        <li class="<?php echo $a['is_correct'] ? 'correct-answer' : ''; ?>">
                                            <?php echo htmlspecialchars($a['answer_text']); ?>
                                            <?php if ($a['is_correct']): ?> (Correcte)<?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php elseif ($q['question_type'] === 'ouverte'): ?>
                                <p>Type: Question ouverte (pas de réponses prédéfinies)</p>
                            <?php endif; ?>
                            <a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>&action=delete_question&question_id=<?php echo $q['id']; ?>"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette question et toutes ses réponses ?');"
                               class="button-delete">Supprimer</a>
                            </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p>Impossible de charger les informations du quiz. Veuillez <a href="../prof_dashboard.php">retourner au tableau de bord</a> et choisir un quiz existant ou en créer un.</p>
        <?php endif; ?>
    </div>

    <script>
        function toggleAnswerFields(questionType) {
            const answerFields = document.getElementById('answer-fields');
            const qcmAnswers = document.getElementById('qcm-answers');
            qcmAnswers.innerHTML = ''; // Nettoyer les champs existants

            if (questionType === 'qcm' || questionType === 'vrai_faux') {
                answerFields.style.display = 'block';
                // Ajouter des champs de réponse par défaut
                if (questionType === 'qcm') {
                    addAnswerField();
                    addAnswerField(); // Deux champs par défaut pour QCM
                } else { // Vrai/Faux
                    addAnswerField('Vrai', true);
                    addAnswerField('Faux', false);
                }
            } else {
                answerFields.style.display = 'none';
            }
        }

        let answerFieldCounter = 0;
        function addAnswerField(defaultValue = '', isCorrectDefault = false) {
            answerFieldCounter++;
            const qcmAnswers = document.getElementById('qcm-answers');
            const div = document.createElement('div');
            div.className = 'answer-input-group';
            div.innerHTML = `
                <input type="radio" name="correct_answer" id="correct_answer_${answerFieldCounter}" value="${answerFieldCounter}" ${isCorrectDefault ? 'checked' : ''}>
                <label for="correct_answer_${answerFieldCounter}">Correcte</label>
                <input type="text" name="answers[]" placeholder="Texte de la réponse ${answerFieldCounter}" value="${defaultValue}" required>
                <button type="button" onclick="this.parentNode.remove()">X</button>
            `;
            qcmAnswers.appendChild(div);
            
            // Si c'est Vrai/Faux, désactiver les champs de texte et les boutons d'ajout/suppression
            const questionType = document.getElementById('question_type').value;
            if (questionType === 'vrai_faux') {
                div.querySelector('input[type="text"]').readOnly = true;
                div.querySelector('button').style.display = 'none';
                document.querySelector('button[onclick="addAnswerField()"]').style.display = 'none';
            } else {
                document.querySelector('button[onclick="addAnswerField()"]').style.display = 'inline-block';
            }
        }

        // Initialiser les champs si le type de question est déjà sélectionné (par exemple, après une erreur de formulaire)
        document.addEventListener('DOMContentLoaded', () => {
            const selectedType = document.getElementById('question_type').value;
            if (selectedType) {
                // Pour éviter le rechargement du DOM après une soumission de formulaire en erreur
                // Pour les rechargements de page classiques, le comportement par défaut de 'addAnswerField' suffira
                if (selectedType === 'qcm' || selectedType === 'vrai_faux') {
                     // Si c'est un rechargement post-erreur, les champs answers[] ne seront pas vides
                     // Ici, on fait juste une initialisation simple, l'idéal serait de reconstruire les champs
                     // avec les valeurs pré-remplies par PHP en cas d'erreur.
                     // Pour l'instant, on se contente d'afficher le bloc si nécessaire.
                     document.getElementById('answer-fields').style.display = 'block';
                }
            }
        });
    </script>
</body>
</html>