<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "⚠️ Devi essere loggato per aggiornare i tuoi dati."]);
    exit;
}

$client_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Aggiornamento PASSWORD
if (!empty($data['password'])) {
    $new_password = trim($data['password']);
    
    if (strlen($new_password) < 6) {
        echo json_encode(["status" => "error", "message" => "⚠️ La password deve contenere almeno 6 caratteri."]);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT password FROM clients WHERE id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $stmt->bind_result($current_hashed_password);
    $stmt->fetch();
    $stmt->close();
    
    if (password_verify($new_password, $current_hashed_password)) {
        echo json_encode(["status" => "error", "message" => "⚠️ La nuova password non può essere uguale a quella attuale."]);
        exit;
    }
    
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE clients SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $client_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "<i class='fas fa-check-circle'></i> Password aggiornata con successo!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "⚠️ Errore durante l'aggiornamento della password."]);
    }
    exit;
}

// Aggiornamento EMAIL
if (!empty($data['email'])) {
    $new_email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "⚠️ Inserisci un indirizzo email valido."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE clients SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $new_email, $client_id);

    if ($stmt->execute()) {
        $_SESSION['email'] = $new_email;
        echo json_encode(["status" => "success", "message" => "<i class='fas fa-check-circle'></i> Email aggiornata con successo!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "⚠️ Errore durante l'aggiornamento dell'email."]);
    }
    exit;
}

// Aggiornamento AZIENDA
if (!empty($data['company'])) {
    $company_name = filter_var($data['company'], FILTER_SANITIZE_STRING);
    $stmt = $conn->prepare("UPDATE clients SET company = ? WHERE id = ?");
    $stmt->bind_param("si", $company_name, $client_id);

    if ($stmt->execute()) {
        $_SESSION['company'] = $company_name;
        echo json_encode(["status" => "success", "message" => "<i class='fas fa-check-circle'></i> Azienda aggiornata con successo!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "⚠️ Errore durante l'aggiornamento del nome dell'azienda."]);
    }
    exit;
}

// Aggiornamento NOME SITO (website name)
if (!empty($data['website_name'])) {
    $website_name = filter_var($data['website_name'], FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("UPDATE websites SET name = ? WHERE clients_id = ?");
    $stmt->bind_param("si", $website_name, $client_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "<i class='fas fa-check-circle'></i> Nome del sito aggiornato con successo!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "⚠️ Errore durante l'aggiornamento del nome del sito."]);
    }
    exit;
}

// Aggiornamento URL SITO (website url)
if (!empty($data['website_url'])) {
    $website_url = filter_var($data['website_url'], FILTER_SANITIZE_URL);

    if (!filter_var($website_url, FILTER_VALIDATE_URL)) {
        echo json_encode(["status" => "error", "message" => "⚠️ Inserisci un URL valido."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE websites SET url = ? WHERE clients_id = ?");
    $stmt->bind_param("si", $website_url, $client_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "<i class='fas fa-check-circle'></i> URL del sito aggiornato con successo!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "⚠️ Errore durante l'aggiornamento dell'URL del sito."]);
    }
    exit;
}

error_log("Dati ricevuti in settings-update.php: " . json_encode($data));

echo json_encode(["status" => "error", "message" => "⚠️ Nessun dato valido ricevuto."]);
$conn->close();
?>