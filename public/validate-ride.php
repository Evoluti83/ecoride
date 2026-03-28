<?php
session_start();
require_once "../config/database.php";
require_once "../config/mongodb.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId    = $_SESSION['user']['id'];
$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null;

if (!$bookingId) {
    header("Location: dashboard.php");
    exit;
}

// Récupérer la réservation avec les infos du trajet
$sqlBooking = "
    SELECT bookings.*,
           rides.departure_city, rides.arrival_city,
           rides.departure_time, rides.arrival_time,
           rides.price, rides.driver_id,
           users.pseudo AS driver_pseudo
    FROM bookings
    JOIN rides ON bookings.ride_id = rides.id
    JOIN users ON rides.driver_id  = users.id
    WHERE bookings.id      = :id
      AND bookings.user_id = :user_id
      AND bookings.status  = 'completed'
";
$stmtBooking = $pdo->prepare($sqlBooking);
$stmtBooking->execute(['id' => $bookingId, 'user_id' => $userId]);
$booking = $stmtBooking->fetch();

if (!$booking) {
    $_SESSION['error_message'] = "Réservation introuvable ou déjà validée.";
    header("Location: dashboard.php");
    exit;
}

$message = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validation = $_POST['validation'] ?? '';
    $rating     = isset($_POST['rating'])  ? (int)$_POST['rating']  : null;
    $comment    = trim($_POST['comment']   ?? '');
    $problem    = trim($_POST['problem']   ?? '');

    if (!in_array($validation, ['ok', 'problem'])) {
        $errors[] = "Veuillez indiquer si le trajet s'est bien passé.";
    }

    if ($validation === 'ok' && $rating && ($rating < 1 || $rating > 5)) {
        $errors[] = "La note doit être entre 1 et 5.";
    }

    if ($validation === 'problem' && empty($problem)) {
        $errors[] = "Veuillez décrire le problème rencontré.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($validation === 'ok') {
                // ✅ Trajet validé : mettre à jour la réservation
                $sqlValidate = "UPDATE bookings SET status = 'validated' WHERE id = :id";
                $stmtValidate = $pdo->prepare($sqlValidate);
                $stmtValidate->execute(['id' => $bookingId]);

                // ✅ Créditer le chauffeur (prix - 2 crédits plateforme)
                $creditsForDriver = $booking['price'] - 2;
                if ($creditsForDriver > 0) {
                    $sqlCredit = "UPDATE users SET credits = credits + :credits WHERE id = :driver_id";
                    $stmtCredit = $pdo->prepare($sqlCredit);
                    $stmtCredit->execute([
                        'credits'   => $creditsForDriver,
                        'driver_id' => $booking['driver_id']
                    ]);
                }

                // ✅ Enregistrer l'avis dans MongoDB si note donnée
                if ($rating && !empty($comment)) {
                    $reviewsCollection->insertOne([
                        'ride_id'       => (int)$booking['ride_id'],
                        'author_id'     => $userId,
                        'author_pseudo' => $_SESSION['user']['pseudo'],
                        'driver_id'     => (int)$booking['driver_id'],
                        'driver_pseudo' => $booking['driver_pseudo'],
                        'rating'        => $rating,
                        'comment'       => htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'),
                        'status'        => 'pending',
                        'created_at'    => new MongoDB\BSON\UTCDateTime(),
                        'ride_info'     => [
                            'departure_city' => $booking['departure_city'],
                            'arrival_city'   => $booking['arrival_city'],
                            'departure_time' => $booking['departure_time'],
                        ]
                    ]);
                }

                $pdo->commit();
                $_SESSION['success_message'] = "Merci pour votre validation ! " .
                    ($rating ? "Votre avis a été soumis à validation." : "") .
                    " Les crédits du chauffeur ont été mis à jour.";

            } else {
                // ❌ Problème signalé : marquer pour traitement employé
                $sqlProblem = "UPDATE bookings SET status = 'problem' WHERE id = :id";
                $stmtProblem = $pdo->prepare($sqlProblem);
                $stmtProblem->execute(['id' => $bookingId]);

                // Enregistrer le problème dans MongoDB
                $reviewsCollection->insertOne([
                    'ride_id'       => (int)$booking['ride_id'],
                    'author_id'     => $userId,
                    'author_pseudo' => $_SESSION['user']['pseudo'],
                    'driver_id'     => (int)$booking['driver_id'],
                    'driver_pseudo' => $booking['driver_pseudo'],
                    'rating'        => 0,
                    'comment'       => htmlspecialchars($problem, ENT_QUOTES, 'UTF-8'),
                    'status'        => 'problem',
                    'created_at'    => new MongoDB\BSON\UTCDateTime(),
                    'ride_info'     => [
                        'departure_city' => $booking['departure_city'],
                        'arrival_city'   => $booking['arrival_city'],
                        'departure_time' => $booking['departure_time'],
                    ]
                ]);

                $pdo->commit();
                $_SESSION['success_message'] = "Votre signalement a été transmis à notre équipe. Un employé contactera le chauffeur. Les crédits seront mis à jour après résolution.";
            }

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
    <title>EcoRide - Valider le trajet</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container" style="margin-top: 30px; max-width: 650px;">

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <?php foreach ($errors as $e): ?>
                <p style="margin: 0;"><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="search-card">
        <h2 style="color: #2e7d32;">Valider votre trajet</h2>

        <!-- Récap du trajet -->
        <div style="background: #F4F8F4; border-radius: 10px; padding: 15px; margin-bottom: 25px;">
            <p style="margin: 0 0 5px;"><strong><?= htmlspecialchars($booking['departure_city']) ?> → <?= htmlspecialchars($booking['arrival_city']) ?></strong></p>
            <p style="margin: 0 0 5px; color: #555;">Chauffeur : <?= htmlspecialchars($booking['driver_pseudo']) ?></p>
            <p style="margin: 0; color: #555;">Le <?= date('d/m/Y à H\hi', strtotime($booking['departure_time'])) ?></p>
        </div>

        <form method="POST" action="" id="validateForm">

            <!-- Tout s'est bien passé ? -->
            <div class="form-group">
                <label style="font-weight: bold; font-size: 1rem;">Le trajet s'est-il bien passé ? *</label>
                <div style="display: flex; gap: 15px; margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px 20px; border-radius: 8px; border: 2px solid #CBD5E1; flex: 1; justify-content: center;"
                           id="label-ok">
                        <input type="radio" name="validation" value="ok"
                               onchange="showSection('ok')"
                               <?= ($_POST['validation'] ?? '') === 'ok' ? 'checked' : '' ?>>
                        ✅ Oui, tout s'est bien passé
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px 20px; border-radius: 8px; border: 2px solid #CBD5E1; flex: 1; justify-content: center;"
                           id="label-problem">
                        <input type="radio" name="validation" value="problem"
                               onchange="showSection('problem')"
                               <?= ($_POST['validation'] ?? '') === 'problem' ? 'checked' : '' ?>>
                        ❌ Non, il y a eu un problème
                    </label>
                </div>
            </div>

            <!-- Section si OK -->
            <div id="section-ok" style="display: none;">
                <div style="background: #E8F5E9; border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                    <p style="margin: 0; color: #2e7d32; font-size: 0.9rem;">
                        ✅ Les crédits du chauffeur seront mis à jour après votre validation.
                    </p>
                </div>

                <div class="form-group">
                    <label for="rating">Note du chauffeur (optionnel)</label>
                    <select id="rating" name="rating">
                        <option value="">-- Ne pas noter --</option>
                        <option value="5" <?= ($_POST['rating'] ?? '') == '5' ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ Excellent (5/5)</option>
                        <option value="4" <?= ($_POST['rating'] ?? '') == '4' ? 'selected' : '' ?>>⭐⭐⭐⭐ Bien (4/5)</option>
                        <option value="3" <?= ($_POST['rating'] ?? '') == '3' ? 'selected' : '' ?>>⭐⭐⭐ Correct (3/5)</option>
                        <option value="2" <?= ($_POST['rating'] ?? '') == '2' ? 'selected' : '' ?>>⭐⭐ Décevant (2/5)</option>
                        <option value="1" <?= ($_POST['rating'] ?? '') == '1' ? 'selected' : '' ?>>⭐ Très mauvais (1/5)</option>
                    </select>
                </div>

                <div class="form-group" id="comment-group" style="display: none;">
                    <label for="comment">Votre commentaire</label>
                    <textarea id="comment" name="comment"
                              placeholder="Décrivez votre expérience..."
                              style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; min-height: 100px; font-family: Arial, sans-serif; font-size: 1rem;"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Section si problème -->
            <div id="section-problem" style="display: none;">
                <div style="background: #FFEBEE; border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                    <p style="margin: 0; color: #C62828; font-size: 0.9rem;">
                        ⚠️ Un employé EcoRide contactera le chauffeur pour résoudre la situation. Les crédits seront mis à jour après résolution.
                    </p>
                </div>

                <div class="form-group">
                    <label for="problem">Décrivez le problème *</label>
                    <textarea id="problem" name="problem"
                              placeholder="Expliquez ce qui s'est mal passé..."
                              style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; min-height: 120px; font-family: Arial, sans-serif; font-size: 1rem;"><?= htmlspecialchars($_POST['problem'] ?? '') ?></textarea>
                </div>
            </div>

            <button type="submit" id="submitBtn" style="display: none;">
                Valider
            </button>
        </form>
    </section>
</main>

<?php require_once "footer.php"; ?>

<script>
function showSection(type) {
    document.getElementById('section-ok').style.display      = type === 'ok'      ? 'block' : 'none';
    document.getElementById('section-problem').style.display = type === 'problem' ? 'block' : 'none';
    document.getElementById('submitBtn').style.display       = 'block';

    // Style des labels radio
    document.getElementById('label-ok').style.borderColor      = type === 'ok'      ? '#2e7d32' : '#CBD5E1';
    document.getElementById('label-ok').style.background       = type === 'ok'      ? '#E8F5E9' : 'white';
    document.getElementById('label-problem').style.borderColor = type === 'problem' ? '#C62828' : '#CBD5E1';
    document.getElementById('label-problem').style.background  = type === 'problem' ? '#FFEBEE' : 'white';

    if (type === 'ok') {
        document.getElementById('submitBtn').textContent   = '✅ Valider le trajet';
        document.getElementById('submitBtn').style.background = '#2e7d32';
    } else {
        document.getElementById('submitBtn').textContent   = '⚠️ Signaler le problème';
        document.getElementById('submitBtn').style.background = '#C62828';
    }
}

// Afficher le textarea commentaire si une note est sélectionnée
document.getElementById('rating').addEventListener('change', function() {
    document.getElementById('comment-group').style.display = this.value ? 'block' : 'none';
});

// Afficher les sections si déjà sélectionné (après erreur)
<?php if (!empty($_POST['validation'])): ?>
    showSection('<?= htmlspecialchars($_POST['validation']) ?>');
<?php endif; ?>
</script>

</body>
</html>