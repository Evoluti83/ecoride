<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Rafraîchir les crédits depuis la BDD
$sqlRefresh = "SELECT credits, role FROM users WHERE id = :id";
$stmtRefresh = $pdo->prepare($sqlRefresh);
$stmtRefresh->execute(['id' => $user['id']]);
$freshUser = $stmtRefresh->fetch();
$_SESSION['user']['credits'] = $freshUser['credits'];
$user['credits'] = $freshUser['credits'];

// Réservations de l'utilisateur
$sqlBookings = "
    SELECT
        bookings.id,
        bookings.status         AS booking_status,
        bookings.booking_date,
        rides.id                AS ride_id,
        rides.departure_city,
        rides.arrival_city,
        rides.departure_time,
        rides.price,
        rides.status            AS ride_status
    FROM bookings
    JOIN rides ON bookings.ride_id = rides.id
    WHERE bookings.user_id = :user_id
    ORDER BY bookings.booking_date DESC
";
$stmtBookings = $pdo->prepare($sqlBookings);
$stmtBookings->execute(['user_id' => $user['id']]);
$bookings = $stmtBookings->fetchAll();

// Trajets proposés par l'utilisateur
$sqlMyRides = "
    SELECT rides.*, vehicles.brand, vehicles.model, vehicles.energy
    FROM rides
    JOIN vehicles ON rides.vehicle_id = vehicles.id
    WHERE rides.driver_id = :driver_id
    ORDER BY rides.departure_time DESC
";
$stmtMyRides = $pdo->prepare($sqlMyRides);
$stmtMyRides->execute(['driver_id' => $user['id']]);
$myRides = $stmtMyRides->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Tableau de bord</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container">

    <!-- Messages flash -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert error">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Infos utilisateur -->
    <section class="search-card">
        <h2>Bienvenue, <?= htmlspecialchars($user['pseudo']) ?> 👋</h2>
        <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Rôle :</strong> <?= htmlspecialchars($user['role']) ?></p>
        <p><strong>Crédits :</strong> <?= htmlspecialchars($user['credits']) ?> 💳</p>

        <div class="actions">
            <a href="index.php" class="btn">Rechercher un trajet</a>
            <a href="profile.php" class="btn">Mon profil</a>
            <a href="create-ride.php" class="btn">Proposer un trajet</a>
            <?php if ($user['role'] === 'employee'): ?>
                <a href="manage-reviews.php" class="btn">Gérer les avis</a>
            <?php endif; ?>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="admin.php" class="btn">Espace admin</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Mes réservations -->
    <section class="search-card">
        <h2>Mes réservations</h2>

        <?php if (empty($bookings)): ?>
            <p>Aucune réservation enregistrée.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($bookings as $booking): ?>
                    <li>
                        <strong>
                            <?= htmlspecialchars($booking['departure_city']) ?>
                            →
                            <?= htmlspecialchars($booking['arrival_city']) ?>
                        </strong>
                        <br>
                        Départ : <?= htmlspecialchars($booking['departure_time']) ?>
                        <br>
                        Prix : <?= htmlspecialchars($booking['price']) ?> crédits
                        <br>
                        Statut : <?= htmlspecialchars($booking['booking_status']) ?>
                        <br>
                        Réservé le : <?= htmlspecialchars($booking['booking_date']) ?>
                        <br>

                        <!-- ✅ US10 : Annuler si encore confirmée -->
                        <?php if ($booking['booking_status'] === 'confirmed' && !in_array($booking['ride_status'], ['started', 'completed'])): ?>
                            <a href="cancel-booking.php?id=<?= (int)$booking['id'] ?>"
                               onclick="return confirm('Annuler cette réservation ? Vos crédits seront remboursés.')"
                               style="color:red;">
                                ❌ Annuler ma réservation
                            </a>
                        <?php endif; ?>

                        <!-- ✅ US11 : Laisser un avis si terminé -->
                        <?php if ($booking['booking_status'] === 'completed'): ?>
                            <a href="leave-review.php?ride_id=<?= (int)$booking['ride_id'] ?>">
                                ⭐ Laisser un avis
                            </a>
                        <?php endif; ?>
                    </li>
                    <br>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- Mes trajets proposés -->
    <section class="search-card">
        <h2>Mes trajets proposés</h2>

        <?php if (empty($myRides)): ?>
            <p>Aucun trajet proposé pour le moment.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($myRides as $ride): ?>
                    <?php $isEco = $ride['energy'] === 'electrique'; ?>
                    <li>
                        <strong>
                            <?= htmlspecialchars($ride['departure_city']) ?>
                            →
                            <?= htmlspecialchars($ride['arrival_city']) ?>
                        </strong>
                        <br>
                        Départ : <?= htmlspecialchars($ride['departure_time']) ?>
                        <br>
                        Arrivée : <?= htmlspecialchars($ride['arrival_time']) ?>
                        <br>
                        Véhicule : <?= htmlspecialchars($ride['brand']) ?> <?= htmlspecialchars($ride['model']) ?>
                        <br>
                        Prix : <?= htmlspecialchars($ride['price']) ?> crédits
                        <br>
                        Places disponibles : <?= htmlspecialchars($ride['available_seats']) ?>
                        <br>
                        Écologique : <?= $isEco ? '🌿 Oui' : 'Non' ?>
                        <br>
                        Statut : <?= htmlspecialchars($ride['status']) ?>
                        <br>

                        <!-- ✅ US11 : Démarrer le trajet -->
                        <?php if ($ride['status'] === 'pending'): ?>
                            <a href="start-ride.php?id=<?= (int)$ride['id'] ?>"
                               onclick="return confirm('Démarrer ce trajet ?')"
                               style="color:green;">
                                ▶️ Démarrer
                            </a>
                            &nbsp;
                            <!-- ✅ US10 : Annuler le trajet -->
                            <a href="cancel-ride.php?id=<?= (int)$ride['id'] ?>"
                               onclick="return confirm('Annuler ce trajet ? Les participants seront remboursés et notifiés.')"
                               style="color:red;">
                                ❌ Annuler le trajet
                            </a>
                        <?php endif; ?>

                        <!-- ✅ US11 : Arrivée à destination -->
                        <?php if ($ride['status'] === 'started'): ?>
                            <a href="end-ride.php?id=<?= (int)$ride['id'] ?>"
                               onclick="return confirm('Confirmer l\'arrivée à destination ?')"
                               style="color:blue;">
                                🏁 Arrivée à destination
                            </a>
                        <?php endif; ?>
                    </li>
                    <br>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

</main>

</body>
</html>