# 🌿 EcoRide — Plateforme de covoiturage écologique

EcoRide est une application web de covoiturage développée dans le cadre du titre professionnel **Développeur Web et Web Mobile (DWWM)**.

🔗 **Application en ligne** : https://sheltered-wildwood-22203-3a0c68434854.herokuapp.com/

---

## 🚀 Stack technique

| Élément | Technologie |
|---------|------------|
| Front-end | HTML5, CSS3, JavaScript |
| Back-end | PHP 8.2 (natif, PDO) |
| Base de données relationnelle | MariaDB (XAMPP) |
| Base de données NoSQL | MongoDB Atlas |
| Librairie MongoDB | mongodb/mongodb 1.19.1 (Composer) |
| Graphiques | Chart.js |
| Déploiement | Heroku + JawsDB |
| Versioning | Git / GitHub |

---

## ⚙️ Installation en local

### Prérequis
- [XAMPP](https://www.apachefriends.org/) (PHP 8.2 + MariaDB)
- [Composer](https://getcomposer.org/)
- [Git](https://git-scm.com/)

### 1. Cloner le projet

```bash
git clone https://github.com/Evoluti62/ecoride.git
cd ecoride
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer l'extension MongoDB pour PHP

1. Télécharger `php_mongodb-1.19.x-8.2-ts-vs16-x64.zip` sur [pecl.php.net](https://pecl.php.net/package/mongodb)
2. Copier `php_mongodb.dll` dans `C:/xampp/php/ext/`
3. Ajouter `extension=mongodb` dans `C:/xampp/php/php.ini`
4. Redémarrer Apache

### 4. Créer la base de données MySQL

```bash
mysql -u root -p < sql/ecoride_create.sql
mysql -u root -p < sql/ecoride_data.sql
```

### 5. Configurer MongoDB

Créer le fichier `config/mongodb.php` (non versionné pour des raisons de sécurité) :

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$mongoUri = "mongodb+srv://<username>:<password>@<cluster>.mongodb.net/ecoride?retryWrites=true&w=majority";

try {
    $mongoClient       = new MongoDB\Client($mongoUri);
    $mongodb           = $mongoClient->selectDatabase('ecoride');
    $reviewsCollection = $mongodb->selectCollection('reviews');
} catch (Exception $e) {
    die("Erreur MongoDB : " . $e->getMessage());
}
```

### 6. Lancer l'application

1. Démarre **Apache** et **MySQL** dans XAMPP
2. Accède à : http://localhost/ecoride/public/

---

## 👤 Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| Administrateur | admin@ecoride.fr | password |
| Employé | employe@ecoride.fr | password |
| Chauffeur | carlos@test.fr | password |
| Passager | diane@test.fr | password |

> ⚠️ Le compte administrateur ne peut pas être créé depuis l'application. Il doit être inséré directement en base de données.

---

## 📁 Structure du projet

```
ecoride/
├── config/
│   ├── database.php        ← connexion MySQL
│   └── mongodb.php         ← connexion MongoDB (non versionné)
├── public/                 ← pages PHP + assets
│   ├── assets/
│   │   ├── css/style.css
│   │   └── js/
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
│   ├── forgot-password.php
│   └── logout.php
├── sql/
│   ├── ecoride_create.sql  ← structure de la base
│   └── ecoride_data.sql    ← données de test
├── vendor/                 ← dépendances Composer (non versionné)
├── .gitignore
├── composer.json
├── composer.lock
├── Procfile                ← configuration Heroku
└── README.md
```

---

## 🔒 Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt)
- Requêtes préparées PDO (protection injection SQL)
- `htmlspecialchars()` sur toutes les sorties (protection XSS)
- Cookie `remember_me` avec flags `Secure` et `HttpOnly`
- Vérification des rôles sur chaque page protégée
- Transactions PDO pour les opérations critiques (réservation, annulation)

---

## 🌿 Fonctionnalités

- Recherche de covoiturages par ville et date
- Filtres : prix, durée, note minimale, écologique
- Réservation avec système de crédits
- Espace chauffeur : proposer un trajet, gérer ses véhicules
- Espace passager : historique, annulation, avis
- Avis stockés dans MongoDB, validés par les employés
- Espace admin : graphiques, gestion des comptes, suspension

---

## 📊 Gestion de projet

Kanban disponible ici : https://trello.com/b/7IdOcz7X/ecoride-gestion-de-projet