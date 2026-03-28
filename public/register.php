<?php
session_start();
require_once "../config/database.php";

$message = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo   = trim($_POST['pseudo']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    // ================================================================
    // Validation côté serveur
    // ================================================================

    // Pseudo
    if (empty($pseudo)) {
        $errors[] = "Le pseudo est obligatoire.";
    } elseif (strlen($pseudo) < 3) {
        $errors[] = "Le pseudo doit contenir au moins 3 caractères.";
    }

    // Email
    if (empty($email)) {
        $errors[] = "L'adresse email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    // ✅ Mot de passe sécurisé (US7)
    if (empty($password)) {
        $errors[] = "Le mot de passe est obligatoire.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un caractère spécial (!@#$%...).";
    }

    // Confirmation mot de passe
    if ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Si pas d'erreurs de validation
    if (empty($errors)) {
        // Vérifier si l'email existe déjà
        $checkSql = "SELECT id FROM users WHERE email = :email";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['email' => $email]);

        if ($checkStmt->fetch()) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        } else {
            // Hacher le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // ✅ US7 : 20 crédits offerts à la création
            $sql = "INSERT INTO users (pseudo, email, password, credits, role)
                    VALUES (:pseudo, :email, :password, 20, 'user')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'pseudo'   => $pseudo,
                'email'    => $email,
                'password' => $hashedPassword
            ]);

            $_SESSION['success_message'] = "Compte créé avec succès ! Vous bénéficiez de 20 crédits. Connectez-vous !";
            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Inscription</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .password-rules {
            background: #F4F8F4;
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 8px;
            font-size: 0.85rem;
        }
        .password-rules p {
            margin: 0 0 5px;
            color: #888;
            font-weight: bold;
        }
        .rule {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 4px 0;
            font-size: 0.85rem;
            color: #888;
        }
        .rule.valid   { color: #2e7d32; }
        .rule.invalid { color: #C62828; }
        .error-list {
            background: #FFEBEE;
            border: 1px solid #EF9A9A;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .error-list p {
            margin: 0 0 8px;
            color: #C62828;
            font-weight: bold;
        }
        .error-list ul {
            margin: 0;
            padding-left: 20px;
            color: #C62828;
        }
        .error-list ul li { margin-bottom: 4px; }
    </style>
</head>
<body>

<?php require_once "navbar.php"; ?>

<main class="container" style="margin-top: 30px; max-width: 550px;">
    <section class="search-card">
        <h2 style="color: #2e7d32;">Créer un compte</h2>
        <p style="color: #555; margin-bottom: 20px;">
            Rejoignez EcoRide et recevez <strong>20 crédits offerts</strong> à l'inscription ! 🎁
        </p>

        <!-- Affichage des erreurs -->
        <?php if (!empty($errors)): ?>
            <div class="error-list">
                <p>Veuillez corriger les erreurs suivantes :</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">

            <div class="form-group">
                <label for="pseudo">Pseudo <span style="color: #C62828;">*</span></label>
                <input type="text" id="pseudo" name="pseudo" required
                       minlength="3"
                       placeholder="Votre pseudo (min. 3 caractères)"
                       value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Adresse email <span style="color: #C62828;">*</span></label>
                <input type="email" id="email" name="email" required
                       placeholder="exemple@mail.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe <span style="color: #C62828;">*</span></label>
                <input type="password" id="password" name="password" required
                       placeholder="Votre mot de passe sécurisé"
                       oninput="checkPassword(this.value)">

                <!-- Indicateur de force du mot de passe -->
                <div class="password-rules">
                    <p>Le mot de passe doit contenir :</p>
                    <div class="rule" id="rule-length">⬜ Au moins 8 caractères</div>
                    <div class="rule" id="rule-upper">⬜ Au moins une majuscule (A-Z)</div>
                    <div class="rule" id="rule-number">⬜ Au moins un chiffre (0-9)</div>
                    <div class="rule" id="rule-special">⬜ Au moins un caractère spécial (!@#$%...)</div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm">Confirmer le mot de passe <span style="color: #C62828;">*</span></label>
                <input type="password" id="confirm" name="confirm" required
                       placeholder="Répétez votre mot de passe">
            </div>

            <button type="submit" id="submitBtn">Créer mon compte 🎁</button>

            <p style="text-align: center; margin-top: 15px; color: #888; font-size: 0.9rem;">
                Déjà un compte ? <a href="login.php" style="color: #2e7d32;">Connectez-vous</a>
            </p>
        </form>
    </section>
</main>

<?php require_once "footer.php"; ?>

<script>
function checkPassword(value) {
    const rules = {
        'rule-length':  value.length >= 8,
        'rule-upper':   /[A-Z]/.test(value),
        'rule-number':  /[0-9]/.test(value),
        'rule-special': /[^a-zA-Z0-9]/.test(value),
    };
    const icons = { true: '✅', false: '⬜' };

    const labels = {
        'rule-length':  ' Au moins 8 caractères',
        'rule-upper':   ' Au moins une majuscule (A-Z)',
        'rule-number':  ' Au moins un chiffre (0-9)',
        'rule-special': ' Au moins un caractère spécial (!@#$%...)',
    };

    let allValid = true;
    for (const [id, valid] of Object.entries(rules)) {
        const el = document.getElementById(id);
        el.textContent = icons[valid] + labels[id];
        el.className = 'rule ' + (valid ? 'valid' : 'invalid');
        if (!valid) allValid = false;
    }

    // Désactiver le bouton si le mot de passe n'est pas valide
    document.getElementById('submitBtn').style.opacity = allValid ? '1' : '0.6';
}
</script>

</body>
</html>