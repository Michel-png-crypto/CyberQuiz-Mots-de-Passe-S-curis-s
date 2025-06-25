<?php
// pages/create_quiz.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

startSession();

if (!isLoggedIn()) { redirect(getBasePath() . 'pages/login.php'); }

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Erreur de sécurité.';
    } else {
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
        $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT, ['options' => ['default' => 10, 'min_range' => 1]]);
        
        $is_official = hasRole('teacher') ? 1 : 0;
        $is_visible = hasRole('teacher') ? (isset($_POST['is_visible']) ? 1 : 0) : 0;

        if (empty($title)) {
            $error_message = 'Le titre et la durée sont obligatoires.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    "INSERT INTO quizzes (title, description, teacher_id, duration, is_visible, is_official) 
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$title, $description, $_SESSION['user_id'], $duration, $is_visible, $is_official]);
                $quiz_id = $pdo->lastInsertId();

                $new_pin = generateUniquePin($pdo);
                $pin_stmt = $pdo->prepare("UPDATE quizzes SET pin_code = ? WHERE id = ?");
                $pin_stmt->execute([$new_pin, $quiz_id]);
                $pdo->commit();

                $_SESSION['new_quiz_pin'] = $new_pin;
                redirect(getBasePath() . 'pages/add_questions.php?quiz_id=' . $quiz_id);
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Erreur création quiz: " . $e->getMessage());
                $error_message = 'Erreur lors de la création du quiz.';
            }
        }
    }
}
$csrf_token = generateCsrfToken();
include '../includes/header.php';
?>

<div class="form-container fade-in-on-load">
    <h2>Créer un Quiz</h2>
    <?php if ($error_message) echo "<p class='message error'>".htmlspecialchars($error_message)."</p>"; ?>

    <form action="create_quiz.php" method="POST" class="animated-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <div class="form-group">
            <label for="title">Titre du Quiz:</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div class="form-group">
            <label for="description">Description (Optionnel):</label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label for="duration">Durée (minutes):</label>
            <input type="number" id="duration" name="duration" min="1" value="10" required>
        </div>
        <?php if (hasRole('teacher')): // La visibilité reste une option pour les profs uniquement ?>
        <div class="form-group">
            <label>Visibilité :</label>
            <div style="display:flex; gap: 20px;">
                <label><input type="radio" name="is_visible" value="1" checked> Public</label>
                <label><input type="radio" name="is_visible" value="0"> Privé</label>
            </div>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn-primary animated-button">Créer et Ajouter des Questions</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>