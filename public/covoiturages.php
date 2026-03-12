<?php
session_start();
require_once "../config/database.php";

// Récupérer les valeurs des filtres depuis l'URL
$departure = $_GET['departure'] ?? '';
$arrival = $_GET['arrival'] ?? '';
$date = $_GET['date'] ?? '';
$price = $_GET['price'] ?? '';
$ecology = $_GET['ecology'] ?? '';
$duration = $_GET['duration'] ?? '';
$rating = $_GET['rating'] ?? '';

// Validation et sécurisation des entrées utilisateur
$departure = htmlspecialchars($departure);
$arrival = htmlspecialchars($arrival);
$date = htmlspecialchars($date);
$price = is_numeric($price) ? (int) $price : '';  // Assurer que price est un nombre
$ecology = $ecology === '1' || $ecology === '0' ? $ecology : '';
$duration = is_numeric($duration) ? (int) $duration : '';
$rating = is_numeric($rating) && $rating >= 1 && $rating <= 5 ? (int) $rating : '';

// Pagination : déterminer la page actuelle et le nombre d'éléments par page
$page = $_GET['page'] ?? 1;
$limit = 10;  // Nombre de trajets par page
$offset = ($page - 1) * $limit;

// Construction de la requête SQL avec filtres
$sql = "SELECT * FROM rides WHERE departure_city LIKE :departure AND arrival_city LIKE :arrival";

// Ajouter des filtres supplémentaires à la requête si spécifiés
if ($price) {
    $sql .= " AND price <= :price";
}
if ($ecology !== '') {
    $sql .= " AND ecological = :ecology";
}
if ($duration) {
    $sql .= " AND duration <= :duration";
}
if ($rating) {
    $sql .= " AND rating >= :rating";
}

// Ajouter la pagination à la requête SQL
$sql .= " LIMIT :limit OFFSET :offset";

// Préparer la requête SQL
$stmt = $pdo->prepare($sql);

// Lier les paramètres de la requête SQL
$params = [
    'departure' => "%$departure%",
    'arrival' => "%$arrival%",
    'limit' => $limit,
    'offset' => $offset
];

// Ajouter les filtres aux paramètres SQL si définis
if ($price) {
    $params['price'] = $price;
}
if ($ecology !== '') {
    $params['ecology'] = $ecology;
}
if ($duration) {
    $params['duration'] = $duration;
}
if ($rating) {
    $params['rating'] = $rating;
}

// Exécuter la requête
$stmt->execute($params);

// Récupérer tous les trajets correspondant aux filtres
$rides = $stmt->fetchAll();

// Récupérer le nombre total de trajets pour la pagination
$sqlCount = "SELECT COUNT(*) FROM rides WHERE departure_city LIKE :departure AND arrival_city LIKE :arrival";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute([
    'departure' => "%$departure%",
    'arrival' => "%$arrival%"
]);

$totalRides = $stmtCount->fetchColumn();
$totalPages = ceil($totalRides / $limit);
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

    <!-- Formulaire de recherche avec filtres -->
    <h2>Rechercher un trajet</h2>
    <form method="GET" action="covoiturages.php">
        <label for="departure">Ville de départ :</label>
        <input type="text" name="departure" id="departure" value="<?= htmlspecialchars($departure) ?>">

        <label for="arrival">Ville d'arrivée :</label>
        <input type="text" name="arrival" id="arrival" value="<?= htmlspecialchars($arrival) ?>">

        <label for="price">Prix max :</label>
        <input type="number" name="price" id="price" min="0" value="<?= htmlspecialchars($price) ?>">

        <label for="ecology">Trajet écologique :</label>
        <select name="ecology" id="ecology">
            <option value="">Tous</option>
            <option value="1" <?= $ecology == '1' ? 'selected' : '' ?>>Oui</option>
            <option value="0" <?= $ecology == '0' ? 'selected' : '' ?>>Non</option>
        </select>

        <label for="duration">Durée max (minutes) :</label>
        <input type="number" name="duration" id="duration" min="0" value="<?= htmlspecialchars($duration) ?>">

        <label for="rating">Note min :</label>
        <input type="number" name="rating" id="rating" min="1" max="5" value="<?= htmlspecialchars($rating) ?>">

        <button type="submit">Rechercher</button>
    </form>

    <!-- Affichage des résultats -->
    <h2>Résultats de recherche</h2>

    <?php if (empty($rides)): ?>

        <p>Aucun trajet trouvé.</p>

    <?php else: ?>

        <ul>

            <?php foreach ($rides as $ride): ?>

                <li>
                    <strong>
                        <?= htmlspecialchars($ride['departure_city']) ?>
                        →
                        <?= htmlspecialchars($ride['arrival_city']) ?>
                    </strong>

                    <br>
                    Prix : <?= htmlspecialchars($ride['price']) ?> crédits

                    <br>
                    Places disponibles : <?= htmlspecialchars($ride['available_seats']) ?>

                    <br><br>

                    <a href="book-ride.php?ride_id=<?= htmlspecialchars($ride['id']) ?>">
                        Réserver
                    </a>

                </li>

            <?php endforeach; ?>

        </ul>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="covoiturages.php?page=<?= $page - 1 ?>">&laquo; Précédent</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="covoiturages.php?page=<?= $i ?>" <?= ($i == $page) ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="covoiturages.php?page=<?= $page + 1 ?>">Suivant &raquo;</a>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</main>

</body>
</html>