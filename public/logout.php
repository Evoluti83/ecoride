<?php

session_start();

// Ajouter un message flash de déconnexion
$_SESSION['message'] = "Vous êtes maintenant déconnecté.";

// Sécurisation de la suppression du cookie "remember_me" (avec 'secure' et 'HttpOnly' pour la sécurité)
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', true, true); // Expire le cookie immédiatement, avec les paramètres sécurisés
}

// Supprimer toutes les données de session
session_unset();

// Détruire la session
session_destroy();

// Renouveler l'ID de session pour éviter les attaques de fixation de session
session_regenerate_id();

// Rediriger vers la page de connexion
header("Location: login.php");
exit;

?>