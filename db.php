<?php

/*MIGLIORARE SICUREZZA*/
$host = 'localhost';
$dbname = 'mglead';
$username = 'mglead';
$password = '*204uapM5';

// Imposta il fuso orario italiano
date_default_timezone_set('Europe/Rome');

try {
    // crea la connessione PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Imposta il fuso orario anche per MySQL
    $pdo->exec("SET time_zone = '+01:00'");
    
} catch (PDOException $e) {
    // gestisci gli errori di connessione
    echo "Connessione al database fallita: " . $e->getMessage();
    die();
}

// restituisci l'oggetto PDO per l'uso successivo
return $pdo;
?>