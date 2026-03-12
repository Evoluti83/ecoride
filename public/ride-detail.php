<?php
// Récupérer les avis validés pour ce trajet
$sqlReviews = "SELECT * FROM reviews WHERE ride_id = :ride_id AND status = 'approved'";
$stmtReviews = $pdo->prepare($sqlReviews);
$stmtReviews->execute(['ride_id' => $rideId]);
$reviews = $stmtReviews->fetchAll();

if (empty($reviews)) {
    echo "<p>Aucun avis disponible pour ce trajet.</p>";
} else {
    foreach ($reviews as $review) {
        echo "<div class='review'>";
        echo "<strong>Note : " . htmlspecialchars($review['rating']) . "/5</strong><br>";
        echo "<p>" . htmlspecialchars($review['comment']) . "</p>";
        echo "<p><i>Avis de : " . htmlspecialchars($review['author_id']) . "</i></p>";
        echo "</div><br>";
    }
}
?>