<?php

session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$rideId = $_GET['ride_id'] ?? null;

if (!$rideId) {
    header("Location: index.php");
    exit;
}

$sqlRide = "SELECT * FROM rides WHERE id = :ride_id";
$stmtRide = $pdo->prepare($sqlRide);
$stmtRide->execute(['ride_id' => $rideId]);
$ride = $stmtRide->fetch();

if (!$ride || $ride['available_seats'] <= 0) {
    die("Aucune place disponible.");
}

$sqlBooking = "INSERT INTO bookings (user_id, ride_id)
VALUES (:user_id, :ride_id)";

$stmtBooking = $pdo->prepare($sqlBooking);
$stmtBooking->execute([
    'user_id' => $userId,
    'ride_id' => $rideId
]);

$sqlUpdate = "UPDATE rides
SET available_seats = available_seats - 1
WHERE id = :ride_id";

$stmtUpdate = $pdo->prepare($sqlUpdate);
$stmtUpdate->execute([
    'ride_id' => $rideId
]);

header("Location: dashboard.php");
exit;