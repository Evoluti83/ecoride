<?php
session_start();
require_once "../config/database.php";

// Vérifier si l'utilisateur est un employé
if ($_SESSION['user']['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}

$message = '';

// Gestion de l'acceptation ou du rejet d’un avis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = $_POST['review_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if ($reviewId && $action) {
        if ($action === 'approve') {
            $sqlUpdate = "UPDATE reviews SET status = 'approved' WHERE id = :review_id";
        } elseif ($action === 'reject') {
            $sqlUpdate = "UPDATE reviews SET status = 'rejected' WHERE id = :review_id";
        }

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute(['review_id' => $reviewId]);

        $message = "Avis mis à jour avec succès.";
    }
}

// Récupérer les avis en attente de validation
$sqlPendingReviews = "SELECT * FROM reviews WHERE status = 'pending'";
$stmtPendingReviews = $pdo->prepare($sqlPendingReviews);
$stmtPendingReviews->execute();
$pendingReviews = $stmtPendingReviews->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EcoRide - Gestion des avis</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <h1>Gestion des avis</h1>
    </div>
</header>

<main class="container">
    <h2>Avis en attente de validation</h2>

    <?php if (!empty($message)): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if (empty($pendingReviews)): ?>
        <p>Aucun avis en attente de validation.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Trajet</th>
                <th>Auteur</th>
                <th>Note</th>
                <th>Commentaire</th>
                <th>Action</th>
            </tr>
            <?php foreach ($pendingReviews as $review): ?>
                <tr>
                    <td><?= htmlspecialchars($review['ride_id']) ?></td>
                    <td><?= htmlspecialchars($review['author_id']) ?></td>
                    <td><?= htmlspecialchars($review['rating']) ?>/5</td>
                    <td><?= htmlspecialchars($review['comment']) ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            <button type="submit" name="action" value="approve">Approuver</button>
                            <button type="submit" name="action" value="reject">Rejeter</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</main>

</body>
</html>