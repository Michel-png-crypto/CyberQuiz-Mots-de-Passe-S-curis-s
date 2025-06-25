<?php
// includes/functions.php

/**
 * Démarre la session si elle n'est pas déjà démarrée.
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Redirige vers une autre page.
 * @param string $path Le chemin vers lequel rediriger.
 */
function redirect($path) {
    header("Location: " . $path);
    exit();
}

/**
 * Génère un jeton CSRF pour la protection des formulaires.
 * @return string Le jeton CSRF.
 */
function generateCsrfToken() {
    startSession(); // Assurez-vous que la session est démarrée
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un jeton CSRF.
 * @param string $token Le jeton CSRF soumis par le formulaire.
 * @return bool Vrai si le jeton est valide, Faux sinon.
 */
function verifyCsrfToken($token) {
    startSession();
    // Utiliser hash_equals pour prévenir les attaques par timing
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Hache un mot de passe pour le stockage sécurisé.
 * @param string $password Le mot de passe en texte clair.
 * @return string Le mot de passe haché.
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Vérifie un mot de passe par rapport à son hachage.
 * @param string $password Le mot de passe en texte clair.
 * @param string $hashedPassword Le mot de passe haché stocké.
 * @return bool Vrai si les mots de passe correspondent, Faux sinon.
 */
function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

/**
 * Vérifie si l'utilisateur est connecté.
 * @return bool Vrai si connecté, Faux sinon.
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur a le rôle spécifié.
 * @param string $role Le rôle à vérifier ('teacher' ou 'student').
 * @return bool Vrai si l'utilisateur a le rôle, Faux sinon.
 */
function hasRole($role) {
    startSession();
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Récupère le chemin de base du projet pour les liens absolus.
 */
function getBasePath() {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $relativePath = str_replace($documentRoot, '', $scriptName);
    $projectDirName = basename(dirname(__DIR__));
    $basePath = '/';
    if (!empty($projectDirName) && strpos($relativePath, '/' . $projectDirName . '/') !== false) {
        $basePath = '/' . $projectDirName . '/';
    }
    return $basePath;
}

/**
 * Génère un code PIN unique de 6 chiffres qui n'est pas déjà utilisé dans la base de données.
 * @param PDO $pdo L'objet de connexion à la base de données.
 * @return int Le code PIN unique.
 */
function generateUniquePin($pdo) {
    do {
        // Génère un nombre aléatoire entre 100000 et 999999
        $pin = random_int(100000, 999999);
        
        // Vérifie si ce PIN existe déjà dans la base de données
        $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE pin_code = ?");
        $stmt->execute([$pin]);
        $exists = $stmt->fetchColumn();

    } while ($exists); // Continue de générer tant que le PIN existe déjà

    return $pin;
}