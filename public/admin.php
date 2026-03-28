<?php
session_start();
require_once "../config/database.php";

// Vérifier que c'est bien un admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$user    = $_SESSION['user'];
$message = '';

// ============================================================
// ACTION : Créer un employé
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Créer un employé
    if ($_POST['action'] === 'create_employee') {
        $pseudo   = trim($_POST['pseudo']   ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';

        if (empty($pseudo) || empty($email) || empty($password)) {
            $message = "Veuillez remplir tous les champs.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Email invalide.";
        } else {
            // Vérifier si l'email existe déjà
            $sqlCheck = "SELECT id FROM users WHERE email = :email";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute(['email' => $email]);

            if ($stmtCheck->fetch()) {
                $message = "Cet email est déjà utilisé.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sqlCreate = "
                    INSERT INTO users (pseudo, email, password, credits, role)
                    VALUES (:pseudo, :email, :password, 0, 'employee')
                ";
                $stmtCreate = $pdo->prepare($sqlCreate);
                $stmtCreate->execute([
                    'pseudo'   => $pseudo,
                    'email'    => $email,
                    'password' => $hashedPassword
                ]);
                $message = "Compte employé créé avec succès !";
            }
        }
    }

    // Suspendre / réactiver un compte
    if ($_POST['action'] === 'toggle_suspend') {
        $targetId = (int)($_POST['target_id'] ?? 0);

        // Ne pas pouvoir se suspendre soi-même
        if ($targetId === $user['id']) {
            $message = "Vous ne pouvez pas suspendre votre propre compte.";
        } else {
            $sqlToggle = "UPDATE users SET suspended = NOT suspended WHERE id = :id";
            $stmtToggle = $pdo->prepare($sqlToggle);
            $stmtToggle->execute(['id' => $targetId]);
            $message = "Statut du compte mis à jour.";
        }
    }
}

// ============================================================
// DONNÉES pour les graphiques
// ============================================================

// Covoiturages par jour (30 derniers jours)
$sqlRidesPerDay = "
    SELECT DATE(created_at) AS day, COUNT(*) AS total
    FROM rides
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
";
$stmtRidesPerDay = $pdo->prepare($sqlRidesPerDay);
$stmtRidesPerDay->execute();
$ridesPerDay = $stmtRidesPerDay->fetchAll();

// Crédits gagnés par la plateforme par jour
// La plateforme prend 2 crédits par réservation confirmée
$sqlCreditsPerDay = "
    SELECT DATE(bookings.booking_date) AS day, COUNT(*) * 2 AS credits_earned
    FROM bookings
    WHERE bookings.status != 'cancelled'
      AND bookings.booking_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(bookings.booking_date)
    ORDER BY day ASC
";
$stmtCreditsPerDay = $pdo->prepare($sqlCreditsPerDay);
$stmtCreditsPerDay->execute();
$creditsPerDay = $stmtCreditsPerDay->fetchAll();

// Total crédits gagnés par la plateforme
$sqlTotalCredits = "
    SELECT COUNT(*) * 2 AS total
    FROM bookings
    WHERE status != 'cancelled'
";
$stmtTotal = $pdo->prepare($sqlTotalCredits);
$stmtTotal->execute();
$totalCredits = $stmtTotal->fetchColumn();

// Liste de tous les utilisateurs et employés
$sqlUsers = "
    SELECT id, pseudo, email, role, credits, suspended, created_at
    FROM users
    WHERE role != 'admin'
    ORDER BY role, created_at DESC
";
$stmtUsers = $pdo->prepare($sqlUsers);
$stmtUsers->execute();
$allUsers = $stmtUsers->fetchAll();

// Préparer les données JSON pour Chart.js
$ridesLabels  = json_encode(array_column($ridesPerDay,   'day'));
$ridesData    = json_encode(array_column($ridesPerDay,   'total'));
$creditsLabels= json_encode(array_column($creditsPerDay, 'day'));
$creditsData  = json_encode(array_column($creditsPerDay, 'credits_earned'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Espace Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container">

    <?php if (!empty($message)): ?>
        <div class="alert <?= str_contains($message, 'succès') ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Statistiques rapides -->
    <section class="search-card">
        <h2>📊 Statistiques de la plateforme</h2>
        <p>
            <strong>💳 Total crédits gagnés par la plateforme :</strong>
            <?= (int)$totalCredits ?> crédits
        </p>
    </section>

    <!-- Graphique 1 : Covoiturages par jour -->
    <section class="search-card">
        <h2>🚗 Covoiturages créés par jour (30 derniers jours)</h2>
        <canvas id="ridesChart" height="100"></canvas>
    </section>

    <!-- Graphique 2 : Crédits gagnés par jour -->
    <section class="search-card">
        <h2>💰 Crédits gagnés par jour (30 derniers jours)</h2>
        <canvas id="creditsChart" height="100"></canvas>
    </section>

    <!-- Créer un employé -->
    <section class="search-card">
        <h2>👤 Créer un compte employé</h2>

        <form method="POST" action="">
            <input type="hidden" name="action" value="create_employee">

            <div class="form-group">
                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       placeholder="Min. 8 caractères">
            </div>

            <button type="submit" class="btn btn-primary">Créer l'employé</button>
        </form>
    </section>

    <!-- Gestion des utilisateurs -->
    <section class="search-card">
        <h2>👥 Gestion des comptes</h2>

        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f0f0f0;">
                    <th style="padding:8px; text-align:left;">Pseudo</th>
                    <th style="padding:8px; text-align:left;">Email</th>
                    <th style="padding:8px; text-align:left;">Rôle</th>
                    <th style="padding:8px; text-align:left;">Crédits</th>
                    <th style="padding:8px; text-align:left;">Statut</th>
                    <th style="padding:8px; text-align:left;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allUsers as $u): ?>
                    <tr style="border-bottom:1px solid #ddd;">
                        <td style="padding:8px;"><?= htmlspecialchars($u['pseudo']) ?></td>
                        <td style="padding:8px;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding:8px;"><?= htmlspecialchars($u['role']) ?></td>
                        <td style="padding:8px;"><?= (int)$u['credits'] ?></td>
                        <td style="padding:8px;">
                            <?= $u['suspended']
                                ? '<span style="color:red;">🔴 Suspendu</span>'
                                : '<span style="color:green;">🟢 Actif</span>'
                            ?>
                        </td>
                        <td style="padding:8px;">
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="action"    value="toggle_suspend">
                                <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn"
                                        onclick="return confirm('Confirmer ?')"
                                        style="background:<?= $u['suspended'] ? '#28a745' : '#dc3545' ?>; color:white;">
                                    <?= $u['suspended'] ? '✅ Réactiver' : '🔴 Suspendre' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

</main>

<script>
// Graphique 1 : Covoiturages par jour
const ridesCtx = document.getElementById('ridesChart').getContext('2d');
new Chart(ridesCtx, {
    type: 'bar',
    data: {
        labels: <?= $ridesLabels ?>,
        datasets: [{
            label: 'Covoiturages créés',
            data: <?= $ridesData ?>,
            backgroundColor: 'rgba(76, 175, 80, 0.6)',
            borderColor: 'rgba(76, 175, 80, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// Graphique 2 : Crédits gagnés par jour
const creditsCtx = document.getElementById('creditsChart').getContext('2d');
new Chart(creditsCtx, {
    type: 'line',
    data: {
        labels: <?= $creditsLabels ?>,
        datasets: [{
            label: 'Crédits gagnés',
            data: <?= $creditsData ?>,
            backgroundColor: 'rgba(33, 150, 243, 0.2)',
            borderColor: 'rgba(33, 150, 243, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

<?php require_once "footer.php"; ?>
</body>
</html>