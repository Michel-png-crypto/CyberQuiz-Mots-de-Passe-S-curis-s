<?php
// php/login.php
session_start(); // Démarrer la session en premier
require_once 'db_connection.php'; // Inclure le script de connexion à la DB

$error_message = '';

// Si l'utilisateur est déjà connecté, le rediriger
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'professeur') {
        header('Location: ../admin/prof_dashboard.php');
    } else {
        header('Location: ../eleve/join_quiz.php');
    }
    exit();
}

// Message de succès après une inscription réussie
if (isset($_GET['registered']) && $_GET['registered'] == 'true') {
    $success_message = "Votre compte a été créé avec succès ! Veuillez vous connecter.";
} else {
    $success_message = '';
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['login_input'] ?? ''); // Peut être username ou email
    $password_saisi = $_POST['password'] ?? '';

    // --- 1. Validation des entrées de base ---
    if (empty($login_input) || empty($password_saisi)) {
        $error_message = "Veuillez entrer votre nom d'utilisateur/email et votre mot de passe.";
    } else {
        try {
            // --- 2. Récupérer l'utilisateur (avec requête préparée) ---
            // On cherche par username OU email
            $sql = "SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$login_input, $login_input]); // On passe l'input deux fois
            $user = $stmt->fetch();

            // --- 3. Vérification du mot de passe (Crucial pour la sécurité) ---
            if ($user && password_verify($password_saisi, $user['password'])) {
                // Mot de passe correct, authentification réussie

                // --- 4. Gestion des sessions (Sécurisée) ---
                session_regenerate_id(true); // TRÈS IMPORTANT: Prévention de la fixation de session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // --- 5. Redirection basée sur le rôle ---
                if ($_SESSION['role'] === 'professeur') {
                    header('Location: ../admin/prof_dashboard.php');
                } else { // Par défaut, élève
                    header('Location: ../eleve/join_quiz.php');
                }
                exit();
            } else {
                // Échec de l'authentification (identifiants invalides)
                // Évitez de dire si c'est l'username ou le mot de passe qui est faux pour ne pas aider les attaquants.
                $error_message = "Nom d'utilisateur/email ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            error_log("Erreur de connexion: " . $e->getMessage());
            $error_message = "Une erreur est survenue lors de la tentative de connexion. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - CyberQuiz</title>
    <link rel="stylesheet" href="../css/main.css"> </head>
<body>
    <div class="container">
        <h1>Connexion</h1>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="login_input">Nom d'utilisateur ou Email :</label>
                <input type="text" id="login_input" name="login_input" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Se connecter</button>
        </form>
        <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a>.</p>
    </div>
</body>
</html>