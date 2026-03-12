<?php
require_once "../config/database.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Accueil</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1>EcoRide</h1>
            <p>La plateforme de covoiturage écologique</p>
        </div>
    </header>

    <main class="container">
        <section class="hero">
            <h2>Voyagez autrement</h2>
            <p>
                Recherchez un trajet, partagez vos déplacements
                et réduisez votre impact environnemental.
            </p>
        </section>

        <section class="search-card">
            <h2>Rechercher un trajet</h2>

            <form action="covoiturages.php" method="GET">
                <div class="form-group">
                    <label for="departure">Ville de départ</label>
                    <input type="text" id="departure" name="departure" placeholder="Ex : Marseille" required>
                </div>

                <div class="form-group">
                    <label for="arrival">Ville d'arrivée</label>
                    <input type="text" id="arrival" name="arrival" placeholder="Ex : Nice" required>
                </div>

                <div class="form-group">
                    <label for="date">Date de départ</label>
                    <input type="date" id="date" name="date" required>
                </div>

                <button type="submit">Rechercher</button>
            </form>
        </section>
    </main>
</body>
</html>