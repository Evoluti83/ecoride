<?php
session_start();
require_once "../config/database.php";
require_once "../config/mongodb.php";
 
$message = '';
 
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
 
$user   = $_SESSION['user'];
$rideId = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : null;
 
if (!$rideId) {
    header("Location: index.php");
    exit;
}
 
// Récupérer le trajet depuis MySQL
$sqlRide = "
    SELECT rides.*, users.pseudo AS driver_pseudo
    FROM rides
    JOIN users ON rides.driver_id = users.id
    WHERE rides.id = :ride_id
";
$stmtRide = $pdo->prepare($sqlRide);
$stmtRide->execute(['ride_id' => $rideId]);
$ride = $stmtRide->fetch();
 
if (!$ride) {
    $_SESSION['error_message'] = "Ce trajet n'existe pas.";
    header("Location: dashboard.php");
    exit;
}
 
// Vérifier que l'utilisateur a bien participé à ce trajet
$sqlBooking = "
    SELECT id FROM bookings
    WHERE user_id = :user_id AND ride_id = :ride_id
";
$stmtBooking = $pdo->prepare($sqlBooking);
$stmtBooking->execute([
    'user_id' => $user['id'],
    'ride_id' => $rideId
]);
$booking = $stmtBooking->fetch();
 
if (!$booking) {
    $_SESSION['error_message'] = "Vous ne pouvez laisser un avis que sur un trajet auquel vous avez participé.";
    header("Location: dashboard.php");
    exit;
}
 
// Vérifier qu'il n'a pas déjà laissé un avis pour ce trajet
$existingReview = $reviewsCollection->findOne([
    'ride_id'   => $rideId,
    'author_id' => $user['id']
]);
 
if ($existingReview) {
    $_SESSION['error_message'] = "Vous avez déjà laissé un avis pour ce trajet.";
    header("Location: dashboard.php");
    exit;
}
 
// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $comment = trim($_POST['comment'] ?? '');
 
    if ($rating && !empty($comment)) {
        if ($rating < 1 || $rating > 5) {
            $message = "La note doit être comprise entre 1 et 5.";
        } else {
            // ✅ Insertion dans MongoDB
            $reviewsCollection->insertOne([
                'ride_id'       => $rideId,
                'author_id'     => $user['id'],
                'author_pseudo' => $user['pseudo'],
                'driver_id'     => $ride['driver_id'],
                'driver_pseudo' => $ride['driver_pseudo'],
                'rating'        => $rating,
                'comment'       => htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'),
                'status'        => 'pending',  // en attente de validation employé
                'created_at'    => new MongoDB\BSON\UTCDateTime(),
                // Infos du trajet pour affichage dans l'espace employé
                'ride_info'     => [
                    'departure_city' => $ride['departure_city'],
                    'arrival_city'   => $ride['arrival_city'],
                    'departure_time' => $ride['departure_time'],
                ]
            ]);
 
            $_SESSION['success_message'] = "Merci pour votre avis ! Il sera visible après validation.";
            header("Location: dashboard.php");
            exit;
        }
    } else {
        $message = "Veuillez compléter tous les champs.";
    }
}
?>
 
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Laisser un avis</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
 
<header class="site-header">
    <div class="container">
        <h1>EcoRide</h1>
        <nav>
            <a href="index.php">Accueil</a> |
            <a href="dashboard.php">Mon espace</a> |
            <a href="logout.php">Se déconnecter</a>
        </nav>
    </div>
</header>
 
<main class="container">
    <section class="search-card">
        <h2>
            Laisser un avis — 
            <?= htmlspecialchars($ride['departure_city']) ?> 
            → 
            <?= htmlspecialchars($ride['arrival_city']) ?>
        </h2>
        <p>Chauffeur : <strong><?= htmlspecialchars($ride['driver_pseudo']) ?></strong></p>
 
        <?php if (!empty($message)): ?>
            <div class="alert error">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
 
        <form method="POST" action="">
            <div class="form-group">
                <label for="rating">Note (1 à 5)</label>
                <select id="rating" name="rating" required>
                    <option value="">-- Choisir une note --</option>
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4">⭐⭐⭐⭐ Bien</option>
                    <option value="3">⭐⭐⭐ Correct</option>
                    <option value="2">⭐⭐ Décevant</option>
                    <option value="1">⭐ Très mauvais</option>
                </select>
            </div>
 
            <div class="form-group">
                <label for="comment">Commentaire</label>
                <textarea id="comment" name="comment" required placeholder="Décrivez votre expérience..."></textarea>
            </div>
 
            <button type="submit" class="btn btn-primary">Soumettre mon avis</button>
        </form>
    </section>
</main>
 
</body>
</html>