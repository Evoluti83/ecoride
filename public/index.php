<?php
session_start();
require_once "../config/database.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Accueil</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Styles spécifiques à la page d'accueil */
        .hero-banner {
            background: linear-gradient(135deg, #2e7d32 0%, #4CAF50 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }
        .hero-banner h2 {
            font-size: 2.2rem;
            margin-bottom: 15px;
        }
        .hero-banner p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        .presentation-section {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }
        .presentation-section h2 {
            color: #2e7d32;
            margin-top: 0;
            font-size: 1.8rem;
        }
        .presentation-section p {
            color: #555;
            line-height: 1.8;
            font-size: 1rem;
        }
        .images-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        .image-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: #E8F5E9;
            text-align: center;
            padding: 30px 20px;
        }
        .image-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .image-card h3 {
            color: #2e7d32;
            margin: 0 0 10px;
            font-size: 1.1rem;
        }
        .image-card p {
            color: #555;
            font-size: 0.9rem;
            margin: 0;
            line-height: 1.5;
        }
        .stats-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: #f4f8f4;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            border: 1px solid #E8F5E9;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #2e7d32;
        }
        .stat-card .label {
            font-size: 0.9rem;
            color: #888;
            margin-top: 5px;
        }
        .how-it-works {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }
        .how-it-works h2 {
            color: #2e7d32;
            margin-top: 0;
        }
        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 25px;
        }
        .step {
            text-align: center;
            padding: 20px;
        }
        .step .step-number {
            width: 45px;
            height: 45px;
            background: #2e7d32;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
            margin: 0 auto 15px;
        }
        .step h4 {
            color: #1f2937;
            margin: 0 0 8px;
        }
        .step p {
            color: #888;
            font-size: 0.9rem;
            margin: 0;
        }
        /* Footer */
        .site-footer {
            background: #1f2937;
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }
        .footer-content {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-bottom: 30px;
        }
        .footer-section h4 {
            color: #4CAF50;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        .footer-section p,
        .footer-section a {
            color: #aaa;
            font-size: 0.9rem;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            line-height: 1.6;
        }
        .footer-section a:hover {
            color: #4CAF50;
        }
        .footer-bottom {
            border-top: 1px solid #333;
            padding-top: 20px;
            text-align: center;
            color: #888;
            font-size: 0.85rem;
        }
        .eco-badge {
            display: inline-block;
            background: #E8F5E9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .images-grid, .stats-section, .steps, .footer-content {
                grid-template-columns: 1fr;
            }
            .hero-banner h2 { font-size: 1.6rem; }
        }
    </style>
</head>
<body>

<?php require_once "navbar.php"; ?>

<!-- HERO BANNER -->
<div class="hero-banner">
    <div class="container">
        <span class="eco-badge">🌱 Plateforme 100% écologique</span>
        <h2>Voyagez autrement, voyagez mieux</h2>
        <p>Rejoignez EcoRide et partagez vos trajets pour réduire votre empreinte carbone tout en faisant des économies.</p>
    </div>
</div>

<main class="container">

    <!-- BARRE DE RECHERCHE -->
    <section class="search-card" style="margin-top: 40px;">
        <h2>🔍 Rechercher un trajet</h2>
        <form action="covoiturages.php" method="GET" id="searchForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="departure">Ville de départ</label>
                    <input type="text" id="departure" name="departure" placeholder="Ex : Marseille" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="arrival">Ville d'arrivée</label>
                    <input type="text" id="arrival" name="arrival" placeholder="Ex : Nice" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="date">Date de départ</label>
                    <input type="date" id="date" name="date" required>
                </div>
                <button type="submit" style="height: 46px; padding: 0 25px;">Rechercher</button>
            </div>
        </form>
    </section>

    <!-- PRÉSENTATION DE L'ENTREPRISE -->
    <section class="presentation-section">
        <h2>Qui sommes-nous ?</h2>
        <p>
            <strong>EcoRide</strong> est une startup française fondée avec une mission claire : 
            <strong>réduire l'impact environnemental des déplacements</strong> en encourageant le covoiturage. 
            Nous croyons qu'il est possible de se déplacer intelligemment tout en préservant notre planète.
        </p>
        <p>
            Notre plateforme met en relation des chauffeurs et des passagers pour des trajets en voiture. 
            Nous valorisons particulièrement les <strong>véhicules électriques</strong>, identifiés par notre badge 
            🌿 <em>Éco</em>, pour encourager une mobilité toujours plus propre.
        </p>

        <!-- IMAGES / ILLUSTRATIONS -->
        <div class="images-grid">
            <div class="image-card">
                <div class="icon">🌿</div>
                <h3>Écologique</h3>
                <p>Les trajets en voiture électrique sont mis en avant avec notre badge écologique. Chaque trajet partagé, c'est du CO₂ en moins.</p>
            </div>
            <div class="image-card">
                <div class="icon">💳</div>
                <h3>Économique</h3>
                <p>Partagez les frais de route grâce à notre système de crédits. 20 crédits offerts à l'inscription pour démarrer sans frais.</p>
            </div>
            <div class="image-card">
                <div class="icon">🤝</div>
                <h3>Communautaire</h3>
                <p>Une communauté de confiance avec un système d'avis vérifiés. Chaque conducteur est évalué par les passagers.</p>
            </div>
        </div>

        <!-- STATISTIQUES -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="number">100%</div>
                <div class="label">Trajets vérifiés</div>
            </div>
            <div class="stat-card">
                <div class="number">20</div>
                <div class="label">Crédits offerts à l'inscription</div>
            </div>
            <div class="stat-card">
                <div class="number">🌱</div>
                <div class="label">Impact environnemental réduit</div>
            </div>
        </div>
    </section>

    <!-- COMMENT ÇA MARCHE -->
    <section class="how-it-works">
        <h2>Comment ça marche ?</h2>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h4>Inscrivez-vous</h4>
                <p>Créez votre compte en 2 minutes et recevez 20 crédits offerts.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h4>Recherchez</h4>
                <p>Trouvez un trajet par ville et par date selon vos besoins.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h4>Réservez</h4>
                <p>Choisissez votre trajet et réservez votre place en un clic.</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <h4>Voyagez !</h4>
                <p>Profitez de votre trajet et laissez un avis pour aider la communauté.</p>
            </div>
        </div>
    </section>

    <!-- CALL TO ACTION -->
    <?php if (!isset($_SESSION['user'])): ?>
    <section class="search-card" style="text-align: center; padding: 40px;">
        <h2 style="color: #2e7d32;">Rejoignez la communauté EcoRide</h2>
        <p style="color: #555; margin-bottom: 25px;">Plus de 1000 trajets disponibles chaque semaine. Inscrivez-vous gratuitement !</p>
        <a href="register.php" style="background: #2e7d32; color: white; padding: 14px 35px; border-radius: 8px; text-decoration: none; font-size: 1rem; font-weight: bold; margin-right: 15px;">
            Créer mon compte
        </a>
        <a href="login.php" style="background: white; color: #2e7d32; padding: 14px 35px; border-radius: 8px; text-decoration: none; font-size: 1rem; border: 2px solid #2e7d32;">
            Se connecter
        </a>
    </section>
    <?php endif; ?>

</main>

<!-- FOOTER -->
<?php require_once "footer.php"; ?>

<script>
    $('#searchForm').on('submit', function(event) {
        var departure = $('#departure').val().trim();
        var arrival   = $('#arrival').val().trim();
        var date      = $('#date').val().trim();
        var today     = new Date().toISOString().split('T')[0];

        if (departure === '' || arrival === '' || date === '') {
            alert("Veuillez remplir tous les champs.");
            event.preventDefault();
            return;
        }
        if (date < today) {
            alert("La date de départ ne peut pas être dans le passé.");
            event.preventDefault();
        }
    });
</script>

</body>
</html>