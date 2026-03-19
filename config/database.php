<?php
// En production (Heroku) on utilise JAWSDB_URL
// En local on utilise les variables directes
if (getenv('JAWSDB_URL')) {
    $url = parse_url(getenv('JAWSDB_URL'));
    $host     = $url['host'];
    $dbname   = ltrim($url['path'], '/');
    $user     = $url['user'];
    $password = $url['pass'];
} else {
    // Config locale XAMPP
    $host     = "127.0.0.1";
    $dbname   = "ecoride";
    $user     = "root";
    $password = "";
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}