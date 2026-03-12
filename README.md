🌿 EcoRide — Plateforme de covoiturage écologique
EcoRide est une application web de covoiturage développée dans le cadre du titre professionnel Développeur Web et Web Mobile (DWWM).

🚀 Stack technique
ÉlémentTechnologieFront-endHTML5, CSS3, JavaScriptBack-endPHP 8.2 (natif, PDO)Base de données relationnelleMariaDB (XAMPP)Base de données NoSQLMongoDB AtlasLibrairie MongoDBmongodb/mongodb 1.19.1 (Composer)GraphiquesChart.jsVersioningGit / GitHub

⚙️ Installation en local
Prérequis

XAMPP (PHP 8.2 + MariaDB)
Composer
Git

1. Cloner le projet
bashgit clone https://github.com/TON_COMPTE/ecoride.git
cd ecoride
2. Installer les dépendances PHP
bashcomposer install
3. Installer l'extension MongoDB pour PHP

Télécharger php_mongodb-1.19.x-8.2-ts-vs16-x64.zip sur pecl.php.net
Copier php_mongodb.dll dans C:/xampp/php/ext/
Ajouter extension=mongodb dans C:/xampp/php/php.ini
Redémarrer Apache

4. Créer la base de données MySQL
Dans phpMyAdmin ou via la ligne de commande :
bashmysql -u root -p < sql/ecoride_create.sql
mysql -u root -p < sql/ecoride_data.sql
5. Configurer MongoDB
Créer le fichier config/mongodb.php (non versionné pour des raisons de sécurité) :
php<?php
require_once __DIR__ . '/../vendor/autoload.php';

$mongoUri = "mongodb+srv://<username>:<password>@<cluster>.mongodb.net/ecoride?retryWrites=true&w=majority";

try {
    $mongoClient       = new MongoDB\Client($mongoUri);
    $mongodb           = $mongoClient->selectDatabase('ecoride');
    $reviewsCollection = $mongodb->selectCollection('reviews');
} catch (Exception $e) {
    die("Erreur MongoDB : " . $e->getMessage());
}

Remplace <username>, <password> et <cluster> par tes valeurs MongoDB Atlas.

6. Lancer l'application

Démarre Apache et MySQL dans XAMPP
Accède à : http://localhost/ecoride/public/


👤 Comptes de test
RôleEmailMot de passeAdministrateuradmin@ecoride.frpasswordEmployéemploye@ecoride.frpasswordChauffeurcarlos@test.frpasswordPassagerdiane@test.frpassword

⚠️ Le compte administrateur ne peut pas être créé depuis l'application. Il doit être inséré directement en base de données.


📁 Structure du projet
ecoride/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
├── config/
│   ├── database.php        ← connexion MySQL
│   └── mongodb.php         ← connexion MongoDB (non versionné)
├── public/                 ← pages PHP
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── profile.php
│   ├── covoiturages.php
│   ├── ride-detail.php
│   ├── book-ride.php
│   ├── create-ride.php
│   ├── cancel-booking.php
│   ├── cancel-ride.php
│   ├── start-ride.php
│   ├── end-ride.php
│   ├── leave-review.php
│   ├── manage-reviews.php
│   ├── admin.php
│   ├── add-vehicle.php
│   ├── preferences.php
│   └── logout.php
├── sql/
│   ├── ecoride_create.sql  ← structure de la base
│   └── ecoride_data.sql    ← données de test
├── vendor/                 ← dépendances Composer (non versionné)
├── .gitignore
├── composer.json
├── composer.lock
└── README.md

🔒 Sécurité

Mots de passe hashés avec password_hash() (bcrypt)
Requêtes préparées PDO (protection injection SQL)
htmlspecialchars() sur toutes les sorties (protection XSS)
Cookie remember_me avec flags Secure et HttpOnly
Vérification des rôles sur chaque page protégée
Transactions PDO pour les opérations critiques (réservation, annulation)


🌿 Fonctionnalités

Recherche de covoiturages par ville et date
Filtres : prix, durée, note minimale, écologique
Réservation avec système de crédits
Espace chauffeur : proposer un trajet, gérer ses véhicules
Espace passager : historique, annulation, avis
Avis stockés dans MongoDB, validés par les employés
Espace admin : graphiques, gestion des comptes, suspension
Déploiement sur [lien de déploiement]


📊 Gestion de projet
Kanban disponible ici : [lien Trello/Notion/Jira]