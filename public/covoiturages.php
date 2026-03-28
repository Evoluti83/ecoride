<?php
session_start();
require_once "../config/database.php";

// Récupérer les valeurs des filtres depuis l'URL
$departure = trim($_GET['departure'] ?? '');
$arrival   = trim($_GET['arrival']   ?? '');
$date      = $_GET['date']      ?? '';
$price     = $_GET['price']     ?? '';
$ecology   = $_GET['ecology']   ?? '';
$duration  = $_GET['duration']  ?? '';
$rating    = $_GET['rating']    ?? '';

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

// Par défaut : aucun résultat affiché si aucune recherche
$rides        = [];
$totalRides   = 0;
$totalPages   = 0;
$searched     = ($departure !== '' || $arrival !== '' || $date !== '');
$nextRideDate = null; // Pour suggérer une date si aucun résultat

if ($searched) {

    // ================================================================
    // Requête principale avec JOIN pour récupérer les infos chauffeur
    // L'aspect écologique est calculé dynamiquement via vehicles.energy
    // ================================================================
    $sql = "
        SELECT
            rides.*,
            users.pseudo        AS driver_pseudo,
            users.photo         AS driver_photo,
            vehicles.energy     AS vehicle_energy,
            (vehicles.energy = 'electrique') AS ecological,
            (
                SELECT ROUND(AVG(r2.rating), 1)
                FROM reviews r2
                WHERE r2.driver_id = rides.driver_id
                  AND r2.status = 'approved'
            ) AS driver_rating
        FROM rides
        JOIN users    ON rides.driver_id   = users.id
        JOIN vehicles ON rides.vehicle_id  = vehicles.id
        WHERE rides.available_seats > 0
          AND rides.status = 'pending'
    ";

    $params = [];

    // Filtre ville de départ
    if ($departure !== '') {
        $sql .= " AND rides.departure_city LIKE :departure";
        $params['departure'] = "%$departure%";
    }

    // Filtre ville d'arrivée
    if ($arrival !== '') {
        $sql .= " AND rides.arrival_city LIKE :arrival";
        $params['arrival'] = "%$arrival%";
    }

    // Filtre date (US3 : recherche basée sur la ville ET la date)
    if ($date !== '') {
        $sql .= " AND DATE(rides.departure_time) = :date";
        $params['date'] = $date;
    }

    // Filtres supplémentaires (US4)
    if ($price !== '') {
        $sql .= " AND rides.price <= :price";
        $params['price'] = (float)$price;
    }
    if ($ecology !== '') {
        if ($ecology == '1') {
            $sql .= " AND vehicles.energy = 'electrique'";
        } else {
            $sql .= " AND vehicles.energy != 'electrique'";
        }
    }
    if ($duration !== '') {
        $sql .= " AND rides.duration <= :duration";
        $params['duration'] = (int)$duration;
    }
    if ($rating !== '') {
        $sql .= " AND (
            SELECT AVG(r3.rating) FROM reviews r3
            WHERE r3.driver_id = rides.driver_id AND r3.status = 'approved'
        ) >= :rating";
        $params['rating'] = (float)$rating;
    }

    // Requête COUNT pour la pagination (mêmes filtres)
    $sqlCount = "
        SELECT COUNT(*)
        FROM rides
        JOIN users    ON rides.driver_id   = users.id
        JOIN vehicles ON rides.vehicle_id  = vehicles.id
        WHERE rides.available_seats > 0
          AND rides.status = 'pending'
    ";
    $countParams = [];
    if ($departure !== '') { $sqlCount .= " AND rides.departure_city LIKE :departure"; $countParams['departure'] = "%$departure%"; }
    if ($arrival   !== '') { $sqlCount .= " AND rides.arrival_city LIKE :arrival";     $countParams['arrival']   = "%$arrival%"; }
    if ($date      !== '') { $sqlCount .= " AND DATE(rides.departure_time) = :date";   $countParams['date']      = $date; }
    if ($price     !== '') { $sqlCount .= " AND rides.price <= :price";                $countParams['price']     = (float)$price; }
    if ($ecology === '1')  { $sqlCount .= " AND vehicles.energy = 'electrique'"; }
    elseif ($ecology === '0') { $sqlCount .= " AND vehicles.energy != 'electrique'"; }
    if ($duration  !== '') { $sqlCount .= " AND rides.duration <= :duration";          $countParams['duration']  = (int)$duration; }

    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($countParams);
    $totalRides = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRides / $limit);

    // Ajouter tri et pagination
    $sql .= " ORDER BY rides.departure_time ASC LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rides = $stmt->fetchAll();

    // ================================================================
    // Si aucun résultat : chercher le prochain trajet disponible
    // (US3 : proposer de modifier la date)
    // ================================================================
    if (empty($rides) && $date !== '') {
        $sqlNext = "
            SELECT DATE(rides.departure_time) AS next_date
            FROM rides
            JOIN vehicles ON rides.vehicle_id = vehicles.id
            WHERE rides.available_seats > 0
              AND rides.status = 'pending'
              AND DATE(rides.departure_time) > :date
        ";
        $nextParams = ['date' => $date];
        if ($departure !== '') { $sqlNext .= " AND rides.departure_city LIKE :departure"; $nextParams['departure'] = "%$departure%"; }
        if ($arrival   !== '') { $sqlNext .= " AND rides.arrival_city LIKE :arrival";     $nextParams['arrival']   = "%$arrival%"; }
        $sqlNext .= " ORDER BY rides.departure_time ASC LIMIT 1";

        $stmtNext = $pdo->prepare($sqlNext);
        $stmtNext->execute($nextParams);
        $nextRide = $stmtNext->fetch();
        if ($nextRide) {
            $nextRideDate = $nextRide['next_date'];
        }
    }
}

// Construire l'URL des paramètres pour la pagination
$queryParams = http_build_query(array_filter([
    'departure' => $departure,
    'arrival'   => $arrival,
    'date'      => $date,
    'price'     => $price,
    'ecology'   => $ecology,
    'duration'  => $duration,
    'rating'    => $rating,
]));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Covoiturages</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .ride-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            border-left: 5px solid #CBD5E1;
        }
        .ride-card.ecological {
            border-left-color: #2e7d32;
        }
        .ride-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .ride-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #1f2937;
            margin: 0;
        }
        .eco-badge {
            background: #E8F5E9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .non-eco-badge {
            background: #f0f0f0;
            color: #888;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .ride-info-grid {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 15px;
            align-items: center;
        }
        .driver-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .driver-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #E8F5E9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #2e7d32;
            font-size: 1.1rem;
            overflow: hidden;
            flex-shrink: 0;
        }
        .driver-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .driver-name {
            font-weight: bold;
            color: #1f2937;
        }
        .driver-rating {
            color: #888;
            font-size: 0.9rem;
        }
        .ride-details {
            text-align: center;
        }
        .ride-time {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 5px;
        }
        .ride-price {
            font-size: 1.4rem;
            font-weight: bold;
            color: #2e7d32;
        }
        .ride-seats {
            font-size: 0.9rem;
            color: #888;
        }
        .ride-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        .btn-detail {
            background: #E8F5E9;
            color: #2e7d32;
            border: 1px solid #2e7d32;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-detail:hover { background: #c8e6c9; }
        .filters-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        .search-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }
        .next-date-suggestion {
            background: #FFF3E0;
            border: 1px solid #FFB74D;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .next-date-suggestion p { margin: 0 0 10px; color: #E65100; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
        }
        .pagination a {
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #CBD5E1;
            text-decoration: none;
            color: #555;
        }
        .pagination a.active {
            background: #2e7d32;
            color: white;
            border-color: #2e7d32;
        }
        .pagination a:hover { background: #E8F5E9; }
        @media (max-width: 768px) {
            .ride-info-grid { grid-template-columns: 1fr; }
            .ride-actions { align-items: flex-start; flex-direction: row; }
            .search-grid { grid-template-columns: 1fr; }
            .filters-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container" style="margin-top: 30px;">

    <!-- FORMULAIRE DE RECHERCHE -->
    <section class="filters-card">
        <h2 style="color: #2e7d32; margin-top: 0;">🔍 Rechercher un trajet</h2>
        <form method="GET" action="covoiturages.php">
            <div class="search-grid">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="departure">Ville de départ</label>
                    <input type="text" name="departure" id="departure"
                           placeholder="Ex : Marseille"
                           value="<?= htmlspecialchars($departure) ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="arrival">Ville d'arrivée</label>
                    <input type="text" name="arrival" id="arrival"
                           placeholder="Ex : Nice"
                           value="<?= htmlspecialchars($arrival) ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="date">Date de départ</label>
                    <input type="date" name="date" id="date"
                           value="<?= htmlspecialchars($date) ?>">
                </div>
                <button type="submit" style="height: 46px;">Rechercher</button>
            </div>

            <!-- FILTRES US4 -->
            <?php if ($searched): ?>
            <div style="margin-top: 15px; border-top: 1px solid #E8F5E9; padding-top: 15px;">
                <p style="color: #888; font-size: 0.9rem; margin: 0 0 10px;"><strong>Filtres avancés :</strong></p>
                <div class="filters-grid">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="price">Prix maximum (crédits)</label>
                        <input type="number" name="price" id="price" min="0"
                               placeholder="Ex : 20"
                               value="<?= htmlspecialchars($price) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="ecology">Trajet écologique</label>
                        <select name="ecology" id="ecology">
                            <option value="">Tous</option>
                            <option value="1" <?= $ecology == '1' ? 'selected' : '' ?>>🌿 Oui (électrique)</option>
                            <option value="0" <?= $ecology == '0' ? 'selected' : '' ?>>Non</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="duration">Durée max (minutes)</label>
                        <input type="number" name="duration" id="duration" min="0"
                               placeholder="Ex : 120"
                               value="<?= htmlspecialchars($duration) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="rating">Note minimale du chauffeur</label>
                        <select name="rating" id="rating">
                            <option value="">Toutes les notes</option>
                            <option value="4" <?= $rating == '4' ? 'selected' : '' ?>>⭐⭐⭐⭐ 4+</option>
                            <option value="3" <?= $rating == '3' ? 'selected' : '' ?>>⭐⭐⭐ 3+</option>
                            <option value="2" <?= $rating == '2' ? 'selected' : '' ?>>⭐⭐ 2+</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end;">
                        <button type="submit" style="background: #E8F5E9; color: #2e7d32; border: 1px solid #2e7d32; width: 100%;">
                            Appliquer les filtres
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </section>

    <!-- RÉSULTATS -->
    <?php if (!$searched): ?>

        <!-- Aucune recherche effectuée : message d'accueil -->
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
            <div style="font-size: 3rem; margin-bottom: 15px;">🚗</div>
            <h3 style="color: #2e7d32;">Trouvez votre prochain trajet</h3>
            <p style="color: #888;">Renseignez une ville de départ, une ville d'arrivée et une date pour voir les covoiturages disponibles.</p>
        </div>

    <?php elseif (empty($rides)): ?>

        <!-- Aucun résultat trouvé -->
        <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 15px;">😔</div>
            <h3 style="color: #555;">Aucun trajet disponible</h3>
            <p style="color: #888;">
                Aucun covoiturage trouvé pour
                <?= $departure ? "<strong>" . htmlspecialchars($departure) . "</strong>" : "cette ville" ?>
                →
                <?= $arrival ? "<strong>" . htmlspecialchars($arrival) . "</strong>" : "cette ville" ?>
                <?= $date ? " le <strong>" . date('d/m/Y', strtotime($date)) . "</strong>" : "" ?>.
            </p>

            <!-- ✅ US3 : Suggérer la prochaine date disponible -->
            <?php if ($nextRideDate): ?>
                <div class="next-date-suggestion" style="display: inline-block; margin-top: 15px;">
                    <p>💡 Le prochain trajet disponible sur cet itinéraire est le <strong><?= date('d/m/Y', strtotime($nextRideDate)) ?></strong></p>
                    <a href="covoiturages.php?departure=<?= urlencode($departure) ?>&arrival=<?= urlencode($arrival) ?>&date=<?= $nextRideDate ?>"
                       style="background: #2e7d32; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold;">
                        Voir les trajets du <?= date('d/m/Y', strtotime($nextRideDate)) ?>
                    </a>
                </div>
            <?php else: ?>
                <p style="color: #aaa; margin-top: 10px;">Aucun trajet prévu sur cet itinéraire pour le moment.</p>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <!-- Résultats trouvés -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <p style="color: #555; margin: 0;">
                <strong><?= $totalRides ?></strong> trajet<?= $totalRides > 1 ? 's' : '' ?> disponible<?= $totalRides > 1 ? 's' : '' ?>
            </p>
        </div>

        <?php foreach ($rides as $ride): ?>

            <div class="ride-card <?= $ride['ecological'] ? 'ecological' : '' ?>">
                <div class="ride-card-header">
                    <h3 class="ride-title">
                        <?= htmlspecialchars($ride['departure_city']) ?>
                        →
                        <?= htmlspecialchars($ride['arrival_city']) ?>
                    </h3>
                    <?php if ($ride['ecological']): ?>
                        <span class="eco-badge">🌿 Trajet écologique</span>
                    <?php else: ?>
                        <span class="non-eco-badge">Trajet standard</span>
                    <?php endif; ?>
                </div>

                <div class="ride-info-grid">

                    <!-- Infos chauffeur -->
                    <div class="driver-info">
                        <div class="driver-avatar">
                            <?php if (!empty($ride['driver_photo'])): ?>
                                <img src="<?= htmlspecialchars($ride['driver_photo']) ?>"
                                     alt="<?= htmlspecialchars($ride['driver_pseudo']) ?>">
                            <?php else: ?>
                                <?= strtoupper(substr($ride['driver_pseudo'], 0, 2)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="driver-name"><?= htmlspecialchars($ride['driver_pseudo']) ?></div>
                            <div class="driver-rating">
                                <?php if ($ride['driver_rating']): ?>
                                    ⭐ <?= htmlspecialchars($ride['driver_rating']) ?> / 5
                                <?php else: ?>
                                    ⭐ Pas encore d'avis
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Détails du trajet -->
                    <div class="ride-details">
                        <div class="ride-time">
                            🕐 Départ : <strong><?= date('d/m/Y à H\hi', strtotime($ride['departure_time'])) ?></strong>
                        </div>
                        <div class="ride-time">
                            🏁 Arrivée : <strong><?= date('d/m/Y à H\hi', strtotime($ride['arrival_time'])) ?></strong>
                        </div>
                        <div style="color: #888; font-size: 0.85rem; margin-top: 5px;">
                            🪑 <?= htmlspecialchars($ride['available_seats']) ?> place<?= $ride['available_seats'] > 1 ? 's' : '' ?> disponible<?= $ride['available_seats'] > 1 ? 's' : '' ?>
                        </div>
                    </div>

                    <!-- Prix et bouton -->
                    <div class="ride-actions">
                        <div class="ride-price"><?= htmlspecialchars($ride['price']) ?> crédits</div>
                        <a href="ride-detail.php?ride_id=<?= (int)$ride['id'] ?>" class="btn-detail">
                            Détail →
                        </a>
                    </div>

                </div>
            </div>

        <?php endforeach; ?>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="covoiturages.php?page=<?= $page - 1 ?>&<?= $queryParams ?>">&laquo; Précédent</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="covoiturages.php?page=<?= $i ?>&<?= $queryParams ?>"
                   class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="covoiturages.php?page=<?= $page + 1 ?>&<?= $queryParams ?>">Suivant &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</main>

<?php require_once "footer.php"; ?>

</body>
</html>