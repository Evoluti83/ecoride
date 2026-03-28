<?php
session_start();
require_once "../config/database.php";
require_once "../config/mongodb.php";

// Vérifier que c'est bien un employé
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}

$message = '';

// Traitement approbation / rejet d'un avis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = $_POST['review_id'] ?? null;
    $action   = $_POST['action']    ?? '';

    if ($reviewId && in_array($action, ['approve', 'reject'])) {
        try {
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

            $reviewsCollection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($reviewId)],
                ['$set' => [
                    'status'      => $newStatus,
                    'reviewed_at' => new MongoDB\BSON\UTCDateTime(),
                    'reviewed_by' => $_SESSION['user']['pseudo']
                ]]
            );

            $message = "Avis " . ($action === 'approve' ? 'approuvé' : 'rejeté') . " avec succès.";

        } catch (Exception $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// ✅ Récupérer les avis en attente depuis MongoDB
$pendingReviews = $reviewsCollection->find(
    ['status' => 'pending'],
    ['sort' => ['created_at' => -1]]
);

// ✅ US12 : Récupérer les covoiturages qui se sont mal passés depuis MongoDB
$problemReviews = $reviewsCollection->find(
    ['status' => 'problem'],
    ['sort' => ['created_at' => -1]]
);

// Récupérer les infos des utilisateurs (passager + chauffeur) depuis MySQL pour les problèmes
// On va chercher les emails via les IDs stockés dans MongoDB
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Gestion des avis</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container" style="margin-top: 30px;">

    <?php if (!empty($message)): ?>
        <div class="alert <?= str_contains($message, 'succès') ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- SECTION 1 : Avis en attente de validation -->
    <!-- ============================================================ -->
    <section class="search-card">
        <h2 style="color: #2e7d32;">⭐ Avis en attente de validation</h2>

        <?php
        $hasPending = false;
        foreach ($pendingReviews as $review):
            $hasPending = true;
            $reviewId   = (string)$review['_id'];
        ?>
            <div style="border: 1px solid #E8F5E9; border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                    <div>
                        <p style="margin: 0 0 5px;"><strong>Trajet :</strong>
                            <?= htmlspecialchars($review['ride_info']['departure_city'] ?? 'N/A') ?>
                            →
                            <?= htmlspecialchars($review['ride_info']['arrival_city'] ?? 'N/A') ?>
                        </p>
                        <p style="margin: 0 0 5px; color: #888; font-size: 0.85rem;">
                            <?= htmlspecialchars($review['ride_info']['departure_time'] ?? '') ?>
                        </p>
                    </div>
                    <div>
                        <p style="margin: 0 0 5px;"><strong>Auteur :</strong> <?= htmlspecialchars($review['author_pseudo'] ?? '') ?></p>
                        <p style="margin: 0 0 5px;"><strong>Chauffeur :</strong> <?= htmlspecialchars($review['driver_pseudo'] ?? '') ?></p>
                    </div>
                </div>
                <p style="margin: 0 0 10px;">
                    <strong>Note :</strong> <?= htmlspecialchars((string)$review['rating']) ?>/5 &nbsp;
                    <strong>Commentaire :</strong> <?= htmlspecialchars($review['comment'] ?? '') ?>
                </p>
                <form method="POST" action="" style="display: flex; gap: 10px;">
                    <input type="hidden" name="review_id" value="<?= htmlspecialchars($reviewId) ?>">
                    <button type="submit" name="action" value="approve"
                            style="background: #2e7d32; color: white; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer;">
                        ✅ Approuver
                    </button>
                    <button type="submit" name="action" value="reject"
                            style="background: #C62828; color: white; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer;">
                        ❌ Rejeter
                    </button>
                </form>
            </div>
        <?php endforeach; ?>

        <?php if (!$hasPending): ?>
            <p style="color: #888;">Aucun avis en attente de validation. ✅</p>
        <?php endif; ?>
    </section>

    <!-- ============================================================ -->
    <!-- SECTION 2 : Covoiturages qui se sont mal passés (US12) -->
    <!-- ============================================================ -->
    <section class="search-card">
        <h2 style="color: #C62828;">⚠️ Covoiturages signalés comme problématiques</h2>
        <p style="color: #888; font-size: 0.9rem;">
            Ces trajets ont été signalés par des passagers. Contactez les deux parties pour résoudre la situation avant de mettre à jour les crédits du chauffeur.
        </p>

        <?php
        $hasProblem = false;
        foreach ($problemReviews as $problem):
            $hasProblem = true;

            // Récupérer les emails depuis MySQL
            $sqlPassenger = "SELECT pseudo, email FROM users WHERE id = :id";
            $stmtPassenger = $pdo->prepare($sqlPassenger);
            $stmtPassenger->execute(['id' => (int)$problem['author_id']]);
            $passenger = $stmtPassenger->fetch();

            $sqlDriver = "SELECT pseudo, email FROM users WHERE id = :id";
            $stmtDriver = $pdo->prepare($sqlDriver);
            $stmtDriver->execute(['id' => (int)$problem['driver_id']]);
            $driver = $stmtDriver->fetch();

            // Récupérer les infos du trajet depuis MySQL
            $sqlRide = "SELECT * FROM rides WHERE id = :id";
            $stmtRide = $pdo->prepare($sqlRide);
            $stmtRide->execute(['id' => (int)$problem['ride_id']]);
            $ride = $stmtRide->fetch();
        ?>
            <div style="border: 1px solid #FFCDD2; border-radius: 10px; padding: 20px; margin-bottom: 20px; background: #FFF8F8;">
                <!-- Numéro du covoiturage -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #C62828;">
                        Covoiturage #<?= (int)$problem['ride_id'] ?>
                    </h3>
                    <span style="background: #FFEBEE; color: #C62828; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold;">
                        ⚠️ Problème signalé
                    </span>
                </div>

                <!-- Infos du trajet -->
                <div style="background: white; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                    <p style="margin: 0 0 5px;"><strong>📍 Trajet :</strong>
                        <?= htmlspecialchars($problem['ride_info']['departure_city'] ?? ($ride['departure_city'] ?? 'N/A')) ?>
                        →
                        <?= htmlspecialchars($problem['ride_info']['arrival_city'] ?? ($ride['arrival_city'] ?? 'N/A')) ?>
                    </p>
                    <?php if ($ride): ?>
                        <p style="margin: 0 0 5px;"><strong>📅 Date de départ :</strong> <?= date('d/m/Y à H\hi', strtotime($ride['departure_time'])) ?></p>
                        <p style="margin: 0;"><strong>🏁 Date d'arrivée :</strong> <?= date('d/m/Y à H\hi', strtotime($ride['arrival_time'])) ?></p>
                    <?php else: ?>
                        <p style="margin: 0; color: #888;">Informations du trajet non disponibles</p>
                    <?php endif; ?>
                </div>

                <!-- Infos des deux parties -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div style="background: white; border-radius: 8px; padding: 12px;">
                        <p style="margin: 0 0 5px; color: #888; font-size: 0.8rem; font-weight: bold;">PASSAGER (signalant)</p>
                        <p style="margin: 0 0 3px;"><strong><?= htmlspecialchars($passenger['pseudo'] ?? $problem['author_pseudo'] ?? 'N/A') ?></strong></p>
                        <p style="margin: 0; color: #1565C0; font-size: 0.9rem;">
                            📧 <?= htmlspecialchars($passenger['email'] ?? 'Email non disponible') ?>
                        </p>
                    </div>
                    <div style="background: white; border-radius: 8px; padding: 12px;">
                        <p style="margin: 0 0 5px; color: #888; font-size: 0.8rem; font-weight: bold;">CHAUFFEUR</p>
                        <p style="margin: 0 0 3px;"><strong><?= htmlspecialchars($driver['pseudo'] ?? $problem['driver_pseudo'] ?? 'N/A') ?></strong></p>
                        <p style="margin: 0; color: #1565C0; font-size: 0.9rem;">
                            📧 <?= htmlspecialchars($driver['email'] ?? 'Email non disponible') ?>
                        </p>
                    </div>
                </div>

                <!-- Description du problème -->
                <div style="background: #FFEBEE; border-radius: 8px; padding: 12px;">
                    <p style="margin: 0 0 5px; font-weight: bold; color: #C62828;">Description du problème :</p>
                    <p style="margin: 0; color: #555;"><?= htmlspecialchars($problem['comment'] ?? '') ?></p>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$hasProblem): ?>
            <p style="color: #888;">Aucun covoiturage problématique signalé. ✅</p>
        <?php endif; ?>
    </section>

</main>

<?php require_once "footer.php"; ?>

</body>
</html>