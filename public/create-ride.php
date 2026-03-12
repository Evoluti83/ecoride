<?php

session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$message = "";

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

    if (
        !empty($vehicleId) &&
        !empty($departureCity) &&
        !empty($arrivalCity) &&
        !empty($departureTime) &&
        !empty($arrivalTime) &&
        $price !== '' &&
        $availableSeats !== ''
    ) {
        $sql = "INSERT INTO rides 
            (driver_id, vehicle_id, departure_city, arrival_city, departure_time, arrival_time, price, available_seats, ecological)
            VALUES
            (:driver_id, :vehicle_id, :departure_city, :arrival_city, :departure_time, :arrival_time, :price, :available_seats, :ecological)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'driver_id' => $user['id'],
            'vehicle_id' => $vehicleId,
            'departure_city' => $departureCity,
            'arrival_city' => $arrivalCity,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
            'price' => $price,
            'available_seats' => $availableSeats,
            'ecological' => $ecological
        ]);

        $message = "Trajet créé avec succès !";
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EcoRide - Proposer un trajet</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <h1>EcoRide</h1>
        <p>Proposer un trajet</p>
    </div>
</header>

<main class="container">
    <section class="search-card">
        <h2>Nouveau trajet</h2>

        <?php if (!empty($message)): ?>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if (empty($vehicles)): ?>
            <p>Vous devez d'abord enregistrer un véhicule avant de proposer un trajet.</p>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="vehicle_id">Véhicule</label>
                    <select id="vehicle_id" name="vehicle_id" required>
                        <option value="">Choisir un véhicule</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?= $vehicle['id'] ?>">
                                <?= htmlspecialchars($vehicle['brand']) ?> - <?= htmlspecialchars($vehicle['model']) ?> (<?= htmlspecialchars($vehicle['registration']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="departure_city">Ville de départ</label>
                    <input type="text" id="departure_city" name="departure_city" required>
                </div>

                <div class="form-group">
                    <label for="arrival_city">Ville d'arrivée</label>
                    <input type="text" id="arrival_city" name="arrival_city" required>
                </div>

                <div class="form-group">
                    <label for="departure_time">Date et heure de départ</label>
                    <input type="datetime-local" id="departure_time" name="departure_time" required>
                </div>

                <div class="form-group">
                    <label for="arrival_time">Date et heure d'arrivée</label>
                    <input type="datetime-local" id="arrival_time" name="arrival_time" required>
                </div>

                <div class="form-group">
                    <label for="price">Prix</label>
                    <input type="number" id="price" name="price" min="0" required>
                </div>

                <div class="form-group">
                    <label for="available_seats">Places disponibles</label>
                    <input type="number" id="available_seats" name="available_seats" min="1" required>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ecological">
                        Trajet écologique
                    </label>
                </div>

                <button type="submit">Créer le trajet</button>
            </form>
        <?php endif; ?>
    </section>
</main>

</body>
</html>