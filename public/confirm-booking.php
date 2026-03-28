<?php
session_start();
require_once "../config/database.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    $_SESSION['error_message'] = "Veuillez vous connecter pour réserver un trajet.";
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$rideId = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : null;

if (!$rideId) {
    header("Location: covoiturages.php");
    exit;
}

// Récupérer les crédits à jour depuis la BDD
$sqlUser = "SELECT credits FROM users WHERE id = :user_id";
$stmtUser = $pdo->prepare($sqlUser);
$stmtUser->execute(['user_id' => $userId]);
$user = $stmtUser->fetch();
$currentCredits = $user['credits'];

// Récupérer le trajet avec infos chauffeur et véhicule
$sqlRide = "
    SELECT rides.*,
           users.pseudo    AS driver_pseudo,
           vehicles.brand  AS vehicle_brand,
           vehicles.model  AS vehicle_model,
           vehicles.energy AS vehicle_energy,
           (vehicles.energy = 'electrique') AS ecological
    FROM rides
    JOIN users    ON rides.driver_id  = users.id
    JOIN vehicles ON rides.vehicle_id = vehicles.id
    WHERE rides.id = :ride_id
      AND rides.status = 'pending'
";
$stmtRide = $pdo->prepare($sqlRide);
$stmtRide->execute(['ride_id' => $rideId]);
$ride = $stmtRide->fetch();

if (!$ride) {
    $_SESSION['error_message'] = "Trajet introuvable ou non disponible.";
    header("Location: covoiturages.php");
    exit;
}

// Vérifier qu'il reste des places
if ($ride['available_seats'] <= 0) {
    $_SESSION['error_message'] = "Plus aucune place disponible pour ce trajet.";
    header("Location: ride-detail.php?ride_id=" . $rideId);
    exit;
}

// Vérifier que l'utilisateur a assez de crédits
if ($currentCredits < $ride['price']) {
    $_SESSION['error_message'] = "Crédits insuffisants. Il vous faut " . $ride['price'] . " crédits, vous en avez " . $currentCredits . ".";
    header("Location: ride-detail.php?ride_id=" . $rideId);
    exit;
}

// Vérifier que l'utilisateur ne réserve pas son propre trajet
if ($ride['driver_id'] == $userId) {
    $_SESSION['error_message'] = "Vous ne pouvez pas réserver votre propre trajet.";
    header("Location: ride-detail.php?ride_id=" . $rideId);
    exit;
}

// Vérifier qu'il n'a pas déjà réservé ce trajet
$sqlCheck = "SELECT id FROM bookings WHERE user_id = :user_id AND ride_id = :ride_id AND status != 'cancelled'";
$stmtCheck = $pdo->prepare($sqlCheck);
$stmtCheck->execute(['user_id' => $userId, 'ride_id' => $rideId]);
if ($stmtCheck->fetch()) {
    $_SESSION['error_message'] = "Vous avez déjà réservé ce trajet.";
    header("Location: ride-detail.php?ride_id=" . $rideId);
    exit;
}

$creditsAfter = $currentCredits - $ride['price'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Confirmer la réservation</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container" style="margin-top: 30px; max-width: 700px;">

    <section class="search-card">
        <h2 style="color: #2e7d32;">Confirmer votre réservation</h2>
        <p style="color: #555;">Veuillez vérifier les détails avant de confirmer.</p>

        <!-- Récapitulatif du trajet -->
        <div style="background: #F4F8F4; border-radius: 10px; padding: 20px; margin: 20px 0;">
            <h3 style="color: #2e7d32; margin-top: 0;">
                <?= htmlspecialchars($ride['departure_city']) ?>
                →
                <?= htmlspecialchars($ride['arrival_city']) ?>
                <?= $ride['ecological'] ? '🌿' : '' ?>
            </h3>
            <p><strong>Chauffeur :</strong> <?= htmlspecialchars($ride['driver_pseudo']) ?></p>
            <p><strong>Véhicule :</strong> <?= htmlspecialchars($ride['vehicle_brand']) ?> <?= htmlspecialchars($ride['vehicle_model']) ?> (<?= htmlspecialchars($ride['vehicle_energy']) ?>)</p>
            <p><strong>Départ :</strong> <?= date('d/m/Y à H\hi', strtotime($ride['departure_time'])) ?></p>
            <p><strong>Arrivée :</strong> <?= date('d/m/Y à H\hi', strtotime($ride['arrival_time'])) ?></p>
            <p><strong>Places restantes :</strong> <?= (int)$ride['available_seats'] ?></p>
        </div>

        <!-- Récapitulatif des crédits -->
        <div style="background: #FFF3E0; border: 1px solid #FFB74D; border-radius: 10px; padding: 20px; margin: 20px 0;">
            <h3 style="color: #E65100; margin-top: 0;">💳 Récapitulatif des crédits</h3>
            <p>
                <strong>Crédits actuels :</strong>
                <?= (int)$currentCredits ?> crédits
            </p>
            <p>
                <strong>Coût du trajet :</strong>
                <span style="color: #C62828;">- <?= (int)$ride['price'] ?> crédits</span>
            </p>
            <hr style="border: none; border-top: 1px solid #FFB74D;">
            <p style="font-size: 1.1rem;">
                <strong>Crédits restants après réservation :</strong>
                <span style="color: #2e7d32; font-weight: bold;"><?= (int)$creditsAfter ?> crédits</span>
            </p>
        </div>

        <!-- Boutons de confirmation -->
        <div style="display: flex; gap: 15px; margin-top: 25px;">
            <!-- ✅ Confirmer = aller vers book-ride.php -->
            <a href="book-ride.php?ride_id=<?= (int)$rideId ?>"
               style="flex: 1; display: block; text-align: center; background: #2e7d32; color: white;
                      padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 1rem;">
                ✅ Confirmer la réservation
            </a>
            <!-- Annuler = retour au détail -->
            <a href="ride-detail.php?ride_id=<?= (int)$rideId ?>"
               style="flex: 1; display: block; text-align: center; background: white; color: #C62828;
                      padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold;
                      font-size: 1rem; border: 2px solid #C62828;">
                ❌ Annuler
            </a>
        </div>
    </section>

</main>

<?php require_once "footer.php"; ?>

</body>
</html>