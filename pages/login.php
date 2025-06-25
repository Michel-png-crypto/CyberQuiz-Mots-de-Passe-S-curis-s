<?php
// pages/login.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

startSession();

$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Erreur de sécurité.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password']; 

        if (empty($email) || empty($password)) {
            $error_message = 'Veuillez entrer votre email et votre mot de passe.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && verifyPassword($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];

                    redirect(getBasePath() . 'index.php');
                } else {
                    $error_message = 'Email ou mot de passe incorrect.';
                }
            } catch (PDOException $e) {
                error_log("Erreur de connexion: " . $e->getMessage());
                $error_message = 'Une erreur est survenue lors de la connexion.';
            }
        }
    }
}

$csrf_token = generateCsrfToken();
include '../includes/header.php';
?>

<div class="form-container fade-in-on-load">
    <h2>Connexion</h2>
    
    <?php if ($error_message) echo "<p class='message error'>".htmlspecialchars($error_message)."</p>"; ?>
    <?php if ($success_message) echo "<p class='message success'>".htmlspecialchars($success_message)."</p>"; ?>

    <form action="login.php" method="POST" class="animated-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Mot de passe:</label>
            <div class="password-input-container">
                <input type="password" id="password" name="password" required>
                <span class="toggle-password">👁️</span>
            </div>
        </div>
        <button type="submit" class="btn-primary animated-button">Se Connecter</button>
    </form>
    <p class="form-link">Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a>.</p>
</div>

<?php include '../includes/footer.php'; ?>