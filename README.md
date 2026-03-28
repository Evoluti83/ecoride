# 🌿 EcoRide — Plateforme de covoiturage écologique

EcoRide est une application web de covoiturage développée dans le cadre du titre professionnel **Développeur Web et Web Mobile (DWWM)**.

🔗 **Application en ligne** : https://sheltered-wildwood-22203-3a0c68434854.herokuapp.com/
📋 **Kanban** : https://trello.com/b/7IdOcz7X/ecoride-gestion-de-projet
💻 **GitHub** : https://github.com/Evoluti62/ecoride

---

## 🚀 Stack technique

| Élément | Technologie |
|---------|------------|
| Front-end | HTML5, CSS3, JavaScript |
| Back-end | PHP 8.2 (natif, PDO) |
| Base de données relationnelle | MariaDB (XAMPP) / JawsDB (Heroku) |
| Base de données NoSQL | MongoDB Atlas (avis utilisateurs) |
| Librairie MongoDB | mongodb/mongodb 1.19.1 (Composer) |
| Graphiques | Chart.js |
| Déploiement | Heroku |
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

Vérification :
```bash
php -r "echo phpversion('mongodb');"
```

### 4. Créer la base de données MySQL

```bash
mysql -u root -p < sql/ecoride_create.sql
mysql -u root -p < sql/ecoride_data.sql
```

### 5. Configurer MongoDB

Créer le fichier `config/mongodb.php` (non versionné — credentials sensibles) :

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

> ⚠️ Le compte administrateur ne peut pas être créé depuis l'application. Il doit être inséré directement en base de données via SQL.

---

## 📁 Structure du projet

```
ecoride/
├── config/
│   ├── database.php           ← connexion MySQL (détecte Heroku/local)
│   └── mongodb.php            ← connexion MongoDB Atlas (non versionné)
├── public/                    ← pages PHP + assets
│   ├── assets/
│   │   ├── css/style.css
│   │   └── js/
│   ├── navbar.php             ← menu commun (inclus dans toutes les pages)
│   ├── footer.php             ← footer commun (inclus dans toutes les pages)
│   ├── index.php              ← accueil + présentation entreprise (US1)
│   ├── contact.php            ← page de contact (US2)
│   ├── mentions-legales.php   ← mentions légales (US1)
│   ├── covoiturages.php       ← liste + filtres (US3, US4)
│   ├── ride-detail.php        ← détail trajet (US5)
│   ├── confirm-booking.php    ← double confirmation réservation (US6)
│   ├── book-ride.php          ← traitement réservation (US6)
│   ├── register.php           ← inscription + MDP sécurisé (US7)
│   ├── login.php              ← connexion
│   ├── logout.php             ← déconnexion sécurisée
│   ├── forgot-password.php    ← mot de passe oublié
│   ├── dashboard.php          ← espace utilisateur (US8, US10)
│   ├── profile.php            ← profil chauffeur/passager (US8)
│   ├── add-vehicle.php        ← ajout véhicule (US8)
│   ├── preferences.php        ← préférences chauffeur (US8)
│   ├── create-ride.php        ← proposer un trajet (US9)
│   ├── cancel-booking.php     ← annulation passager (US10)
│   ├── cancel-ride.php        ← annulation chauffeur (US10)
│   ├── start-ride.php         ← démarrer trajet (US11)
│   ├── end-ride.php           ← terminer trajet + mail participants (US11)
│   ├── validate-ride.php      ← validation passager + avis (US11)
│   ├── leave-review.php       ← avis MongoDB (US11)
│   ├── manage-reviews.php     ← espace employé (US12)
│   └── admin.php              ← espace admin + graphiques (US13)
├── sql/
│   ├── ecoride_create.sql     ← structure de la base
│   └── ecoride_data.sql       ← données de test
├── vendor/                    ← dépendances Composer (non versionné)
├── .gitignore
├── composer.json
├── composer.lock
├── Procfile                   ← configuration Heroku
└── README.md
```

---

## 🔒 Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt) + règles de complexité
- Requêtes préparées PDO (protection injection SQL)
- `htmlspecialchars()` sur toutes les sorties (protection XSS)
- Cookie `remember_me` avec flags `Secure` et `HttpOnly`
- Vérification des rôles sur chaque page protégée
- Transactions PDO pour les opérations critiques
- `session_regenerate_id(true)` à la déconnexion

---

## 🌿 Fonctionnalités (US1 à US13)

- **US1** : Page d'accueil avec présentation entreprise, recherche et footer
- **US2** : Menu de navigation uniforme (Accueil, Covoiturages, Contact, Connexion)
- **US3** : Vue des covoiturages (chauffeur, note, dates, badge écologique)
- **US4** : Filtres (prix, durée, note minimale, écologique)
- **US5** : Vue détaillée (véhicule, préférences, avis, bouton participer)
- **US6** : Participer avec double confirmation et récapitulatif des crédits
- **US7** : Création de compte avec validation mot de passe sécurisé + 20 crédits
- **US8** : Espace utilisateur (rôle chauffeur/passager, véhicules, préférences)
- **US9** : Saisir un voyage (2 crédits plateforme, indicateur écologique auto)
- **US10** : Historique et annulation (remboursement crédits, mail participants)
- **US11** : Démarrer/terminer trajet + validation passager + avis MongoDB
- **US12** : Espace employé (validation avis, gestion problèmes signalés)
- **US13** : Espace admin (graphiques Chart.js, créer employés, suspension comptes)

---

## 📊 Gestion de projet

Kanban Trello : https://trello.com/b/7IdOcz7X/ecoride-gestion-de-projet