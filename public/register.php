<?php

require_once "../config/database.php";

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($pseudo) && !empty($email) && !empty($password)) {
        $checkSql = "SELECT id FROM users WHERE email = :email";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['email' => $email]);
        $existingUser = $checkStmt->fetch();

        if ($existingUser) {
            $message = "Cette adresse email est déjà utilisée.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (pseudo, email, password, credits, role)
                    VALUES (:pseudo, :email, :password, 20, 'user')";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'pseudo' => $pseudo,
                'email' => $email,
                'password' => $hashedPassword
            ]);

            $message = "Compte créé avec succès !";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EcoRide - Inscription</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <h1>EcoRide</h1>
        <p>Créer un compte utilisateur</p>
    </div>
</header>

<main class="container">
    <section class="search-card">
        <h2>Inscription</h2>

        <?php if (!empty($message)): ?>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo" required>
            </div>

            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Créer mon compte</button>
        </form>
    </section>
</main>

</body>
</html>