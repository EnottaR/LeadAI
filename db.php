<?php

$host = 'localhost';
$dbname = 'mglead';
$username = 'mglead';
$password = '*204uapM5';

date_default_timezone_set('Europe/Rome');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("SET time_zone = '+01:00'");
    
} catch (PDOException $e) {
    echo "Connessione al database fallita: " . $e->getMessage();
    die();
}

return $pdo;
?>