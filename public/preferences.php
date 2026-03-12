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
    $_SESSION['error_message'] = "Seuls les chauffeurs peuvent définir des préférences.";
    header("Location: profile.php");
    exit;
}

// Récupérer les préférences existantes
$sqlPrefs = "SELECT * FROM preferences WHERE user_id = :user_id LIMIT 1";
$stmtPrefs = $pdo->prepare($sqlPrefs);
$stmtPrefs->execute(['user_id' => $user['id']]);
$prefs = $stmtPrefs->fetch();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smoking          = isset($_POST['smoking'])  ? 1 : 0;
    $animals          = isset($_POST['animals'])  ? 1 : 0;
    $customPreference = trim($_POST['custom_preference'] ?? '');

    if ($prefs) {
        // Mise à jour
        $sqlSave = "
            UPDATE preferences SET
                smoking           = :smoking,
                animals           = :animals,
                custom_preference = :custom_preference
            WHERE user_id = :user_id
        ";
    } else {
        // Insertion
        $sqlSave = "
            INSERT INTO preferences (user_id, smoking, animals, custom_preference)
            VALUES (:user_id, :smoking, :animals, :custom_preference)
        ";
    }

    $stmtSave = $pdo->prepare($sqlSave);
    $stmtSave->execute([
        'user_id'          => $user['id'],
        'smoking'          => $smoking,
        'animals'          => $animals,
        'custom_preference'=> htmlspecialchars($customPreference)
    ]);

    // Recharger les préférences
    $stmtPrefs->execute(['user_id' => $user['id']]);
    $prefs = $stmtPrefs->fetch();

    $message = "Préférences enregistrées avec succès !";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Mes préférences</title>
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
        <h2>Mes préférences de trajet</h2>
        <p>Ces préférences seront visibles par les passagers sur la page de détail du trajet.</p>

        <?php if (!empty($message)): ?>
            <div class="alert success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>🚬 Fumeur</label>
                <label>
                    <input type="checkbox" name="smoking"
                           <?= ($prefs && $prefs['smoking']) ? 'checked' : '' ?>>
                    J'accepte les fumeurs
                </label>
            </div>

            <div class="form-group">
                <label>🐾 Animaux</label>
                <label>
                    <input type="checkbox" name="animals"
                           <?= ($prefs && $prefs['animals']) ? 'checked' : '' ?>>
                    J'accepte les animaux
                </label>
            </div>

            <div class="form-group">
                <label for="custom_preference">📝 Préférence personnalisée</label>
                <input type="text" id="custom_preference" name="custom_preference"
                       placeholder="Ex : Musique douce, pas de nourriture..."
                       value="<?= htmlspecialchars($prefs['custom_preference'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary">Enregistrer mes préférences</button>
            <a href="profile.php" class="btn">Retour au profil</a>
        </form>
    </section>
</main>

</body>
</html>