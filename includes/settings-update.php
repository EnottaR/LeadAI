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

// Per la gestione dell'aggiornamento password
if (!empty($data['password'])) {
    $new_password = trim($data['password']);
    
    if (strlen($new_password) < 6) {
        echo json_encode(["status" => "error", "message" => "⚠️ La password deve contenere almeno 6 caratteri."]);
        exit;
    }
    
    // Verifica se la password è uguale a quella attuale
    $stmt = $conn->prepare("SELECT password FROM clients WHERE id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $stmt->bind_result($current_hashed_password);
    $stmt->fetch();
    $stmt->close();
    
    // Verifica se la nuova password corrisponde a quella esistente
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

if (!empty($data['name'])) {
    $website_name = filter_var($data['name'], FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("UPDATE websites SET name = ? WHERE clients_id = ?");
    $stmt->bind_param("si", $website_name, $client_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "<i class='fas fa-check-circle'></i> Nome del sito aggiornato con successo!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "⚠️ Errore durante l'aggiornamento del nome del sito."]);
    }
    exit;
}

if (!empty($data['url'])) {
    $website_url = filter_var($data['url'], FILTER_SANITIZE_URL);

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

echo json_encode(["status" => "error", "message" => "⚠️ Nessun dato valido ricevuto."]);
$conn->close();
?>
