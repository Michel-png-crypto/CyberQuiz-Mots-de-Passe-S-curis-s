<?php
// pages/results.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
startSession();
if (!isLoggedIn()) { redirect(getBasePath() . 'pages/login.php'); }

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
// Récupérer l'ID du résultat spécifique depuis l'URL (s'il existe)
$result_id = filter_input(INPUT_GET, 'result_id', FILTER_VALIDATE_INT);

include '../includes/header.php';
?>
<div class="container fade-in-on-load">
    <h2>Mes Résultats</h2>

    <?php if ($result_id && $user_role === 'student'): // CAS 1: Afficher un résultat spécifique juste après un quiz ?>
        
        <?php
        try {
            $stmt = $pdo->prepare("
                SELECT r.score, r.total_questions, r.submission_date, q.title as quiz_title
                FROM results r
                JOIN quizzes q ON r.quiz_id = q.id
                WHERE r.id = ? AND r.user_id = ?
            ");
            $stmt->execute([$result_id, $user_id]);
            $result = $stmt->fetch();

            if ($result):
                $percentage = $result['total_questions'] > 0 ? round(($result['score'] / $result['total_questions']) * 100) : 0;
            ?>
                <div class="result-summary animated-form">
                    <h3>Résultat pour le quiz : "<?php echo htmlspecialchars($result['quiz_title']); ?>"</h3>
                    <p>Passé le <?php echo date('d/m/Y à H:i', strtotime($result['submission_date'])); ?></p>
                    <p class="final-score" style="font-size: 2em; font-weight: bold; color: var(--primary-color);">
                        Votre score : <?php echo $result['score']; ?> / <?php echo $result['total_questions']; ?> (<?php echo $percentage; ?>%)
                    </p>
                    <a href="<?php echo getBasePath(); ?>pages/results.php" class="btn-secondary">Voir tous mes résultats</a>
                    <a href="<?php echo getBasePath(); ?>pages/dashboard.php" class="btn-primary">Retour au tableau de bord</a>
                </div>
            <?php else: ?>
                <p class="message error">Impossible de trouver ce résultat ou vous n'avez pas la permission de le voir.</p>
            <?php endif;
        } catch (PDOException $e) {
            echo '<p class="message error">Erreur lors du chargement des résultats.</p>';
            error_log("Erreur BDD results.php (specific): " . $e->getMessage());
        }
        ?>
    
    <?php elseif ($user_role === 'student'): // CAS 2: Afficher l'historique complet des résultats ?>
        
        <h3>Historique de vos performances</h3>
        <?php
        try {
            // Requête pour récupérer tous les résultats de l'utilisateur connecté
            $stmt = $pdo->prepare("
                SELECT r.id, r.score, r.total_questions, r.submission_date, q.title as quiz_title
                FROM results r
                JOIN quizzes q ON r.quiz_id = q.id
                WHERE r.user_id = ?
                ORDER BY r.submission_date DESC
            ");
            $stmt->execute([$user_id]);
            $all_results = $stmt->fetchAll();

            if (empty($all_results)): ?>
                <p class="message info">Vous n'avez pas encore passé de quiz. <a href="<?php echo getBasePath(); ?>pages/select_quiz.php">Commencez-en un maintenant !</a></p>
            <?php else: ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Titre du Quiz</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Pourcentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_results as $res): 
                            $percentage = $res['total_questions'] > 0 ? round(($res['score'] / $res['total_questions']) * 100) : 0;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($res['quiz_title']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($res['submission_date'])); ?></td>
                                <td><?php echo $res['score']; ?> / <?php echo $res['total_questions']; ?></td>
                                <td>
                                    <span class="percentage-bar" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $percentage >= 50 ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">
                                        <?php echo $percentage; ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif;
        } catch (PDOException $e) {
            echo '<p class="message error">Erreur lors du chargement de votre historique.</p>';
            error_log("Erreur BDD results.php (history): " . $e->getMessage());
        }
        ?>

    <?php elseif ($user_role === 'teacher'): // Vue pour le professeur (inchangée) ?>
        <h3>Résultats des Élèves pour Vos Quiz</h3>
        <p>Consultez les performances de vos élèves sur les quiz que vous avez créés.</p>
        <p>Fonctionnalité à développer : Sélectionnez un quiz pour voir les résultats des élèves.</p>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>