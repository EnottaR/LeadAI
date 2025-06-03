<?php
$pdo = require_once 'db.php';

// funzione per generare una chiave di crittografia sicura
function generateEncryptionKey() {
    return bin2hex(random_bytes(32)); // chiave di 256 bit
}

// verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name = htmlspecialchars($_POST['name']);
    $surname = htmlspecialchars($_POST['surname']);
    $email = htmlspecialchars($_POST['email']);
    $company = htmlspecialchars($_POST['company']);
    $website_name = htmlspecialchars($_POST['website_name']);
    $website_url = htmlspecialchars($_POST['website_url']);
	$username = htmlspecialchars($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // password criptata

    // genera una chiave di crittografia unica per il cliente
	$encryptionKey = generateEncryptionKey(); 

    try {
        // Inserisci il cliente nel database
        $stmt = $pdo->prepare("INSERT INTO clients (name, surname, company, username, password, email, encryption_key, type) 
                               VALUES (:name, :surname, :company, :username, :password, :email, :encryption_key, :type)");
        $stmt->execute([
            ':name' => $name,
            ':surname' => $surname,
            ':company' => $company,
            ':username' => $username,
            ':password' => $password,
            ':email' => $email,
            ':encryption_key' => $encryptionKey,
            ':type' => 1 // supponiamo che il tipo di cliente sia "generico" (1)
        ]);

        // ottieni l'ID del cliente appena inserito
        $clientId = $pdo->lastInsertId();

        // inserisci il sito web del cliente
        $stmt2 = $pdo->prepare("INSERT INTO websites (clients_id, name, url) VALUES (:clients_id, :name, :url)");
        $stmt2->execute([
            ':clients_id' => $clientId,
            ':name' => $website_name,
            ':url' => $website_url
        ]);

        echo "cliente e sito web aggiunti correttamente!<br>";
        echo "la chiave di crittografia è stata salvata";

    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage();
    }
}
?>