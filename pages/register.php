<?php
// pages/register.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

startSession();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Erreur de sécurité: Requête non valide (CSRF).';
    } else {
        $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!in_array($role, ['teacher', 'student'])) { $role = 'student'; }

        if (empty($username) || empty($email) || empty($password)) {
            $error_message = 'Tous les champs sont requis.';
        } elseif (!$email) {
            $error_message = 'Format d\'email invalide.';
        } elseif (strlen($password) < 8) {
            $error_message = 'Le mot de passe doit contenir au moins 8 caractères.';
        } else {
            try {
                $hashedPassword = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword, $role]);
                
                $_SESSION['success_message'] = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
                redirect(getBasePath() . 'pages/login.php');

            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $error_message = 'Cet email ou nom d\'utilisateur est déjà utilisé.';
                } else {
                    error_log("Erreur d'inscription: " . $e->getMessage());
                    $error_message = 'Une erreur est survenue lors de l\'inscription.';
                }
            }
        }
    }
}

$csrf_token = generateCsrfToken();
include '../includes/header.php';
?>

<div class="form-container fade-in-on-load">
    <h2>Créer un Compte</h2>
    <?php if ($error_message): ?>
        <p class="message error"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    
    <form action="register.php" method="POST" class="animated-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <div class="form-group">
            <label for="username">Nom d'utilisateur:</label>
            <input type="text" id="username" name="username" required>
        </div>
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
            <button type="button" id="generatePasswordBtn" class="btn-secondary animated-button-small" style="margin-top:10px;">Générer</button>
            <p id="passwordStrength" class="password-strength"></p>
        </div>
        <div class="form-group">
            <label for="role">Je suis un:</label>
            <select id="role" name="role" required>
                <option value="student">Élève</option>
                <option value="teacher">Professeur</option>
            </select>
        </div>
        <button type="submit" class="btn-primary animated-button">S'inscrire</button>
    </form>
    <p class="form-link">Déjà un compte ? <a href="login.php">Connectez-vous ici</a>.</p>
</div>

<?php include '../includes/footer.php'; ?>