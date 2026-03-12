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
        try {
            // Préparer la mise à jour du statut de l'avis
            if ($action === 'approve') {
                $sqlUpdate = "UPDATE reviews SET status = 'approved' WHERE id = :review_id";
            } elseif ($action === 'reject') {
                $sqlUpdate = "UPDATE reviews SET status = 'rejected' WHERE id = :review_id";
            }

            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute(['review_id' => $reviewId]);

            $message = "Avis mis à jour avec succès.";

        } catch (Exception $e) {
            $message = "Une erreur est survenue. Veuillez réessayer.";
        }
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
        <div class="alert <?= (strpos($message, 'succès') !== false) ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
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
                            <button type="submit" name="action" value="approve" class="approve-btn">Approuver</button>
                            <button type="submit" name="action" value="reject" class="reject-btn">Rejeter</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</main>

</body>
</html>

<style>
    /* Styles supplémentaires pour améliorer l'UX des boutons */
    .approve-btn {
        background-color: #28a745; /* Vert pour "Approuver" */
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }

    .approve-btn:hover {
        background-color: #218838;
    }

    .reject-btn {
        background-color: #dc3545; /* Rouge pour "Rejeter" */
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }

    .reject-btn:hover {
        background-color: #c82333;
    }

    /* Alerte pour le message de succès ou d'erreur */
    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .alert.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>