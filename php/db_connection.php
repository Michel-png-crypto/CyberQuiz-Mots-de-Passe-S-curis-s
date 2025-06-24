<?php
$host = 'localhost';
$port = '4240'; // Le port que tu utilises dans MAMP (ou '3306' si c'est le cas)
$dbname = 'quizz'; // <-- S'assurer que c'est le bon nom de DB pour le projet
$username = 'root';
$password = 'root';

try {
    // Utiliser un tableau d'options pour inclure ATTR_EMULATE_PREPARES
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // <-- TRÈS IMPORTANT POUR LA SÉCURITÉ !
    ];

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, $options);
    // Vous n'avez pas besoin de setAttribute ici si vous passez les options dans le constructeur
    // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Déjà dans les options

} catch (PDOException $e) {
    // En cas d'échec de connexion, loggez l'erreur pour le débogage
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.");
}
?>