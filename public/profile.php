<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user    = $_SESSION['user'];
$message = '';

// Récupérer les infos complètes de l'utilisateur depuis la BDD
$sqlUser = "SELECT * FROM users WHERE id = :id";
$stmtUser = $pdo->prepare($sqlUser);
$stmtUser->execute(['id' => $user['id']]);
$userFull = $stmtUser->fetch();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isDriver    = isset($_POST['is_driver'])    ? 1 : 0;
    $isPassenger = isset($_POST['is_passenger']) ? 1 : 0;
    $pseudo      = trim($_POST['pseudo']   ?? $userFull['pseudo']);
    $firstname   = trim($_POST['firstname'] ?? '');
    $lastname    = trim($_POST['lastname']  ?? '');
    $phone       = trim($_POST['phone']     ?? '');

    // Au moins un rôle obligatoire
    if (!$isDriver && !$isPassenger) {
        $message = "Vous devez sélectionner au moins un rôle (chauffeur ou passager).";
    } else {
        $sqlUpdate = "
            UPDATE users SET
                pseudo       = :pseudo,
                firstname    = :firstname,
                lastname     = :lastname,
                phone        = :phone,
                is_driver    = :is_driver,
                is_passenger = :is_passenger
            WHERE id = :id
        ";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'pseudo'       => $pseudo,
            'firstname'    => $firstname,
            'lastname'     => $lastname,
            'phone'        => $phone,
            'is_driver'    => $isDriver,
            'is_passenger' => $isPassenger,
            'id'           => $user['id']
        ]);

        // Mettre à jour la session
        $_SESSION['user']['pseudo'] = $pseudo;

        // Recharger les données
        $stmtUser->execute(['id' => $user['id']]);
        $userFull = $stmtUser->fetch();

        $message = "Profil mis à jour avec succès !";
    }
}

// Récupérer les véhicules de l'utilisateur
$sqlVehicles = "SELECT * FROM vehicles WHERE user_id = :user_id";
$stmtVehicles = $pdo->prepare($sqlVehicles);
$stmtVehicles->execute(['user_id' => $user['id']]);
$vehicles = $stmtVehicles->fetchAll();

// Récupérer les préférences
$sqlPrefs = "SELECT * FROM preferences WHERE user_id = :user_id LIMIT 1";
$stmtPrefs = $pdo->prepare($sqlPrefs);
$stmtPrefs->execute(['user_id' => $user['id']]);
$preferences = $stmtPrefs->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Mon profil</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container">

    <?php if (!empty($message)): ?>
        <div class="alert <?= str_contains($message, 'succès') ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire profil -->
    <section class="search-card">
        <h2>Mon profil</h2>

        <form method="POST" action="">
            <div class="form-group">
                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo"
                       value="<?= htmlspecialchars($userFull['pseudo']) ?>" required>
            </div>

            <div class="form-group">
                <label for="firstname">Prénom</label>
                <input type="text" id="firstname" name="firstname"
                       value="<?= htmlspecialchars($userFull['firstname'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="lastname">Nom</label>
                <input type="text" id="lastname" name="lastname"
                       value="<?= htmlspecialchars($userFull['lastname'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone"
                       value="<?= htmlspecialchars($userFull['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Mon rôle</label>
                <label>
                    <input type="checkbox" name="is_driver"
                           <?= $userFull['is_driver']    ? 'checked' : '' ?>>
                    🚗 Je suis chauffeur
                </label>
                <br>
                <label>
                    <input type="checkbox" name="is_passenger"
                           <?= $userFull['is_passenger'] ? 'checked' : '' ?>>
                    🧳 Je suis passager
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </section>

    <!-- Véhicules (visible uniquement si chauffeur) -->
    <?php if ($userFull['is_driver']): ?>
    <section class="search-card">
        <h2>Mes véhicules</h2>

        <?php if (empty($vehicles)): ?>
            <p>Aucun véhicule enregistré. Ajoutez-en un pour proposer des trajets.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($vehicles as $v): ?>
                    <li>
                        🚙 <strong><?= htmlspecialchars($v['brand']) ?> <?= htmlspecialchars($v['model']) ?></strong>
                        — <?= htmlspecialchars($v['color']) ?>
                        — <?= htmlspecialchars($v['registration']) ?>
                        — <?= htmlspecialchars($v['energy']) ?>
                        — <?= htmlspecialchars($v['seats']) ?> places
                        <a href="add-vehicle.php?delete=<?= (int)$v['id'] ?>"
                           onclick="return confirm('Supprimer ce véhicule ?')"
                           style="color:red; margin-left:10px;">Supprimer</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <a href="add-vehicle.php" class="btn">+ Ajouter un véhicule</a>
    </section>

    <!-- Préférences (visible uniquement si chauffeur) -->
    <section class="search-card">
        <h2>Mes préférences</h2>
        <a href="preferences.php" class="btn">
            <?= $preferences ? 'Modifier mes préférences' : 'Définir mes préférences' ?>
        </a>
    </section>
    <?php endif; ?>

</main>

</body>
</html>