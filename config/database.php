<?php

$host = "127.0.0.1";
$dbname = "ecoride";
$user = "root";
$password = "";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Erreur de connexion à la base de données : " . $e->getMessage());

}