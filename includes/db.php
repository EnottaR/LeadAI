<?php
$host = "localhost";
$username = "mglead";
$password = "*204uapM5";
$dbname = "mglead";

// Imposta il fuso orario italiano
date_default_timezone_set('Europe/Rome');

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione al database fallita: " . htmlspecialchars($conn->connect_error));
}

// Imposta il fuso orario anche per MySQL
$conn->query("SET time_zone = '+01:00'");
?>