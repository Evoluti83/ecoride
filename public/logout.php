<?php
session_start();
 
// 1. Régénérer l'ID AVANT de détruire (protection fixation de session)
session_regenerate_id(true);
 
// 2. Supprimer le cookie "remember_me" de façon sécurisée
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', true, true);
    unset($_COOKIE['remember_me']);
}
 
// 3. Vider puis détruire la session
session_unset();
session_destroy();
 
// 4. Rediriger vers login avec message flash
// On recrée une session minimale juste pour le message
session_start();
$_SESSION['success_message'] = "Vous êtes maintenant déconnecté.";
 
header("Location: login.php");
exit;
?>