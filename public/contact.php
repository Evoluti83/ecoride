<?php
session_start();

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($content)) {
        $message = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Veuillez entrer une adresse email valide.";
    } else {
        // Envoi du mail à l'équipe EcoRide
        $to      = "contact@ecoride.fr";
        $headers = "From: " . htmlspecialchars($email) . "\r\nContent-Type: text/plain; charset=UTF-8";
        $body    = "Nom : " . htmlspecialchars($name) . "\n"
                 . "Email : " . htmlspecialchars($email) . "\n"
                 . "Sujet : " . htmlspecialchars($subject) . "\n\n"
                 . htmlspecialchars($content);

        mail($to, "[EcoRide Contact] " . htmlspecialchars($subject), $body, $headers);

        $success = true;
        $message = "Votre message a bien été envoyé ! Nous vous répondrons sous 48h.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Contact</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container" style="max-width: 700px; margin: 40px auto;">

    <!-- Messages flash -->
    <?php if (!empty($message)): ?>
        <div class="alert <?= $success ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="search-card">
        <h2>Nous contacter</h2>
        <p style="color: #555; margin-bottom: 25px;">
            Une question, un problème ou une suggestion ? Notre équipe est là pour vous aider.
        </p>

        <!-- Infos de contact -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;">
            <div style="background: #E8F5E9; border-radius: 10px; padding: 20px; text-align: center;">
                <div style="font-size: 1.8rem; margin-bottom: 8px;">📧</div>
                <strong style="color: #2e7d32;">Email</strong>
                <p style="margin: 5px 0 0; color: #555; font-size: 0.9rem;">
                    <a href="mailto:contact@ecoride.fr" style="color: #2e7d32;">contact@ecoride.fr</a>
                </p>
            </div>
            <div style="background: #E8F5E9; border-radius: 10px; padding: 20px; text-align: center;">
                <div style="font-size: 1.8rem; margin-bottom: 8px;">🕐</div>
                <strong style="color: #2e7d32;">Disponibilité</strong>
                <p style="margin: 5px 0 0; color: #555; font-size: 0.9rem;">
                    Lundi - Vendredi<br>9h00 - 18h00
                </p>
            </div>
        </div>

        <!-- Formulaire de contact -->
        <?php if (!$success): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Votre nom</label>
                <input type="text" id="name" name="name"
                       value="<?= htmlspecialchars($_POST['name'] ?? (isset($_SESSION['user']) ? $_SESSION['user']['pseudo'] : '')) ?>"
                       required placeholder="Votre nom ou pseudo">
            </div>

            <div class="form-group">
                <label for="email">Votre email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? (isset($_SESSION['user']) ? $_SESSION['user']['email'] : '')) ?>"
                       required placeholder="votre@email.fr">
            </div>

            <div class="form-group">
                <label for="subject">Sujet</label>
                <select id="subject" name="subject" required>
                    <option value="">-- Choisir un sujet --</option>
                    <option value="Problème technique" <?= ($_POST['subject'] ?? '') === 'Problème technique' ? 'selected' : '' ?>>Problème technique</option>
                    <option value="Question sur un trajet" <?= ($_POST['subject'] ?? '') === 'Question sur un trajet' ? 'selected' : '' ?>>Question sur un trajet</option>
                    <option value="Problème de crédits" <?= ($_POST['subject'] ?? '') === 'Problème de crédits' ? 'selected' : '' ?>>Problème de crédits</option>
                    <option value="Signalement" <?= ($_POST['subject'] ?? '') === 'Signalement' ? 'selected' : '' ?>>Signalement d'un utilisateur</option>
                    <option value="Autre" <?= ($_POST['subject'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>

            <div class="form-group">
                <label for="content">Votre message</label>
                <textarea id="content" name="content" required
                          placeholder="Décrivez votre demande..."
                          style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem; min-height: 150px; resize: vertical; font-family: Arial, sans-serif;"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
            </div>

            <button type="submit">📨 Envoyer le message</button>
        </form>
        <?php else: ?>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 3rem; margin-bottom: 15px;">✅</div>
                <p style="color: #2e7d32; font-size: 1.1rem;">Message envoyé avec succès !</p>
                <a href="index.php" style="color: #2e7d32;">← Retour à l'accueil</a>
            </div>
        <?php endif; ?>
    </section>

</main>

<footer style="background: #1f2937; color: #aaa; text-align: center; padding: 20px; margin-top: 40px;">
    <p>EcoRide — <a href="mailto:contact@ecoride.fr" style="color: #4CAF50;">contact@ecoride.fr</a> — <a href="mentions-legales.php" style="color: #4CAF50;">Mentions légales</a></p>
</footer>

</body>
</html>