<?php
// pages/dashboard.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
startSession();

if (!isLoggedIn()) { redirect(getBasePath() . 'pages/login.php'); }

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

include '../includes/header.php';
?>

<div class="container fade-in-on-load">
    <h2>Bienvenue, <?php echo htmlspecialchars($username); ?> !</h2>
    <p>Vous êtes connecté en tant que **<?php echo htmlspecialchars(ucfirst($user_role === 'teacher' ? 'Professeur' : 'Élève')); ?>**.</p>
    
    <?php if (hasRole('teacher')): // VUE POUR LE PROFESSEUR ?>
        <div class="dashboard-section">
            <h3>Vos Quiz</h3>
            <a href="<?php echo getBasePath(); ?>pages/create_quiz.php" class="btn-primary animated-button">Créer un nouveau Quiz</a>
            <?php
            try {
                $stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE teacher_id = ? ORDER BY created_at DESC");
                $stmt->execute([$user_id]);
                $teacher_quizzes = $stmt->fetchAll();

                if ($teacher_quizzes) {
                    echo '<ul class="quiz-list" style="margin-top:20px;">';
                    foreach ($teacher_quizzes as $quiz) {
                        echo '<li class="quiz-item"><span>' . htmlspecialchars($quiz['title']) . '</span><div class="quiz-actions">';
                        echo '<a href="' . getBasePath() . 'pages/edit_quiz.php?id=' . htmlspecialchars($quiz['id']) . '" class="btn-secondary animated-button-small">Gérer</a> ';
                        echo '<a href="' . getBasePath() . 'pages/delete_quiz.php?id=' . htmlspecialchars($quiz['id']) . '" class="btn-danger animated-button-small" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ce quiz ?\');">Supprimer</a>';
                        echo '</div></li>';
                    }
                    echo '</ul>';
                } else { echo '<p style="margin-top:20px;">Vous n\'avez pas encore créé de quiz.</p>'; }
            } catch (PDOException $e) { echo '<p class="message error">Erreur de chargement des quiz.</p>'; }
            ?>
        </div>

        <div class="dashboard-section">
            <h3>Quiz Publics créés par les Élèves (Modération)</h3>
            <?php
            try {
                $stmt = $pdo->query("SELECT q.id, q.title, u.username as student_name FROM quizzes q JOIN users u ON q.teacher_id = u.id WHERE q.is_official = 0 AND q.is_visible = 1 ORDER BY q.created_at DESC");
                $student_public_quizzes = $stmt->fetchAll();
                if ($student_public_quizzes) {
                    echo '<ul class="quiz-list">';
                    foreach ($student_public_quizzes as $quiz) {
                        echo '<li class="quiz-item"><span>' . htmlspecialchars($quiz['title']) . ' <em>(par ' . htmlspecialchars($quiz['student_name']) . ')</em></span>';
                        echo '<div class="quiz-actions">';
                        echo '<a href="' . getBasePath() . 'pages/take_quiz.php?quiz_id=' . htmlspecialchars($quiz['id']) . '" class="btn-info animated-button-small">Voir le Quiz</a> ';
                        echo '<a href="' . getBasePath() . 'pages/delete_quiz.php?id=' . htmlspecialchars($quiz['id']) . '" class="btn-danger animated-button-small" onclick="return confirm(\'En tant que professeur, voulez-vous vraiment supprimer ce quiz d\\\'élève ?\');">Supprimer</a>';
                        echo '</div></li>';
                    }
                    echo '</ul>';
                } else { echo '<p>Aucun quiz public créé par des élèves pour le moment.</p>'; }
            } catch (PDOException $e) { echo '<p class="message error">Erreur de chargement des quiz élèves.</p>'; }
            ?>
        </div>

    <?php else: // VUE POUR L'ÉLÈVE ?>
        
        <div class="dashboard-section">
            <h3>Rejoindre un Quiz Privé</h3>
            <form action="<?php echo getBasePath(); ?>pages/join_with_pin.php" method="POST" class="join-pin-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group"><input type="text" name="pin_code" placeholder="Entrez le code PIN..." required pattern="\d{6}"><button type="submit" class="btn-primary">Rejoindre</button></div>
            </form>
        </div>

        <div class="dashboard-section">
            <h3>Mes Quiz d'Entraînement
                <a href="<?php echo getBasePath(); ?>pages/create_quiz.php" class="btn-primary animated-button-small" style="float: right;">+ Créer un quiz</a>
            </h3>
            <?php
            try {
                $stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE teacher_id = ? AND is_official = 0 ORDER BY created_at DESC");
                $stmt->execute([$user_id]);
                $student_quizzes = $stmt->fetchAll();
                if ($student_quizzes) {
                    echo '<ul class="quiz-list">';
                    foreach ($student_quizzes as $quiz) {
                        echo '<li class="quiz-item"><span>' . htmlspecialchars($quiz['title']) . '</span><div class="quiz-actions">';
                        echo '<a href="' . getBasePath() . 'pages/take_quiz.php?quiz_id=' . htmlspecialchars($quiz['id']) . '" class="btn-success animated-button-small">S\'entraîner</a> ';
                        echo '<a href="' . getBasePath() . 'pages/edit_quiz.php?id=' . htmlspecialchars($quiz['id']) . '" class="btn-secondary animated-button-small">Gérer</a> ';
                        // --- BOUTON SUPPRIMER AJOUTÉ ICI ---
                        echo '<a href="' . getBasePath() . 'pages/delete_quiz.php?id=' . htmlspecialchars($quiz['id']) . '" class="btn-danger animated-button-small" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ce quiz ?\');">Supprimer</a>';
                        echo '</div></li>';
                    }
                    echo '</ul>';
                } else { echo '<p>Vous n\'avez pas encore créé de quiz personnel.</p>'; }
            } catch (PDOException $e) { echo '<p class="message error">Erreur de chargement de vos quiz.</p>'; }
            ?>
        </div>
    <?php endif; ?>
</div>

<style>.join-pin-form .form-group { display: flex; gap: 10px; } .join-pin-form input { flex-grow: 1; }</style>

<?php include '../includes/footer.php'; ?>