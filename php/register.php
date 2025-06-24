<?php
// Assurez-vous que session_start() est appelé AVANT toute sortie HTML
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données (ce fichier doit initialiser $pdo)
require_once 'db_connection.php';
// Inclure les fonctions utilitaires que nous avons définies dans utils.php
require_once 'utils.php';

// Vérifier si la requête HTTP est de type POST (formulaire soumis)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = []; // Tableau pour stocker les messages d'erreur de validation

    // --- 1. Validation CSRF ---
    // Vérifier si le jeton CSRF est présent et valide pour prévenir les attaques CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        addMessage('error', 'Erreur de sécurité : Jeton invalide. Veuillez réessayer.');
        header('Location: ../register.php'); // Redirige vers la page d'inscription
        exit(); // Arrête l'exécution du script
    }

    // --- 2. Récupération et nettoyage des entrées utilisateur ---
    // Utiliser sanitizeInput pour nettoyer les chaînes avant de les traiter ou de les afficher (si affichées directement)
    $username = sanitizeInput($_POST['username'] ?? '');
    // Pour l'email, utiliser filter_var avec FILTER_SANITIZE_EMAIL pour un nettoyage spécifique
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    // Les mots de passe clairs ne doivent pas être passés par htmlspecialchars pour éviter des altérations
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // --- 3. Validation des entrées utilisateur (côté serveur) ---

    // Vérifier que tous les champs obligatoires sont remplis
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $errors[] = "Tous les champs sont obligatoires.";
    }

    // Valider le format de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    // Vérifier que les mots de passe correspondent
    if ($password !== $confirmPassword) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Appeler la fonction d'analyse de robustesse du mot de passe
    $strengthErrors = analyzePasswordStrength($password);
    if (!empty($strengthErrors)) {
        $errors = array_merge($errors, $strengthErrors); // Fusionner les erreurs de robustesse
    }

    // Vérifier si le mot de passe a été compromis via l'API Have I Been Pwned
    $pwnedCount = checkPwnedPassword($password);
    if ($pwnedCount > 0) {
        $errors[] = "ATTENTION : Ce mot de passe a été trouvé " . $pwnedCount . " fois dans des fuites de données ! Veuillez en choisir un autre.";
    } elseif ($pwnedCount === -1) {
        // En cas d'erreur de connexion à l'API HIBP, ne pas bloquer l'inscription
        // mais informer l'utilisateur qu'une vérification n'a pas pu être faite.
        addMessage('warning', "Impossible de vérifier si le mot de passe a été compromis (erreur API HIBP).");
    }

    // --- 4. Si des erreurs de validation sont présentes, les stocker et rediriger ---
    if (!empty($errors)) {
        foreach ($errors as $error) {
            addMessage('error', $error); // Ajouter chaque erreur au système de messages
        }
        header('Location: ../register.php'); // Redirige l'utilisateur vers le formulaire avec les erreurs
        exit();
    }

    // --- 5. Si toutes les validations passent, hacher le mot de passe et insérer dans la base de données ---
    // Hachage du mot de passe (essentiel pour la sécurité, n'enregistrez jamais les mots de passe en clair !)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Vérifier si le nom d'utilisateur ou l'email existe déjà dans la base de données
        // C'est une bonne pratique pour éviter les doublons avant l'insertion.
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $stmt_check->bindParam(':username', $username);
        $stmt_check->bindParam(':email', $email);
        $stmt_check->execute();
        if ($stmt_check->fetchColumn() > 0) {
            addMessage('error', 'Le nom d\'utilisateur ou l\'adresse email est déjà utilisé.');
            header('Location: ../register.php');
            exit();
        }

        // Insertion du nouvel utilisateur dans la table 'users'
        // Utilisation de requêtes préparées avec PDO pour prévenir les injections SQL.
        $stmt_insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'student')");
        $stmt_insert->bindParam(':username', $username);
        $stmt_insert->bindParam(':email', $email);
        $stmt_insert->bindParam(':password', $hashedPassword);
        // Assurez-vous que la colonne 'role' existe dans votre table 'users' et 'student' est une valeur valide.
        $stmt_insert->execute();

        // Ajouter un message de succès et rediriger l'utilisateur vers la page de connexion
        addMessage('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
        header('Location: ../login.php');
        exit();

    } catch (PDOException $e) {
        // Gérer les erreurs spécifiques de la base de données (ex: problèmes de connexion, contraintes)
        // Loguer l'erreur détaillée pour le débogage (ne pas l'afficher à l'utilisateur final)
        error_log("Erreur PDO lors de l'enregistrement de l'utilisateur : " . $e->getMessage());
        addMessage('error', 'Une erreur inattendue est survenue lors de l\'inscription. Veuillez réessayer plus tard.');
        header('Location: ../register.php');
        exit();
    }
} else {
    // Si le script est accédé directement (pas via une soumission de formulaire POST)
    addMessage('error', 'Accès direct non autorisé à ce script.');
    header('Location: ../register.php'); // Redirige vers la page d'inscription
    exit();
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    </head>
<body>
    <div class="container">
        <h1>Inscription</h1>

        <?php
        // Assurez-vous que session_start() est appelé avant l'inclusion de utils.php si ce n'est pas déjà fait
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // Inclure le fichier utils.php pour utiliser getAndClearMessages() et generateCsrfToken()
        require_once 'php/utils.php'; // Chemin relatif à cette page

        // Récupérer et afficher les messages de la session (succès, erreur, avertissement)
        $messages = getAndClearMessages();
        if (!empty($messages)) {
            foreach ($messages as $msg) {
                // IMPORTANT : Utiliser htmlspecialchars pour échapper le contenu du message et prévenir les attaques XSS
                // Les classes 'alert' et 'alert-type' devront être stylisées par votre CSS externe
                echo '<div class="alert alert-' . htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($msg['text'], ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }
        ?>

        <form action="php/register.php" method="POST">
            <?php
            // Générer et afficher le jeton CSRF pour ce formulaire
            // C'est un champ caché essentiel pour la protection CSRF
            $csrf_token = generateCsrfToken(); // Appelle la fonction de utils.php
            ?>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="username">Nom d'utilisateur :</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email :</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">S'inscrire</button>
        </form>
    </div>
</body>
</html>