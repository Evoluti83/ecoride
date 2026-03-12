<?php

session_start();
require_once "../config/database.php";

$message = '';

// Vérification de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) ? true : false;  // Récupère l'option "Se souvenir de moi"

    // Validation des champs
    if (!empty($email) && !empty($password)) {
        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Veuillez entrer une adresse email valide.";
        } else {
            // Vérification dans la base de données
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Vérification du mot de passe
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'pseudo' => $user['pseudo'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'credits' => $user['credits']
                ];

                // Gérer "Se souvenir de moi" avec un cookie sécurisé
                if ($rememberMe) {
                    $expireTime = time() + 60 * 60 * 24 * 30; // 30 jours
                    setcookie('remember_me', $user['id'], $expireTime, '/', '', true, true); // secure et HttpOnly
                }

                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Email ou mot de passe incorrect.";
            }
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
    <title>EcoRide - Connexion</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<header class="site-header">
    <div class="container">
        <h1>EcoRide</h1>
        <p>Connexion à votre espace</p>
    </div>
</header>

<main class="container">
    <section class="search-card">
        <h2>Connexion</h2>

        <!-- Message d'erreur ou succès -->
        <?php if (!empty($message)): ?>
            <div class="alert <?= (strpos($message, 'incorrect') !== false) ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required placeholder="exemple@mail.com">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required placeholder="********">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember_me">
                    Se souvenir de moi
                </label>
            </div>

            <button type="submit">Se connecter</button>
        </form>

        <p><a href="forgot-password.php">Mot de passe oublié ?</a></p>
    </section>
</main>

<script>
    // Validation côté client
    $('#loginForm').on('submit', function(event) {
        var email = $('#email').val().trim();
        var password = $('#password').val().trim();

        if (email === '' || password === '') {
            alert("Veuillez remplir tous les champs.");
            event.preventDefault();
        }
    });
</script>

</body>
</html>