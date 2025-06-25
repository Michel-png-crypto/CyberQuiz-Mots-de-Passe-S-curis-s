<?php
// includes/db.php

// Paramètres de connexion à la base de données
$host = 'localhost';
$db   = 'quiz_platform'; // Nom de votre base de données (que vous avez créé avec le SQL fourni)
$user = 'root';          // Votre nom d'utilisateur MySQL (souvent 'root' pour XAMPP/WAMP)
$pass = '';              // Votre mot de passe MySQL (souvent vide '' pour XAMPP/WAMP, sinon le vôtre)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Active les exceptions PDO pour la gestion des erreurs
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Récupère les résultats sous forme de tableau associatif
    PDO::ATTR_EMULATE_PREPARES   => false,                // Désactive l'émulation des requêtes préparées pour une meilleure sécurité
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connexion à la base de données réussie!"; // Décommentez pour un test rapide
} catch (\PDOException $e) {
    // En production, vous ne devriez JAMAIS afficher l'erreur brute à l'utilisateur final.
    // Loggez l'erreur pour le débogage et affichez un message générique.
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die('Impossible de se connecter à la base de données. Veuillez réessayer plus tard.');
}
?>