<?php
// Paramètres de connexion à la base de données
$host = 'localhost'; // Ou l'adresse de votre serveur de base de données
$db   = 'quizz'; 
$port = '4240';      // Le nom de votre base de données (comme défini dans votre schéma)
$user = 'root';      // Votre nom d'utilisateur pour la base de données
$pass = 'root';          // Votre mot de passe pour la base de données (NE PAS LAISSER VIDE EN PRODUCTION !)
$charset = 'utf8mb4'; // Encodage des caractères

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Récupère les résultats sous forme de tableau associatif
    PDO::ATTR_EMULATE_PREPARES   => false, // DÉSACTIVER l'émulation des requêtes préparées pour une vraie protection contre les injections SQL
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // La connexion est établie, la variable $pdo est disponible.
} catch (\PDOException $e) {
    // En cas d'échec de connexion, loggez l'erreur pour le débogage
    // ATTENTION : Ne jamais afficher $e->getMessage() directement sur une page en production !
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.");
}
?>