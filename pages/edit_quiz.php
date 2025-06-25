<?php
// pages/edit_quiz.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
startSession();

if (!isLoggedIn()) { redirect(getBasePath() . 'pages/login.php'); }

$quiz_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error_message = ''; $success_message = ''; $quiz_data = null; $questions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $quiz_id) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Erreur de sécurité.';
    } else {
        if (isset($_POST['update_details'])) {
            $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS));
            $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
            $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT, ['options' => ['default' => 10, 'min_range' => 1]]);
            $is_visible = filter_input(INPUT_POST, 'is_visible', FILTER_VALIDATE_INT);

            try {
                $stmt = $pdo->prepare("UPDATE quizzes SET title = ?, description = ?, duration = ?, is_visible = ? WHERE id = ? AND teacher_id = ?");
                $stmt->execute([$title, $description, $duration, $is_visible, $quiz_id, $_SESSION['user_id']]);
                $success_message = 'Détails du quiz mis à jour !';
            } catch (PDOException $e) { $error_message = 'Erreur de mise à jour.'; }
        }
        if (isset($_POST['generate_pin'])) {
            try {
                $new_pin = generateUniquePin($pdo);
                $stmt = $pdo->prepare("UPDATE quizzes SET pin_code = ? WHERE id = ? AND teacher_id = ?");
                $stmt->execute([$new_pin, $quiz_id, $_SESSION['user_id']]);
                $success_message = 'Nouveau code PIN généré avec succès !';
            } catch (PDOException $e) { $error_message = 'Erreur lors de la génération du PIN.'; }
        }
    }
}

if ($quiz_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$quiz_id, $_SESSION['user_id']]);
        $quiz_data = $stmt->fetch();
        if ($quiz_data) {
            $questions_stmt = $pdo->prepare("SELECT id, question_text FROM questions WHERE quiz_id = ? ORDER BY id ASC");
            $questions_stmt->execute([$quiz_id]);
            $questions = $questions_stmt->fetchAll();
        } else {
            $error_message = "Quiz non trouvé ou vous n'avez pas la permission de le modifier.";
            $quiz_id = null;
        }
    } catch (PDOException $e) { /* ... */ }
} else { $error_message = 'Aucun quiz sélectionné.'; }

$csrf_token = generateCsrfToken();
include '../includes/header.php';
?>

<div class="container fade-in-on-load">
    <p class="back-link"><a href="<?php echo getBasePath(); ?>pages/dashboard.php">← Retour au Tableau de Bord</a></p>
    
    <?php if ($error_message) echo "<p class='message error'>".htmlspecialchars($error_message)."</p>"; ?>
    <?php if ($success_message) echo "<p class='message success'>".htmlspecialchars($success_message)."</p>"; ?>

    <?php if ($quiz_data): ?>
    
    <div class="form-container" style="margin-top: 0;">
        <h2>Gérer le Quiz : <?php echo htmlspecialchars($quiz_data['title']); ?></h2>
        
        <form action="edit_quiz.php?id=<?php echo $quiz_id; ?>" method="POST" class="animated-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group"><label for="title">Titre:</label><input type="text" name="title" value="<?php echo htmlspecialchars($quiz_data['title']); ?>" required></div>
            <div class="form-group"><label for="description">Description:</label><textarea name="description" rows="4"><?php echo htmlspecialchars($quiz_data['description']); ?></textarea></div>
            <div class="form-group"><label for="duration">Durée (min):</label><input type="number" id="duration" name="duration" min="1" value="<?php echo htmlspecialchars($quiz_data['duration']); ?>" required></div>
            <div class="form-group"><label>Visibilité:</label><div class="radio-group"><input type="radio" id="is_visible_public" name="is_visible" value="1" <?php echo $quiz_data['is_visible'] ? 'checked' : ''; ?>><label for="is_visible_public">Public</label></div><div class="radio-group"><input type="radio" id="is_visible_private" name="is_visible" value="0" <?php echo !$quiz_data['is_visible'] ? 'checked' : ''; ?>><label for="is_visible_private">Privé</label></div></div>
            <button type="submit" name="update_details" class="btn-primary">Mettre à Jour</button>
        </form>

        <hr style="margin: 30px 0;">

        <h4>Accès par Code PIN</h4>
        <?php if ($quiz_data['pin_code']): ?>
            <p>Code PIN actuel à partager : 
                <strong style="font-size: 1.5em; color: var(--success-color); user-select: all; background: #f0f0f0; padding: 5px 10px; border-radius: 5px;">
                    <?php echo htmlspecialchars($quiz_data['pin_code']); ?>
                </strong>
            </p>
        <?php else: ?>
            <p>Aucun code PIN n'est actuellement défini pour ce quiz.</p>
        <?php endif; ?>
        
        <form action="edit_quiz.php?id=<?php echo $quiz_id; ?>" method="POST" style="display:inline;">
             <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <button type="submit" name="generate_pin" class="btn-secondary animated-button-small">
                <?php echo $quiz_data['pin_code'] ? 'Générer un nouveau PIN' : 'Générer un PIN'; ?>
            </button>
        </form>
    </div>

    <hr style="margin: 40px 0;">

    <div class="dashboard-section">
        <h3>Gérer les Questions</h3>
        <a href="<?php echo getBasePath(); ?>pages/add_questions.php?quiz_id=<?php echo htmlspecialchars($quiz_id); ?>" class="btn-success animated-button">+ Ajouter une Question</a>
        </div>

    <?php endif; ?>
</div>

<style>.radio-group{display:flex; align-items:center; margin-bottom:10px;} .radio-group input{width:auto; margin-right:10px;}</style>
<?php include '../includes/footer.php'; ?>