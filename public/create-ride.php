<?php
session_start();
require_once "../config/database.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// ✅ US9 : Vérifier que l'utilisateur est chauffeur
$sqlCheck = "SELECT is_driver FROM users WHERE id = :id";
$stmtCheck = $pdo->prepare($sqlCheck);
$stmtCheck->execute(['id' => $user['id']]);
$userCheck = $stmtCheck->fetch();

if (!$userCheck['is_driver']) {
    $_SESSION['error_message'] = "Vous devez être chauffeur pour proposer un trajet. Mettez à jour votre profil.";
    header("Location: profile.php");
    exit;
}

// Récupérer les véhicules de l'utilisateur
$sqlVehicles = "SELECT * FROM vehicles WHERE user_id = :user_id";
$stmtVehicles = $pdo->prepare($sqlVehicles);
$stmtVehicles->execute(['user_id' => $user['id']]);
$vehicles = $stmtVehicles->fetchAll();

$message = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId      = $_POST['vehicle_id']      ?? '';
    $departureCity  = trim($_POST['departure_city']  ?? '');
    $arrivalCity    = trim($_POST['arrival_city']    ?? '');
    $departureTime  = $_POST['departure_time']  ?? '';
    $arrivalTime    = $_POST['arrival_time']    ?? '';
    $price          = $_POST['price']           ?? '';
    $availableSeats = $_POST['available_seats'] ?? '';

    // Validation
    if (empty($vehicleId))      $errors[] = "Veuillez sélectionner un véhicule.";
    if (empty($departureCity))  $errors[] = "La ville de départ est obligatoire.";
    if (empty($arrivalCity))    $errors[] = "La ville d'arrivée est obligatoire.";
    if (empty($departureTime))  $errors[] = "La date et heure de départ sont obligatoires.";
    if (empty($arrivalTime))    $errors[] = "La date et heure d'arrivée sont obligatoires.";
    if ($price === '' || !is_numeric($price) || $price < 0) {
        $errors[] = "Le prix doit être un nombre positif.";
    }
    if ($availableSeats === '' || !is_numeric($availableSeats) || $availableSeats < 1) {
        $errors[] = "Le nombre de places doit être au moins 1.";
    }
    if (!empty($departureTime) && !empty($arrivalTime)) {
        if (strtotime($arrivalTime) <= strtotime($departureTime)) {
            $errors[] = "L'heure d'arrivée doit être après l'heure de départ.";
        }
        if (strtotime($departureTime) < time()) {
            $errors[] = "La date de départ ne peut pas être dans le passé.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Calculer la durée en minutes
            $duration = round((strtotime($arrivalTime) - strtotime($departureTime)) / 60);

            $sql = "INSERT INTO rides
                (driver_id, vehicle_id, departure_city, arrival_city,
                 departure_time, arrival_time, price, available_seats, status, duration)
                VALUES
                (:driver_id, :vehicle_id, :departure_city, :arrival_city,
                 :departure_time, :arrival_time, :price, :available_seats, 'pending', :duration)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'driver_id'      => $user['id'],
                'vehicle_id'     => (int)$vehicleId,
                'departure_city' => $departureCity,
                'arrival_city'   => $arrivalCity,
                'departure_time' => $departureTime,
                'arrival_time'   => $arrivalTime,
                'price'          => (float)$price,
                'available_seats'=> (int)$availableSeats,
                'duration'       => $duration,
            ]);

            $pdo->commit();

            $_SESSION['success_message'] = "Trajet créé avec succès ! 🚗";
            header("Location: dashboard.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Proposer un trajet</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container" style="margin-top: 30px; max-width: 700px;">

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <strong>Erreurs :</strong>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="search-card">
        <h2 style="color: #2e7d32;">🚗 Proposer un trajet</h2>

        <!-- ✅ US9 : Info sur les 2 crédits plateforme -->
        <div style="background: #FFF3E0; border: 1px solid #FFB74D; border-radius: 8px; padding: 12px 15px; margin-bottom: 20px;">
            <p style="margin: 0; color: #E65100; font-size: 0.9rem;">
                💡 <strong>Information :</strong> La plateforme prélève <strong>2 crédits par passager</strong> pour garantir le bon fonctionnement du service.
                Si vous fixez un prix de 10 crédits, vous recevrez <strong>8 crédits</strong> par passager.
            </p>
        </div>

        <?php if (empty($vehicles)): ?>
            <div class="alert error">
                Vous n'avez pas encore de véhicule enregistré.
                <a href="add-vehicle.php" style="color: #2e7d32; font-weight: bold;">Ajouter un véhicule →</a>
            </div>
        <?php else: ?>

        <form method="POST" action="">

            <!-- Sélection du véhicule -->
            <div class="form-group">
                <label for="vehicle_id">Véhicule <span style="color: #C62828;">*</span></label>
                <select id="vehicle_id" name="vehicle_id" required onchange="updateEcoInfo(this)">
                    <option value="">-- Choisir un véhicule --</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= (int)$vehicle['id'] ?>"
                                data-energy="<?= htmlspecialchars($vehicle['energy']) ?>"
                                <?= (isset($_POST['vehicle_id']) && $_POST['vehicle_id'] == $vehicle['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vehicle['brand']) ?>
                            <?= htmlspecialchars($vehicle['model']) ?>
                            (<?= htmlspecialchars($vehicle['registration']) ?>)
                            — <?= htmlspecialchars($vehicle['energy']) ?>
                            <?= $vehicle['energy'] === 'electrique' ? '🌿' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- Indicateur écologique automatique -->
                <div id="eco-info" style="margin-top: 6px; font-size: 0.85rem; color: #888;"></div>
            </div>

            <!-- ✅ US9 : Lien pour ajouter un nouveau véhicule -->
            <p style="font-size: 0.85rem; color: #888; margin-top: -10px; margin-bottom: 15px;">
                Votre véhicule n'est pas dans la liste ?
                <a href="add-vehicle.php" style="color: #2e7d32;">Ajouter un nouveau véhicule →</a>
            </p>

            <div class="form-group">
                <label for="departure_city">Ville de départ <span style="color: #C62828;">*</span></label>
                <input type="text" id="departure_city" name="departure_city" required
                       placeholder="Ex : Marseille"
                       value="<?= htmlspecialchars($_POST['departure_city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="arrival_city">Ville d'arrivée <span style="color: #C62828;">*</span></label>
                <input type="text" id="arrival_city" name="arrival_city" required
                       placeholder="Ex : Nice"
                       value="<?= htmlspecialchars($_POST['arrival_city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="departure_time">Date et heure de départ <span style="color: #C62828;">*</span></label>
                <input type="datetime-local" id="departure_time" name="departure_time" required
                       value="<?= htmlspecialchars($_POST['departure_time'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="arrival_time">Date et heure d'arrivée <span style="color: #C62828;">*</span></label>
                <input type="datetime-local" id="arrival_time" name="arrival_time" required
                       value="<?= htmlspecialchars($_POST['arrival_time'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="price">Prix par personne (crédits) <span style="color: #C62828;">*</span></label>
                <input type="number" id="price" name="price" min="2" required
                       placeholder="Min. 2 crédits (2 reviennent à la plateforme)"
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                       oninput="updateNetPrice(this.value)">
                <div id="net-price" style="margin-top: 6px; font-size: 0.85rem; color: #2e7d32;"></div>
            </div>

            <div class="form-group">
                <label for="available_seats">Nombre de places disponibles <span style="color: #C62828;">*</span></label>
                <input type="number" id="available_seats" name="available_seats" min="1" max="8" required
                       placeholder="Ex : 3"
                       value="<?= htmlspecialchars($_POST['available_seats'] ?? '') ?>">
            </div>

            <button type="submit">Créer le trajet 🚗</button>
            <a href="dashboard.php"
               style="display: inline-block; margin-left: 15px; color: #888; text-decoration: none;">
                Annuler
            </a>
        </form>

        <?php endif; ?>
    </section>
</main>

<footer style="background: #1f2937; color: #aaa; text-align: center; padding: 20px; margin-top: 40px;">
    <p>EcoRide — <a href="mailto:contact@ecoride.fr" style="color: #4CAF50;">contact@ecoride.fr</a> — <a href="mentions-legales.php" style="color: #4CAF50;">Mentions légales</a></p>
</footer>

<script>
// Afficher si le véhicule est écologique
function updateEcoInfo(select) {
    const option = select.options[select.selectedIndex];
    const energy = option.getAttribute('data-energy');
    const info   = document.getElementById('eco-info');

    if (!energy) {
        info.textContent = '';
        return;
    }
    if (energy === 'electrique') {
        info.innerHTML = '🌿 <strong style="color:#2e7d32;">Ce trajet sera écologique</strong> (véhicule électrique)';
    } else {
        info.innerHTML = '⚠️ Ce trajet ne sera pas écologique (énergie : ' + energy + ')';
        info.style.color = '#888';
    }
}

// Afficher le net reçu par le chauffeur
function updateNetPrice(value) {
    const net  = document.getElementById('net-price');
    const price = parseInt(value);
    if (isNaN(price) || price < 2) {
        net.textContent = '';
        return;
    }
    const received = price - 2;
    net.innerHTML = 'Vous recevrez <strong>' + received + ' crédits</strong> par passager (après prélèvement de 2 crédits plateforme)';
}
</script>

</body>
</html>