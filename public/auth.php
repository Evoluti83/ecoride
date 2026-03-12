<?php
session_start();
require_once "../config/database.php";

// Vérifier si l'utilisateur est déjà connecté via la session
if (!isset($_SESSION['user'])) {
    // Vérifier si un cookie 'remember_me' existe
    if (isset($_COOKIE['remember_me'])) {
        $userId = $_COOKIE['remember_me'];

        // Récupérer l'utilisateur à partir de l'ID du cookie
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user) {
            // Connexion automatique de l'utilisateur
            $_SESSION['user'] = [
                'id' => $user['id'],
                'pseudo' => $user['pseudo'],
                'email' => $user['email'],
                'role' => $user['role'],
                'credits' => $user['credits']
            ];

            // Si le cookie est trouvé, on le sécurise en ajoutant une expiration
            $expireTime = time() + 60 * 60 * 24 * 30; // 30 jours
            setcookie('remember_me', $user['id'], $expireTime, '/', '', true, true); // Secure and HttpOnly
        }
    }
}

// Si l'utilisateur est connecté, rediriger vers la page dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}
?>