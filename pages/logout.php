<?php
// pages/logout.php
require_once '../includes/functions.php';
startSession(); // Démarre la session
session_unset(); // Supprime toutes les variables de session
session_destroy(); // Détruit la session
redirect(getBasePath() . 'pages/login.php'); // Redirige vers la page de connexion
?>