<?php

session_start();
require_once "../config/database.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$rideId = $_GET['ride_id'] ?? null;

if (!$rideId) {
    $_SESSION['error_message'] = "Aucun trajet sélectionné.";
    header("Location: error.php");
    exit;
}

// Récupérer les crédits de l'utilisateur connecté
$sqlUser = "SELECT credits FROM users WHERE id = :user_id";
$stmtUser = $pdo->prepare($sqlUser);
$stmtUser->execute(['user_id' => $userId]);
$user = $stmtUser->fetch();

// Récupérer le trajet
$sqlRide = "SELECT * FROM rides WHERE id = :ride_id";
$stmtRide = $pdo->prepare($sqlRide);
$stmtRide->execute(['ride_id' => $rideId]);
$ride = $stmtRide->fetch();

// Vérifier que le trajet existe
if (!$ride) {
    $_SESSION['error_message'] = "Trajet introuvable.";
    header("Location: error.php");
    exit;
}

// Vérifier qu'il reste des places
if ($ride['available_seats'] <= 0) {
    $_SESSION['error_message'] = "Aucune place disponible pour ce trajet.";
    header("Location: error.php");
    exit;
}

// Vérifier que l'utilisateur a assez de crédits
if ($user['credits'] < $ride['price']) {
    $_SESSION['error_message'] = "Crédits insuffisants pour réserver ce trajet.";
    header("Location: error.php");
    exit;
}

// Vérifier que l'utilisateur n'a pas déjà réservé ce trajet
$sqlCheckBooking = "SELECT id FROM bookings WHERE user_id = :user_id AND ride_id = :ride_id";
$stmtCheckBooking = $pdo->prepare($sqlCheckBooking);
$stmtCheckBooking->execute([
    'user_id' => $userId,
    'ride_id' => $rideId
]);
$existingBooking = $stmtCheckBooking->fetch();

if ($existingBooking) {
    $_SESSION['error_message'] = "Vous avez déjà réservé ce trajet.";
    header("Location: error.php");
    exit;
}

// Début de la transaction pour garantir que tout se passe bien
$pdo->beginTransaction();

try {
    // Créer la réservation
    $sqlBooking = "INSERT INTO bookings (user_id, ride_id) VALUES (:user_id, :ride_id)";
    $stmtBooking = $pdo->prepare($sqlBooking);
    $stmtBooking->execute([
        'user_id' => $userId,
        'ride_id' => $rideId
    ]);

    // Déduire les crédits de l'utilisateur
    $sqlCredits = "UPDATE users SET credits = credits - :price WHERE id = :user_id";
    $stmtCredits = $pdo->prepare($sqlCredits);
    $stmtCredits->execute([
        'price' => $ride['price'],
        'user_id' => $userId
    ]);

    // Mettre à jour les places disponibles
    $sqlUpdateRide = "UPDATE rides SET available_seats = available_seats - 1 WHERE id = :ride_id";
    $stmtUpdateRide = $pdo->prepare($sqlUpdateRide);
    $stmtUpdateRide->execute([
        'ride_id' => $rideId
    ]);

    // Mettre à jour les crédits en session
    $_SESSION['user']['credits'] = $_SESSION['user']['credits'] - $ride['price'];

    // Commit de la transaction
    $pdo->commit();

    // Message de succès
    $_SESSION['success_message'] = "Réservation réussie ! Il vous reste " . ($_SESSION['user']['credits']) . " crédits.";
    header("Location: dashboard.php");
    exit;

} catch (Exception $e) {
    // En cas d'erreur, rollback de la transaction
    $pdo->rollBack();
    $_SESSION['error_message'] = "Une erreur est survenue lors de la réservation. Veuillez réessayer plus tard.";
    header("Location: error.php");
    exit;
}
?>