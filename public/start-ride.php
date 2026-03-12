<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$rideId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$rideId) {
    header("Location: dashboard.php");
    exit;
}

// Vérifier que le trajet appartient bien au chauffeur connecté
$sqlRide = "SELECT * FROM rides WHERE id = :id AND driver_id = :driver_id";
$stmtRide = $pdo->prepare($sqlRide);
$stmtRide->execute(['id' => $rideId, 'driver_id' => $userId]);
$ride = $stmtRide->fetch();

if (!$ride) {
    $_SESSION['error_message'] = "Trajet introuvable.";
    header("Location: dashboard.php");
    exit;
}

// Vérifier que le trajet est bien en attente
if ($ride['status'] !== 'pending') {
    $_SESSION['error_message'] = "Ce trajet ne peut pas être démarré.";
    header("Location: dashboard.php");
    exit;
}

// Démarrer le trajet
$sqlStart = "UPDATE rides SET status = 'started' WHERE id = :id";
$stmtStart = $pdo->prepare($sqlStart);
$stmtStart->execute(['id' => $rideId]);

$_SESSION['success_message'] = "Trajet démarré ! Bon voyage 🚗";
header("Location: dashboard.php");
exit;
?>