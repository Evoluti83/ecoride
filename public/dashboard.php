<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EcoRide - Tableau de bord</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <h1>Tableau de bord</h1>
        <p>Bienvenue <?= htmlspecialchars($user['pseudo']) ?></p>
    </div>
</header>

<main class="container">
    <section class="search-card">
        <h2>Mon espace</h2>
        <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Rôle :</strong> <?= htmlspecialchars($user['role']) ?></p>
        <p><strong>Crédits :</strong> <?= htmlspecialchars($user['credits']) ?></p>

        <p>
            <a href="logout.php">Se déconnecter</a>
        </p>
    </section>
</main>

</body>
</html>