<?php

session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

/*
    Recharger les données utilisateur depuis la base
*/
$sqlCurrentUser = "SELECT * FROM users WHERE id = :id";
$stmtCurrentUser = $pdo->prepare($sqlCurrentUser);
$stmtCurrentUser->execute(['id' => $user['id']]);
$currentUser = $stmtCurrentUser->fetch();

if ($currentUser) {
    $_SESSION['user']['credits'] = $currentUser['credits'];
    $_SESSION['user']['pseudo'] = $currentUser['pseudo'];
    $_SESSION['user']['email'] = $currentUser['email'];
    $_SESSION['user']['role'] = $currentUser['role'];
    $user = $_SESSION['user'];
}

/*
    Réservations de l'utilisateur
*/
$sqlBookings = "SELECT bookings.booking_date, bookings.status, rides.departure_city, rides.arrival_city, rides.departure_time, rides.price
FROM bookings
INNER JOIN rides ON bookings.ride_id = rides.id
WHERE bookings.user_id = :user_id
ORDER BY bookings.booking_date DESC";

$stmtBookings = $pdo->prepare($sqlBookings);
$stmtBookings->execute(['user_id' => $user['id']]);
$bookings = $stmtBookings->fetchAll();

/*
    Trajets proposés par l'utilisateur
*/
$sqlMyRides = "SELECT departure_city, arrival_city, departure_time, arrival_time, price, available_seats, ecological
FROM rides
WHERE driver_id = :driver_id
ORDER BY departure_time DESC";

$stmtMyRides = $pdo->prepare($sqlMyRides);
$stmtMyRides->execute(['driver_id' => $user['id']]);
$myRides = $stmtMyRides->fetchAll();

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
            <a href="index.php">Rechercher un trajet</a>
        </p>

        <p>
            <a href="create-ride.php">Proposer un trajet</a>
        </p>

        <p>
            <a href="logout.php">Se déconnecter</a>
        </p>
    </section>

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
                        Date du trajet : <?= htmlspecialchars($booking['departure_time']) ?>
                        <br>
                        Prix : <?= htmlspecialchars($booking['price']) ?> crédits
                        <br>
                        Statut : <?= htmlspecialchars($booking['status']) ?>
                        <br>
                        Réservé le : <?= htmlspecialchars($booking['booking_date']) ?>
                    </li>
                    <br>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="search-card">
        <h2>Mes trajets proposés</h2>

        <?php if (empty($myRides)): ?>
            <p>Aucun trajet proposé pour le moment.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($myRides as $ride): ?>
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
                        Prix : <?= htmlspecialchars($ride['price']) ?> crédits
                        <br>
                        Places disponibles : <?= htmlspecialchars($ride['available_seats']) ?>
                        <br>
                        Écologique : <?= $ride['ecological'] ? 'Oui' : 'Non' ?>
                    </li>
                    <br>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

</body>
</html>