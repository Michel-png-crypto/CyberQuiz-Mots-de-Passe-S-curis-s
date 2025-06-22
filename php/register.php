<?php
// php/register.php
require_once 'db_connection.php'; // Inclure le script de connexion à la DB

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- 1. Validation des entrées côté serveur (Très Important) ---
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "L'adresse email n'est pas valide.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) { // Exemple: mot de passe d'au moins 8 caractères
        $error_message = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        try {
            // --- 2. Vérifier l'unicité du nom d'utilisateur et de l'email ---
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error_message = "Ce nom d'utilisateur ou cet email est déjà utilisé.";
            } else {
                // --- 3. Hachage du mot de passe (Crucial pour la sécurité) ---
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // PASSWORD_DEFAULT utilise un algorithme de hachage fort (actuellement BCRYPT)
                // et gère automatiquement le salage (salt) pour vous.

                $role = 'eleve'; // Rôle par défaut pour les nouvelles inscriptions

                // --- 4. Insertion dans la base de données (avec requête préparée) ---
                $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $email, $hashed_password, $role]);

                $success_message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                // Redirection après succès pour éviter le re-soumission du formulaire
                header("Location: login.php?registered=true");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Erreur d'inscription: " . $e->getMessage());
            $error_message = "Une erreur est survenue lors de l'inscription. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - CyberQuiz</title>
    <link rel="stylesheet" href="../css/main.css"> </head>
<body>
    <div class="container">
        <h1>Inscription</h1>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe :</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            <button type="submit">S'inscrire</button>
        </form>
        <p>Déjà un compte ? <a href="login.php">Connectez-vous ici</a>.</p>
    </div>
</body>
</html>