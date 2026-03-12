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

// Vérifier que le trajet n'est pas déjà démarré ou terminé
if (in_array($ride['status'], ['started', 'completed', 'cancelled'])) {
    $_SESSION['error_message'] = "Impossible d'annuler ce trajet.";
    header("Location: dashboard.php");
    exit;
}

// Récupérer les participants avec leur email et crédits
$sqlParticipants = "
    SELECT bookings.id AS booking_id, bookings.user_id,
           users.email, users.pseudo
    FROM bookings
    JOIN users ON bookings.user_id = users.id
    WHERE bookings.ride_id = :ride_id
      AND bookings.status  = 'confirmed'
";
$stmtParticipants = $pdo->prepare($sqlParticipants);
$stmtParticipants->execute(['ride_id' => $rideId]);
$participants = $stmtParticipants->fetchAll();

try {
    $pdo->beginTransaction();

    // 1. Annuler le trajet
    $sqlCancelRide = "UPDATE rides SET status = 'cancelled' WHERE id = :id";
    $stmtCancelRide = $pdo->prepare($sqlCancelRide);
    $stmtCancelRide->execute(['id' => $rideId]);

    // 2. Pour chaque participant : annuler sa réservation + rembourser ses crédits
    foreach ($participants as $participant) {
        // Annuler la réservation
        $sqlCancelBooking = "UPDATE bookings SET status = 'cancelled' WHERE id = :id";
        $stmtCancelBooking = $pdo->prepare($sqlCancelBooking);
        $stmtCancelBooking->execute(['id' => $participant['booking_id']]);

        // Rembourser les crédits
        $sqlRefund = "UPDATE users SET credits = credits + :price WHERE id = :user_id";
        $stmtRefund = $pdo->prepare($sqlRefund);
        $stmtRefund->execute([
            'price'   => $ride['price'],
            'user_id' => $participant['user_id']
        ]);

        // 3. Envoyer un mail au participant
        $subject = "EcoRide - Votre trajet a été annulé";
        $body    = "Bonjour " . $participant['pseudo'] . ",\n\n"
                 . "Le trajet " . $ride['departure_city'] . " → " . $ride['arrival_city']
                 . " prévu le " . $ride['departure_time']
                 . " a été annulé par le chauffeur.\n\n"
                 . "Vos crédits (" . $ride['price'] . " crédits) ont été remboursés.\n\n"
                 . "L'équipe EcoRide";

        $headers = "From: noreply@ecoride.fr\r\nContent-Type: text/plain; charset=UTF-8";

        mail($participant['email'], $subject, $body, $headers);
    }

    $pdo->commit();

    $_SESSION['success_message'] = "Trajet annulé. Les participants ont été remboursés et notifiés par mail.";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Erreur lors de l'annulation. Veuillez réessayer.";
}

header("Location: dashboard.php");
exit;
?>