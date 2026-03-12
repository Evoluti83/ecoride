<?php

session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$rideId = $_GET['ride_id'] ?? null;

if (!$rideId) {
    header("Location: index.php");
    exit;
}

$sqlRide = "SELECT * FROM rides WHERE id = :ride_id";
$stmtRide = $pdo->prepare($sqlRide);
$stmtRide->execute(['ride_id' => $rideId]);
$ride = $stmtRide->fetch();

if (!$ride) {
    die("Ce trajet n'existe pas.");
}

$message = '';

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? null;
    $comment = $_POST['comment'] ?? '';

    if ($rating && !empty($comment)) {
        $sqlReview = "INSERT INTO reviews (ride_id, author_id, driver_id, rating, comment, status)
                      VALUES (:ride_id, :author_id, :driver_id, :rating, :comment, 'pending')";

        $stmtReview = $pdo->prepare($sqlReview);
        $stmtReview->execute([
            'ride_id' => $rideId,
            'author_id' => $user['id'],
            'driver_id' => $ride['driver_id'],
            'rating' => $rating,
            'comment' => $comment
        ]);

        $message = "Merci pour votre avis. Il sera soumis à validation.";
    } else {
        $message = "Veuillez compléter tous les champs.";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EcoRide - Laisser un avis</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <h1>EcoRide</h1>
        <p>Laissez votre avis sur le trajet</p>
    </div>
</header>

<main class="container">
    <section class="search-card">
        <h2>Donner un avis pour le trajet <?= htmlspecialchars($ride['departure_city']) ?> → <?= htmlspecialchars($ride['arrival_city']) ?></h2>

        <?php if (!empty($message)): ?>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="rating">Note (sur 5)</label>
                <input type="number" id="rating" name="rating" min="1" max="5" required>
            </div>

            <div class="form-group">
                <label for="comment">Commentaire</label>
                <textarea id="comment" name="comment" required></textarea>
            </div>

            <button type="submit">Soumettre l'avis</button>
        </form>
    </section>
</main>

</body>
</html>