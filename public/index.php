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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

            <!-- Formulaire de recherche -->
            <form action="covoiturages.php" method="GET" id="searchForm">
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

                <button type="submit" id="searchBtn">Rechercher</button>
            </form>
        </section>

        <!-- Liens d'inscription et de connexion -->
        <section class="user-actions">
            <p>Vous n'êtes pas encore membre ? <a href="register.php">Inscrivez-vous ici</a> !</p>
            <p>Vous avez déjà un compte ? <a href="login.php">Connectez-vous</a></p>
        </section>
    </main>

    <script>
        // Ajout d'une validation rapide en JavaScript
        $('#searchForm').on('submit', function(event) {
            var departure = $('#departure').val().trim();
            var arrival = $('#arrival').val().trim();
            var date = $('#date').val().trim();
            var errorMessage = '';

            // Validation de base
            if (departure === '' || arrival === '' || date === '') {
                errorMessage = "Veuillez remplir tous les champs.";
            }

            // Validation de la date : vérifier que la date n'est pas dans le passé
            var today = new Date().toISOString().split('T')[0]; // La date du jour
            if (date < today) {
                errorMessage = "La date de départ ne peut pas être dans le passé.";
            }

            if (errorMessage) {
                event.preventDefault(); // Empêche l'envoi du formulaire
                alert(errorMessage);
            }
        });
    </script>
</body>
</html>