<?php
session_start();
require_once "../config/database.php";
 
// ✅ Récupérer et valider l'ID du trajet
$rideId = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : null;
 
if (!$rideId) {
    header("Location: covoiturages.php");
    exit;
}
 
// ✅ Récupérer le trajet avec les infos du chauffeur et du véhicule
$sqlRide = "
    SELECT
        rides.*,
        users.pseudo        AS driver_pseudo,
        users.photo         AS driver_photo,
        vehicles.brand      AS vehicle_brand,
        vehicles.model      AS vehicle_model,
        vehicles.energy     AS vehicle_energy,
        vehicles.color      AS vehicle_color,
        (vehicles.energy = 'electrique') AS ecological
    FROM rides
    JOIN users    ON rides.driver_id   = users.id
    JOIN vehicles ON rides.vehicle_id  = vehicles.id
    WHERE rides.id = :ride_id
      AND rides.status != 'cancelled'
";
$stmtRide = $pdo->prepare($sqlRide);
$stmtRide->execute(['ride_id' => $rideId]);
$ride = $stmtRide->fetch();
 
if (!$ride) {
    $_SESSION['error_message'] = "Ce trajet n'existe pas ou a été annulé.";
    header("Location: covoiturages.php");
    exit;
}
 
// ✅ Récupérer la note moyenne du chauffeur
$sqlAvgRating = "
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
    FROM reviews
    WHERE driver_id = :driver_id AND status = 'approved'
";
$stmtAvg = $pdo->prepare($sqlAvgRating);
$stmtAvg->execute(['driver_id' => $ride['driver_id']]);
$ratingData = $stmtAvg->fetch();
 
// ✅ Récupérer les avis approuvés du chauffeur
$sqlReviews = "
    SELECT reviews.*, users.pseudo AS author_pseudo
    FROM reviews
    JOIN users ON reviews.author_id = users.id
    WHERE reviews.driver_id = :driver_id AND reviews.status = 'approved'
    ORDER BY reviews.created_at DESC
    LIMIT 5
";
$stmtReviews = $pdo->prepare($sqlReviews);
$stmtReviews->execute(['driver_id' => $ride['driver_id']]);
$reviews = $stmtReviews->fetchAll();
 
// ✅ Récupérer les préférences du chauffeur
$sqlPrefs = "SELECT * FROM preferences WHERE user_id = :user_id";
$stmtPrefs = $pdo->prepare($sqlPrefs);
$stmtPrefs->execute(['user_id' => $ride['driver_id']]);
$preferences = $stmtPrefs->fetchAll();
?>
 
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Détail du trajet</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
 
<?php require_once "navbar.php"; ?>
 
<main class="container">
 
    <!-- Détail du trajet -->
    <section class="search-card">
        <h2>
            <?= htmlspecialchars($ride['departure_city']) ?>
            →
            <?= htmlspecialchars($ride['arrival_city']) ?>
            <?= $ride['ecological'] ? ' 🌿' : '' ?>
        </h2>
 
        <p><strong>Départ :</strong> <?= htmlspecialchars($ride['departure_time']) ?></p>
        <p><strong>Arrivée :</strong> <?= htmlspecialchars($ride['arrival_time']) ?></p>
        <p><strong>Prix :</strong> <?= htmlspecialchars($ride['price']) ?> crédits</p>
        <p><strong>Places disponibles :</strong> <?= htmlspecialchars($ride['available_seats']) ?></p>
        <p><strong>Trajet écologique :</strong> <?= $ride['ecological'] ? '🌿 Oui (véhicule électrique)' : 'Non' ?></p>
    </section>
 
    <!-- Infos chauffeur -->
    <section class="search-card">
        <h3>Le chauffeur</h3>
 
        <?php if ($ride['driver_photo']): ?>
            <img src="<?= htmlspecialchars($ride['driver_photo']) ?>" alt="Photo de <?= htmlspecialchars($ride['driver_pseudo']) ?>" width="80">
        <?php endif; ?>
 
        <p><strong>Pseudo :</strong> <?= htmlspecialchars($ride['driver_pseudo']) ?></p>
        <p>
            <strong>Note :</strong>
            <?php if ($ratingData['total_reviews'] > 0): ?>
                <?= number_format($ratingData['avg_rating'], 1) ?>/5
                (<?= $ratingData['total_reviews'] ?> avis)
            <?php else: ?>
                Pas encore d'avis
            <?php endif; ?>
        </p>
    </section>
 
    <!-- Véhicule -->
    <section class="search-card">
        <h3>Le véhicule</h3>
        <p><strong>Marque :</strong> <?= htmlspecialchars($ride['vehicle_brand']) ?></p>
        <p><strong>Modèle :</strong> <?= htmlspecialchars($ride['vehicle_model']) ?></p>
        <p><strong>Couleur :</strong> <?= htmlspecialchars($ride['vehicle_color']) ?></p>
        <p><strong>Énergie :</strong> <?= htmlspecialchars($ride['vehicle_energy']) ?></p>
    </section>
 
    <!-- Préférences du chauffeur -->
    <?php if (!empty($preferences)): ?>
    <section class="search-card">
        <h3>Préférences du chauffeur</h3>
        <?php foreach ($preferences as $pref): ?>
            <p>🚬 Fumeur : <?= $pref['smoking'] ? 'Accepté' : 'Non accepté' ?></p>
            <p>🐾 Animaux : <?= $pref['animals'] ? 'Acceptés' : 'Non acceptés' ?></p>
            <?php if ($pref['custom_preference']): ?>
                <p>📝 <?= htmlspecialchars($pref['custom_preference']) ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>
 
    <!-- Bouton participer -->
    <section class="search-card">
        <?php if ($ride['available_seats'] > 0): ?>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="book-ride.php?ride_id=<?= (int)$ride['id'] ?>" class="btn btn-primary">
                    Participer à ce trajet
                </a>
            <?php else: ?>
                <p>Vous devez être connecté pour participer.</p>
                <a href="login.php" class="btn">Se connecter</a>
                <a href="register.php" class="btn">Créer un compte</a>
            <?php endif; ?>
        <?php else: ?>
            <p>❌ Plus de places disponibles.</p>
        <?php endif; ?>
    </section>
 
    <!-- Avis sur le chauffeur -->
    <section class="search-card">
        <h3>Avis sur le chauffeur</h3>
 
        <?php if (empty($reviews)): ?>
            <p>Aucun avis disponible pour ce chauffeur.</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review">
                    <strong>⭐ <?= htmlspecialchars($review['rating']) ?>/5</strong>
                    — <em><?= htmlspecialchars($review['author_pseudo']) ?></em>
                    <p><?= htmlspecialchars($review['comment']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
 
</main>
 
</body>
</html>