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

// Vérifier que le trajet est bien démarré
if ($ride['status'] !== 'started') {
    $_SESSION['error_message'] = "Ce trajet n'est pas en cours.";
    header("Location: dashboard.php");
    exit;
}

// Récupérer les participants confirmés avec leur email
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

    // 1. Marquer le trajet comme terminé
    $sqlEnd = "UPDATE rides SET status = 'completed' WHERE id = :id";
    $stmtEnd = $pdo->prepare($sqlEnd);
    $stmtEnd->execute(['id' => $rideId]);

    // 2. Mettre à jour les réservations en 'completed'
    $sqlCompleteBookings = "
        UPDATE bookings SET status = 'completed'
        WHERE ride_id = :ride_id AND status = 'confirmed'
    ";
    $stmtCompleteBookings = $pdo->prepare($sqlCompleteBookings);
    $stmtCompleteBookings->execute(['ride_id' => $rideId]);

    // 3. Créditer le chauffeur
    // Prix total - 2 crédits plateforme par passager
    $nbPassagers     = count($participants);
    $creditsEarned   = ($ride['price'] * $nbPassagers) - (2 * $nbPassagers);

    if ($creditsEarned > 0) {
        $sqlCredit = "UPDATE users SET credits = credits + :credits WHERE id = :driver_id";
        $stmtCredit = $pdo->prepare($sqlCredit);
        $stmtCredit->execute([
            'credits'   => $creditsEarned,
            'driver_id' => $userId
        ]);

        // Mettre à jour la session
        $_SESSION['user']['credits'] += $creditsEarned;
    }

    // 4. Envoyer un mail à chaque participant
    foreach ($participants as $participant) {
        $subject = "EcoRide - Votre trajet est terminé, donnez votre avis !";
        $body    = "Bonjour " . $participant['pseudo'] . ",\n\n"
                 . "Votre trajet " . $ride['departure_city'] . " → " . $ride['arrival_city']
                 . " est terminé.\n\n"
                 . "Rendez-vous sur votre espace EcoRide pour :\n"
                 . "  ✅ Valider que tout s'est bien passé\n"
                 . "  ⭐ Laisser un avis sur le chauffeur\n\n"
                 . "Lien : http://localhost/ecoride/public/dashboard.php\n\n"
                 . "Merci d'utiliser EcoRide 🌿\n"
                 . "L'équipe EcoRide";

        $headers = "From: noreply@ecoride.fr\r\nContent-Type: text/plain; charset=UTF-8";
        mail($participant['email'], $subject, $body, $headers);
    }

    $pdo->commit();

    $_SESSION['success_message'] = "Trajet terminé ! Vous avez gagné "
        . $creditsEarned . " crédits. Les participants ont été notifiés. 🎉";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Erreur lors de la clôture du trajet. Veuillez réessayer.";
}

header("Location: dashboard.php");
exit;
?>