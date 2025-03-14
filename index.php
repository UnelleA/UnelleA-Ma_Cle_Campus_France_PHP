<?php

// Configuration sécurisée des sessions
ini_set('session.cookie_httponly', 1); // Empêche l'accès à la session via JavaScript
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1); // Assure que le cookie de session est envoyé uniquement en HTTPS
}
session_start(); // Démarre la session

// Régénérer l'ID de session périodiquement pour éviter les attaques de fixation de session
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true); // Régénère l'ID de session
    $_SESSION['last_regeneration'] = time(); // Met à jour le temps de régénération
}

// Initialisation de la session

// Inclusion des fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Récupération de la page demandée
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Toutes les pages sont accessibles sans connexion
// Aucune redirection automatique vers le dashboard pour les admins
// (seulement lors de la connexion)
var_dump($page);

// Inclusion de l'en-tête
include 'includes/header.php';
if($page == 'admin')
{
    header('Location: admin/admin_dashboard.php');
}

// Inclusion de la page demandée
$file_path = 'pages/' . $page . '.php';
if (file_exists($file_path)) {
    include $file_path;
} else {
    // Page 404
    include 'pages/404.php';
}

// Inclusion du pied de page
include 'includes/footer.php';
?>
