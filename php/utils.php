<?php
// Assurez-vous que session_start() est appelé au tout début de votre script PHP
// C'est crucial avant toute utilisation de $_SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Génère un mot de passe sécurisé et aléatoire.
 *
 * @param int $length La longueur souhaitée du mot de passe.
 * @param bool $useUppercase Inclure des lettres majuscules.
 * @param bool $useNumbers Inclure des chiffres.
 * @param bool $useSymbols Inclure des symboles.
 * @return string Le mot de passe généré.
 * @throws Exception Si aucun ensemble de caractères n'est sélectionné ou longueur trop courte.
 */
function generateSecurePassword(int $length = 12, bool $useUppercase = true, bool $useNumbers = true, bool $useSymbols = true): string
{
    $lowercaseChars = 'abcdefghijklmnopqrstuvwxyz';
    $uppercaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numberChars = '0123456789';
    $symbolChars = '!@#$%^&*()-_+=[]{}\|;:,.<>?`~';

    $allChars = $lowercaseChars; // Toujours inclure des minuscules

    if ($useUppercase) {
        $allChars .= $uppercaseChars;
    }
    if ($useNumbers) {
        $allChars .= $numberChars;
    }
    if ($useSymbols) {
        $allChars .= $symbolChars;
    }

    $password = '';
    $allCharsLength = strlen($allChars) - 1;

    if ($allCharsLength < 0) {
        throw new Exception("Aucun ensemble de caractères n'est sélectionné pour générer le mot de passe.");
    }

    // Assurer la présence d'au moins un caractère de chaque type si demandé
    $requiredChars = [];
    if ($useUppercase && !empty($uppercaseChars)) $requiredChars[] = $uppercaseChars[random_int(0, strlen($uppercaseChars) - 1)];
    if ($useNumbers && !empty($numberChars)) $requiredChars[] = $numberChars[random_int(0, strlen($numberChars) - 1)];
    if ($useSymbols && !empty($symbolChars)) $requiredChars[] = $symbolChars[random_int(0, strlen($symbolChars) - 1)];

    $remainingLength = $length - count($requiredChars);

    if ($remainingLength < 0) {
         throw new Exception("La longueur du mot de passe est trop courte pour inclure tous les types de caractères requis.");
    }

    for ($i = 0; $i < $remainingLength; $i++) {
        $randomIndex = random_int(0, $allCharsLength);
        $password .= $allChars[$randomIndex];
    }

    // Mélanger les caractères requis avec les autres
    $password = str_shuffle($password . implode('', $requiredChars));

    // Assurez-vous que la longueur finale est correcte (peut dépasser si requiredChars rend le total > length initial)
    return substr($password, 0, $length);
}

/**
 * Analyse la robustesse d'un mot de passe côté serveur.
 * Retourne un tableau d'erreurs si le mot de passe n'est pas robuste.
 *
 * @param string $password Le mot de passe à analyser.
 * @return array Un tableau d'erreurs ou vide si robuste.
 */
function analyzePasswordStrength(string $password): array
{
    $errors = [];
    $minLength = 12; // Recommandation : 12 caractères minimum

    if (strlen($password) < $minLength) {
        $errors[] = "Le mot de passe doit contenir au moins " . $minLength . " caractères.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre majuscule.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre minuscule.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
    }
    if (!preg_match('/[!@#$%^&*()-_+=[]{}\|;:,.<>?`~]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un caractère spécial (symbole).";
    }
    if (strpos($password, ' ') !== false) {
        $errors[] = "Le mot de passe ne doit pas contenir d'espaces.";
    }

    // Vérification pour les répétitions simples de caractères (plus de 3 identiques consécutifs)
    if (preg_match('/(.)\1{3,}/', $password)) {
        $errors[] = "Le mot de passe ne doit pas contenir de longues répétitions de caractères.";
    }

    // Vérification pour les séquences simples (alphabétiques ou numériques)
    // Séquences ascendantes ou descendantes de 3 caractères minimum
    $sequences = [
        'abc', 'bcd', 'cde', 'def', 'efg', 'fgh', 'ghi', 'hij', 'ijk', 'jkl', 'klm', 'lmn', 'mno', 'nop', 'opq', 'pqr', 'qrs', 'rst', 'stu', 'tuv', 'uvw', 'vwx', 'wxy', 'xyz',
        '012', '123', '234', '345', '456', '567', '678', '789',
        'cba', 'dcb', 'edc', 'fed', 'gfe', 'hgf', 'ihg', 'jih', 'kji', 'lkj', 'mlk', 'nml', 'onm', 'pon', 'qpo', 'rqp', 'srq', 'tsr', 'uts', 'vut', 'wvu', 'xwv', 'yxw', 'zyx',
        '210', '321', '432', '543', '654', '765', '876', '987'
    ];
    $lowerPassword = strtolower($password);
    foreach ($sequences as $seq) {
        if (strpos($lowerPassword, $seq) !== false) {
            $errors[] = "Le mot de passe ne doit pas contenir de séquences courantes (ex: 'abc', '123').";
            break;
        }
    }

    return $errors;
}

/**
 * Vérifie si un mot de passe a été compromis via l'API "Have I Been Pwned".
 * Nécessite l'extension cURL de PHP.
 *
 * @param string $password Le mot de passe clair à vérifier.
 * @return int Le nombre de fois que le mot de passe a été trouvé dans des fuites, ou 0 si non trouvé.
 * Retourne -1 en cas d'erreur de connexion à l'API.
 */
function checkPwnedPassword(string $password): int
{
    if (!function_exists('curl_init')) {
        error_log("Extension cURL non installée. Impossible d'utiliser checkPwnedPassword.");
        return -1;
    }

    $sha1Password = strtoupper(sha1($password)); // Hachage SHA-1 en majuscules
    $prefix = substr($sha1Password, 0, 5);       // Les 5 premiers caractères
    $suffix = substr($sha1Password, 5);         // Le reste du hachage

    $url = "https://api.pwnedpasswords.com/range/" . $prefix;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CyberQuiz-App'); // User-Agent personnalisé
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Temps d'attente maximum de 5 secondes

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        error_log("Erreur HIBP: HTTP Status " . $httpCode . ", cURL Error: " . $curlError);
        return -1; // Indiquer une erreur
    }

    $lines = explode("\n", $response);
    foreach ($lines as $line) {
        // Chaque ligne est au format "SUFFIX:COUNT"
        $parts = explode(':', $line);
        if (count($parts) === 2) {
            list($returnedSuffix, $count) = $parts;
            if ($returnedSuffix === $suffix) {
                return (int)$count; // Mot de passe trouvé, retourne le nombre de fois
            }
        }
    }

    return 0; // Mot de passe non trouvé
}

/**
 * Ajoute un message (succès, erreur, avertissement) à la session.
 * @param string $type Le type de message ('success', 'error', 'warning').
 * @param string $message Le texte du message.
 */
function addMessage(string $type, string $message): void
{
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = ['type' => $type, 'text' => $message];
}

/**
 * Récupère et vide les messages de la session.
 * @return array Tableau des messages.
 */
function getAndClearMessages(): array
{
    $messages = $_SESSION['messages'] ?? [];
    unset($_SESSION['messages']); // Important pour éviter d'afficher le message plusieurs fois
    return $messages;
}

/**
 * Génère un jeton CSRF et le stocke en session.
 * @return string Le jeton CSRF généré.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un jeton CSRF soumis par un formulaire.
 * @param string $submittedToken Le jeton reçu via POST.
 * @return bool True si le jeton est valide, false sinon.
 */
function verifyCsrfToken(string $submittedToken): bool
{
    if (empty($submittedToken) || empty($_SESSION['csrf_token']) || $submittedToken !== $_SESSION['csrf_token']) {
        return false;
    }
    // Optionnel : Détruire le jeton après utilisation pour le rendre à usage unique.
    // unset($_SESSION['csrf_token']);
    return true;
}

// Fonction utilitaire pour nettoyer les entrées utilisateur pour l'affichage HTML
function sanitizeInput(string $data): string
{
    $data = trim($data);
    // stripslashes() est rarement nécessaire avec les versions modernes de PHP (magic_quotes_gpc est désactivé)
    // $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Échappe les caractères HTML pour prévenir XSS lors de l'affichage
    return $data;
}

?>