<?php
// Démarrer la session si elle ne l'est pas déjà
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Déterminer la page active pour le style
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="site-header">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">

            <!-- Logo -->
            <a href="index.php" style="text-decoration: none; color: white;">
                <h1 style="margin: 0; font-size: 1.8rem;">🌿 EcoRide</h1>
            </a>

            <!-- Navigation -->
            <nav style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">

                <!-- Accueil -->
                <a href="index.php"
                   style="color: white; text-decoration: none; padding: 8px 14px; border-radius: 6px;
                          background: <?= $currentPage === 'index.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;">
                    Accueil
                </a>

                <!-- Covoiturages -->
                <a href="covoiturages.php"
                   style="color: white; text-decoration: none; padding: 8px 14px; border-radius: 6px;
                          background: <?= $currentPage === 'covoiturages.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;">
                    Covoiturages
                </a>

                <!-- Contact -->
                <a href="contact.php"
                   style="color: white; text-decoration: none; padding: 8px 14px; border-radius: 6px;
                          background: <?= $currentPage === 'contact.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;">
                    Contact
                </a>

                <!-- Séparateur -->
                <span style="color: rgba(255,255,255,0.3); margin: 0 5px;">|</span>

                <?php if (isset($_SESSION['user'])): ?>
                    <!-- Utilisateur connecté -->
                    <a href="dashboard.php"
                       style="color: white; text-decoration: none; padding: 8px 14px; border-radius: 6px;
                              background: <?= $currentPage === 'dashboard.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;">
                        👤 <?= htmlspecialchars($_SESSION['user']['pseudo']) ?>
                    </a>

                    <?php if ($_SESSION['user']['role'] === 'employee'): ?>
                        <a href="manage-reviews.php"
                           style="color: white; text-decoration: none; padding: 8px 14px; border-radius: 6px;
                                  background: <?= $currentPage === 'manage-reviews.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;">
                            Avis
                        </a>
                    <?php endif; ?>

                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                        <a href="admin.php"
                           style="color: #FFD700; text-decoration: none; padding: 8px 14px; border-radius: 6px;
                                  background: <?= $currentPage === 'admin.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;">
                            ⚙️ Admin
                        </a>
                    <?php endif; ?>

                    <a href="logout.php"
                       style="color: white; text-decoration: none; padding: 8px 14px; border-radius: 6px;
                              border: 1px solid rgba(255,255,255,0.4);">
                        Déconnexion
                    </a>

                <?php else: ?>
                    <!-- Visiteur non connecté -->
                    <a href="login.php"
                       style="color: white; text-decoration: none; padding: 8px 14px; border-radius: 6px;
                              background: <?= $currentPage === 'login.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;">
                        Connexion
                    </a>

                    <a href="register.php"
                       style="color: #2e7d32; background: white; text-decoration: none; padding: 8px 16px;
                              border-radius: 6px; font-weight: bold;">
                        Inscription
                    </a>
                <?php endif; ?>

            </nav>
        </div>
    </div>
</header>