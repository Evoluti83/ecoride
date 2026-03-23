<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Mentions légales</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">

    <section class="search-card">
        <h2 style="color: #2e7d32;">Mentions légales</h2>

        <h3 id="editeur" style="color: #2e7d32;">1. Éditeur du site</h3>
        <p>
            <strong>EcoRide</strong><br>
            Plateforme de covoiturage écologique<br>
            Email : <a href="mailto:contact@ecoride.fr" style="color: #2e7d32;">contact@ecoride.fr</a><br>
            Développé dans le cadre du Titre Professionnel DWWM — 2026
        </p>

        <h3 id="hebergement" style="color: #2e7d32;">2. Hébergement</h3>
        <p>
            Ce site est hébergé par <strong>Heroku</strong> (Salesforce)<br>
            415 Mission Street, Suite 300<br>
            San Francisco, CA 94105, États-Unis<br>
            <a href="https://www.heroku.com" style="color: #2e7d32;" target="_blank">www.heroku.com</a>
        </p>

        <h3 id="confidentialite" style="color: #2e7d32;">3. Politique de confidentialité</h3>
        <p>
            EcoRide collecte uniquement les données nécessaires au bon fonctionnement de la plateforme :
        </p>
        <ul>
            <li>Pseudo, adresse email et mot de passe (hashé) pour la création de compte</li>
            <li>Informations de véhicule pour les chauffeurs</li>
            <li>Historique des trajets et réservations</li>
            <li>Avis et notes sur les conducteurs</li>
        </ul>
        <p>
            Ces données sont stockées de manière sécurisée et ne sont jamais vendues à des tiers.
            Les mots de passe sont hashés avec l'algorithme bcrypt et ne peuvent pas être récupérés.
        </p>

        <h3 id="cookies" style="color: #2e7d32;">4. Cookies</h3>
        <p>
            EcoRide utilise uniquement des cookies de session nécessaires au fonctionnement de l'application
            (maintien de la connexion utilisateur). Aucun cookie publicitaire ou de tracking n'est utilisé.
        </p>

        <h3 id="cgu" style="color: #2e7d32;">5. Conditions générales d'utilisation</h3>
        <p>
            En utilisant EcoRide, vous acceptez les conditions suivantes :
        </p>
        <ul>
            <li>Fournir des informations exactes lors de la création de votre compte</li>
            <li>Ne pas utiliser la plateforme à des fins frauduleuses</li>
            <li>Respecter les autres membres de la communauté</li>
            <li>Honorer vos réservations et trajets confirmés</li>
            <li>Les avis laissés doivent être honnêtes et respectueux</li>
        </ul>

        <h3 id="credits" style="color: #2e7d32;">6. Système de crédits</h3>
        <p>
            Les crédits EcoRide sont une monnaie virtuelle utilisée exclusivement sur la plateforme.
            Ils ne peuvent pas être convertis en argent réel. EcoRide se réserve le droit de
            modifier le système de crédits à tout moment.
        </p>

        <h3 id="responsabilite" style="color: #2e7d32;">7. Limitation de responsabilité</h3>
        <p>
            EcoRide est une plateforme de mise en relation. Nous ne sommes pas responsables
            des incidents pouvant survenir lors des trajets. Nous encourageons tous les utilisateurs
            à vérifier les informations des conducteurs avant de réserver.
        </p>

        <p style="margin-top: 30px; color: #888; font-size: 0.9rem;">
            Dernière mise à jour : Mars 2026<br>
            Pour toute question : <a href="mailto:contact@ecoride.fr" style="color: #2e7d32;">contact@ecoride.fr</a>
        </p>
    </section>

</main>

<footer style="background: #1f2937; color: #aaa; text-align: center; padding: 20px; margin-top: 40px;">
    <p>EcoRide — contact@ecoride.fr — <a href="mentions-legales.php" style="color: #4CAF50;">Mentions légales</a></p>
</footer>

</body>
</html>