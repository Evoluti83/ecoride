<?php
session_start();
require_once "../config/database.php";

$message = "";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

/*
    On récupère les véhicules de l'utilisateur connecté
*/
$sqlVehicles = "SELECT * FROM vehicles WHERE user_id = :user_id";
$stmtVehicles = $pdo->prepare($sqlVehicles);
$stmtVehicles->execute(['user_id' => $user['id']]);
$vehicles = $stmtVehicles->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = $_POST['vehicle_id'] ?? '';
    $departureCity = trim($_POST['departure_city'] ?? '');
    $arrivalCity = trim($_POST['arrival_city'] ?? '');
    $departureTime = $_POST['departure_time'] ?? '';
    $arrivalTime = $_POST['arrival_time'] ?? '';
    $price = $_POST['price'] ?? '';
    $availableSeats = $_POST['available_seats'] ?? '';
    $ecological = isset($_POST['ecological']) ? 1 : 0;

    // Validation des données
    if (
        !empty($vehicleId) &&
        !empty($departureCity) &&
        !empty($arrivalCity) &&
        !empty($departureTime) &&
        !empty($arrivalTime) &&
        $price !== '' &&
        $availableSeats !== '' &&
        is_numeric($price) &&
        is_numeric($availableSeats) &&
        strtotime($departureTime) !== false &&
        strtotime($arrivalTime) !== false
    ) {
        try {
            // Début de la transaction
            $pdo->beginTransaction();

            // Insertion du trajet
            $sql = "INSERT INTO rides 
                (driver_id, vehicle_id, departure_city, arrival_city, departure_time, arrival_time, price, available_seats, ecological)
                VALUES
                (:driver_id, :vehicle_id, :departure_city, :arrival_city, :departure_time, :arrival_time, :price, :available_seats, :ecological)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'driver_id' => $user['id'],
                'vehicle_id' => $vehicleId,
                'departure_city' => htmlspecialchars($departureCity),
                'arrival_city' => htmlspecialchars($arrivalCity),
                'departure_time' => $departureTime,
                'arrival_time' => $arrivalTime,
                'price' => $price,
                'available_seats' => $availableSeats,
                'ecological' => $ecological
            ]);

            // Commit de la transaction
            $pdo->commit();
            $message = "Trajet créé avec succès !";

        } catch (Exception $e) {
            // Rollback en cas d'erreur
            $pdo->rollBack();
            $message = "Une erreur est survenue lors de la création du trajet. Veuillez réessayer plus tard.";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires avec des valeurs valides.";
    }
}
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
        <h1>EcoRide</h1>
        <p>Bienvenue <?= htmlspecialchars($user['pseudo']) ?></p>
    </div>
</header>

<main class="container">
    <!-- Messages flash -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert success">
            <?= $_SESSION['success_message']; ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert error">
            <?= $_SESSION['error_message']; ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

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