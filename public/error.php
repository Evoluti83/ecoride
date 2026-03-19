<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Erreur - EcoRide</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <h1>EcoRide - Erreur</h1>
</header>

<main class="container">
    <h2>Oups ! Une erreur est survenue</h2>

    <!-- Affichage du message d'erreur -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert error">
            <?= htmlspecialchars($_SESSION['error_message']); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php else: ?>
        <div class="alert error">
            Un problème est survenu lors de la réservation. Veuillez réessayer plus tard.
        </div>
    <?php endif; ?>

    <!-- Options de navigation -->
    <div class="navigation-options">
        <p><a href="covoiturages.php" class="button-link">Retour à la recherche des trajets</a></p>
        <p><a href="dashboard.php" class="button-link">Retour à mon tableau de bord</a></p>
    </div>
</main>

</body>
</html>