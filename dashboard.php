<?php
// Initialisation de la session
session_start();

// Vérification des droits d'accès
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    // Redirection vers la page de connexion si l'utilisateur n'est pas administrateur
    header('Location: index.php?page=login');
    exit;
}

// Redirection vers le tableau de bord d'administration
header('Location: admin/admin_dashboard.php');
exit;
?>

