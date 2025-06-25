<?php
// pages/select_quiz.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
startSession();

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    redirect(getBasePath() . 'pages/login.php');
}

$available_quizzes = [];
$error_message = '';

try {
    // On sélectionne tous les quiz qui sont à la fois visibles (publics) ET officiels (créés par des profs)
    $stmt = $pdo->prepare("SELECT q.id, q.title, q.description, q.duration, u.username as teacher_name
                           FROM quizzes q
                           JOIN users u ON q.teacher_id = u.id
                           WHERE q.is_visible = TRUE AND q.is_official = TRUE
                           ORDER BY q.title ASC");
    $stmt->execute();
    $available_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur chargement quizzes publics: " . $e->getMessage());
    $error_message = "Une erreur est survenue lors du chargement des quizzes disponibles.";
}

include '../includes/header.php';
?>

<div class="container fade-in-on-load">
    <h2>Bibliothèque de Quiz Publics</h2>
    <p>Choisissez un quiz ci-dessous pour tester vos connaissances.</p>

    <?php if ($error_message): ?>
        <p class="message error"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <?php if (empty($available_quizzes)): ?>
        <p class="message info">Aucun quiz public n'est disponible pour le moment. Revenez plus tard !</p>
    <?php else: ?>
        <div class="quiz-list-container">
            <?php foreach ($available_quizzes as $quiz): ?>
                <div class="quiz-card">
                    <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                    <p class="quiz-author"><em>par <?php echo htmlspecialchars($quiz['teacher_name']); ?></em></p>
                    <p class="quiz-description"><?php echo htmlspecialchars($quiz['description']); ?></p>
                    <div class="quiz-meta">
                        <span><i class="icon-time"></i> <?php echo htmlspecialchars($quiz['duration']); ?> min</span>
                    </div>
                    <a href="<?php echo getBasePath(); ?>pages/take_quiz.php?quiz_id=<?php echo htmlspecialchars($quiz['id']); ?>" class="btn-primary">Commencer le Quiz</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.quiz-list-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 30px;
}
.quiz-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 25px;
    display: flex;
    flex-direction: column;
    box-shadow: var(--box-shadow-light);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.quiz-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--box-shadow-medium);
}
.quiz-card h3 {
    margin-top: 0;
    color: var(--primary-color);
}
.quiz-card .quiz-description {
    flex-grow: 1; /* Pousse le bouton vers le bas */
    margin: 15px 0;
}
.quiz-card .btn-primary {
    display: block;
    text-align: center;
    margin-top: auto; /* Aligne le bouton en bas */
}
</style>

<?php include '../includes/footer.php'; ?>