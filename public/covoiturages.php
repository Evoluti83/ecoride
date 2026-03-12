<?php

require_once "../config/database.php";

$departure = $_GET['departure'] ?? '';
$arrival = $_GET['arrival'] ?? '';
$date = $_GET['date'] ?? '';

$sql = "SELECT * FROM rides 
        WHERE departure_city LIKE :departure 
        AND arrival_city LIKE :arrival";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'departure' => "%$departure%",
    'arrival' => "%$arrival%"
]);

$rides = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Résultats covoiturage</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header class="site-header">
<h1>EcoRide</h1>
</header>

<main class="container">

<h2>Résultats de recherche</h2>

<?php if(empty($rides)): ?>

<p>Aucun trajet trouvé.</p>

<?php else: ?>

<ul>

<?php foreach($rides as $ride): ?>

<li>

<strong>
<?= htmlspecialchars($ride['departure_city']) ?>
→
<?= htmlspecialchars($ride['arrival_city']) ?>
</strong>

<br>
Prix : <?= $ride['price'] ?> crédits

<br>
Places disponibles : <?= $ride['available_seats'] ?>

<br><br>

<a href="book-ride.php?ride_id=<?= $ride['id'] ?>">
Réserver
</a>

</li>

<?php endforeach; ?>

</ul>

<?php endif; ?>

</main>

</body>
</html>