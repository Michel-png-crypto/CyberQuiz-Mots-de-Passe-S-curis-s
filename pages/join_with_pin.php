<?php
// pages/join_with_pin.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
startSession();

if (!isLoggedIn()) { redirect(getBasePath() . 'pages/login.php'); }

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Erreur de sécurité.';
    } else {
        $pin_code = filter_input(INPUT_POST, 'pin_code', FILTER_SANITIZE_NUMBER_INT);

        if (empty($pin_code)) {
            $error_message = 'Veuillez entrer un code PIN.';
        } else {
            // Chercher le quiz correspondant au PIN
            $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE pin_code = ?");
            $stmt->execute([$pin_code]);
            $quiz = $stmt->fetch();

            if ($quiz) {
                // Si trouvé, rediriger l'utilisateur pour passer le quiz
                redirect(getBasePath() . 'pages/take_quiz.php?quiz_id=' . $quiz['id']);
            } else {
                $error_message = 'Code PIN invalide ou quiz introuvable.';
            }
        }
    }
}
$csrf_token = generateCsrfToken();
include '../includes/header.php';
?>

<div class="form-container fade-in-on-load">
    <h2>Rejoindre un Quiz Privé</h2>
    <p>Entrez le code PIN fourni par le créateur du quiz.</p>

    <?php if ($error_message) echo "<p class='message error'>".htmlspecialchars($error_message)."</p>"; ?>

    <form action="join_with_pin.php" method="POST" class="animated-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="form-group">
            <label for="pin_code">Code PIN :</label>
            <input type="text" id="pin_code" name="pin_code" required autofocus pattern="\d{6}" title="Un code PIN est composé de 6 chiffres.">
        </div>
        <button type="submit" class="btn-primary animated-button">Rejoindre le Quiz</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>