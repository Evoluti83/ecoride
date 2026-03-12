<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user    = $_SESSION['user'];
$message = '';

// Vérifier que l'utilisateur est chauffeur
$sqlCheck = "SELECT is_driver FROM users WHERE id = :id";
$stmtCheck = $pdo->prepare($sqlCheck);
$stmtCheck->execute(['id' => $user['id']]);
$userCheck = $stmtCheck->fetch();

if (!$userCheck['is_driver']) {
    $_SESSION['error_message'] = "Vous devez être chauffeur pour ajouter un véhicule.";
    header("Location: profile.php");
    exit;
}

// Suppression d'un véhicule
if (isset($_GET['delete'])) {
    $vehicleId = (int)$_GET['delete'];

    // Vérifier que le véhicule appartient bien à l'utilisateur
    $sqlOwner = "SELECT id FROM vehicles WHERE id = :id AND user_id = :user_id";
    $stmtOwner = $pdo->prepare($sqlOwner);
    $stmtOwner->execute(['id' => $vehicleId, 'user_id' => $user['id']]);

    if ($stmtOwner->fetch()) {
        $sqlDelete = "DELETE FROM vehicles WHERE id = :id";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute(['id' => $vehicleId]);
        $_SESSION['success_message'] = "Véhicule supprimé.";
    }

    header("Location: profile.php");
    exit;
}

// Ajout d'un véhicule
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand                  = trim($_POST['brand']                   ?? '');
    $model                  = trim($_POST['model']                   ?? '');
    $color                  = trim($_POST['color']                   ?? '');
    $registration           = trim($_POST['registration']            ?? '');
    $firstRegistrationDate  = $_POST['first_registration_date']      ?? '';
    $seats                  = (int)($_POST['seats']                  ?? 0);
    $energy                 = $_POST['energy']                       ?? '';

    $validEnergies = ['essence', 'diesel', 'hybride', 'electrique'];

    if (
        empty($brand) || empty($model) || empty($color) ||
        empty($registration) || empty($firstRegistrationDate) ||
        $seats < 1 || !in_array($energy, $validEnergies)
    ) {
        $message = "Veuillez remplir tous les champs correctement.";
    } else {
        $sqlInsert = "
            INSERT INTO vehicles
                (user_id, brand, model, color, registration, first_registration_date, seats, energy)
            VALUES
                (:user_id, :brand, :model, :color, :registration, :first_registration_date, :seats, :energy)
        ";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            'user_id'                => $user['id'],
            'brand'                  => $brand,
            'model'                  => $model,
            'color'                  => $color,
            'registration'           => strtoupper($registration),
            'first_registration_date'=> $firstRegistrationDate,
            'seats'                  => $seats,
            'energy'                 => $energy
        ]);

        $_SESSION['success_message'] = "Véhicule ajouté avec succès !";
        header("Location: profile.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Ajouter un véhicule</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <h1>EcoRide</h1>
        <nav>
            <a href="index.php">Accueil</a> |
            <a href="dashboard.php">Mon espace</a> |
            <a href="profile.php">Mon profil</a> |
            <a href="logout.php">Se déconnecter</a>
        </nav>
    </div>
</header>

<main class="container">
    <section class="search-card">
        <h2>Ajouter un véhicule</h2>

        <?php if (!empty($message)): ?>
            <div class="alert error">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="brand">Marque</label>
                <input type="text" id="brand" name="brand" required placeholder="Ex : Renault">
            </div>

            <div class="form-group">
                <label for="model">Modèle</label>
                <input type="text" id="model" name="model" required placeholder="Ex : Clio">
            </div>

            <div class="form-group">
                <label for="color">Couleur</label>
                <input type="text" id="color" name="color" required placeholder="Ex : Bleu">
            </div>

            <div class="form-group">
                <label for="registration">Plaque d'immatriculation</label>
                <input type="text" id="registration" name="registration" required
                       placeholder="Ex : AB-123-CD"
                       pattern="[A-Za-z]{2}-[0-9]{3}-[A-Za-z]{2}"
                       title="Format : AB-123-CD">
            </div>

            <div class="form-group">
                <label for="first_registration_date">Date de première immatriculation</label>
                <input type="date" id="first_registration_date" name="first_registration_date" required>
            </div>

            <div class="form-group">
                <label for="seats">Nombre de places</label>
                <input type="number" id="seats" name="seats" min="1" max="9" required>
            </div>

            <div class="form-group">
                <label for="energy">Énergie</label>
                <select id="energy" name="energy" required>
                    <option value="">-- Choisir --</option>
                    <option value="essence">Essence</option>
                    <option value="diesel">Diesel</option>
                    <option value="hybride">Hybride</option>
                    <option value="electrique">⚡ Électrique</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Ajouter le véhicule</button>
            <a href="profile.php" class="btn">Annuler</a>
        </form>
    </section>
</main>

</body>
</html>