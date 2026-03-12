<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId    = $_SESSION['user']['id'];
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$bookingId) {
    header("Location: dashboard.php");
    exit;
}

// Vérifier que la réservation appartient bien à l'utilisateur
$sqlBooking = "
    SELECT bookings.*, rides.price, rides.departure_time, rides.status AS ride_status
    FROM bookings
    JOIN rides ON bookings.ride_id = rides.id
    WHERE bookings.id = :id AND bookings.user_id = :user_id
";
$stmtBooking = $pdo->prepare($sqlBooking);
$stmtBooking->execute(['id' => $bookingId, 'user_id' => $userId]);
$booking = $stmtBooking->fetch();

if (!$booking) {
    $_SESSION['error_message'] = "Réservation introuvable.";
    header("Location: dashboard.php");
    exit;
}

// Vérifier que le trajet n'est pas déjà démarré ou terminé
if (in_array($booking['ride_status'], ['started', 'completed'])) {
    $_SESSION['error_message'] = "Impossible d'annuler : le trajet est déjà en cours ou terminé.";
    header("Location: dashboard.php");
    exit;
}

// Vérifier que la réservation n'est pas déjà annulée
if ($booking['status'] === 'cancelled') {
    $_SESSION['error_message'] = "Cette réservation est déjà annulée.";
    header("Location: dashboard.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Annuler la réservation
    $sqlCancel = "UPDATE bookings SET status = 'cancelled' WHERE id = :id";
    $stmtCancel = $pdo->prepare($sqlCancel);
    $stmtCancel->execute(['id' => $bookingId]);

    // 2. Rembourser les crédits au passager
    $sqlRefund = "UPDATE users SET credits = credits + :price WHERE id = :user_id";
    $stmtRefund = $pdo->prepare($sqlRefund);
    $stmtRefund->execute([
        'price'   => $booking['price'],
        'user_id' => $userId
    ]);

    // 3. Remettre une place disponible dans le trajet
    $sqlSeat = "UPDATE rides SET available_seats = available_seats + 1 WHERE id = :ride_id";
    $stmtSeat = $pdo->prepare($sqlSeat);
    $stmtSeat->execute(['ride_id' => $booking['ride_id']]);

    // 4. Mettre à jour les crédits en session
    $_SESSION['user']['credits'] += $booking['price'];

    $pdo->commit();

    $_SESSION['success_message'] = "Réservation annulée. Vos crédits ont été remboursés.";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Erreur lors de l'annulation. Veuillez réessayer.";
}

header("Location: dashboard.php");
exit;
?>