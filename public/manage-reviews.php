<?php
session_start();
require_once "../config/database.php";
 
// ✅ Vérifier que la session existe ET que le rôle est 'employee'
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}
 
$message = '';
 
// Gestion de l'acceptation ou du rejet d'un avis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = $_POST['review_id'] ?? null;
    $action   = $_POST['action']    ?? '';
 
    // ✅ Vérifier que l'action est bien l'une des deux valeurs attendues
    if ($reviewId && in_array($action, ['approve', 'reject'])) {
        try {
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
 
            $sqlUpdate = "UPDATE reviews SET status = :status WHERE id = :review_id";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                'status'    => $newStatus,
                'review_id' => $reviewId
            ]);
 
            $message = "Avis mis à jour avec succès.";
 
        } catch (Exception $e) {
            $message = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}
 
// Récupérer les avis en attente avec les infos utiles (trajet + auteur)
$sqlPendingReviews = "
    SELECT
        reviews.*,
        users.pseudo    AS author_pseudo,
        rides.departure_city,
        rides.arrival_city,
        rides.departure_time
    FROM reviews
    JOIN users ON reviews.author_id = users.id
    JOIN rides ON reviews.ride_id   = rides.id
    WHERE reviews.status = 'pending'
    ORDER BY reviews.created_at DESC
";
$stmtPendingReviews = $pdo->prepare($sqlPendingReviews);
$stmtPendingReviews->execute();
$pendingReviews = $stmtPendingReviews->fetchAll();
?>
 
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Gestion des avis</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
 
<header class="site-header">
    <div class="container">
        <h1>EcoRide</h1>
        <p>Espace employé — Gestion des avis</p>
        <nav>
            <a href="dashboard.php">Mon espace</a> |
            <a href="logout.php">Se déconnecter</a>
        </nav>
    </div>
</header>
 
<main class="container">
    <h2>Avis en attente de validation</h2>
 
    <?php if (!empty($message)): ?>
        <div class="alert <?= (strpos($message, 'succès') !== false) ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
 
    <?php if (empty($pendingReviews)): ?>
        <p>Aucun avis en attente de validation.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Trajet</th>
                    <th>Auteur</th>
                    <th>Note</th>
                    <th>Commentaire</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingReviews as $review): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($review['departure_city']) ?>
                            →
                            <?= htmlspecialchars($review['arrival_city']) ?>
                            <br>
                            <small><?= htmlspecialchars($review['departure_time']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($review['author_pseudo']) ?></td>
                        <td><?= htmlspecialchars($review['rating']) ?>/5</td>
                        <td><?= htmlspecialchars($review['comment']) ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="review_id" value="<?= (int)$review['id'] ?>">
                                <button type="submit" name="action" value="approve" class="approve-btn">✅ Approuver</button>
                                <button type="submit" name="action" value="reject"  class="reject-btn">❌ Rejeter</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
 
</body>
</html>